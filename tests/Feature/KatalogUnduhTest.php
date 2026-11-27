<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Menu Unduh Katalog sudah DILEPAS, dan berkas ini menjaganya tetap lepas.
 *
 * -------------------------------------------------------------------------
 * KENAPA ISINYA BERUBAH SAMA SEKALI
 * -------------------------------------------------------------------------
 *
 * Dulu berkas ini menguji halaman /katalog, yaitu katalog seluruh produk
 * yang disiapkan untuk diunduh atau dicetak. Halaman itu dibuang atas
 * arahan pembimbing, bersama menu Semua Produk, dengan alasan yang masuk
 * akal: katalognya memang sudah ada di layar, bisa disaring dan dicari,
 * dan tidak ada seorang pun yang mengunduh daftar produk toko pakaian
 * untuk dibaca di luar situsnya.
 *
 * Berkas pengujiannya tidak bisa dihapus dari sini, sebab perkakas yang
 * dipakai menyalin perubahan ke komputer hanya bisa MENULIS berkas, tidak
 * bisa menghapusnya. Kalau isinya dibiarkan seperti semula, tujuh
 * pengujian gagal selamanya dengan RouteNotFoundException, dan kegagalan
 * yang sudah dianggap biasa akan menyembunyikan kegagalan sungguhan.
 *
 * -------------------------------------------------------------------------
 * JADI YANG DIUJI SEKARANG KEBALIKANNYA
 * -------------------------------------------------------------------------
 *
 * Bahwa routenya memang sudah tidak ada, dan menunya memang sudah tidak
 * tertulis di header maupun footer. Dengan begitu berkas ini tetap punya
 * guna: kalau suatu saat ada yang menghidupkan lagi halaman itu tanpa
 * sengaja, misalnya ketika menyalin kode lama, pengujian ini yang
 * memberitahu.
 *
 * Berkas sisa yang aman dihapus sendiri di komputer, sebab tidak ada lagi
 * yang menunjuknya:
 *
 *   app/Http/Controllers/KatalogController.php
 *   resources/views/katalog/
 */
class KatalogUnduhTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_unduh_katalog_sudah_tidak_ada(): void
    {
        $this->assertFalse(Route::has('katalog.unduh'),
            'Halaman Unduh Katalog sudah dilepas, jadi routenya tidak boleh ada lagi.');

        $this->get('/katalog')->assertNotFound();
    }

    public function test_menu_unduh_katalog_tidak_ada_di_header_dan_footer(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Unduh Katalog')
            // Semua Produk ikut dilepas: menekannya sama saja dengan
            // menekan Kategori tanpa memilih apa apa.
            ->assertDontSee('Semua Produk');
    }

    public function test_trending_menggantikan_tempatnya_di_bilah_utama(): void
    {
        $this->seed();

        // Yang naik ke bilah utama menggantikan kolom Lainnya adalah
        // Trending, dan isinya produk terlaris dari pesanan yang sudah
        // dibayar. Pengujian ini menjaga menunya tetap bisa ditekan.
        $this->get('/')
            ->assertOk()
            ->assertSee('Trending');

        $this->get(route('produk.index', ['urut' => 'terlaris']))->assertOk();
    }
}
