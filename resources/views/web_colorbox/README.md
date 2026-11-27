# web_colorbox

Website penjualan Colorbox untuk pelanggan, beserta dashboard admin dan meja kasir toko.
Dibuat dengan Laravel (PHP), Blade, Bootstrap 5, JavaScript biasa, dan MySQL.

Nama foldernya `web_colorbox` tanpa akhiran peran, sebab satu project ini memuat ketiga peran
sekaligus. Pemisahan per peran ada di dalamnya, lihat bagian **Satu project, tiga peran** pada
nomor 9. Nama databasenya tetap `colorbox_pelanggan`.

Project ini adalah project baru yang terpisah dari `web_colorbox_pelanggan`.
Project lama dipakai sebagai referensi konsep, alur, dan **tampilan**: berkas gaya
`public/css/app.css`, huruf, serta susunan halamannya mengikuti project lama, sedangkan kodenya
ditulis ulang dengan gaya Laravel sederhana (Route → Controller → Model → Blade, tanpa `@json`).
Project lama sendiri tidak diubah sama sekali.

Logo memakai berkas PNG yang diberikan: tanda kotak "C" (`public/images/logo/colorbox-mark.png`)
di sebelah kiri dan tulisan COLORBOX (`public/images/logo/colorbox-wordmark.png`) di sebelahnya.

---

## 1. Kebutuhan

- PHP 8.2 atau lebih baru, dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `curl`
- Composer 2
- MySQL / MariaDB (paling mudah lewat XAMPP + phpMyAdmin)
- VS Code (gratis), tidak memerlukan alat berbayar apa pun

## 2. Cara menjalankan di XAMPP

Ringkasannya ada di bawah ini. Kalau baru pertama kali memasang, ikuti
`TUTORIAL_MENJALANKAN.md` yang menjelaskan tiap langkah satu per satu beserta hasil yang
seharusnya muncul di layar dan daftar pesan error yang sering ditemui.

```bash
# 1. nyalakan Apache dan MySQL di XAMPP, lalu buat database di phpMyAdmin:
#    nama database : colorbox_pelanggan
#    collation     : utf8mb4_unicode_ci

# 2. masuk ke folder project, lalu pasang paket Laravel
composer install

# 3. salin pengaturan
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

Buka `.env` lalu isi bagian ini:

```env
DB_DATABASE=colorbox_pelanggan
DB_USERNAME=root
DB_PASSWORD=

COLORBOX_STAFF_PIN=                                             # boleh kosong, bawaannya 246810

MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxx
MIDTRANS_IS_PRODUCTION=false
```

Lalu:

```bash
# 4. unduh foto produk asli dari situs resmi Colorbox (sekali saja)
php tools/unduh_gambar.php

# 5. buat tabel dan isi data contoh
php artisan migrate:fresh --seed

# 6. jalankan
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Opsional, di terminal kedua: `php artisan schedule:work` supaya pesanan yang melewati batas
waktu pembayaran dibatalkan otomatis.

### Akun contoh

Seluruh akun contoh memakai aturan kata sandi yang sama, yaitu **tanggal lahirnya sendiri**
dengan penulisan `yyyy-mm-dd`. Tidak ada kata sandi yang perlu diisi di `.env`, dan tidak ada
kata sandi yang ditulis sebagai teks di dalam kode:

| Peran | Masuk lewat | Nama pengguna | Kata sandi |
|---|---|---|---|
| Pelanggan | `/masuk` | real.notmreli@gmail.com | 2003-05-14 |
| Admin | `/admin/masuk` | Admin Colorbox | 1995-08-17 |
| Kasir toko | `/admin/masuk` | Kasir Colorbox Central Park | 1991-02-02 |

Pelanggan tetap bisa mendaftar sendiri lewat halaman daftar. Akun admin dan kasir
**dibuatkan perusahaan**, tidak bisa mendaftar sendiri, dan nama penggunanya adalah nama
lengkap pada akun.

Tanggal lahir tiap kasir berbeda-beda. Seluruhnya dicetak di terminal ketika
`php artisan migrate --seed` dijalankan, jadi tidak perlu ditebak. Email juga tetap diterima
pada kolom nama pengguna, sehingga akun lama tidak ada yang terkunci di luar.

Dashboard admin ada di `http://127.0.0.1:8000/admin`, antrean kasir di
`http://127.0.0.1:8000/kasir`. Tamu yang membuka keduanya dilempar ke halaman masuk petugas.
Kata sandi tidak pernah ditulis di dalam kode.

---

## 3. Foto produk dan logo

- **Logo** memakai berkas logo Colorbox yang diberikan, disimpan di
  `public/images/logo/colorbox-logo.png` dan `colorbox-wordmark.png`. Tidak ada logo buatan baru.
- **Foto produk** diunduh dari situs resmi `https://colorbox.co.id` memakai
  `php tools/unduh_gambar.php`. Skrip itu membaca `database/data/produk.json`, mengambil daftar
  foto tiap produk lewat alamat resmi Colorbox, lalu menyimpannya sebagai PNG di
  `public/images/products` dengan pola nama `{slug}-{warna}-{nomor}.png`.
  Yang dilakukan hanyalah mengunduh berkas yang sudah ada di situs Colorbox dan mengubah
  formatnya menjadi PNG. **Tidak ada gambar yang dibuat dengan AI** dan tidak ada foto dari
  situs gambar acak.
- Sebelum skrip dijalankan, kartu produk memakai gambar penampung netral
  (`public/images/products/placeholder.png`) yang bertuliskan bahwa foto belum diunduh.
- Pilihan skrip:
  `php tools/unduh_gambar.php --ulang` mengunduh ulang, `--maks=2` membatasi jumlah foto per warna.

Nama produk, harga, warna, dan ukuran pada `database/data/produk.json` diambil dari katalog
resmi Colorbox. Deskripsi, bahan, perawatan, berat, dan stok adalah contoh untuk keperluan
penelitian.

---

## 4. Alur yang bisa dicoba

| Alur | Langkah singkat |
|---|---|
| Belanja | Produk → pilih warna dan ukuran → Tambah ke Keranjang → centang item → Checkout |
| Checkout | Alamat atau ambil di toko → jasa kirim → voucher dan poin → buat pesanan |
| Pembayaran | Halaman pembayaran membuka **Midtrans Snap (sandbox)**. Status pembayaran hanya berubah setelah Midtrans mengirim notifikasi atau setelah status ditanyakan ulang ke Midtrans |
| Lacak | Lacak Pesanan → masukkan kode `CBX-YYYYMMDD-0001` |
| Retur | Akun → Retur → pilih pesanan (atau masukkan kode pembelian di toko) → alasan, foto, online atau ke toko |
| Tap Try and Collect | **Mulai dari katalog**: buka produk → tombol Tap Try and Collect → pilih toko → pilih tanggal di kalender → pilih jam (kuota tampil) → konfirmasi |
| Donasi | Donasi → toko → tanggal → jam (kuota sendiri) → jumlah item → datang ke toko → QR → staf verifikasi → poin |
| Virtual Try On | Ambil foto → pilih beberapa produk (Mix and Match) → layanan AI menggabungkan → simpan ke My Looks |
| Dashboard admin | `/admin` → menu burger di kiri atas |

### Verifikasi staf toko

Pelanggan menunjukkan QR pada halaman donasi / Tap Try and Collect. Staf memindainya, lalu
membuka `/staf/verifikasi/{donasi|ttc|retur}/{kode}` dan memasukkan PIN toko dari
`COLORBOX_STAFF_PIN`. Setelah donasi disetujui, poin otomatis masuk ke akun pelanggan sebanyak
jumlah pakaian yang diterima dikali `donation_points_per_item`.

---

## 5. Pembayaran Midtrans (sandbox)

- Kunci Midtrans hanya dibaca dari `.env`. **Server Key tidak pernah dikirim ke peramban.**
- Halaman pembayaran memuat Snap dengan Client Key, lalu membuka jendela pembayaran Midtrans.
- Notifikasi dari server Midtrans masuk ke `POST /payment/notification`. Tanda tangannya
  diperiksa dengan rumus `sha512(order_id + status_code + gross_amount + server_key)`.
  Kalau tidak cocok, permintaan ditolak.
- Tidak ada tombol "Saya sudah membayar". Status pembayaran hanya berubah karena jawaban Midtrans.
- Pemetaan status: `settlement`/`capture` → lunas, `pending` → menunggu, `expire` → kedaluwarsa,
  `cancel` → dibatalkan, `deny`/`failure` → gagal.
- Supaya notifikasi bisa masuk ke komputer sendiri, alamat `http://127.0.0.1:8000` perlu dibuka
  ke internet (misalnya memakai ngrok), lalu alamat notifikasinya diisikan di dashboard sandbox
  Midtrans. Selama itu belum dilakukan, status dapat diperbarui dengan membuka halaman
  pembayaran (halaman itu menanyakan status ke Midtrans setiap beberapa detik) atau lewat
  dashboard admin → Pembayaran → **Periksa Status ke Midtrans**.

---

## 6. Kuota slot

Donasi dan Tap Try and Collect memakai sistem jadwal yang **benar-benar terpisah**:

| | Donasi | Tap Try and Collect |
|---|---|---|
| Tabel slot | `donation_slots` | `tap_try_collect_slots` |
| Tabel pemesanan | `donations` | `tap_try_collects` |
| Jam bawaan | 09.00–17.00 | 10.00–21.00 (slot satu jam) |
| Kuota bawaan | 5 per slot | 4 per slot |
| Controller pelanggan | `DonasiController` | `TtcController` |
| Controller admin | `SlotDonasiController` | `SlotTtcController` |

Tap Try and Collect juga punya **waktu penyiapan produk** `ttc_prep_minutes` (bawaan 30 menit):
jam yang jaraknya kurang dari 30 menit dari sekarang ditandai penuh dan tidak bisa dipilih,
karena produknya perlu disiapkan lebih dulu di toko.

Kuota diperiksa dua kali: ditampilkan di layar ("3 slot tersisa" / "Penuh"), lalu diperiksa
ulang di server memakai `DB::transaction` dan `lockForUpdate()` sebelum booking disimpan.
Kalau slot penuh tepat sebelum tombol ditekan, muncul pesan
"Maaf, slot baru saja penuh. Silakan pilih waktu lainnya."

Admin dapat membuat slot secara massal (pilih toko, rentang tanggal, jam, kuota), mengubah
kuota, dan menonaktifkan slot. Kuota tidak boleh diubah menjadi lebih kecil daripada jumlah
booking yang sudah masuk.

---

## 7. Virtual Try On

Fitur ini memakai layanan AI di luar aplikasi. Alurnya:

1. Pelanggan menyalakan kamera atau mengunggah foto.
2. Memilih sampai empat produk dari kategori berbeda (Mix and Match). Produk yang dipilih
   muncul di panel **Build Your Look**.
3. Foto dan foto produk dikirim ke layanan AI, lalu hasilnya ditampilkan.
4. Hasilnya bisa disimpan ke **My Looks**, produknya bisa langsung dimasukkan ke keranjang.

### Menyalakan layanannya

Isi berkas `.env`:

```
AI_VTO_DRIVER=replicate
AI_VTO_API_KEY=r8_xxxxxxxxxxxxxxxxxxxx
AI_VTO_MODEL=cuuupid/idm-vton
```

Cara mendapatkan kuncinya: daftar di replicate.com, isi saldo (5 dolar cukup untuk sekitar
215 gambar), lalu salin API token pada halaman akun. Selain Replicate, driver `fal` juga
didukung; tinggal ganti `AI_VTO_DRIVER=fal` dan isi kunci dari fal.ai.

### Cara kerjanya di dalam

Model try-on hanya bisa memasang **satu** pakaian sekali jalan. Karena itu Mix and Match
dikerjakan bertahap: produk pertama dipasang ke foto pelanggan, hasilnya menjadi foto masukan
untuk produk kedua, dan seterusnya. Kemajuannya tercatat pada kolom `virtual_try_ons.step`, dan
halaman hasil menanyakan keadaan terakhir ke server setiap empat detik.

Status pada tabel `virtual_try_ons`: `DRAFT`, `PROCESSING`, `SUCCESS`, `FAILED`. Gambar hasil
disimpan sebagai PNG di `public/images/tryon`. Produk yang dipakai dicatat di
`virtual_try_on_items` beserta kategorinya.

### Jujur soal kemampuannya

Ini penting supaya tidak salah dijelaskan saat sidang:

- Selama `AI_VTO_API_KEY` belum diisi, tombol **Buat Gambar** dimatikan dan halamannya menulis
  apa adanya bahwa layanan AI belum tersambung. **Tidak ada gambar contoh, gambar tiruan, atau
  foto manusia buatan yang ditampilkan seolah-olah hasil AI.**
- Kalau layanan menolak atau gagal, sebabnya ditulis apa adanya di halaman hasil, misalnya
  "Kunci AI_VTO_API_KEY ditolak layanan AI" atau "Saldo layanan AI habis".
- Kualitas gambar akhirnya ditentukan oleh model AI yang dipakai, bukan oleh aplikasi ini.
  Aplikasi ini bertanggung jawab atas alur, penanganan gagal, dan penyimpanannya.

Alur lengkapnya bisa diuji tanpa memanggil layanan sungguhan dengan
`php tools/uji_vto.php`, yang menirukan jawaban layanan memakai `Http::fake()`.

## 8. Memeriksa kelengkapan data produk

Data produk dipakai hampir di seluruh halaman: katalog, detail produk, keranjang, checkout,
stok toko, Tap Try and Collect, sampai Virtual Try On. Satu kolom kosong bisa membuat halaman
tampak rusak di depan pelanggan, jadi kelengkapannya diperiksa dengan perintah, bukan dikira-kira.

```
php artisan colorbox:periksa-produk              ringkasan dan sepuluh temuan pertama
php artisan colorbox:periksa-produk --rinci      seluruh temuan ditulis
php artisan colorbox:periksa-produk --toko       ikut menghitung cakupan stok tiap toko
```

Yang diperiksa:

| Bagian | Yang dipastikan ada |
|---|---|
| Produk | kategori, harga, deskripsi, bahan, cara perawatan, berat, SKU, slug |
| Harga diskon | harus lebih murah daripada harga normal |
| SKU dan slug | unik, tidak ada yang dipakai dua produk |
| Varian | minimal satu varian, punya warna, ukuran, kode warna hex, dan stok |
| Gambar | minimal satu gambar, dan berkasnya benar-benar ada di `public/images/products` |
| Warna | setiap warna varian punya fotonya sendiri, karena halaman detail mengganti gambar mengikuti warna |
| Stok toko | tidak ada toko yang kosong sama sekali, dan tahanan stok tidak melebihi stok fisik |

Perintah ini ikut dijalankan otomatis di akhir `php artisan db:seed`, sehingga data yang kurang
langsung ketahuan saat database diisi. Kode keluarnya 1 bila ada temuan, jadi bisa dipakai pada
skrip pemeriksaan otomatis.

## 9. Struktur project

```
app/
  Console/Commands/PerbaruiStatus.php   pekerjaan berkala (pesanan kedaluwarsa, jadwal lewat)
  Http/Controllers/                     23 controller pelanggan
  Http/Controllers/Admin/               16 controller dashboard admin
  Http/Controllers/Kasir/               4 controller dashboard kasir toko
  Http/Middleware/HanyaAdmin.php        penjaga halaman admin
  Http/Middleware/HanyaKasir.php        penjaga halaman kasir
  Models/                               32 model Eloquent
  Services/MidtransService.php          penghubung Midtrans Snap
  Support/helpers.php                   rupiah(), setting(), gambar_produk(), tanggal_id(), jam_singkat()
database/
  data/produk.json                      data katalog dari situs resmi Colorbox
  migrations/                           7 berkas migrasi
  seeders/                              pengaturan, kategori, toko, produk, slot, pengguna, contoh transaksi
public/
  css/app.css                           gaya halaman pelanggan
  css/admin.css                         gaya dashboard admin dan kasir
  js/app.js                             skrip halaman pelanggan
  js/admin.js                           skrip dashboard: menu, saring cepat, grafik
  images/logo, images/products, ...     aset gambar
  vendor/                               Bootstrap, Bootstrap Icons, qrcode.js, jsQR (lokal, tanpa CDN)
resources/views/                        halaman pelanggan
resources/views/admin/                  halaman admin (kerangkanya admin/layout.blade.php)
resources/views/kasir/                  halaman kasir (kerangkanya kasir/layout.blade.php)
routes/web.php                          seluruh route: pelanggan, kasir, dan admin
tests/Feature/                          pengujian alur utama, keterhubungan halaman, rantai peran
tools/unduh_gambar.php                  pengunduh foto produk resmi Colorbox
tools/check.py                          pemeriksaan statis (route, view, sintaks, kata terlarang)
```

### Satu project, tiga peran

Pelanggan, admin, dan kasir memakai **satu project Laravel yang sama**, bukan tiga project
terpisah. Alasannya satu: ketiganya membaca dan menulis database yang sama. Kalau dipisah
menjadi tiga project, model, migration, dan pengaturan databasenya harus digandakan, dan
setiap perubahan tabel harus dikerjakan tiga kali.

Yang dipisah adalah isinya, bukan projectnya:

| Bagian | Pelanggan | Kasir | Admin |
|---|---|---|---|
| Route | `routes/web.php` | `routes/web.php` | `routes/web.php` |
| Controller | `app/Http/Controllers/` | `app/Http/Controllers/Kasir/` | `app/Http/Controllers/Admin/` |
| Middleware | — | `app/Http/Middleware/HanyaKasir.php` | `app/Http/Middleware/HanyaAdmin.php` |
| View | `resources/views/` | `resources/views/kasir/` | `resources/views/admin/` |
| Kerangka halaman | `layouts/app.blade.php` | `kasir/layout.blade.php` | `admin/layout.blade.php` |
| Gaya dan skrip | `css/app.css`, `js/app.js` | `css/admin.css`, `js/admin.js` | `css/admin.css`, `js/admin.js` |
| Alamat | `/` | `/kasir` | `/admin` |
| Halaman masuk | `/masuk` | `/admin/masuk` | `/admin/masuk` |
| Penjaga | `auth` | `auth` + `kasir` | `auth` + `admin` |

Pelanggan masuk lewat `/masuk`, petugas lewat `/admin/masuk`. Keduanya tetap bisa dipakai
bergantian karena pencarian akunnya sama, hanya pintunya yang berbeda. Setelah masuk, pelanggan
diarahkan ke beranda, kasir ke `/kasir`, admin ke `/admin`. Tamu yang membuka `/admin` atau
`/kasir` dilempar ke halaman masuk petugas, dan tombol **Keluar** ada di pojok kanan atas pada
ketiga kerangka halaman.

### Tampilan dashboard

Panel admin dan kasir memakai kerangka yang sama, mirip panel penjual pada marketplace:

- menu tetap di sebelah kiri, dikelompokkan menjadi Utama, Katalog, Penjualan, Layanan Toko,
  Pelanggan, dan Perusahaan, lengkap dengan angka kecil penanda pekerjaan yang menunggu
- tombol memperkecil menu menjadi deretan ikon saja, pilihannya diingat di peramban
- bilah atas berisi judul halaman, pengubah mode gelap, pintasan ke situs pelanggan, nama
  petugas, dan tombol keluar
- tabel dengan kepala yang menempel saat digulir, lencana status berwarna, dan kolom saring
  cepat yang menyaring baris seketika tanpa memuat ulang halaman
- dashboard memuat grafik penjualan empat belas hari terakhir yang digambar sendiri sebagai
  SVG, tanpa pustaka grafik dan tanpa CDN

### Delapan aturan perancangan antarmuka

Tampilan dashboard disusun mengikuti delapan aturan emas perancangan antarmuka milik
Shneiderman. Tabel ini menyebutkan di mana tiap aturan diterapkan, supaya mudah diperiksa.

| Aturan | Wujudnya di panel ini |
|---|---|
| 1. Konsisten | Satu kerangka halaman, satu berkas gaya (`css/admin.css`), satu aturan tombol: utama memakai `btn-primary`, tindakan biasa `btn-outline-dark`, tindakan merusak `btn-outline-danger`. Admin dan kasir memakai kerangka yang sama. |
| 2. Dapat dipakai siapa saja | Petugas baru cukup memakai tombol di layar; yang sudah terbiasa punya pintasan `/`, `Esc`, `Alt`+`M`, `Alt`+`D`, dan `?`. Daftarnya ada di tombol papan tik pada bilah atas. Tidak ada satu pun tindakan yang hanya bisa lewat papan tik. |
| 3. Umpan balik yang memberi tahu | Setiap penyimpanan memunculkan pesan dan toast. Kolom saring cepat menuliskan "Menampilkan 3 dari 20 baris". Tombol kirim berubah menjadi "Menyimpan..." selama prosesnya jalan. |
| 4. Dialog yang menutup | Tiap tindakan berakhir dengan kalimat lengkap tentang apa yang terjadi, misalnya "Donasi DON-... tercatat: 4 potong layak, 1 potong tidak layak, 4 poin diberikan", lalu kembali ke daftar. |
| 5. Mencegah galat | Tombol kirim terkunci setelah ditekan supaya data tidak masuk dua kali. Tanggal akhir tidak bisa mendahului tanggal mulai. Tindakan merusak bertanya lebih dulu. Status pesanan hanya menawarkan langkah yang memang boleh dari status sekarang. |
| 6. Pembatalan yang mudah | Tiap formulir punya tombol Batal. Tiap kepingan penyaring bisa dilepas satu per satu. Pertanyaan konfirmasi menyebutkan apa yang tidak bisa dibatalkan, misalnya tahanan stok toko yang sudah dilepas. |
| 7. Pemakai yang memegang kendali | Halaman tidak pernah berpindah sendiri. Saring cepat bekerja di halaman yang sedang dibuka, tidak memuat ulang apa pun. Lebar menu kiri mengikuti pilihan petugas dan diingat peramban. |
| 8. Beban ingatan yang ringan | Penyaring yang sedang menempel ditampilkan sebagai kepingan, bukan disimpan diam-diam. Jejak halaman menyebutkan kelompok menunya. Kepala tabel menempel saat digulir. Jam WIB berjalan di bilah atas, jadi tidak perlu melihat jam lain. |

### Tanggal dan jam

Seluruh aturan waktu dihitung server pada zona `Asia/Jakarta`: batas pesan Tap Try and Collect
pukul 15.00, jeda penyiapan 30 menit, dan penutupan booking pada H+1.

Penunjuk waktu `<x-jam-wib />` mengambil waktu awalnya dari server, lalu dijalankan tiap detik
oleh `public/js/jam.js`. Selisih dengan jam komputer pembaca dihitung sekali di awal, jadi angka
yang tampil tetap mengikuti waktu server meskipun jam komputernya meleset. Penunjuk ini dipasang
pada bilah atas panel admin, bilah atas panel kasir, dan tepat di atas daftar jam pada halaman
Tap Try and Collect, donasi, serta checkout.

Pemilihan tanggal dan jam memakai pemilih, bukan isian bebas:

| Tempat | Pemilihnya |
|---|---|
| Jadwal Tap Try and Collect, donasi, dan checkout | kalender `data-calendar` dengan batas tanggal paling awal dan paling akhir, lalu daftar jam yang diambil ulang dari server tiap 30 detik |
| Antrean kasir, daftar booking admin, slot admin | `<input type="date">` bawaan peramban |
| Laporan penjualan | dua `<input type="date">` yang saling membatasi, tanggal akhir tidak bisa mendahului tanggal mulai |
| Jam buka dan tutup toko | `<input type="time">` bawaan peramban |

### Cara kode ditulis

```
Route  →  Controller  →  Model  →  Database
                      ↘  Blade
```

- Validasi ditulis langsung di controller memakai `$request->validate([...])`.
- Method HTTP standar dipakai: GET, POST, PUT/PATCH, DELETE (dengan `@method('PUT')` pada form).
- **Blade directive `@json` tidak dipakai sama sekali.** Data untuk JavaScript dikirim lewat
  atribut `data-...` pada elemen HTML, atau lewat endpoint yang mengembalikan
  `response()->json(...)` lalu diambil dengan `fetch()`. Contohnya daftar slot
  (`/tap-try-collect/slot`, `/donasi/slot`) dan stok toko (`/produk/{slug}/stok-toko`).
- Tidak ada paket berbayar, tidak ada layanan berbayar, dan tidak ada fitur yang memerlukan
  IDE premium.

---

## 10. Tabel utama

`users`, `addresses`, `categories`, `products`, `product_variants`, `product_images`,
`stores`, `store_stocks`, `carts`, `cart_items`, `wishlists`, `vouchers`, `shipping_rates`,
`orders`, `order_items`, `payments`, `shipments`, `tracking_events`, `reviews`,
`return_requests`, `return_items`, `complaints`, `donation_slots`, `donations`,
`tap_try_collect_slots`, `tap_try_collects`, `tap_try_collect_items`, `point_transactions`,
`notifications`, `virtual_try_ons`, `settings`.

Nama tabel memakai bentuk jamak bahasa Inggris mengikuti kebiasaan Laravel. Padanannya dengan
entitas pada skripsi: `users` = Customer/Admin, `products` = Produk, `product_variants` = Stok,
`orders` = Order, `order_items` = Order_Detail, `payments` = Payment, `shipments` = Shipment,
`return_requests` = Retur, `return_items` = Retur_Detail, `donations` = Donasi,
`tap_try_collects` = Tap_Try_Collect, `complaints` = Keluhan, `reviews` = Review,
`notifications` = Notifikasi, `virtual_try_ons` = Virtual_Try_On.

## 11. Aturan bisnis (tabel `settings`)

`free_shipping_threshold` (350000), `donation_points_per_item` (1), `point_value` (100),
`points_max_percent` (30), `return_window_days` (7), `payment_expiry_hours` (24),
`ttc_slot_quota` (4), `donation_slot_quota` (5), `booking_window_days` (14),
`min_donation_items` (1), `max_donation_items` (50).

Semuanya dapat diubah lewat dashboard admin → Pengaturan, atau langsung di phpMyAdmin,
tanpa mengubah kode.

## 12. Diagram UML

Seluruh diagram ada di folder `diagram/`. Sequence dan class diagram tersedia dalam bentuk
`.svg` untuk dicetak, `.png` untuk ditempel ke laporan, dan `.drawio` untuk diedit sendiri di
diagrams.net. Khusus statechart diagram ditambah bentuk `.vsdx` supaya bisa dibuka di Microsoft
Visio.

| Berkas | Isi |
|---|---|
| `class_diagram_colorbox` | 32 kelas model beserta atribut, method, dan relasinya |
| `statechart_pesanan` | seluruh nilai `orders.status`, dari tagihan terbit sampai retur selesai |
| `statechart_kunjungan_toko` | seluruh nilai `tap_try_collects.status` dan `donations.status` |
| `sequence_01` sampai `sequence_10` | sepuluh sequence diagram |

Tiga aturan yang dipegang saat menggambar:

1. **Statechart dibuat dua diagram besar per objek inti, bukan empat diagram kecil.**
   Naskah sebelumnya memakai empat statechart terpisah: status pesanan, status pembayaran,
   status retur, dan status donasi. Keempatnya dilebur menjadi dua. Diagram pertama memuat
   objek Pesanan, dari tagihan terbit sampai retur selesai, termasuk dua metode pemenuhan dan
   tindak lanjut pesanan yang tidak diambil sampai H+1. Diagram kedua memuat objek Kunjungan
   ke Toko, yaitu booking Tap Try and Collect dan janji donasi pakaian.
   Notasinya mengikuti ketentuan UML state machine diagram: initial state berupa lingkaran
   hitam penuh, keadaan berupa persegi panjang bersudut tumpul yang **hanya memuat nama
   keadaannya** (kompartemen kedua di dalam kotak khusus untuk aktivitas internal `entry`,
   `do`, dan `exit`, jadi tidak boleh dipakai untuk keterangan lain), peralihan berupa garis
   berpanah yang diberi nama pemicunya dengan syarat di dalam tanda kurung siku, dan final
   state berupa lingkaran hitam penuh yang dilingkari satu lingkaran lagi. Padanan tiap nama
   keadaan dengan nilai kolom `status` di basis data ditulis pada paragraf penjelasannya,
   bukan di dalam kotak. `tools/statechart_acc.py` memeriksa sendiri hasilnya sebelum
   berkasnya ditulis, yaitu memastikan seluruh ruas garis lurus, tidak ada garis yang menembus
   kotak, tidak ada kotak yang bertindihan, tidak ada keterangan yang menutupi kotak atau
   keterangan lain, dan tidak ada isi yang keluar dari bingkai.
2. **Satu sequence diagram hanya memakai satu aktor.** Serah terima antar peran tidak digambar
   sebagai percakapan langsung, melainkan lewat catatan dan notifikasi yang disimpan sistem.
   Contohnya kasir menekan Tandai Siap, sistem menyimpan notifikasi, lalu pelanggan membacanya
   sendiri di halaman Booking Saya.
3. **Notasi sequence diagram memakai Boundary, Control, Entity.** Halaman digambar sebagai
   boundary, controller sebagai control, dan tabel basis data sebagai entity. Setiap pesan
   diberi nomor urut; di dalam bingkai `alt`, nomornya memakai akhiran huruf, misalnya `10a`
   dan `11a` untuk cabang `[Failure]`, lalu `10b` sampai `14b` untuk cabang `[Success]`.
   Bentuk ini mengikuti contoh sequence diagram yang sudah disetujui pembimbing.

Sepuluh sequence diagram itu: pelanggan memesan Tap Try and Collect, membayar lewat Midtrans,
mengajukan retur, menjadwalkan donasi, memakai Virtual Try On; kasir menyiapkan dan menyerahkan
booking, memverifikasi donasi; admin mengelola produk dan stok, memproses retur, dan
menindaklanjuti pesanan yang tidak diambil.

Diagramnya dibuat ulang dengan:

```bash
php tools/data_kelas.php && python3 tools/buat_class_diagram.py
python3 tools/buat_diagram_uml.py      # sepuluh sequence diagram
python3 tools/buat_statechart.py       # dua statechart diagram
python3 tools/buat_erd.py              # entity relationship diagram
python3 tools/kamus_data.py            # kamus data seluruh tabel
```

## 13. Pemeriksaan

```bash
python3 tools/check.py
```

Memeriksa sintaks seluruh berkas PHP, keberadaan controller dan methodnya, nama route yang
dipakai view, keberadaan view yang dipanggil, pemakaian `@json`, dan kata yang tidak boleh
dipakai pada teks antarmuka.

```bash
php artisan test
```

Menjalankan tiga berkas pengujian, seluruhnya 22 pengujian.

`tests/Feature/AlurUtamaTest.php` menguji aturan yang paling mudah rusak diam-diam ketika
kodenya diubah: pembelian langsung tanpa keranjang, poin donasi sebagai potongan harga beserta
batas persentasenya, tahanan stok toko dan kuota slot pada pesanan ambil di toko, batas jam
15.00 dan jeda penyiapan 30 menit, booking hangus H+1, pembatalan oleh pelanggan, verifikasi
donasi oleh kasir beserta poinnya, pembatasan kasir pada tokonya sendiri, dan tindak lanjut
admin atas pesanan yang tidak diambil.

`tests/Feature/HalamanTersambungTest.php` membuka seluruh halaman GET memakai akun yang berhak,
pelanggan, admin, dan kasir, lalu memastikan tidak ada yang menghasilkan error dan tidak ada
yang menolak perannya sendiri. Daftar halamannya diambil dari daftar route aplikasi, jadi route
yang baru ditambahkan otomatis ikut diuji. Yang dilewati hanya halaman yang butuh sesuatu dari
luar aplikasi: token dari email, hasil pemindaian QR kasir, dan rincian Virtual Try On yang
pengerjaannya masih berjalan.

`tests/Feature/RantaiPeranTest.php` menguji pekerjaan yang dimulai pelanggan dan diselesaikan
petugas: keluhan dijawab admin lalu terlihat kembali oleh pelanggan, pengajuan retur disetujui
admin, kasir menyiapkan booking sampai kode pembelian terbit, dan kasir mencatat kedatangan
pendonor sampai poinnya bertambah.

Pengujian selalu memakai database sementara di memori. Pemaksaannya ada di
`tests/TestCase.php`, bukan hanya di `phpunit.xml`, supaya `php artisan test` tidak mungkin
menghapus isi database yang sedang dipakai meskipun ada variabel lingkungan yang menimpa
pengaturannya.

```bash
php artisan colorbox:periksa-produk
php tools/uji_vto.php
```

Yang pertama memeriksa kelengkapan data produk (lihat bagian 8). Yang kedua menguji alur
Virtual Try On dari awal sampai selesai tanpa memanggil layanan AI sungguhan.
