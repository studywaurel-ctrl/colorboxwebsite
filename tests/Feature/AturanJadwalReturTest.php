<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Aturan jam, pembatalan, dan bukti pada jadwal antar retur ke toko.
 *
 * ---------------------------------------------------------------------
 * YANG DIKUNCI PENGUJIAN INI
 * ---------------------------------------------------------------------
 *
 * 1. Sebelum pukul 15.00, jadwal hari ini masih bisa dipesan, dan slot
 *    sorenya sampai pukul 21.00 ikut ditawarkan.
 * 2. Sesudah pukul 15.00, SELURUH jam hari ini tertutup, dan server
 *    menolak pengajuan yang tetap memaksakan jam hari itu.
 * 3. Slot terakhir mulai pukul 21.00, tidak lebih malam dari itu.
 * 4. Kasir bisa membatalkan jadwalnya dengan alasan dari daftar, dan
 *    alasan di luar daftar ditolak.
 * 5. Kasir toko lain tidak bisa membatalkan jadwal toko ini.
 * 6. Sistem membatalkan sendiri jadwal yang lewat tiga hari.
 *
 * Jam dibekukan di tiap pengujian, sebab seluruh aturan di sini
 * bergantung pada jam berapa sekarang. Tanpa pembekuan, pengujian yang
 * sama lulus pagi hari lalu gagal sore hari.
 */
class AturanJadwalReturTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Pelanggan contoh beserta satu pesanan yang boleh diretur. */
    private function siapRetur(): array
    {
        $pelanggan = User::where('role', 'pelanggan')
            ->whereHas('pesanan', fn ($satu) => $satu->whereIn('status', ['DELIVERED', 'PICKED_UP']))
            ->firstOrFail();

        $pesanan = Order::where('user_id', $pelanggan->id)
            ->whereIn('status', ['DELIVERED', 'PICKED_UP'])
            ->with('item')
            ->firstOrFail();

        // Batas tujuh hari retur dihitung dari completed_at, jadi
        // tanggalnya didekatkan supaya pengajuannya tidak ditolak karena
        // kedaluwarsa, bukan karena aturan jam yang sedang diuji.
        $pesanan->forceFill(['completed_at' => now()->subDay()])->save();

        return [$pelanggan, $pesanan, $pesanan->item->first()];
    }

    /** Isian formulir pengajuan retur yang lengkap. */
    private function isian(Store $toko, string $tanggal, string $jam, $baris): array
    {
        return [
            'item' => [$baris->id.'-1'],
            'reason' => 'Ukuran kekecilan di bagian bahu',
            'exchange_type' => 'ganti_ukuran',
            'method' => 'toko',
            'store_id' => $toko->id,
            'return_date' => $tanggal,
            'return_time' => $jam,
            'photos' => [UploadedFile::fake()->image('bukti.jpg', 400, 400)],
            'video' => UploadedFile::fake()->create('unboxing.mp4', 900, 'video/mp4'),
        ];
    }

    public function test_sebelum_pukul_tiga_jadwal_hari_ini_masih_terbuka(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 11:00:00'));

        [$pelanggan, $pesanan] = $this->siapRetur();

        $isi = $this->actingAs($pelanggan)
            ->get(route('retur.create', [$pesanan->order_code, 'tanggal' => now()->toDateString()]))
            ->assertOk()
            ->getContent();

        // Jam sore masih ditawarkan walaupun dipesan siang hari. Inilah
        // bedanya batas MEMESAN dengan batas MELAYANI: toko tetap
        // menerima sampai pukul 21.00.
        $this->assertStringContainsString('20:00', $isi,
            'Slot sore harus ditawarkan kepada yang memesan sebelum batas pemesanan.');
        $this->assertStringNotContainsString('Pemesanan hari ini sudah ditutup', $isi);
    }

    public function test_sesudah_pukul_tiga_seluruh_jam_hari_ini_tertutup(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 17:55:00'));

        [$pelanggan, $pesanan] = $this->siapRetur();

        $this->actingAs($pelanggan)
            ->get(route('retur.create', [$pesanan->order_code, 'tanggal' => now()->toDateString()]))
            ->assertOk()
            // Sebabnya harus tertulis apa adanya. Kalau cuma dicoret
            // tanpa keterangan, pelanggan akan terus mencoba jam yang
            // lebih malam dan selalu ditolak tanpa tahu kenapa.
            ->assertSee('Pemesanan hari ini sudah ditutup');
    }

    public function test_server_menolak_jam_hari_ini_sesudah_batas_pemesanan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 17:55:00'));

        [$pelanggan, $pesanan, $baris] = $this->siapRetur();
        $toko = Store::where('is_active', true)->firstOrFail();

        // Jam 20:00 hari ini secara jam memang belum lewat, jadi kalau
        // pemeriksaannya cuma membandingkan jam, pengajuan ini akan
        // lolos. Yang menolaknya harus batas pemesanan, bukan jamnya.
        $this->actingAs($pelanggan)
            ->post(
                route('retur.store', $pesanan->order_code),
                $this->isian($toko, now()->toDateString(), '20:00', $baris)
            )
            ->assertSessionHas('gagal');

        $this->assertDatabaseMissing('return_requests', ['order_id' => $pesanan->id]);
    }

    public function test_slot_terakhir_mulai_pukul_sembilan_malam(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 11:00:00'));

        [$pelanggan, $pesanan] = $this->siapRetur();

        $isi = $this->actingAs($pelanggan)
            ->get(route('retur.create', [$pesanan->order_code, 'tanggal' => now()->toDateString()]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('21:00', $isi, 'Slot pukul 21.00 harus ada.');
        $this->assertStringNotContainsString('22:00-23:00', $isi,
            'Tidak boleh ada slot yang mulai sesudah toko tutup.');
    }

    public function test_kasir_membatalkan_jadwal_dengan_alasan_dari_daftar(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 11:00:00'));

        $retur = $this->returDiToko();
        $kasir = User::where('role', 'kasir')->where('store_id', $retur->store_id)->firstOrFail();

        $this->actingAs($kasir)
            ->put(route('kasir.retur.batal', $retur), [
                'alasan' => 'tidak_datang',
                'catatan' => 'Sudah ditelepon dua kali, tidak diangkat.',
            ])
            ->assertRedirect(route('kasir.retur.index'));

        $retur->refresh();

        $this->assertSame('dibatalkan', $retur->status);
        $this->assertSame('tidak_datang', $retur->reject_reason);
        $this->assertStringContainsString('tidak datang', strtolower((string) $retur->admin_note));

        // Pelanggan diberi tahu, lengkap dengan alasannya. Pembatalan
        // diam diam adalah yang paling membuat orang menelepon.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $retur->user_id,
            'title' => 'Jadwal retur dibatalkan',
        ]);
    }

    public function test_alasan_pembatalan_di_luar_daftar_ditolak(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 11:00:00'));

        $retur = $this->returDiToko();
        $kasir = User::where('role', 'kasir')->where('store_id', $retur->store_id)->firstOrFail();

        $this->actingAs($kasir)
            ->put(route('kasir.retur.batal', $retur), ['alasan' => 'malas_melayani'])
            ->assertSessionHasErrors('alasan');

        $this->assertNotSame('dibatalkan', $retur->fresh()->status);
    }

    public function test_kasir_toko_lain_tidak_bisa_membatalkan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 11:00:00'));

        $retur = $this->returDiToko();

        $kasirLain = User::where('role', 'kasir')
            ->where('store_id', '!=', $retur->store_id)
            ->firstOrFail();

        $this->actingAs($kasirLain)
            ->put(route('kasir.retur.batal', $retur), ['alasan' => 'tidak_datang'])
            ->assertForbidden();

        $this->assertNotSame('dibatalkan', $retur->fresh()->status);
    }

    public function test_sistem_membatalkan_jadwal_yang_lewat_tiga_hari(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 11:00:00'));

        $retur = $this->returDiToko();
        // Jadwalnya empat hari lalu dan barangnya tidak pernah datang.
        $retur->forceFill([
            'status' => 'disetujui',
            'return_date' => now()->subDays(4)->toDateString(),
        ])->save();

        $this->artisan('colorbox:perbarui')->assertSuccessful();

        $retur->refresh();

        $this->assertSame('dibatalkan', $retur->status);
        $this->assertSame('tidak_datang', $retur->reject_reason,
            'Kodenya harus sama dengan yang dipakai kasir, supaya laporannya tidak terpecah.');
    }

    public function test_jadwal_yang_belum_lewat_tidak_ikut_dibatalkan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 11:00:00'));

        $retur = $this->returDiToko();
        $retur->forceFill([
            'status' => 'disetujui',
            'return_date' => now()->addDay()->toDateString(),
        ])->save();

        $this->artisan('colorbox:perbarui')->assertSuccessful();

        $this->assertSame('disetujui', $retur->fresh()->status);
    }

    /** Satu pengajuan retur jalur antar ke toko, dibuat langsung. */
    private function returDiToko(): ReturnRequest
    {
        $pelanggan = User::where('role', 'pelanggan')->firstOrFail();
        $toko = Store::where('is_active', true)->firstOrFail();
        $pesanan = Order::where('user_id', $pelanggan->id)->firstOrFail();

        return ReturnRequest::create([
            'return_code' => 'RTR-UJI-0001',
            'user_id' => $pelanggan->id,
            'order_id' => $pesanan->id,
            'store_id' => $toko->id,
            'type' => 'tukar',
            'method' => 'toko',
            'reason' => 'Ukuran kekecilan',
            'status' => 'disetujui',
            'return_date' => now()->toDateString(),
            'return_time' => '13:00',
        ]);
    }
}
