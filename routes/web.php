<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DiskonController;
use App\Http\Controllers\Admin\DonasiAdminController;
use App\Http\Controllers\Admin\FaqAdminController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\KeluhanAdminController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\MasukController;
use App\Http\Controllers\Admin\PelangganController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PesananAdminController;
use App\Http\Controllers\Admin\ProdukAdminController;
use App\Http\Controllers\Admin\ReturAdminController;
use App\Http\Controllers\Admin\SlotDonasiController;
use App\Http\Controllers\Admin\SlotTtcController;
use App\Http\Controllers\Admin\StokAdminController;
use App\Http\Controllers\Admin\StokGudangController;
use App\Http\Controllers\Admin\TokoAdminController;
use App\Http\Controllers\Admin\TransferStokController;
use App\Http\Controllers\Admin\TtcAdminController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\AlamatController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LupaSandiController;
use App\Http\Controllers\BantuanController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DonasiController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\Kasir\BookingController as KasirBookingController;
use App\Http\Controllers\Kasir\DashboardController as KasirDashboardController;
use App\Http\Controllers\Kasir\DonasiController as KasirDonasiController;
use App\Http\Controllers\Kasir\MemberController as KasirMemberController;
use App\Http\Controllers\Kasir\ReturController as KasirReturController;
use App\Http\Controllers\Kasir\StokController as KasirStokController;
use App\Http\Controllers\Kasir\TransferStokController as KasirTransferController;
use App\Http\Controllers\KeluhanController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\KurirController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Petugas\AkunPetugasController;
use App\Http\Controllers\Petugas\NotifikasiPetugasController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PreferensiController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ReturController;
use App\Http\Controllers\StafController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\TtcController;
use App\Http\Controllers\UlasanController;
use App\Http\Controllers\VoucherSayaController;
use App\Http\Controllers\VtoController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman umum
|--------------------------------------------------------------------------
*/
Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::post('/tema', [PreferensiController::class, 'tema'])->name('tema.ubah');
// Pilihan bahasa berlaku untuk tiga aktor sekaligus, sebab seluruh halaman
// memakai penolong teks() yang membaca pilihan ini dari session.
Route::post('/bahasa', [PreferensiController::class, 'bahasa'])->name('bahasa.ganti');

// Lokasi pelanggan dipakai untuk mengurutkan daftar toko dari yang terdekat.
// Disimpan di session saja, tidak pernah masuk ke basis data.
Route::post('/lokasi', [PreferensiController::class, 'lokasi'])->name('lokasi.simpan');
Route::delete('/lokasi', [PreferensiController::class, 'lupakanLokasi'])->name('lokasi.lupakan');

Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
Route::get('/produk-baru', [ProdukController::class, 'baru'])->name('produk.baru');
Route::get('/produk/{product}', [ProdukController::class, 'show'])->name('produk.show');
/*
 * Halaman Unduh Katalog SUDAH DIBUANG.
 *
 * Katalog PDF itu menduplikasi apa yang sudah ada di layar: pelanggan
 * yang ingin melihat produk membuka halaman Kategori, bukan mengunduh
 * berkas lalu membukanya di aplikasi lain. Menu Unduh Katalog dibuang
 * atas arahan pembimbing, dan routenya ikut dibuang supaya tidak
 * tertinggal sebagai halaman yang tidak bisa dijangkau siapa pun.
 */

Route::get('/toko', [TokoController::class, 'index'])->name('toko.index');
Route::get('/toko/{store}', [TokoController::class, 'show'])->name('toko.show');

/*
| Sale of Season
|
| Tempat pelanggan melihat program diskon yang sedang berjalan. Musimnya
| dibuat admin, dan halaman ini hanya membacanya. Terbuka untuk tamu,
| sebab program diskon memang dipakai menarik pengunjung baru.
*/
Route::get('/sale', [SaleController::class, 'index'])->name('sale.index');
Route::get('/sale/{discountSeason}', [SaleController::class, 'show'])->name('sale.show');


Route::get('/bantuan', [BantuanController::class, 'index'])->name('bantuan.index');
// Route kontak ditulis SEBELUM route bertitik dua di bawahnya. Laravel
// mencocokkan route dari atas ke bawah, jadi kalau urutannya dibalik,
// /bantuan/kontak akan tertangkap sebagai nama halaman bantuan lalu 404.
Route::get('/bantuan/kontak', [BantuanController::class, 'kontak'])->name('bantuan.kontak');
Route::get('/bantuan/{halaman}', [BantuanController::class, 'show'])->name('bantuan.show');

/*
 * Pertanyaan yang sering diajukan.
 *
 * Terpisah dari /bantuan, sebab keduanya menjawab kebutuhan yang berbeda.
 * Pusat Bantuan berisi artikel panjang untuk orang yang belum pernah
 * memakai satu layanan; FAQ menjawab satu pertanyaan sempit dalam dua
 * kalimat untuk orang yang sudah di tengah jalan lalu tersangkut.
 *
 * Isinya dibaca dari basis data dan dikelola admin, jadi halaman ini
 * tidak perlu disentuh lagi ketika pertanyaannya ditambah.
 *
 * Route pencatatan ditulis SEBELUM yang tidak ada ruasnya supaya jelas
 * terbaca berpasangan, dan nomornya dikunci hanya cocok dengan angka.
 */
Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
// Dipanggil halamannya sendiri ketika satu jawaban dibuka. Bukan untuk
// dibuka langsung di peramban, jadi hanya menerima POST dan menjawab tanpa
// isi. Tidak memerlukan akun: pelanggan yang belum masuk pun membaca FAQ.
Route::post('/faq/dibaca/{faq}', [FaqController::class, 'dibaca'])
    ->whereNumber('faq')->name('faq.dibaca');

/*
 * Halaman belanja yang boleh dipakai tamu.
 *
 * Seluruhnya dibungkus penjaga 'pelanggan'. Tamu dan pelanggan lewat,
 * petugas dipulangkan ke dashboardnya sendiri. Tanpa penjaga ini, akun
 * kasir bisa punya keranjang, memesan Tap Try and Collect di tokonya
 * sendiri, lalu memeriksanya sendiri pula, sehingga pemisahan peran yang
 * menjadi inti sistem ini jadi tidak ada artinya.
 */
Route::middleware('pelanggan')->group(function () {
    // Keranjang boleh dipakai tamu, isinya disimpan memakai session id
    Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang.index');
    Route::post('/keranjang', [KeranjangController::class, 'store'])->name('keranjang.store');
    Route::post('/keranjang/pilih-semua', [KeranjangController::class, 'pilihSemua'])->name('keranjang.pilih-semua');
    Route::put('/keranjang/{item}', [KeranjangController::class, 'update'])->name('keranjang.update');
    Route::post('/keranjang/{item}/pilih', [KeranjangController::class, 'pilih'])->name('keranjang.pilih');
    Route::delete('/keranjang/{item}', [KeranjangController::class, 'destroy'])->name('keranjang.destroy');

    // Beli Sekarang: barang disimpan di session lalu dibawa ke checkout. Tamu
    // boleh menekannya, login baru diminta ketika masuk halaman checkout.
    Route::post('/checkout/beli-langsung', [CheckoutController::class, 'beliLangsung'])->name('checkout.beli-langsung');
    Route::delete('/checkout/beli-langsung', [CheckoutController::class, 'batalBeliLangsung'])->name('checkout.beli-langsung.batal');

    // Tap Try and Collect dan donasi: halamannya boleh dilihat tamu. Toko dan
    // tanggal dipilih lewat query pada halaman yang sama, jadi daftar jamnya
    // disusun di server tanpa alamat tambahan.
    Route::get('/tap-try-collect', [TtcController::class, 'index'])->name('ttc.index');
    Route::get('/donasi', [DonasiController::class, 'index'])->name('donasi.index');

    // Virtual Try On memakai layanan AI. Boleh dicoba tamu, hasilnya diingat lewat session.
    Route::get('/virtual-try-on', [VtoController::class, 'index'])->name('vto.index');
    Route::post('/virtual-try-on', [VtoController::class, 'store'])->name('vto.store');
    Route::get('/virtual-try-on/gaya-saya', [VtoController::class, 'looks'])->name('vto.looks');
    Route::get('/virtual-try-on/{virtualTryOn}', [VtoController::class, 'show'])->name('vto.show');
    Route::get('/virtual-try-on/{virtualTryOn}/status', [VtoController::class, 'status'])->name('vto.status');
    Route::post('/virtual-try-on/{virtualTryOn}/proses', [VtoController::class, 'proses'])->name('vto.proses');
    Route::post('/virtual-try-on/{virtualTryOn}/simpan', [VtoController::class, 'simpan'])->name('vto.simpan');
    Route::post('/virtual-try-on/{virtualTryOn}/keranjang', [VtoController::class, 'tambahKeranjang'])->name('vto.keranjang');
    Route::delete('/virtual-try-on/{virtualTryOn}', [VtoController::class, 'destroy'])->name('vto.destroy');
});

// Notifikasi pembayaran dari server Midtrans (tanpa CSRF, diperiksa signature)
Route::post('/payment/notification', [PembayaranController::class, 'notifikasi'])->name('pembayaran.notifikasi');

// Kabar perjalanan paket dari penyedia jasa kirim pihak ketiga. Dipanggil
// mesin, bukan peramban, jadi di luar CSRF dan dijaga token pada alamatnya.
Route::post('/kurir/notifikasi', [KurirController::class, 'notifikasi'])->name('kurir.notifikasi');

// Halaman verifikasi staf toko (dibuka staf setelah memindai QR pelanggan)
Route::get('/staf/verifikasi/{jenis}/{kode}', [StafController::class, 'form'])
    ->whereIn('jenis', ['donasi', 'ttc', 'retur'])->name('staf.verifikasi');
Route::post('/staf/verifikasi/{jenis}/{kode}', [StafController::class, 'simpan'])
    ->whereIn('jenis', ['donasi', 'ttc', 'retur'])->name('staf.verifikasi.simpan');

/*
|--------------------------------------------------------------------------
| Masuk dan daftar
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'formMasuk'])->name('login');
    Route::post('/masuk', [AuthController::class, 'masuk'])->middleware('throttle:8,1')->name('login.proses');
    Route::get('/daftar', [AuthController::class, 'formDaftar'])->name('register');
    Route::post('/daftar', [AuthController::class, 'daftar'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/lupa-kata-sandi', [LupaSandiController::class, 'formPermintaan'])->name('password.request');
    Route::post('/lupa-kata-sandi', [LupaSandiController::class, 'kirimTautan'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/atur-kata-sandi/{token}', [LupaSandiController::class, 'formAtur'])->name('password.reset');
    Route::post('/atur-kata-sandi', [LupaSandiController::class, 'atur'])->name('password.update');
});

Route::post('/keluar', [AuthController::class, 'keluar'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Halaman pelanggan yang sudah masuk
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'pelanggan'])->group(function () {
    // Akun
    Route::get('/akun', [AkunController::class, 'index'])->name('akun.index');
    Route::get('/akun/profil', [AkunController::class, 'formProfil'])->name('profil.edit');
    Route::put('/akun/profil', [AkunController::class, 'simpanProfil'])->name('profil.update');
    Route::put('/akun/kata-sandi', [AkunController::class, 'gantiSandi'])->name('sandi.update');

    // Alamat pengiriman (CRUD)
    /*
    |----------------------------------------------------------------------
    | Voucher Saya
    |----------------------------------------------------------------------
    | Sebelum ini voucher cuma bisa dipakai kalau pelanggan sudah tahu
    | kodenya, dan yang kehilangan pesannya tidak punya cara menemukannya
    | lagi. Halaman ini menampilkan voucher yang benar benar bisa dia pakai
    | hari ini beserta syaratnya.
    */
    Route::get('/akun/voucher', [VoucherSayaController::class, 'index'])->name('voucher.saya');
    Route::post('/akun/voucher/{voucher}/pakai', [VoucherSayaController::class, 'pakai'])->name('voucher.saya.pakai');

    Route::get('/akun/alamat', [AlamatController::class, 'index'])->name('alamat.index');
    Route::get('/akun/alamat/tambah', [AlamatController::class, 'create'])->name('alamat.create');
    Route::post('/akun/alamat', [AlamatController::class, 'store'])->name('alamat.store');
    Route::get('/akun/alamat/{address}/ubah', [AlamatController::class, 'edit'])->name('alamat.edit');
    Route::put('/akun/alamat/{address}', [AlamatController::class, 'update'])->name('alamat.update');
    Route::put('/akun/alamat/{address}/utama', [AlamatController::class, 'jadikanUtama'])->name('alamat.utama');
    Route::delete('/akun/alamat/{address}', [AlamatController::class, 'destroy'])->name('alamat.destroy');

    // Notifikasi. Halaman Poin Saya dan halaman Keluhan sudah dilepas:
    // poin pelanggan menyatu di Riwayat Donasi, dan keluhan barang ditangani
    // lewat Retur dan Penukaran.
    Route::get('/akun/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/akun/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::get('/akun/notifikasi/{notification}', [NotifikasiController::class, 'buka'])->name('notifikasi.buka');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    // Checkout dan pembayaran
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/voucher', [CheckoutController::class, 'voucher'])->name('checkout.voucher');
    Route::delete('/checkout/voucher', [CheckoutController::class, 'hapusVoucher'])->name('checkout.voucher.hapus');

    Route::get('/pembayaran/{payment}', [PembayaranController::class, 'show'])->name('pembayaran.show');
    Route::get('/pembayaran/{payment}/status', [PembayaranController::class, 'status'])->name('pembayaran.status');
    // Ditanyakan halaman pembayaran sendiri tiap sepuluh detik, menggantikan
    // tombol Perbarui Status yang dulu harus ditekan pelanggan.
    Route::get('/pembayaran/{payment}/periksa', [PembayaranController::class, 'periksa'])->name('pembayaran.periksa');
    Route::get('/pembayaran/{payment}/selesai', [PembayaranController::class, 'selesai'])->name('pembayaran.selesai');

    // Pesanan
    Route::get('/pesanan', [PesananController::class, 'index'])->name('pesanan.index');
    Route::get('/pesanan/{order}', [PesananController::class, 'show'])->name('pesanan.show');
    Route::put('/pesanan/{order}/batal', [PesananController::class, 'batal'])->name('pesanan.batal');
    Route::put('/pesanan/{order}/terima', [PesananController::class, 'terima'])->name('pesanan.terima');
    Route::post('/pesanan/{order}/beli-lagi', [PesananController::class, 'beliLagi'])->name('pesanan.beli-lagi');
    Route::post('/pesanan/{order}/ulasan', [UlasanController::class, 'store'])->name('ulasan.store');

    // Retur
    Route::get('/retur', [ReturController::class, 'index'])->name('retur.index');
    Route::post('/retur/cari-struk', [ReturController::class, 'cariStruk'])->name('retur.cari-struk');
    Route::get('/retur/ajukan/{order}', [ReturController::class, 'create'])->name('retur.create');
    Route::post('/retur/ajukan/{order}', [ReturController::class, 'store'])->name('retur.store');
    Route::get('/retur/{returnRequest}', [ReturController::class, 'show'])->name('retur.show');
    Route::put('/retur/{returnRequest}/batal', [ReturController::class, 'batal'])->name('retur.batal');
    // Penutup pengajuan retur. Ditekan pelanggan sesudah barang penggantinya
    // diterima, sama seperti tombol Barang Sudah Diterima pada pesanan biasa.
    // Tanpa ini retur berhenti di status Sedang Diproses tanpa ujung, sebab
    // admin dilarang menutup retur toko dan kasir sudah selesai bekerja.
    Route::put('/retur/{returnRequest}/konfirmasi', [ReturController::class, 'konfirmasi'])->name('retur.konfirmasi');
    // Pelanggan memasukkan sendiri nomor resi sesudah mengirim barangnya ke
    // kantor jasa kirim. Inilah yang memindahkan status retur menjadi dikirim.
    Route::put('/retur/{returnRequest}/resi', [ReturController::class, 'resi'])->name('retur.resi');
    // Slip pengembalian siap cetak, ditempel di bungkus paket retur.
    Route::get('/retur/{returnRequest}/slip', [ReturController::class, 'slip'])->name('retur.slip');

    /*
    |----------------------------------------------------------------------
    | Contact Us, yaitu keluhan pelanggan
    |----------------------------------------------------------------------
    | Satu keluhan menjadi satu tiket bernomor yang berisi percakapan
    | dengan admin, bukan sekadar pesan sekali kirim. Jalurnya terpisah
    | dari retur: retur mengurus barang yang harus ditukar, keluhan
    | mengurus hal yang tidak selesai dengan menukar barang.
    */
    Route::get('/keluhan', [KeluhanController::class, 'index'])->name('keluhan.index');
    // Route tambah ditulis SEBELUM route yang memuat parameter di bawahnya.
    // Laravel mencocokkan route dari atas ke bawah, jadi kalau terbalik,
    // /keluhan/tambah akan tertangkap sebagai nomor tiket lalu 404.
    Route::get('/keluhan/tambah', [KeluhanController::class, 'create'])->name('keluhan.create');
    Route::post('/keluhan', [KeluhanController::class, 'store'])->name('keluhan.store');
    Route::get('/keluhan/{keluhan}', [KeluhanController::class, 'show'])->name('keluhan.show');
    Route::post('/keluhan/{keluhan}/balas', [KeluhanController::class, 'balas'])->name('keluhan.balas');
    Route::put('/keluhan/{keluhan}/tutup', [KeluhanController::class, 'tutup'])->name('keluhan.tutup');

    // Tap Try and Collect
    Route::post('/tap-try-collect', [TtcController::class, 'store'])->name('ttc.store');
    Route::get('/akun/tap-try-collect', [TtcController::class, 'riwayat'])->name('ttc.riwayat');
    Route::get('/akun/tap-try-collect/{tapTryCollect}', [TtcController::class, 'show'])->name('ttc.show');
    // Pernyataan pelanggan bahwa barang kunjungannya sudah dia bawa pulang,
    // sepasang dengan tombol Barang Sudah Diterima pada pesanan daring.
    Route::put('/akun/tap-try-collect/{tapTryCollect}/konfirmasi', [TtcController::class, 'konfirmasi'])->name('ttc.konfirmasi');
    Route::put('/akun/tap-try-collect/{tapTryCollect}/batal', [TtcController::class, 'batal'])->name('ttc.batal');

    // Donasi pakaian
    Route::post('/donasi', [DonasiController::class, 'store'])->name('donasi.store');
    Route::get('/akun/donasi', [DonasiController::class, 'riwayat'])->name('donasi.riwayat');
    Route::get('/akun/donasi/{donation}', [DonasiController::class, 'show'])->name('donasi.show');
    // Tidak ada route check-in untuk pelanggan. Dulu pelanggan harus menekan
    // "Saya sudah di toko" lebih dahulu supaya QRnya muncul, padahal langkah
    // itu tidak menghasilkan apa apa: yang menentukan pelanggan benar benar
    // datang tetap kasir yang memindai QRnya. Jadi QRnya langsung ditampilkan,
    // dan kedatangannya dicatat kasir saat memindai.
    Route::put('/akun/donasi/{donation}/batal', [DonasiController::class, 'batal'])->name('donasi.batal');
});

/*
|--------------------------------------------------------------------------
| Dashboard kasir toko
|--------------------------------------------------------------------------
| Kasir hanya melihat data tokonya sendiri. Semua serah terima dengan
| pelanggan lewat catatan dan notifikasi, bukan percakapan langsung.
*/
Route::middleware(['auth', 'kasir'])->prefix('kasir')->name('kasir.')->group(function () {
    Route::get('/', [KasirDashboardController::class, 'index'])->name('dashboard');
    Route::post('/cari', [KasirDashboardController::class, 'cari'])->name('cari');

    /*
    |----------------------------------------------------------------------
    | Kiriman stok dari gudang pusat
    |----------------------------------------------------------------------
    | Yang membuka kardusnya kasir, jadi dialah yang menghitung dan
    | mencatat jumlah yang benar benar sampai. Selama penerimaan dicatat
    | admin di kantor, selisih tidak pernah mungkin ditemukan: angkanya
    | disalin dari surat jalannya sendiri.
    */
    Route::get('/transfer', [KasirTransferController::class, 'index'])->name('transfer.index');
    Route::get('/transfer/{stockTransfer}', [KasirTransferController::class, 'show'])
        ->whereNumber('stockTransfer')->name('transfer.show');
    Route::put('/transfer/{stockTransfer}/terima', [KasirTransferController::class, 'terima'])
        ->whereNumber('stockTransfer')->name('transfer.terima');

    /*
    |----------------------------------------------------------------------
    | Antrean per layanan
    |----------------------------------------------------------------------
    | Layar utama memuat dua layanan sekaligus dan hanya menampilkan satu
    | daftar pada satu waktu. Dua halaman di bawah memuat satu layanan saja
    | beserta seluruh keadaannya sekaligus, jadi satu layanan bisa
    | dikerjakan dari atas ke bawah tanpa menekan kepingan bolak balik.
    */
    Route::get('/antrean/tap-try-collect', [KasirDashboardController::class, 'antreanTtc'])->name('antrean.ttc');
    Route::get('/antrean/donasi', [KasirDashboardController::class, 'antreanDonasi'])->name('antrean.donasi');

    // Booking Tap Try and Collect
    Route::get('/booking/{tapTryCollect}', [KasirBookingController::class, 'show'])->name('booking.show');
    Route::put('/booking/{tapTryCollect}/siap', [KasirBookingController::class, 'siap'])->name('booking.siap');
    Route::put('/booking/{tapTryCollect}/ambil', [KasirBookingController::class, 'ambil'])->name('booking.ambil');
    Route::put('/booking/{tapTryCollect}/tidak-diambil', [KasirBookingController::class, 'tidakDiambil'])->name('booking.tidak-diambil');

    // Donasi pakaian
    Route::get('/donasi/{donation}', [KasirDonasiController::class, 'show'])->name('donasi.show');
    Route::put('/donasi/{donation}/check-in', [KasirDonasiController::class, 'checkIn'])->name('donasi.check-in');
    Route::put('/donasi/{donation}/verifikasi', [KasirDonasiController::class, 'verifikasi'])->name('donasi.verifikasi');

    // Persediaan toko ini. Petugas toko menghitung barang di rak lalu
    // mengetik hasilnya, dan sistem mencatat selisihnya di kartu stok.
    // Angka committed tidak punya isian sama sekali, sebab angka itu milik
    // pesanan pelanggan, bukan milik petugas.
    Route::get('/stok', [KasirStokController::class, 'index'])->name('stok.index');
    Route::get('/stok/pilih-varian', [KasirStokController::class, 'pilihVarian'])->name('stok.pilih-varian');
    // Rincian satu produk beserta seluruh warna dan ukurannya. Ditulis
    // SEBELUM /stok/{storeStock}, sebab route yang lebih umum akan
    // menelan alamat ini kalau didaftarkan lebih dahulu: kata produk
    // terbaca sebagai nomor baris stok, lalu halamannya galat.
    Route::get('/stok/produk/{product}', [KasirStokController::class, 'produk'])->name('stok.produk');
    Route::post('/stok', [KasirStokController::class, 'tambah'])->name('stok.tambah');
    Route::get('/stok/{storeStock}', [KasirStokController::class, 'show'])->name('stok.show');
    Route::put('/stok/{storeStock}/hitung-ulang', [KasirStokController::class, 'hitungUlang'])->name('stok.hitung-ulang');
    Route::put('/stok/{storeStock}/masuk', [KasirStokController::class, 'masuk'])->name('stok.masuk');
    Route::put('/stok/{storeStock}/keluar', [KasirStokController::class, 'keluar'])->name('stok.keluar');
    // Menghapus barisnya dari daftar barang toko ini. Hanya boleh kalau
    // angkanya sudah nol, supaya tidak ada baju di rak yang tidak tercatat.
    Route::delete('/stok/{storeStock}', [KasirStokController::class, 'hapus'])->name('stok.hapus');

    // Data member
    /*
    |----------------------------------------------------------------------
    | Retur yang diantar ke toko ini
    |----------------------------------------------------------------------
    | Pemeriksaan retur pakaian itu pekerjaan tangan, bukan pekerjaan layar.
    | Yang menentukan diterima atau tidak hanya bisa diketahui orang yang
    | memegang bajunya: labelnya masih menempel atau tidak, sudah dicuci
    | atau belum, berbau atau tidak. Karena itu retur dengan cara antar
    | sendiri ke toko diurus kasir toko, dan admin kantor hanya melihat.
    |
    | Retur yang dikirim lewat jasa kirim tetap urusan admin, sebab
    | paketnya memang sampai ke kantor.
    */
    Route::get('/retur', [KasirReturController::class, 'index'])->name('retur.index');
    Route::get('/retur/{returnRequest}', [KasirReturController::class, 'show'])->name('retur.show');
    Route::put('/retur/{returnRequest}/periksa', [KasirReturController::class, 'periksa'])->name('retur.periksa');
    Route::put('/retur/{returnRequest}/selesai', [KasirReturController::class, 'selesai'])->name('retur.selesai');
    // Membatalkan JADWAL kedatangannya, bukan menolak barangnya. Dipakai
    // ketika pelanggan tidak pernah datang, jadi tidak ada barang yang
    // bisa diperiksa.
    Route::put('/retur/{returnRequest}/batal', [KasirReturController::class, 'batal'])->name('retur.batal');

    Route::get('/member', [KasirMemberController::class, 'index'])->name('member.index');
    Route::get('/member/{user}', [KasirMemberController::class, 'show'])->name('member.show');
});

/*
|--------------------------------------------------------------------------
| Halaman masuk petugas
|--------------------------------------------------------------------------
| Akun admin dan kasir dibuatkan perusahaan, jadi tidak ada pendaftaran di
| sini. Nama lengkap dipakai sebagai nama pengguna, kata sandinya tanggal
| lahir dengan penulisan yyyy-mm-dd.
*/
// Satu pintu untuk satu peran. Alamatnya sengaja dibedakan supaya ketika
// sistemnya diperagakan, ketiga peran bisa dibuka berdampingan di tiga
// jendela dan terbukti benar benar terpisah.
// defaults() mengisi parameter $peran pada methodnya tanpa menuliskannya di
// alamat, jadi alamatnya tetap pendek dan routenya tetap bisa disimpan cache.
Route::middleware('guest')->group(function () {
    Route::get('/admin/masuk', [MasukController::class, 'form'])
        ->defaults('peran', 'admin')->name('admin.masuk');
    Route::post('/admin/masuk', [MasukController::class, 'masuk'])
        ->defaults('peran', 'admin')->middleware('throttle:8,1')->name('admin.masuk.proses');

    Route::get('/kasir/masuk', [MasukController::class, 'form'])
        ->defaults('peran', 'kasir')->name('kasir.masuk');
    Route::post('/kasir/masuk', [MasukController::class, 'masuk'])
        ->defaults('peran', 'kasir')->middleware('throttle:8,1')->name('kasir.masuk.proses');

});

/*
|--------------------------------------------------------------------------
| Halaman milik petugas sendiri, dipakai admin maupun kasir
|--------------------------------------------------------------------------
| Dua halaman yang isinya bukan pekerjaan yang dibagi menurut peran,
| melainkan milik akunnya masing masing: Pemberitahuan dan Pengaturan
| Akun. Dulu sebagian isinya berupa ikon di bilah atas, yang dipakai
| paling banyak sekali seumur pemakaian tetapi menempati tempat paling
| mahal di layar sepanjang hari.
|
| Pelanggan ditolak di sini oleh middleware petugas, dan halaman
| Notifikasi miliknya sendiri tetap di /akun/notifikasi.
*/
Route::middleware(['auth', 'petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/notifikasi', [NotifikasiPetugasController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [NotifikasiPetugasController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{notification}', [NotifikasiPetugasController::class, 'buka'])
        ->whereNumber('notification')->name('notifikasi.buka');

    Route::get('/akun', [AkunPetugasController::class, 'index'])->name('akun');
    Route::put('/akun/kata-sandi', [AkunPetugasController::class, 'gantiSandi'])->name('akun.sandi');
});

/*
|--------------------------------------------------------------------------
| Dashboard admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Produk (CRUD lengkap)
    Route::get('/produk', [ProdukAdminController::class, 'index'])->name('produk.index');
    Route::get('/produk/tambah', [ProdukAdminController::class, 'create'])->name('produk.create');
    Route::post('/produk', [ProdukAdminController::class, 'store'])->name('produk.store');
    Route::get('/produk/{product}', [ProdukAdminController::class, 'show'])->name('produk.show');
    Route::get('/produk/{product}/ubah', [ProdukAdminController::class, 'edit'])->name('produk.edit');
    Route::put('/produk/{product}', [ProdukAdminController::class, 'update'])->name('produk.update');
    Route::delete('/produk/{product}', [ProdukAdminController::class, 'destroy'])->name('produk.destroy');

    // Pengawasan persediaan menyeluruh, gudang pusat maupun seluruh toko.
    // Ketiganya hanya menampilkan, tidak mengubah.
    Route::get('/stok', [StokAdminController::class, 'index'])->name('stok.index');
    Route::get('/stok/kartu', [StokAdminController::class, 'kartu'])->name('stok.kartu');
    // Rincian satu produk di satu toko, hanya untuk dilihat. Angka stok toko
    // diubah petugas tokonya sendiri, sebab dialah yang memegang barangnya.
    Route::get('/stok/produk/{product}', [StokAdminController::class, 'produk'])->name('stok.produk');

    /*
    |----------------------------------------------------------------------
    | Gudang pusat
    |----------------------------------------------------------------------
    | Stok barang fisik di gudang pusat, yang melayani pesanan kirim.
    |
    | Dulu bagian ini punya aktornya sendiri, yaitu Admin, dengan
    | halaman masuk terpisah di /gudang/masuk. Aktor itu dihapus atas
    | arahan pembimbing, dan pekerjaannya dipegang admin.
    |
    | Yang berubah hanya SIAPA yang mengerjakan. Pekerjaannya sendiri tetap
    | sama persis: mencatat barang masuk dari pemasok, mencatat barang
    | keluar tanpa pesanan, dan menghitung ulang isi rak. Stok tiap toko
    | tetap terpisah dan tetap diurus petugas tokonya masing masing lewat
    | App\Http\Controllers\Kasir\StokController.
    |
    | whereNumber() mengunci parameternya hanya cocok dengan angka, supaya
    | alamat yang ruasnya bukan angka tidak ikut tertangkap route
    | /admin/gudang/{productVariant} lalu dicari sebagai varian dengan nama
    | itu dan berakhir 404.
    */
    Route::get('/gudang', [StokGudangController::class, 'index'])->name('gudang.index');
    // Rincian satu produk beserta seluruh warna dan ukurannya. Ditulis
    // SEBELUM route /gudang/{productVariant}, sebab kalau sesudahnya, kata
    // "produk" akan tertangkap lebih dulu sebagai nomor varian.
    Route::get('/gudang/produk/{product}', [StokGudangController::class, 'produk'])->name('gudang.produk');
    Route::get('/gudang/{productVariant}', [StokGudangController::class, 'show'])
        ->whereNumber('productVariant')->name('gudang.show');
    Route::put('/gudang/{productVariant}/hitung-ulang', [StokGudangController::class, 'hitungUlang'])
        ->whereNumber('productVariant')->name('gudang.hitung-ulang');
    Route::put('/gudang/{productVariant}/masuk', [StokGudangController::class, 'masuk'])
        ->whereNumber('productVariant')->name('gudang.masuk');
    Route::put('/gudang/{productVariant}/keluar', [StokGudangController::class, 'keluar'])
        ->whereNumber('productVariant')->name('gudang.keluar');

    /*
    |----------------------------------------------------------------------
    | Pemindahan stok gudang pusat ke toko
    |----------------------------------------------------------------------
    | Dua langkah di sisi admin: surat jalannya dibuat dulu, baru
    | disetujui. Pada langkah pertama barangnya masih di rak dan
    | daftarnya masih bisa dibetulkan; pada langkah kedua barangnya benar
    | benar keluar dari gudang.
    |
    | Penerimaannya dikerjakan kasir toko tujuan, sebab dialah yang
    | membuka kardusnya. Routenya ada di kelompok kasir di bawah.
    */
    Route::get('/transfer', [TransferStokController::class, 'index'])->name('transfer.index');
    Route::get('/transfer/buat', [TransferStokController::class, 'create'])->name('transfer.create');
    Route::post('/transfer', [TransferStokController::class, 'store'])->name('transfer.store');
    Route::get('/transfer/{stockTransfer}', [TransferStokController::class, 'show'])
        ->whereNumber('stockTransfer')->name('transfer.show');
    Route::put('/transfer/{stockTransfer}/setujui', [TransferStokController::class, 'setujui'])
        ->whereNumber('stockTransfer')->name('transfer.setujui');
    Route::delete('/transfer/{stockTransfer}', [TransferStokController::class, 'batal'])
        ->whereNumber('stockTransfer')->name('transfer.batal');

    // Kategori (CRUD)
    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
    Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::put('/kategori/{category}', [KategoriController::class, 'update'])->name('kategori.update');
    Route::delete('/kategori/{category}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

    /*
    |----------------------------------------------------------------------
    | Pertanyaan yang sering diajukan
    |----------------------------------------------------------------------
    | Isi halaman FAQ pelanggan, beserta pengaturan kategorinya.
    |
    | Route kategori ditulis SEBELUM route /faq/{faq}, sebab Laravel
    | mencocokkan dari atas ke bawah: kalau urutannya dibalik, alamat
    | /admin/faq/kategori akan tertangkap sebagai nomor pertanyaan lalu
    | berakhir 404. Nomor pertanyaannya juga dikunci hanya cocok dengan
    | angka, supaya pasangannya tidak saling mencuri alamat.
    */
    Route::get('/faq', [FaqAdminController::class, 'index'])->name('faq.index');
    Route::get('/faq/tambah', [FaqAdminController::class, 'create'])->name('faq.create');
    Route::post('/faq', [FaqAdminController::class, 'store'])->name('faq.store');
    // Kategori: daftar dan formulirnya di satu halaman.
    Route::get('/faq/kategori', [FaqAdminController::class, 'kategori'])->name('faq.kategori');
    Route::post('/faq/kategori', [FaqAdminController::class, 'simpanKategori'])->name('faq.kategori.store');
    Route::put('/faq/kategori/{faqCategory}', [FaqAdminController::class, 'ubahKategori'])->name('faq.kategori.update');
    Route::delete('/faq/kategori/{faqCategory}', [FaqAdminController::class, 'hapusKategori'])->name('faq.kategori.destroy');
    Route::get('/faq/{faq}/ubah', [FaqAdminController::class, 'edit'])->whereNumber('faq')->name('faq.edit');
    Route::put('/faq/{faq}', [FaqAdminController::class, 'update'])->whereNumber('faq')->name('faq.update');
    Route::delete('/faq/{faq}', [FaqAdminController::class, 'destroy'])->whereNumber('faq')->name('faq.destroy');

    // Toko (CRUD)
    Route::get('/toko', [TokoAdminController::class, 'index'])->name('toko.index');
    Route::get('/toko/tambah', [TokoAdminController::class, 'create'])->name('toko.create');
    Route::post('/toko', [TokoAdminController::class, 'store'])->name('toko.store');
    Route::get('/toko/{store}/ubah', [TokoAdminController::class, 'edit'])->name('toko.edit');
    Route::put('/toko/{store}', [TokoAdminController::class, 'update'])->name('toko.update');
    Route::delete('/toko/{store}', [TokoAdminController::class, 'destroy'])->name('toko.destroy');
    // Lembar QR meja kasir, dicetak petugas lalu diletakkan di mejanya.
    // Dulu halaman ini ada di sisi pelanggan, padahal yang mencetaknya toko.
    Route::get('/toko/{store}/qr-kasir', [TokoAdminController::class, 'qrKasir'])->name('toko.qr');

    // Slot donasi (CRUD, kuota terpisah)
    Route::get('/slot-donasi', [SlotDonasiController::class, 'index'])->name('slot-donasi.index');
    Route::get('/slot-donasi/tambah', [SlotDonasiController::class, 'create'])->name('slot-donasi.create');
    Route::post('/slot-donasi', [SlotDonasiController::class, 'store'])->name('slot-donasi.store');
    Route::get('/slot-donasi/{donationSlot}/ubah', [SlotDonasiController::class, 'edit'])->name('slot-donasi.edit');
    Route::put('/slot-donasi/{donationSlot}', [SlotDonasiController::class, 'update'])->name('slot-donasi.update');
    Route::delete('/slot-donasi/{donationSlot}', [SlotDonasiController::class, 'destroy'])->name('slot-donasi.destroy');

    // Slot Tap Try and Collect (CRUD, kuota terpisah)
    Route::get('/slot-ttc', [SlotTtcController::class, 'index'])->name('slot-ttc.index');
    Route::get('/slot-ttc/tambah', [SlotTtcController::class, 'create'])->name('slot-ttc.create');
    Route::post('/slot-ttc', [SlotTtcController::class, 'store'])->name('slot-ttc.store');
    Route::get('/slot-ttc/{tapTryCollectSlot}/ubah', [SlotTtcController::class, 'edit'])->name('slot-ttc.edit');
    Route::put('/slot-ttc/{tapTryCollectSlot}', [SlotTtcController::class, 'update'])->name('slot-ttc.update');
    Route::delete('/slot-ttc/{tapTryCollectSlot}', [SlotTtcController::class, 'destroy'])->name('slot-ttc.destroy');

    // Pesanan dan pembayaran
    Route::get('/pesanan', [PesananAdminController::class, 'index'])->name('pesanan.index');
    Route::get('/pesanan/{order}', [PesananAdminController::class, 'show'])->name('pesanan.show');
    Route::put('/pesanan/{order}/status', [PesananAdminController::class, 'ubahStatus'])->name('pesanan.status');
    // Pengesahan akhir. Ditekan admin sesudah pelanggan menyatakan barangnya
    // diterima, jadi yang menutup berkas pesanan ada dua pihak.
    Route::put('/pesanan/{order}/sahkan', [PesananAdminController::class, 'sahkan'])->name('pesanan.sahkan');
    // Mengganti nomor resi peragaan dengan nomor sungguhan dari struk kurir.
    // Terpisah dari langkah pesanan supaya alur utamanya tetap satu tekanan
    // tanpa isian apa pun.
    Route::put('/pesanan/{order}/resi', [PesananAdminController::class, 'gantiResi'])->name('pesanan.resi');
    Route::get('/pembayaran', [PesananAdminController::class, 'pembayaran'])->name('pembayaran.index');
    Route::put('/pembayaran/{payment}/periksa', [PesananAdminController::class, 'periksaPembayaran'])->name('pembayaran.periksa');
    // Hanya terpakai selama kunci Midtrans kosong, yaitu mode demo. Controllernya
    // menolak sendiri begitu Midtrans sudah tersambung.
    Route::put('/pembayaran/{payment}/tandai-demo', [PesananAdminController::class, 'tandaiDemo'])->name('pembayaran.demo');

    /*
    |----------------------------------------------------------------------
    | Diskon musiman
    |----------------------------------------------------------------------
    | Fitur tersendiri, bukan isian di formulir produk. Satu musim berisi
    | nama, tanggal mulai, tanggal berakhir, dan daftar produk yang ikut,
    | jadi program diskon berakhir sendiri begitu tanggalnya lewat.
    */
    Route::get('/diskon', [DiskonController::class, 'index'])->name('diskon.index');
    Route::get('/diskon/tambah', [DiskonController::class, 'create'])->name('diskon.create');
    Route::post('/diskon', [DiskonController::class, 'store'])->name('diskon.store');
    Route::get('/diskon/{discountSeason}', [DiskonController::class, 'show'])->name('diskon.show');
    Route::get('/diskon/{discountSeason}/ubah', [DiskonController::class, 'edit'])->name('diskon.edit');
    Route::put('/diskon/{discountSeason}', [DiskonController::class, 'update'])->name('diskon.update');
    Route::delete('/diskon/{discountSeason}', [DiskonController::class, 'destroy'])->name('diskon.destroy');
    Route::post('/diskon/{discountSeason}/produk', [DiskonController::class, 'tambahProduk'])->name('diskon.produk.tambah');
    Route::put('/diskon/{discountSeason}/produk/{baris}', [DiskonController::class, 'ubahProduk'])->name('diskon.produk.ubah');
    Route::delete('/diskon/{discountSeason}/produk/{baris}', [DiskonController::class, 'hapusProduk'])->name('diskon.produk.hapus');

    // Retur
    Route::get('/retur', [ReturAdminController::class, 'index'])->name('retur.index');
    Route::get('/retur/{returnRequest}', [ReturAdminController::class, 'show'])->name('retur.show');
    Route::put('/retur/{returnRequest}/status', [ReturAdminController::class, 'ubahStatus'])->name('retur.status');

    // Donasi
    Route::get('/donasi', [DonasiAdminController::class, 'index'])->name('donasi.index');
    Route::get('/donasi/{donation}', [DonasiAdminController::class, 'show'])->name('donasi.show');

    // Tap Try and Collect
    Route::get('/tap-try-collect', [TtcAdminController::class, 'index'])->name('ttc.index');
    Route::get('/tap-try-collect/{tapTryCollect}', [TtcAdminController::class, 'show'])->name('ttc.show');

    // Keluhan pelanggan, yaitu meja layanan Contact Us
    Route::get('/keluhan', [KeluhanAdminController::class, 'index'])->name('keluhan.index');
    Route::get('/keluhan/{keluhan}', [KeluhanAdminController::class, 'show'])->name('keluhan.show');
    Route::post('/keluhan/{keluhan}/balas', [KeluhanAdminController::class, 'balas'])->name('keluhan.balas');
    Route::put('/keluhan/{keluhan}/tutup', [KeluhanAdminController::class, 'tutup'])->name('keluhan.tutup');

    // Pelanggan
    Route::get('/pelanggan', [PelangganController::class, 'index'])->name('pelanggan.index');
    Route::get('/pelanggan/{user}', [PelangganController::class, 'show'])->name('pelanggan.show');

    // Voucher (CRUD)
    Route::get('/voucher', [VoucherController::class, 'index'])->name('voucher.index');
    Route::post('/voucher', [VoucherController::class, 'store'])->name('voucher.store');
    Route::put('/voucher/{voucher}', [VoucherController::class, 'update'])->name('voucher.update');
    Route::delete('/voucher/{voucher}', [VoucherController::class, 'destroy'])->name('voucher.destroy');

    // Laporan penjualan dan pengaturan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
});
