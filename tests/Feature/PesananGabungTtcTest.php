<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\TapTryCollect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pesanan daring dan kunjungan Tap Try and Collect di satu halaman.
 *
 * ---------------------------------------------------------------------
 * KENAPA DIGABUNG
 * ---------------------------------------------------------------------
 *
 * Dari sisi pelanggan keduanya sama saja: dia membeli baju di Colorbox.
 * Dulu yang satu ada di Pesanan Saya dan yang satu di menu terpisah, jadi
 * pelanggan harus ingat dulu bajunya dapat lewat cara yang mana sebelum
 * bisa mencarinya.
 *
 * Sekarang satu halaman dengan penyaring jenis. Alamat menu lamanya tetap
 * hidup dan dialihkan ke sini, supaya tautan yang tersimpan tidak mati.
 */
class PesananGabungTtcTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** Pelanggan yang punya pesanan daring sekaligus kunjungan. */
    private function pelangganDuaDuanya(): User
    {
        $booking = TapTryCollect::whereNotNull('user_id')->firstOrFail();
        $pengguna = User::findOrFail($booking->user_id);

        // Dipastikan dia juga punya pesanan daring, supaya halaman
        // gabungannya memang memuat dua jenis.
        $pesanan = Order::where('user_id', $pengguna->id)->first();

        if (! $pesanan) {
            Order::whereNull('user_id')->orWhere('user_id', '!=', $pengguna->id)
                ->firstOrFail()->update(['user_id' => $pengguna->id]);
        }

        return $pengguna;
    }

    public function test_halaman_pesanan_punya_dua_tab(): void
    {
        $pengguna = $this->pelangganDuaDuanya();
        $booking = TapTryCollect::where('user_id', $pengguna->id)->firstOrFail();
        $pesanan = Order::where('user_id', $pengguna->id)->firstOrFail();

        // Keduanya dibuat paling baru, supaya pasti berada di halaman
        // pertama. Daftarnya dipenggal sepuluh baris per halaman, dan
        // pelanggan contoh bisa punya lebih banyak dari itu.
        TapTryCollect::where('id', $booking->id)->update(['created_at' => now()]);
        Order::where('id', $pesanan->id)->update(['created_at' => now()->subMinute()]);

        // Dua tab, sama seperti pembagian yang dipakai aplikasi ritel lain.
        // Tab bawaannya transaksi online.
        //
        // Tulisannya diubah dari "Transaksi di Aplikasi" menjadi
        // "Transaksi Online". Colorbox ini situs web, bukan aplikasi yang
        // dipasang di ponsel, dan menyebut dirinya aplikasi membuat
        // pelanggan mencarinya di toko aplikasi.
        $this->actingAs($pengguna)->get(route('pesanan.index'))
            ->assertOk()
            ->assertSee('Transaksi Online')
            ->assertDontSee('Transaksi di Aplikasi')
            ->assertSee('Transaksi di Toko')
            ->assertSee($pesanan->order_code)
            ->assertDontSee(route('ttc.show', $booking), false);
    }

    public function test_penyaring_ttc_hanya_memuat_kunjungan(): void
    {
        $pengguna = $this->pelangganDuaDuanya();
        $booking = TapTryCollect::where('user_id', $pengguna->id)->firstOrFail();
        $pesanan = Order::where('user_id', $pengguna->id)->firstOrFail();

        // Yang diperiksa TAUTAN rincian pesanannya, bukan kode pesanannya.
        // Kode pesanan bisa ikut tertulis di lonceng pemberitahuan pada
        // header, dan pemberitahuan itu memang bukan bagian dari daftar.
        // Nama lamanya tetap diterima supaya tautan yang tersimpan tidak mati.
        $this->actingAs($pengguna)->get(route('pesanan.index', ['jenis' => 'ttc']))
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertDontSee(route('pesanan.show', $pesanan), false);

        $this->actingAs($pengguna)->get(route('pesanan.index', ['jenis' => 'toko']))
            ->assertOk()
            ->assertSee($booking->booking_code);
    }

    public function test_penyaring_daring_hanya_memuat_pesanan(): void
    {
        $pengguna = $this->pelangganDuaDuanya();
        $booking = TapTryCollect::where('user_id', $pengguna->id)->firstOrFail();
        $pesanan = Order::where('user_id', $pengguna->id)->firstOrFail();

        foreach (['online', 'aplikasi'] as $kode) {
            $this->actingAs($pengguna)->get(route('pesanan.index', ['jenis' => $kode]))
                ->assertOk()
                ->assertSee($pesanan->order_code)
                ->assertDontSee(route('ttc.show', $booking), false);
        }
    }

    /** Alamat menu lama tetap hidup dan mengantar ke halaman gabungannya. */
    public function test_alamat_riwayat_ttc_lama_dialihkan(): void
    {
        $pengguna = $this->pelangganDuaDuanya();

        $this->actingAs($pengguna)->get(route('ttc.riwayat'))
            ->assertRedirect(route('pesanan.index', ['jenis' => 'ttc']));
    }

    /** Pelanggan mengonfirmasi barang kunjungannya, sama seperti pesanan. */
    public function test_pelanggan_mengonfirmasi_kunjungan(): void
    {
        $pengguna = $this->pelangganDuaDuanya();
        $booking = TapTryCollect::where('user_id', $pengguna->id)->firstOrFail();
        $booking->update(['status' => 'COLLECTED', 'confirmed_at' => null]);

        $this->actingAs($pengguna)->get(route('pesanan.index', ['jenis' => 'toko']))
            ->assertOk()
            ->assertSee('Barang Sudah Saya Terima');

        $this->actingAs($pengguna)->put(route('ttc.konfirmasi', $booking))->assertRedirect();

        $this->assertNotNull($booking->fresh()->confirmed_at,
            'Konfirmasi pelanggan wajib tercatat waktunya.');
    }

    /** Kunjungan yang belum diambil belum bisa dikonfirmasi. */
    public function test_kunjungan_yang_belum_diambil_belum_bisa_dikonfirmasi(): void
    {
        $pengguna = $this->pelangganDuaDuanya();
        $booking = TapTryCollect::where('user_id', $pengguna->id)->firstOrFail();
        $booking->update(['status' => 'SCHEDULED', 'confirmed_at' => null]);

        $this->actingAs($pengguna)->put(route('ttc.konfirmasi', $booking));

        $this->assertNull($booking->fresh()->confirmed_at);
    }

    /** Tiga hari tidak dikonfirmasi, sistem yang menutup. */
    public function test_kunjungan_dikonfirmasi_sendiri_sesudah_tiga_hari(): void
    {
        $pengguna = $this->pelangganDuaDuanya();
        $booking = TapTryCollect::where('user_id', $pengguna->id)->firstOrFail();
        $booking->update(['status' => 'COLLECTED', 'confirmed_at' => null]);
        TapTryCollect::where('id', $booking->id)->update(['updated_at' => now()->subDays(4)]);

        $this->artisan('colorbox:perbarui')->assertExitCode(0);

        $this->assertNotNull($booking->fresh()->confirmed_at);
    }

    /** Kunjungan orang lain tidak bisa dikonfirmasi. */
    public function test_kunjungan_orang_lain_ditolak(): void
    {
        $pengguna = $this->pelangganDuaDuanya();
        $booking = TapTryCollect::where('user_id', $pengguna->id)->firstOrFail();
        $booking->update(['status' => 'COLLECTED', 'confirmed_at' => null]);

        $orangLain = User::where('role', 'pelanggan')->where('id', '!=', $pengguna->id)->firstOrFail();

        $this->actingAs($orangLain)->put(route('ttc.konfirmasi', $booking))->assertNotFound();
    }
}
