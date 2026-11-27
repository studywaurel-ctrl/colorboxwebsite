# Audit Project `web_colorbox` dan Rencana Pengembangan

Tanggal audit: 24 September 2026
Lokasi project: `web_colorbox` (Laravel 12.69.2, PHP 8.4.21)
Referensi tampilan: `web_colorbox_pelanggan` (21 September 2026, hanya dibaca, tidak diubah)

Status: **belum ada satu baris kode pun yang diubah pada tahap ini.** Dokumen ini
adalah hasil audit dan rencana, sesuai permintaan "jangan langsung coding sebelum
melakukan audit".

---

## 1. Ringkasan Audit

Project ini **hidup dan bisa dijalankan**. Server uji dijalankan dengan
`php artisan serve`, database SQLite diisi seeder, lalu seluruh halaman publik
dibuka satu per satu. Hasilnya: tidak ada halaman 500, tidak ada route mati,
tidak ada controller atau view yang hilang.

| Pemeriksaan | Hasil |
|---|---|
| Jumlah route terdaftar | 146 (semua punya controller dan method yang ada) |
| Controller | 40 berkas (24 pelanggan, 15 admin, 2 autentikasi) |
| Model | 31 |
| Migration | 7 berkas, 33 tabel |
| View Blade | 82 |
| Halaman publik dibuka (17 URL) | 12 balas 200, 5 balas 302 ke halaman masuk (memang perlu login) |
| `php -l` seluruh berkas PHP | bersih |
| Larangan `@json(...)` di Blade | tidak ditemukan satu pun |
| Foto produk lokal | 268 PNG, 267 terpasang ke 62 produk |

Jadi masalahnya **bukan project rusak**, melainkan **project belum sesuai
spesifikasi baru**. Ada 14 selisih besar antara keadaan sekarang dan permintaan
Anda. Bagian 8–10 menjelaskannya satu per satu.

---

## 2. Struktur Folder Penting

```
web_colorbox/
├── app/
│   ├── Console/Commands/PerbaruiStatus.php      perintah terjadwal: kedaluwarsa pesanan dan booking
│   ├── Http/
│   │   ├── Controllers/                         24 controller pelanggan
│   │   │   ├── Admin/                           15 controller dashboard admin
│   │   │   └── Auth/                            AuthController, LupaSandiController
│   │   └── Middleware/HanyaAdmin.php            penjaga halaman admin
│   ├── Models/                                  31 model Eloquent
│   ├── Services/MidtransService.php             pembungkus Snap sandbox
│   └── Support/helpers.php                      rupiah(), setting(), gambar_produk(), tanggal_id(), jam_singkat()
├── config/colorbox.php                          jam layanan, kuota bawaan, mode Virtual Try On
├── database/
│   ├── data/produk.json                         62 produk, 89 varian warna (nama dan harga asli)
│   ├── migrations/                              7 berkas
│   └── seeders/                                 9 seeder
├── public/
│   ├── css/app.css                              satu-satunya stylesheet (disalin dari project 21 September)
│   ├── js/app.js                                satu-satunya skrip (kalender, slot, stepper, tema, polling)
│   ├── fonts/                                   Jakarta Sans + Fraunces (.woff2)
│   ├── images/logo/                             colorbox-logo.png, colorbox-mark.png, colorbox-wordmark.png, favicon.png
│   ├── images/products/                         268 foto produk PNG lokal
│   └── vendor/                                  bootstrap, bootstrap-icons, qrcode, jsqr (offline, tanpa CDN)
├── resources/views/                             82 Blade (layouts, partials, components, halaman)
├── routes/web.php                               satu-satunya berkas route, 146 baris route
└── tools/                                       unduh_gambar.php (pengunduh foto), check.py (pemeriksa statis)
```

---

## 3. Routes yang Sudah Ada

### 3.1 Tamu dan umum (tanpa login)

| Method | URI | Controller | Nama route |
|---|---|---|---|
| GET | `/` | BerandaController@index | `beranda` |
| POST | `/tema` | PreferensiController@tema | `tema.ubah` |
| GET | `/produk` | ProdukController@index | `produk.index` |
| GET | `/produk-baru` | ProdukController@baru | `produk.baru` |
| GET | `/produk/{product}` | ProdukController@show | `produk.show` |
| GET | `/produk/{product}/stok-toko` | ProdukController@stokToko | `produk.stok` |
| GET | `/toko` | TokoController@index | `toko.index` |
| GET | `/toko/{store}` | TokoController@show | `toko.show` |
| GET | `/toko/{store}/qr-kasir` | TokoController@qrKasir | `toko.qr` |
| GET/POST | `/lacak-pesanan` | LacakController@index / @cari | `lacak.index`, `lacak.cari` |
| GET | `/bantuan`, `/bantuan/{halaman}` | BantuanController | `bantuan.index`, `bantuan.show` |
| GET | `/keranjang` | KeranjangController@index | `keranjang.index` |
| POST | `/keranjang` | KeranjangController@store | `keranjang.store` |
| POST | `/keranjang/pilih-semua` | KeranjangController@pilihSemua | `keranjang.pilih-semua` |
| PUT | `/keranjang/{item}` | KeranjangController@update | `keranjang.update` |
| POST | `/keranjang/{item}/pilih` | KeranjangController@pilih | `keranjang.pilih` |
| DELETE | `/keranjang/{item}` | KeranjangController@destroy | `keranjang.destroy` |
| GET | `/tap-try-collect` | TtcController@index | `ttc.index` |
| GET | `/tap-try-collect/slot` | TtcController@slot | `ttc.slot` (JSON) |
| GET | `/donasi`, `/donasi/slot` | DonasiController | `donasi.index`, `donasi.slot` |
| GET/POST | `/virtual-try-on` | VtoController@index / @store | `vto.index`, `vto.store` |
| GET | `/virtual-try-on/{id}` | VtoController@show | `vto.show` |
| POST | `/virtual-try-on/{id}/proses` | VtoController@proses | `vto.proses` |
| DELETE | `/virtual-try-on/{id}` | VtoController@destroy | `vto.destroy` |
| POST | `/payment/notification` | PembayaranController@notifikasi | `pembayaran.notifikasi` (tanpa CSRF, diperiksa signature) |
| GET/POST | `/staf/verifikasi/{jenis}/{kode}` | StafController | `staf.verifikasi`, `staf.verifikasi.simpan` |

### 3.2 Autentikasi

`login`, `login.proses` (throttle 8/menit), `register`, `register.store`
(throttle 6/menit), `password.request`, `password.email`, `password.reset`,
`password.update`, `logout`.

### 3.3 Wajib login (middleware `auth`)

Akun (`akun.index`, `profil.edit`, `profil.update`, `sandi.update`),
Alamat (7 route CRUD `alamat.*`), Poin (`poin.index`),
Notifikasi (`notifikasi.index`, `.baca-semua`, `.buka`),
Keluhan (`keluhan.index`, `keluhan.store`),
Wishlist (`wishlist.index`, `wishlist.store`, `wishlist.destroy`),
Checkout (`checkout.index`, `.store`, `.voucher`, `.voucher.hapus`),
Pembayaran (`pembayaran.show`, `.status`, `.selesai`),
Pesanan (`pesanan.index`, `.show`, `.batal`, `.terima`, `.beli-lagi`, `ulasan.store`),
Retur (`retur.index`, `.cari-struk`, `.create`, `.store`, `.show`, `.batal`),
Tap Try and Collect (`ttc.store`, `.riwayat`, `.show`, `.batal`),
Donasi (`donasi.store`, `.riwayat`, `.show`, `.check-in`, `.batal`).

### 3.4 Dashboard admin (prefix `/admin`, middleware `auth` + `HanyaAdmin`)

`admin.dashboard`, dan CRUD untuk: produk (8 route, termasuk ubah stok),
kategori (4), toko (6), slot-donasi (6), slot-ttc (6), pesanan (3), pembayaran (2),
retur (3), donasi (3), tap-try-collect (3), keluhan (3), pelanggan (2),
voucher (4), laporan (1), pengaturan (2).

**Hasil pemeriksaan route:** tidak ada route mati, tidak ada nama route dobel,
tidak ada controller atau method yang tidak ada, dan method HTTP-nya sudah wajar
(GET untuk baca, POST untuk buat, PUT untuk ubah, DELETE untuk hapus).

---

## 4. Controller Penting

| Controller | Isi ringkas | Catatan audit |
|---|---|---|
| `BerandaController` | hero, produk baru, kategori, banner kolaborasi | banner kolaborasi masih data statis di controller, belum tabel |
| `ProdukController` | daftar + filter + urut + pencarian, detail, stok toko (JSON) | jalan baik; belum ada rekomendasi ukuran |
| `KeranjangController` | keranjang tamu (session id) dan anggota, pilih barang | sudah mendukung tamu |
| `CheckoutController` | alamat/toko, jasa kirim, voucher, poin, buat pesanan + Payment + TrackingEvent | **ambil di toko belum tersambung ke Tap Try and Collect** |
| `PembayaranController` | halaman Snap, polling status, webhook Midtrans | sudah benar: status hanya berubah dari jawaban Midtrans |
| `PesananController` | daftar, rincian, batal, terima, beli lagi | status masih bahasa Indonesia |
| `ReturController` | ajukan dari pesanan online atau dari kode struk toko | belum ada `return_type`, varian pengganti, bukti video |
| `TtcController` | masuk dari detail produk, pilih toko, kalender, jam, kunci kuota | paling dekat dengan spesifikasi; jam masih sampai 20.00 |
| `DonasiController` | pilih toko, kalender, slot donasi sendiri, check-in QR | jumlah masih "potong", belum kilogram, belum tolak pakaian dalam |
| `VtoController` | unggah foto, mode `preview` (kanvas) atau `api` | masih satu produk per percobaan |
| `StafController` | verifikasi QR booking dan donasi oleh staf toko | jalan baik |
| `Admin\*` | 15 controller CRUD dashboard | lengkap, termasuk kuota slot terpisah |

---

## 5. Model

31 model, relasinya memakai nama Indonesia (`produk()`, `varian()`, `item()`,
`pengguna()`, `toko()`, `slot()`, `pembayaran()`, `pengiriman()`, `riwayat()`,
`retur()`, `stokToko()`, `booking()`).

Address, Cart, CartItem, Category, Complaint, Donation, DonationSlot,
Notification, Order, OrderItem, Payment, PointTransaction, Product, ProductImage,
ProductVariant, ReturnItem, ReturnRequest, Review, Setting, Shipment,
ShippingRate, Store, StoreStock, TapTryCollect, TapTryCollectItem,
TapTryCollectSlot, TrackingEvent, User, VirtualTryOn, Voucher, Wishlist.

Method bantu yang sudah ada: `Product::hargaJual()`, `gambarUtama()`,
`totalStok()`; `ProductVariant::label()`; `Order::labelStatus()`,
`bisaDibatalkan()`; `DonationSlot|TapTryCollectSlot::sisaKuota()`, `tersedia()`;
`Payment::petaStatus()`; `User::adalahAdmin()`, `ubahPoin()`;
`Setting::ambil()`, `simpan()`.

**Model yang belum ada menurut spesifikasi baru:** `Profile`, `WishlistItem`,
`Collaboration`, `CollaborationProduct`, `VirtualTryOnItem`, `DonationAppointment`,
`DonationPoint`.

---

## 6. Migration dan Tabel

7 migration, 33 tabel.

| Berkas | Tabel |
|---|---|
| `000001_create_users_table` | users, password_reset_tokens, sessions, cache, cache_locks, settings |
| `000002_create_catalog_tables` | categories, products, product_variants, product_images, stores, store_stocks |
| `000003_create_cart_tables` | addresses, carts, cart_items, wishlists, vouchers, shipping_rates |
| `000004_create_order_tables` | orders, order_items, payments, shipments, tracking_events, reviews |
| `000005_create_return_tables` | return_requests, return_items, complaints |
| `000006_create_slot_tables` | donation_slots, donations, tap_try_collect_slots, tap_try_collects, tap_try_collect_items |
| `000007_create_support_tables` | point_transactions, notifications, virtual_try_ons |

Kolom yang perlu diperhatikan:

- `orders`: `method` berisi `kirim` atau `ambil_toko`; `status` berisi
  `menunggu_pembayaran, dibayar, diproses, dikemas, dikirim, siap_diambil,
  diterima, selesai, dibatalkan`.
- `donations`: `estimated_items`, `received_items`, `points_awarded` (satuan potong).
- `virtual_try_ons`: satu baris satu produk (`product_id`, `color`, `size`).
- `return_requests`: `type` (`tukar`/`refund`), `method` (`online`/`toko`),
  `status` bahasa Indonesia.
- `stores`: sudah punya `latitude`, `longitude`, `open_time`, `close_time`,
  `has_ttc`, `has_donation` (modal untuk toko terdekat sudah tersedia).

Isi database uji sekarang: 62 produk, 340 varian, 267 gambar, 12 toko,
1700 baris stok toko, 1848 slot Tap Try and Collect, 784 slot donasi,
11 kategori, 4 voucher, 4 tarif kirim, 5 pesanan contoh, 3 pengguna.

---

## 7. View

82 Blade, disusun begini:

- `layouts/app.blade.php` (pelanggan) dan `layouts/admin.blade.php` (dashboard, menu burger).
- `partials/` : header, footer, pesan.
- `components/` : kartu-produk, kosong, langkah-belanja, remah, status-pesanan, status-retur.
- Halaman pelanggan: beranda, produk (index, show), keranjang, checkout,
  pembayaran (show, selesai), pesanan (index, show), retur (index, form, show),
  toko (index, show, qr), lacak, bantuan (index, show), ttc (index, riwayat, show),
  donasi (index, riwayat, show), vto (index, show), wishlist, akun, alamat, poin,
  notifikasi, keluhan, auth (masuk, daftar, lupa-sandi, atur-sandi), staf/form.
- Admin: 29 view untuk 15 modul.

Semua memakai satu stylesheet `public/css/app.css` dan satu skrip
`public/js/app.js`. Tidak ada CDN, tidak ada `@json`, tidak ada inline framework.

---

## 8. Masalah yang Ditemukan

Dibagi tiga: yang benar-benar salah, yang belum sesuai spesifikasi baru, dan
yang perlu dirapikan.

### 8.1 Salah / bikin bingung (harus diperbaiki)

1. **Ambil di toko tidak memakai sistem Tap Try and Collect.** Di checkout,
   memilih "Ambil di toko" hanya mengisi `orders.store_id`. Tidak ada tanggal,
   tidak ada jam, tidak ada kuota, tidak ada booking. Padahal spesifikasi
   meminta Store Pickup memakai tabel `tap_try_collect_slots`, `tap_try_collect`,
   dan `tap_try_collect_items` yang sama. Akibatnya pelanggan tidak tahu kapan
   barangnya siap, dan toko tidak punya antrean.
2. **Status pesanan masih bahasa Indonesia campur.** `menunggu_pembayaran`,
   `dibayar`, `dikirim`, dan seterusnya. Spesifikasi meminta status Inggris
   yang baku (Pending Payment sampai Refunded), termasuk jalur Store Pickup
   (Ready for Pickup, Picked Up).
3. **Kolom `orders.method` tidak sesuai nama yang diminta.** Harus
   `fulfillment_method` dengan nilai `DELIVERY` dan `STORE_PICKUP`.
4. **Batas waktu retur dihitung dari tanggal pesanan diterima, bukan dari
   "Picked Up".** Untuk pembelian Tap Try and Collect, perhitungannya jadi
   salah.
5. **Aturan tukar belum dibatasi.** Sekarang pelanggan bisa mengajukan tukar
   tanpa dibatasi "produk sama, warna sama, ukuran boleh beda, jumlah tidak
   boleh lebih dari pembelian".
6. **Jam Tap Try and Collect berhenti di 20.00.** `config/colorbox.php` mengisi
   jam 10.00 sampai 20.00, sedangkan toko contoh buka 10.00 sampai 22.00 dan
   spesifikasi meminta slot sampai 22.00 mengikuti jam toko.
7. **Wishlist wajib login.** Membuka `/wishlist` sebagai tamu langsung
   dilempar ke halaman masuk. Spesifikasi memperbolehkan tamu memakai wishlist
   (session), login hanya diminta saat checkout.
8. **Seeder pengaturan tidak sinkron dengan database uji.** Seeder punya 12
   kunci, database uji baru berisi 11 (`ttc_prep_minutes` belum tertulis karena
   ditambahkan belakangan). Perlu `db:seed` ulang atau migrasi data.

### 8.2 Belum ada (diminta spesifikasi baru)

9. **Tabel dan halaman Collaboration.** Banner kolaborasi di beranda masih
   data statis di controller. Belum ada `collaborations` dan
   `collaboration_products`, belum ada menu Collaboration di navigasi.
10. **Virtual Try On masih satu produk.** Belum ada Mix and Match antar
    kategori, belum ada panel "Build Your Look", belum ada `virtual_try_on_items`,
    belum ada halaman My Looks, dan env-nya masih `TRYON_API_URL`/`TRYON_API_KEY`
    (spesifikasi meminta `AI_VTO_API_URL` dan `AI_VTO_API_KEY`).
11. **Rekomendasi ukuran belum ada.** Belum ada tabel `profiles` (tinggi,
    berat, preferred fit, opsional lingkar dada/pinggang/pinggul) dan belum ada
    tampilan "Recommended Size: M" di detail produk.
12. **Donasi belum sesuai.** Sekarang satuannya potong dan poin dihitung dari
    `received_items`. Spesifikasi meminta satuan kilogram, poin diberikan hanya
    setelah verifikasi staf berdasarkan berat yang diterima, serta pakaian
    dalam tidak boleh didonasikan. Tabel `donation_appointments` dan
    `donation_points` juga belum ada.
13. **Toko terdekat lewat geolokasi belum ada.** Kolom `latitude`/`longitude`
    sudah ada di tabel `stores` tetapi belum dipakai. Belum ada tombol izin
    lokasi dan belum ada urutan berdasarkan jarak beserta cadangannya kalau
    izin ditolak.
14. **Pengalih bahasa belum ada** di navigasi (mode terang/gelap sudah ada).

### 8.3 Perlu dirapikan (tidak mendesak)

15. Kelas CSS `.track-steps` memakai 8 kolom, sedangkan alur status pesanan
    hanya 7 langkah. Sekarang ditambal lewat gaya inline di beberapa view.
16. Seeder slot belum idempoten (dijalankan dua kali bisa bentrok unique index
    pada lingkungan SQLite). Di MySQL tidak terasa karena tipe kolomnya DATE,
    tetapi sebaiknya tetap dibuat aman.
17. Jumlah produk seeder (62) sudah lewat syarat minimal 20, tetapi belum ada
    data contoh untuk kolaborasi dan belum ada contoh retur jalur Tap Try and
    Collect.

---

## 9. Fitur yang Sudah Berjalan

Diuji langsung di server, bukan dibaca dari kode saja.

| Fitur | Keadaan |
|---|---|
| Beranda, navigasi, mode terang/gelap | berjalan |
| Katalog, filter kategori/warna/ukuran/harga, urutan, pencarian | berjalan |
| Detail produk: galeri per warna, pilih warna dan ukuran, stok, stok per toko | berjalan |
| Keranjang tamu dan anggota, ubah jumlah, centang barang | berjalan |
| Masuk, daftar, lupa kata sandi, keluar | berjalan |
| Checkout jalur kirim: alamat, jasa kirim, ongkir gratis, voucher, poin | berjalan |
| Midtrans Snap sandbox: token, halaman bayar, polling, webhook signature | berjalan, tanpa tombol palsu |
| Pesanan: daftar, rincian, batal, terima, beli lagi, ulasan | berjalan |
| Lacak pesanan dengan kode | berjalan |
| Tap Try and Collect: masuk dari detail produk, pilih toko, kalender, jam, kuota terkunci, QR, riwayat, batal | berjalan |
| Donasi: pilih toko, kalender, slot sendiri, QR check-in, riwayat | berjalan |
| Verifikasi staf toko lewat QR | berjalan |
| Virtual Try On mode preview (kanvas peramban) | berjalan |
| Wishlist anggota | berjalan |
| Akun, profil, alamat CRUD, poin, notifikasi, keluhan | berjalan |
| Retur jalur pesanan online dan jalur kode struk toko | berjalan |
| Dashboard admin 15 modul termasuk kuota slot | berjalan |
| Perintah terjadwal kedaluwarsa pesanan dan booking | berjalan |

---

## 10. Fitur yang Belum Berjalan / Belum Sesuai

| Fitur | Keadaan sekarang | Yang diminta |
|---|---|---|
| Store Pickup di checkout | hanya pilih toko | pilih toko + kalender + slot Tap Try and Collect, booking dibuat setelah bayar |
| Status pesanan | bahasa Indonesia, 9 status | 15 status Inggris, dua jalur (kirim dan ambil) |
| `fulfillment_method` | kolom bernama `method` | `DELIVERY` / `STORE_PICKUP` |
| Jam slot Tap Try and Collect | 10.00–20.00 | 10.00–22.00 mengikuti jam toko, jeda siap 30 menit tetap di dalam jam buka |
| Toko terdekat | daftar biasa | geolokasi + jarak + cadangan bila ditolak |
| Donasi | satuan potong | satuan kilogram, poin setelah verifikasi, pakaian dalam ditolak |
| Virtual Try On | satu produk, mode kanvas | AI generatif, Mix and Match, Build Your Look, My Looks |
| Rekomendasi ukuran | belum ada | dari tinggi, berat, preferred fit |
| Wishlist tamu | wajib login | boleh tamu (session) |
| Collaboration | banner statis | tabel, halaman daftar, halaman detail, menu navigasi |
| Retur dan tukar | satu jalur umum | `return_type`, varian pengganti, bukti foto dan video, alur inspeksi |
| Pengalih bahasa | belum ada | ada di navigasi |

---

## 11. Rencana Arsitektur

Tetap sederhana, gaya mahasiswa, tanpa repository pattern dan tanpa service
layer berlebihan. Yang sudah ada dipertahankan.

```
Route (routes/web.php)
   ↓
Controller (validasi dengan $request->validate, aturan bisnis singkat)
   ↓
Model Eloquent (relasi + method bantu kecil)
   ↓
Blade (tanpa @json, data ke JavaScript lewat atribut data-... atau endpoint JSON)
```

Aturan yang dipegang:

- Satu berkas route, satu stylesheet, satu skrip.
- Pemeriksaan kuota selalu di server memakai `DB::transaction` +
  `lockForUpdate()` + periksa ulang `booked_count < quota`.
- Perubahan status pembayaran hanya dari jawaban Midtrans atau webhook.
- Service hanya untuk hal yang benar-benar di luar (Midtrans, layanan AI
  Virtual Try On). Selebihnya di controller.
- Aturan bisnis yang sering diubah tetap di tabel `settings`.
- Otorisasi: setiap data milik pengguna diperiksa `user_id` di controller
  sebelum ditampilkan atau diubah.

---

## 12. Rencana Relasi Database

### 12.1 Tabel baru

| Tabel | Isi pokok | Relasi |
|---|---|---|
| `profiles` | user_id, height_cm, weight_kg, preferred_fit, bust_cm, waist_cm, hip_cm | 1–1 ke `users` |
| `wishlist_items` | wishlist_id, product_id, product_variant_id | milik `wishlists` |
| `collaborations` | slug, title, partner, description, banner_path, starts_at, ends_at, is_active | 1–n ke `collaboration_products` |
| `collaboration_products` | collaboration_id, product_id, sort_order | penghubung ke `products` |
| `virtual_try_on_items` | virtual_try_on_id, product_id, product_variant_id, category_slot | milik `virtual_try_ons` |
| `donation_appointments` | donation_slot_id, store_id, user_id, date, time, status, qr_code | 1–n ke `donations` |
| `donation_points` | user_id, donation_id, weight_kg_accepted, points, verified_by, verified_at | catatan poin donasi |

### 12.2 Perubahan tabel lama

| Tabel | Perubahan |
|---|---|
| `orders` | `method` → `fulfillment_method` (`DELIVERY`/`STORE_PICKUP`); `status` diganti daftar Inggris; tambah `tap_try_collect_id`, `picked_up_at`, `courier`, `eta_min_days`, `eta_max_days` |
| `wishlists` | jadi wadah (user_id nullable + session_id) agar tamu bisa punya wishlist, isinya pindah ke `wishlist_items` |
| `donations` | `estimated_items` → `estimated_weight_kg`, `received_items` → `accepted_weight_kg`, tambah `donation_appointment_id` |
| `return_requests` | tambah `return_type` (`ONLINE_ORDER`/`TAP_TRY_COLLECT`), `picked_up_at`, `video_path`; status ganti daftar Inggris |
| `return_items` | tambah `original_variant_id`, `requested_variant_id`, `reason_code`, `reason_detail`, `photo_path` |
| `virtual_try_ons` | `product_id` jadi opsional, tambah `look_name`, `saved` |
| `tap_try_collects` | tambah `source` (`TTC` atau `STORE_PICKUP`) dan `order_id` agar Store Pickup ikut di sini |
| `stores` | isi `latitude`/`longitude` semua toko lewat seeder |

### 12.3 Peta relasi ringkas

```
User 1─1 Profile
User 1─n Address, Order, Wishlist, Donation, TapTryCollect, VirtualTryOn,
         ReturnRequest, Notification, PointTransaction, Complaint

Category 1─n Product 1─n ProductVariant 1─n StoreStock n─1 Store
Product  1─n ProductImage
Product  n─n Collaboration (lewat CollaborationProduct)

Cart 1─n CartItem n─1 ProductVariant
Wishlist 1─n WishlistItem n─1 Product

Order 1─n OrderItem
Order 1─1 Payment, 1─1 Shipment, 1─n TrackingEvent
Order n─1 Address (DELIVERY) atau n─1 Store (STORE_PICKUP)
Order 1─1 TapTryCollect  ← inilah sambungan Store Pickup

Store 1─n TapTryCollectSlot 1─n TapTryCollect 1─n TapTryCollectItem
Store 1─n DonationSlot 1─n DonationAppointment 1─n Donation 1─n DonationPoint

Order|TapTryCollect 1─n ReturnRequest 1─n ReturnItem
VirtualTryOn 1─n VirtualTryOnItem n─1 ProductVariant
```

---

## 13. Rencana Implementasi

Dikerjakan bertahap. Setiap tahap diuji dulu (halaman dibuka, `php -l`,
pemeriksa statis) sebelum lanjut, dan zip dikirim bertahap supaya tidak ada
pekerjaan yang hilang.

| Tahap | Isi | Perkiraan berkas |
|---|---|---|
| 1 | Migration dan model baru: `profiles`, `wishlist_items`, `collaborations`, `collaboration_products`, `virtual_try_on_items`, `donation_appointments`, `donation_points` | 3 migration, 7 model |
| 2 | Perubahan tabel lama: `fulfillment_method`, status Inggris, kolom retur, kolom donasi kilogram, `tap_try_collects.order_id` | 2 migration, 8 model |
| 3 | Checkout dua jalur: DELIVERY (alamat, kurir JNE / Ninja Xpress / SPX, ongkir, perkiraan tiba) dan STORE_PICKUP (toko terdekat, kalender, slot Tap Try and Collect) | CheckoutController, checkout/index |
| 4 | Booking Tap Try and Collect dibuat otomatis setelah pembayaran berhasil, barang diambil dari keranjang | PembayaranController, TtcController |
| 5 | Status pesanan Inggris lengkap dua jalur + halaman lacak dan riwayat | Order, PesananController, admin pesanan, view status |
| 6 | Slot Tap Try and Collect 10.00–22.00 mengikuti jam toko, jeda siap 30 menit dihitung di dalam jam buka, kuota dikunci ulang di server | config, SlotSeeder, TtcController |
| 7 | Toko terdekat lewat geolokasi + cadangan bila izin ditolak | app.js, TokoController, checkout, ttc |
| 8 | Donasi: kilogram, kalender dan slot sendiri, QR, verifikasi staf, poin sesuai berat diterima, pakaian dalam ditolak | DonasiController, StafController, admin donasi |
| 9 | Wishlist tamu (session) dan penggabungan ke akun saat login | WishlistController, AuthController |
| 10 | Profil ukuran badan + rekomendasi ukuran di detail produk | profiles, AkunController, ProdukController |
| 11 | Collaboration: tabel, seeder, halaman daftar dan detail, menu navigasi | Collaboration, view baru, header |
| 12 | Virtual Try On: Mix and Match, Build Your Look, keadaan proses, My Looks, env `AI_VTO_API_KEY` dan `AI_VTO_API_URL` | VtoController, vto/*, config |
| 13 | Retur dan tukar: `return_type`, batas 7 hari sejak Picked Up, tukar produk sama warna sama, bukti foto dan video, alur inspeksi | ReturController, retur/*, admin retur |
| 14 | Pengalih bahasa di navigasi | header, PreferensiController |
| 15 | Seeder diperkaya: kolaborasi, contoh retur jalur toko, koordinat semua toko, contoh donasi kilogram | seeder |
| 16 | Pemeriksaan keamanan menyeluruh: CSRF, validasi, kepemilikan data, unggahan berkas, XSS | seluruh controller |
| 17 | Uji tampilan responsif dan rapikan sisa CSS (`.track-steps`) | css, view |
| 18 | Uji jalur penuh: belanja kirim, belanja ambil di toko, Tap Try and Collect, donasi, retur | pengujian |
| 19 | README, cara menjalankan di XAMPP dan VS Code, catatan jujur soal Virtual Try On | dokumentasi |

### Catatan jujur

Virtual Try On akan dibangun **siap sambung** ke layanan AI: kalau
`AI_VTO_API_URL` dan `AI_VTO_API_KEY` diisi, permintaan dikirim ke sana dan
hasilnya ditampilkan. Kalau belum diisi, halaman akan menyatakan apa adanya
bahwa layanan AI belum tersambung, dan tidak akan menampilkan gambar manusia
buatan seolah-olah itu hasil AI. Tidak ada tombol palsu, tidak ada stok palsu,
tidak ada kuota palsu, tidak ada pelacakan palsu.

---

## Langkah Berikutnya

Menunggu persetujuan Anda atas rencana ini. Setelah disetujui, pengerjaan
dimulai dari Tahap 1 dan zip dikirim bertahap (bukan sekali di akhir), supaya
kalau ada yang perlu diubah di tengah jalan, tidak perlu mengulang dari nol.
