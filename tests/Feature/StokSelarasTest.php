<?php

// Menandai berkas ini berada di ruang nama Tests\Feature.
namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\User;
// RefreshDatabase membuat ulang seluruh tabel sebelum tiap pengujian.
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Angka persediaan yang dilihat pelanggan, admin, dan kasir wajib SAMA.
 *
 * Ini bukan sekadar soal rapi. Kalau pelanggan melihat sisa 3 padahal di
 * sistem kasir tinggal 0, pelanggan akan memesan barang yang sudah tidak ada,
 * lalu pesanannya batal di tengah jalan dan kepercayaannya hilang. Maka
 * ketiganya harus membaca angka dari satu sumber yang sama, bukan dari
 * salinan yang diperbarui belakangan.
 *
 * Yang dibuktikan di sini:
 *
 *   1. halaman produk pelanggan menampilkan angka yang sama dengan isi tabel
 *   2. begitu barang dijanjikan ke pesanan, angka pelanggan ikut turun
 *      SEKETIKA, tanpa menunggu pekerjaan berkala apa pun
 *   3. halaman stok toko milik kasir menunjukkan angka yang sama persis
 *   4. tidak ada cache yang membuat angkanya ketinggalan
 */
class StokSelarasTest extends TestCase
{
    use RefreshDatabase;

    /** Seeder dijalankan sekali untuk tiap pengujian di kelas ini. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_angka_stok_toko_sama_antara_pelanggan_dan_kasir(): void
    {
        // Satu baris stok toko yang memang ada isinya dipakai sebagai bahan.
        // Tokonya wajib yang masih aktif, sebab halaman produk memang hanya
        // menampilkan toko aktif.
        $stok = StoreStock::where('on_hand', '>', 0)
            ->whereHas('toko', fn ($q) => $q->where('is_active', true))
            ->firstOrFail();
        $varian = ProductVariant::findOrFail($stok->product_variant_id);
        $produk = Product::findOrFail($varian->product_id);
        $toko = Store::findOrFail($stok->store_id);

        // Kasir toko tersebut. Kasir toko lain tidak boleh dipakai, sebab
        // kasir hanya melihat stok tokonya sendiri.
        $kasir = User::where('role', 'kasir')->where('store_id', $toko->id)->first();

        // Angka acuan: on hand dikurangi committed, dihitung satu kali oleh
        // trait MengelolaStok. Inilah satu satunya sumber kebenarannya.
        $acuan = $stok->tersedia();

        // 1. Halaman produk pelanggan. Bagian Cek Stok Toko sekarang
        //    merinci stok PER UKURAN, jadi angka tiap ukuran digambar sebagai
        //    satu petak kecil. Yang dicari petak milik ukuran varian ini.
        $isi = $this->get(route('produk.show', [$produk, 'varian' => $varian->id]))
            ->assertOk()
            ->getContent();

        $this->assertTrue($this->petakUkuran($isi, $varian->size, $acuan),
            'Angka available ukuran '.$varian->size.' di halaman pelanggan harus '.$acuan.'.');

        // 2. Halaman stok toko milik kasir. Dilewati kalau toko contohnya
        //    kebetulan belum punya kasir, supaya pengujian tidak gagal karena
        //    data contoh, bukan karena kodenya salah.
        if ($kasir) {
            $this->actingAs($kasir)
                ->get(route('kasir.stok.show', $stok))
                ->assertOk()
                ->assertSee((string) $acuan);
        }
    }

    public function test_janji_stok_langsung_terlihat_pelanggan_tanpa_jeda(): void
    {
        $stok = StoreStock::where('on_hand', '>', 2)
            ->whereHas('toko', fn ($q) => $q->where('is_active', true))
            ->firstOrFail();
        $varian = ProductVariant::findOrFail($stok->product_variant_id);
        $produk = Product::findOrFail($varian->product_id);

        $sebelum = $stok->tersedia();

        // Dua buah dijanjikan kepada sebuah pesanan. Yang bertambah hanya
        // kolom committed, angka fisik on_hand tidak disentuh, sebab barangnya
        // memang masih ada di rak toko.
        $stok->janjikan(2, 'Pengujian keselarasan stok');

        // Barisnya dibaca ulang dari basis data, bukan dari objek lama di
        // memori, supaya yang diuji benar benar angka yang tersimpan.
        $stok->refresh();

        $this->assertSame($sebelum - 2, $stok->tersedia(),
            'Angka available harus turun dua begitu barangnya dijanjikan.');

        // Halaman pelanggan wajib langsung menunjukkan angka yang baru. Kalau
        // ada cache yang menyimpan angka lama, baris ini yang akan gagal.
        //
        // Yang diperiksa hanya kehadiran angka barunya, bukan ketidakhadiran
        // angka lamanya, sebab toko LAIN bisa saja kebetulan punya sisa
        // sebanyak angka lama itu dan tulisannya ikut muncul di halaman yang
        // sama.
        $isi = $this->get(route('produk.show', [$produk, 'varian' => $varian->id]))
            ->assertOk()
            ->getContent();

        $this->assertTrue($this->petakUkuran($isi, $varian->size, $stok->tersedia()),
            'Angka baru harus langsung terlihat pelanggan, tanpa cache yang menyimpan angka lama.');
    }

    /**
     * Halaman produk memuat petak ukuran tertentu dengan angka tertentu?
     *
     * Dibaca memakai pola, bukan dicocokkan sebagai teks utuh, sebab Blade
     * memecah atribut HTMLnya ke beberapa baris. Mencocokkan teks utuh
     * membuat pengujiannya gagal hanya karena spasi berubah, padahal
     * halamannya baik baik saja.
     */
    private function petakUkuran(string $isi, string $ukuran, int $angka): bool
    {
        /*
         * Dibaca dari ATRIBUT DATA, bukan dari tulisan di layar.
         *
         * Dulu pola ini mencari <span class="stok-ukuran__angka">8</span>,
         * yaitu angka yang dicetak apa adanya ke layar. Angka itu sudah
         * dilepas dari tampilan: pelanggan cuma perlu tahu boleh datang
         * atau tidak, dan sisa tujuh atau delapan bukan urusannya, apalagi
         * memajang isi rak tiap toko ke publik.
         *
         * Angkanya tetap dibawa halaman itu sebagai atribut data-tersedia,
         * justru supaya bisa dibuktikan di sini bahwa angka yang mendasari
         * layar pelanggan sama persis dengan angka yang dilihat kasir. Jadi
         * yang berubah cuma TEMPAT angkanya dibaca, bukan apa yang diuji.
         */
        $pola = '#data-ukuran="'.preg_quote($ukuran, '#').'"\s*'
            .'data-tersedia="'.$angka.'"#s';

        return (bool) preg_match($pola, $isi);
    }

    public function test_stok_gudang_pusat_sama_antara_pelanggan_dan_petugas_gudang(): void
    {
        $varian = ProductVariant::where('on_hand', '>', 0)->firstOrFail();
        $produk = Product::findOrFail($varian->product_id);
        $gudang = User::where('role', 'admin')->first();

        $acuan = $varian->tersedia();

        // Halaman produk pelanggan memakai angka ini untuk menentukan ukuran
        // mana yang boleh dipilih, dan menuliskannya pada atribut data-stok.
        $this->get(route('produk.show', $produk))
            ->assertOk()
            ->assertSee('data-stok="'.$acuan.'"', false);

        if ($gudang) {
            $this->actingAs($gudang)
                ->get(route('admin.gudang.show', $varian))
                ->assertOk()
                ->assertSee((string) $acuan);
        }
    }
}
