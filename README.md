# colorbox_pelanggan

Website penjualan Colorbox sisi pelanggan beserta dashboard admin.
Dibuat dengan Laravel (PHP), Blade, Bootstrap 5, JavaScript biasa, dan MySQL.

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

COLORBOX_DEMO_PASSWORD=isi-kata-sandi-akun-pelanggan-contoh     # minimal 8 karakter
COLORBOX_ADMIN_PASSWORD=isi-kata-sandi-akun-admin               # minimal 8 karakter
COLORBOX_STAFF_PIN=246810                                       # PIN staf toko, 4 sampai 8 angka

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

| Peran | Email | Kata sandi |
|---|---|---|
| Pelanggan | real.notmreli@gmail.com | isi `COLORBOX_DEMO_PASSWORD` |
| Admin | admin@colorbox.test | isi `COLORBOX_ADMIN_PASSWORD` |
| Kasir toko | kasir.bintaro@colorbox.test | isi `COLORBOX_ADMIN_PASSWORD` |

Dashboard admin ada di `http://127.0.0.1:8000/admin`.
Kata sandi tidak pernah ditulis di dalam kode; seluruhnya dibaca dari `.env`.

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
| Virtual Try On | Pilih produk → pilih warna → kamera menyala → panduan posisi tubuh → Generate → hasil |
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

Alurnya: pilih produk → pilih warna → kamera menyala → panduan posisi tubuh
("Posisikan tubuh di dalam bingkai" berubah menjadi "Posisi sudah sesuai") → tombol Generate →
hasil, lalu bisa ditambahkan ke keranjang atau wishlist.

Ada dua jalur kerja yang dipilih otomatis lewat `.env`:

| Keadaan `.env` | Mode | Yang terjadi |
|---|---|---|
| `TRYON_API_URL` kosong | `preview` | Hasil disusun di peramban: tangkapan kamera digambar ke `<canvas>`, titik tubuh dibaca dengan model pose MoveNet (TensorFlow.js dari cdn.jsdelivr.net), lalu foto produk ditempelkan mengikuti lebar bahu dan panjang badan. Tidak ada panggilan keluar. |
| `TRYON_API_URL` diisi | `api` | Tangkapan kamera beserta foto produk dikirim ke layanan virtual try-on itu (`TRYON_API_KEY` sebagai bearer token), dan gambar balasannya yang ditampilkan. |

Hasilnya disimpan sebagai PNG di `public/images/tryon` dan dicatat pada tabel `virtual_try_ons`
(`engine` = `preview` atau `api`, `status` = `dibuat|diproses|selesai|gagal`).
Bila layanan tidak dapat dihubungi, muncul pesan "Layanan Virtual Try On sedang tidak dapat
dihubungi. Coba lagi sebentar lagi." dan kesalahannya dicatat di `storage/logs/laravel.log`.

Jujur soal kemampuannya, supaya tidak salah dijelaskan saat sidang: selama `TRYON_API_URL`
belum diisi, pakaian ditempelkan mengikuti titik tubuh di peramban, bukan dibuat ulang oleh
model AI. Untuk hasil bergaya generatif seperti layanan komersial, isi `TRYON_API_URL` dengan
layanan virtual try-on yang kamu pakai, dan kode ini sudah siap memanggilnya.

## 8. Struktur project

```
app/
  Console/Commands/PerbaruiStatus.php   pekerjaan berkala (pesanan kedaluwarsa, jadwal lewat)
  Http/Controllers/                     controller pelanggan
  Http/Controllers/Admin/               controller dashboard admin
  Http/Middleware/HanyaAdmin.php        penjaga halaman admin
  Models/                               31 model Eloquent
  Services/MidtransService.php          penghubung Midtrans Snap
  Support/helpers.php                   rupiah(), setting(), gambar_produk(), tanggal_id(), jam_singkat()
database/
  data/produk.json                      data katalog dari situs resmi Colorbox
  migrations/                           7 berkas migrasi
  seeders/                              pengaturan, kategori, toko, produk, slot, pengguna, contoh transaksi
public/
  css/app.css                           gaya tampilan (mengikuti pratinjau Colorbox lama)
  js/app.js                             skrip umum
  images/logo, images/products, ...     aset gambar
  vendor/                               Bootstrap, Bootstrap Icons, qrcode.js, jsQR (lokal, tanpa CDN)
resources/views/                        seluruh halaman Blade
routes/web.php                          seluruh route
tools/unduh_gambar.php                  pengunduh foto produk resmi Colorbox
tools/check.py                          pemeriksaan statis (route, view, sintaks, kata terlarang)
```

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

## 9. Tabel utama

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

## 10. Aturan bisnis (tabel `settings`)

`free_shipping_threshold` (350000), `donation_points_per_item` (1), `point_value` (100),
`points_max_percent` (30), `return_window_days` (7), `payment_expiry_hours` (24),
`ttc_slot_quota` (4), `donation_slot_quota` (5), `booking_window_days` (14),
`min_donation_items` (1), `max_donation_items` (50).

Semuanya dapat diubah lewat dashboard admin → Pengaturan, atau langsung di phpMyAdmin,
tanpa mengubah kode.

## 11. Pemeriksaan

```bash
python3 tools/check.py
```

Memeriksa sintaks seluruh berkas PHP, keberadaan controller dan methodnya, nama route yang
dipakai view, keberadaan view yang dipanggil, pemakaian `@json`, dan kata yang tidak boleh
dipakai pada teks antarmuka.
