<?php

// Menandai berkas ini berada di ruang nama Tests\Feature.
namespace Tests\Feature;

// Model yang dipakai menyiapkan bahan uji.
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Store;
use App\Models\TapTryCollect;
use App\Models\User;
// RefreshDatabase membuat ulang seluruh tabel sebelum tiap pengujian.
use Illuminate\Foundation\Testing\RefreshDatabase;
// TestCase adalah kelas induk seluruh pengujian Laravel.
use Tests\TestCase;

/**
 * Pengujian pengganti daftar pilihan status dengan tombol satu klik.
 *
 * Permintaannya: pekerjaan petugas jangan dibuat berbelit. Daftar pilihan
 * memaksa tiga gerakan untuk satu maksud, yaitu membuka daftarnya, membaca
 * seluruh isinya, lalu memilih satu, padahal yang masuk akal dari keadaan
 * sekarang biasanya cuma satu atau dua.
 *
 * Yang dibuktikan di sini:
 *
 *   1. tidak ada lagi daftar pilihan status di halaman petugas mana pun
 *   2. tiap langkah yang boleh dikerjakan punya tombolnya sendiri
 *   3. tombolnya bertuliskan perbuatan, bukan kode status
 *   4. langkah yang tidak masuk akal tidak muncul tombolnya, dan tetap
 *      ditolak server walaupun dikirim paksa
 *   5. langkah yang merugikan pelanggan wajib diberi alasan
 *   6. halaman retur benar benar menampilkan tombolnya
 *
 * Nomor 6 terlihat sepele tetapi justru penting: sebelum perbaikan ini,
 * halaman rincian retur membaca variabel bernama aksiBerikutnya sedangkan
 * controllernya mengirim variabel bernama daftarStatus. Karena empty() pada
 * variabel yang tidak ada menghasilkan true tanpa error, halamannya diam
 * diam menampilkan tulisan "tidak ada tindakan" selamanya, dan admin tidak
 * pernah bisa mengubah status retur sama sekali.
 */
class TombolSatuKlikTest extends TestCase
{
    use RefreshDatabase;

    /** Dijalankan sebelum tiap method pengujian. */
    protected function setUp(): void
    {
        parent::setUp();

        // seed() menjalankan seluruh seeder, jadi datanya sama dengan sistem
        // yang dipakai, bukan data buatan khusus pengujian.
        $this->seed();
    }

    /** Akun admin kantor dari seeder. */
    private function admin(): User
    {
        return User::where('role', 'admin')->firstOrFail();
    }

    /**
     * Membuat satu pengajuan retur lewat jalur yang sesungguhnya.
     *
     * Data contoh dari seeder tidak memuat retur, sebab retur baru lahir
     * ketika pelanggan mengajukannya sendiri. Jadi di sini pengajuannya
     * benar benar dikirim lewat halaman pelanggan, bukan ditulis langsung ke
     * tabel, supaya yang diuji memang jalur yang dipakai orang.
     */
    private function buatRetur(): ReturnRequest
    {
        $pelanggan = User::where('role', 'pelanggan')->firstOrFail();

        $pesanan = Order::where('user_id', $pelanggan->id)
            ->where('status', 'DELIVERED')
            ->firstOrFail();

        // Supaya pengajuannya tidak tertolak batas waktu, tanggal selesainya
        // dibuat masih di dalam masa retur.
        $pesanan->update(['completed_at' => now()->subDay()]);

        $barang = $pesanan->item()->firstOrFail();
        $toko = Store::where('is_active', true)->firstOrFail();

        $this->actingAs($pelanggan)->post(route('retur.store', $pesanan->order_code), [
            'item' => [$barang->id.'-1'],
            'reason' => 'Ukuran tidak sesuai badan',
            'exchange_type' => 'ganti_ukuran',
            // Jalur KURIR, sebab yang diuji di sini tombol langkah milik
            // ADMIN. Retur yang diantar sendiri ke toko sudah bukan urusan
            // admin lagi: yang memeriksa barangnya kasir toko, sebab hanya
            // orang yang memegang bajunya yang bisa menilai labelnya masih
            // menempel, baunya, dan apakah sudah dicuci. Alurnya diuji
            // terpisah di ReturKasirTokoTest.
            'method' => 'kurir',
            'return_date' => now()->addDay()->toDateString(),
            'return_time' => '13:00',
            // Bukti pengajuan retur dua berkas dan keduanya wajib: satu
            // sampai empat foto, dan satu video pembukaan paket.
            'photos' => [\Illuminate\Http\UploadedFile::fake()->image('bukti.jpg', 400, 400)],
            'video' => \Illuminate\Http\UploadedFile::fake()->create('unboxing.mp4', 900, 'video/mp4'),
        ]);

        return ReturnRequest::where('user_id', $pelanggan->id)->firstOrFail();
    }

    /**
     * Tidak ada lagi daftar pilihan status di halaman petugas mana pun.
     *
     * Seluruh halaman admin dan kasir dibuka satu per satu, lalu isinya
     * diperiksa. Sebuah halaman dinyatakan gagal kalau memuat tulisan
     * select yang namanya status.
     */
    public function test_tidak_ada_daftar_pilihan_status_di_halaman_petugas(): void
    {
        $halaman = [
            '/admin/pesanan',
            '/admin/pembayaran',
            '/admin/retur',
            '/admin/tap-try-collect',
            '/admin/donasi',
            '/admin/stok',
            '/admin/stok/kartu',
        ];

        $bermasalah = [];

        foreach ($halaman as $alamat) {
            $jawaban = $this->actingAs($this->admin())->get($alamat);

            $jawaban->assertOk();

            // getContent() memberi seluruh isi HTML halamannya sebagai teks.
            $isi = $jawaban->getContent();

            // Daftar pilihan status selalu ditulis begini di Blade. Kalau ada
            // yang terlewat dikerjakan, tulisan ini pasti ketemu.
            if (str_contains($isi, 'name="status"') && str_contains($isi, '<select')) {
                // Pemeriksaan kedua: select-nya bisa saja milik isian lain
                // pada halaman yang sama, jadi yang dihitung hanya select
                // yang namanya memang status.
                if (preg_match('/<select[^>]*name="status"/', $isi)) {
                    $bermasalah[] = $alamat;
                }
            }
        }

        $this->assertSame([], $bermasalah,
            'Masih ada daftar pilihan status pada halaman: '.implode(', ', $bermasalah));
    }

    /**
     * Halaman rincian pesanan menampilkan satu tombol untuk tiap langkah,
     * dan tombolnya bertuliskan perbuatan.
     */
    public function test_rincian_pesanan_menampilkan_tombol_tiap_langkah(): void
    {
        // Pesanan yang baru dibayar, jadi langkah berikutnya memang ada.
        $pesanan = Order::where('status', 'PAYMENT_CONFIRMED')->first()
            ?: Order::firstOrFail()->fill(['status' => 'PAYMENT_CONFIRMED']);
        $pesanan->save();

        $jawaban = $this->actingAs($this->admin())->get(route('admin.pesanan.show', $pesanan));

        $jawaban->assertOk();

        // Tiap langkah yang boleh dikerjakan wajib punya tombolnya sendiri,
        // dan tulisannya diambil dari labelPeralihan() yang berisi perbuatan.
        foreach ($pesanan->lanjutanStatus() as $langkah) {
            $jawaban->assertSee(Order::labelPeralihan($langkah));
            $jawaban->assertSee('value="'.$langkah.'"', false);
        }

        // Langkah yang jauh di depan tidak boleh muncul tombolnya.
        $jawaban->assertDontSee('value="DELIVERED"', false);

        // Tidak ada tombol kirim bersama bernama Simpan, sebab tiap tombol
        // sudah menjadi tombol kirimnya sendiri.
        $jawaban->assertDontSee('Jalankan Langkah Ini');
    }

    /**
     * Halaman rincian retur menampilkan tombolnya.
     *
     * Inilah pengujian yang menjaga bug lama tidak kembali: dulu halaman ini
     * selalu menampilkan tulisan tidak ada tindakan, karena nama variabel
     * yang dikirim controller berbeda dengan yang dibaca view.
     */
    public function test_rincian_retur_menampilkan_tombol_langkahnya(): void
    {
        $retur = $this->buatRetur();

        $jawaban = $this->actingAs($this->admin())->get(route('admin.retur.show', $retur));

        $jawaban->assertOk();

        // Dari menunggu validasi, dua langkah yang masuk akal adalah
        // menyetujui dan menolak.
        $jawaban->assertSee('Setujui Pengajuan');
        $jawaban->assertSee('Tolak Pengajuan');

        // Langkah yang jauh di depan belum boleh muncul: retur mustahil
        // ditandai selesai padahal barangnya belum pernah diperiksa.
        $jawaban->assertDontSee('Tandai Retur Selesai');

        // Tulisan lama yang dulu selalu muncul tidak boleh ada lagi.
        $jawaban->assertDontSee('Tidak ada langkah manual untuk status ini.');
    }

    /** Retur tidak bisa dilompatkan ke status yang jauh. */
    public function test_retur_tidak_bisa_dilompatkan(): void
    {
        $retur = $this->buatRetur();

        // Melompat langsung ke selesai harus ditolak, sebab barangnya belum
        // pernah diperiksa. Pemeriksaannya di server, jadi tetap berlaku
        // walaupun tombolnya diubah dari peramban.
        $this->actingAs($this->admin())
            ->put(route('admin.retur.status', $retur), ['status' => 'selesai'])
            ->assertSessionHasErrors('status');

        $this->assertSame('menunggu_validasi', $retur->refresh()->status,
            'Retur berhasil dilompatkan ke status selesai, padahal barangnya belum diperiksa.');

        // Dikerjakan berurutan, barulah diterima satu per satu.
        //
        // Langkah terakhirnya BUKAN selesai, dan itu disengaja. Sesudah
        // barang penggantinya dikirim, yang menutup pengajuan adalah
        // pelanggan lewat tombol konfirmasi di halaman returnya, sebab
        // dialah yang memegang barangnya. Kalau tiga hari tidak ditekan,
        // sistem yang menutup.
        foreach ([['disetujui', []], ['diperiksa', []], ['diproses', []]] as [$langkah, $tambahan]) {
            $this->actingAs($this->admin())
                ->put(route('admin.retur.status', $retur), ['status' => $langkah] + $tambahan);

            $this->assertSame($langkah, $retur->fresh()->status,
                'Langkah '.$langkah.' seharusnya diterima pada urutannya.');
        }

        // Dan sesudah itu admin memang tidak punya langkah apa apa lagi.
        $this->actingAs($this->admin())
            ->put(route('admin.retur.status', $retur), ['status' => 'selesai'])
            ->assertSessionHasErrors('status');

        $this->assertSame('diproses', $retur->fresh()->status,
            'Penutupan retur bukan wewenang admin, melainkan konfirmasi pelanggan.');
    }

    /** Penolakan retur wajib diberi alasan. */
    public function test_penolakan_retur_wajib_diberi_alasan(): void
    {
        $retur = $this->buatRetur();

        // Tanpa alasan, permintaannya ditolak dan statusnya tidak berubah.
        $this->actingAs($this->admin())
            ->put(route('admin.retur.status', $retur), ['status' => 'ditolak']);

        $this->assertSame('menunggu_validasi', $retur->refresh()->status,
            'Retur ditolak tanpa alasan, padahal alasannya ikut dikirim ke pelanggan.');

        // Catatan bebas saja tidak cukup. Alasannya sekarang DIPILIH dari
        // daftar, supaya penolakan yang sebabnya sama selalu terbaca sama
        // oleh pelanggan dan jumlah tiap sebab bisa dihitung di laporan.
        $this->actingAs($this->admin())->put(route('admin.retur.status', $retur), [
            'status' => 'ditolak',
            'admin_note' => 'Barang sudah melewati batas waktu pengajuan retur.',
        ]);

        $this->assertSame('menunggu_validasi', $retur->refresh()->status,
            'Catatan bebas bukan pengganti alasan yang dipilih dari daftar.');

        // Dengan alasan dari daftar, barulah diterima.
        $this->actingAs($this->admin())->put(route('admin.retur.status', $retur), [
            'status' => 'ditolak',
            'reject_reason' => 'lewat_batas_waktu',
            'admin_note' => 'Barang diterima 5 Oktober, diajukan 20 Oktober.',
        ]);

        $retur->refresh();

        $this->assertSame('ditolak', $retur->status);
        $this->assertSame('lewat_batas_waktu', $retur->reject_reason);

        // Yang dibaca pelanggan: alasan dari daftar lebih dahulu, lalu
        // keterangan tambahan dari admin.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $retur->user_id,
            'body' => 'Alasan: Pengajuan lewat batas waktu retur. '
                .'Barang diterima 5 Oktober, diajukan 20 Oktober.',
        ]);
    }

    /** Pembatalan pesanan wajib diberi alasan. */
    public function test_pembatalan_pesanan_wajib_diberi_alasan(): void
    {
        $pesanan = Order::where('status', 'PENDING_PAYMENT')->first();

        // Kalau data contohnya tidak memuat pesanan menunggu bayar, satu
        // pesanan mana pun dipakai lalu statusnya disetel.
        if (! $pesanan) {
            $pesanan = Order::firstOrFail();
            $pesanan->update(['status' => 'PENDING_PAYMENT']);
        }

        $this->actingAs($this->admin())
            ->put(route('admin.pesanan.status', $pesanan), ['status' => 'CANCELLED']);

        $this->assertSame('PENDING_PAYMENT', $pesanan->refresh()->status,
            'Pesanan dibatalkan tanpa alasan, padahal alasannya dibaca pelanggan.');

        $this->actingAs($this->admin())->put(route('admin.pesanan.status', $pesanan), [
            'status' => 'CANCELLED',
            'keterangan' => 'Stok barang habis di gudang maupun di seluruh toko.',
        ])->assertRedirect();

        $this->assertSame('CANCELLED', $pesanan->refresh()->status);
    }

    /**
     * Rincian kunjungan di admin hanya MENAMPILKAN, tanpa satu pun tombol
     * pengubah status.
     *
     * Yang berhak menyatakan barang sudah disiapkan atau sudah diserahkan
     * adalah kasir toko, sebab kasirlah yang berdiri di depan pelanggan dan
     * memegang barangnya.
     */
    public function test_rincian_kunjungan_di_admin_tanpa_tombol_langkah(): void
    {
        $kunjungan = TapTryCollect::where('status', 'SCHEDULED')->first();

        if (! $kunjungan) {
            $kunjungan = TapTryCollect::firstOrFail();
            $kunjungan->update(['status' => 'SCHEDULED']);
        }

        $jawaban = $this->actingAs($this->admin())->get(route('admin.ttc.show', $kunjungan));

        $jawaban->assertOk();
        $jawaban->assertDontSee('Tandai Barang Siap di Toko');
        $jawaban->assertDontSee('Simpan Status');
        // Kalimatnya dipendekkan ketika tulisan di panel admin dipangkas.
        // Yang dikunci tetap sama: halaman ini menyatakan dirinya hanya
        // untuk melihat, dan menyebut kasir toko sebagai yang berwenang.
        $jawaban->assertSee('hanya untuk melihat', false);
        $jawaban->assertSee('kasir toko', false);

        // Admin kantor tidak punya tombol pengubah status kunjungan lagi,
        // jadi alamatnya pun sudah tidak ada.
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.ttc.status'),
            'Route pengubah status kunjungan di admin seharusnya sudah tidak terdaftar.');

        $this->assertSame('SCHEDULED', $kunjungan->refresh()->status);
    }

    /** Penyaring status pada halaman daftar berbentuk tautan, bukan formulir. */
    public function test_penyaring_status_berbentuk_tautan(): void
    {
        $jawaban = $this->actingAs($this->admin())->get(route('admin.pesanan.index'));

        $jawaban->assertOk();

        // Tautan penyaring membawa status pada alamatnya, jadi hasil
        // penyaringannya bisa disimpan sebagai penanda peramban.
        $jawaban->assertSee('status=PENDING_PAYMENT', false);

        // Tulisannya bahasa Indonesia, bukan kode status.
        $jawaban->assertSee('Menunggu Pembayaran');
        $jawaban->assertDontSee('>PENDING_PAYMENT<', false);

        // Menekan tautannya benar benar menyaring tabelnya.
        $tersaring = $this->actingAs($this->admin())
            ->get(route('admin.pesanan.index', ['status' => 'PENDING_PAYMENT']));

        $tersaring->assertOk();
        $tersaring->assertDontSee('Dibatalkan</span>', false);
    }
}
