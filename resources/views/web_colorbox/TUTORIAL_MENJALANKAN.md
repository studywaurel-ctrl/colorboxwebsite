# Tutorial Menjalankan Colorbox di Komputer Sendiri

Panduan ini ditulis langkah demi langkah untuk Windows dengan XAMPP. Kerjakan berurutan dari
atas ke bawah. Setiap langkah ada bagian **Hasil yang benar**, jadi kalau yang muncul di layar
berbeda, berhenti dulu di langkah itu dan lihat bagian **Kalau ada pesan error** di halaman
paling bawah.

Satu hal yang perlu dipahami dulu supaya tidak bingung: **XAMPP di sini hanya dipakai untuk
databasenya saja**, bukan untuk menyajikan situsnya. Situsnya dijalankan oleh Laravel sendiri
lewat perintah `php artisan serve`. Jadi folder project ini tidak perlu ditaruh di dalam
`htdocs`, dan alamat situsnya bukan `http://localhost/colorbox` melainkan
`http://127.0.0.1:8000`.

---

## Kenapa foldernya satu, bukan tiga

Pelanggan, admin, dan kasir berada di dalam **satu folder project** bernama `web_colorbox`,
bukan tiga folder terpisah `web_colorbox_pelanggan`, `web_colorbox_admin`, dan
`web_colorbox_kasir`. Alasannya ketiganya membaca dan menulis database yang sama. Kalau
dipecah menjadi tiga project:

- model dan migration harus digandakan ke tiga tempat, dan setiap kali satu tabel berubah
  pekerjaannya diulang tiga kali,
- `composer install` dijalankan tiga kali dan folder `vendor` ada tiga, masing-masing sekitar
  70 MB,
- menjalankannya perlu tiga jendela terminal pada tiga port berbeda, dan tiga alamat berbeda
  untuk dihafal,
- hanya satu project yang boleh menjalankan migration, sebab yang lain akan menghapus tabel
  yang sudah terisi.

Jadi yang dipisah adalah **isinya**, bukan foldernya. Di dalam satu project itu, kode tiap peran
sudah terpisah rapi dan mudah ditunjukkan:

| Bagian | Pelanggan | Admin | Kasir |
|---|---|---|---|
| Route | `routes\web.php` | `routes\web.php` | `routes\web.php` |
| Controller | `app\Http\Controllers\` | `app\Http\Controllers\Admin\` | `app\Http\Controllers\Kasir\` |
| Penjaga halaman | bawaan Laravel | `app\Http\Middleware\HanyaAdmin.php` | `app\Http\Middleware\HanyaKasir.php` |
| Halaman | `resources\views\` | `resources\views\admin\` | `resources\views\kasir\` |
| Alamat | `/` | `/admin` | `/kasir` |

Satu kali `php artisan serve`, satu database, tiga pintu masuk. Kalau nanti dosen pembimbing
tetap ingin tiga project yang benar-benar terpisah, itu bisa dikerjakan, tetapi sebaiknya
dibicarakan dulu sebab konsekuensinya seperti daftar di atas.

---

## Langkah 0. Yang perlu ada di komputer

| Yang dibutuhkan | Untuk apa | Cara mendapatkan |
|---|---|---|
| XAMPP | MySQL dan phpMyAdmin | https://www.apachefriends.org |
| Composer 2 | memasang paket Laravel | https://getcomposer.org/download |
| VS Code (boleh dilewati) | menyunting berkas kode | https://code.visualstudio.com |

PHP tidak perlu dipasang terpisah, sebab XAMPP sudah membawa PHP di dalam `C:\xampp\php`.
Yang penting versinya 8.2 atau lebih baru. XAMPP 8.2 ke atas sudah memenuhi.

---

## Langkah 1. Keluarkan isi berkas zip

1. Buka folder `Downloads`.
2. Klik kanan `kode_colorbox.zip`, pilih **Extract All**, lalu **Extract**.
3. Hasilnya folder `kode_colorbox`, dan di dalamnya ada folder `web_colorbox`.
4. Pindahkan folder `web_colorbox` ke tempat yang mudah dijangkau, misalnya langsung
   di `C:\Users\ASUS\web_colorbox`.

**Hasil yang benar:** di dalam `C:\Users\ASUS\web_colorbox` terlihat folder `app`,
`routes`, `resources`, `database`, `public`, dan berkas `artisan`, `composer.json`,
`.env.example`.

Mulai sekarang, setiap kali panduan ini menulis "folder project", yang dimaksud adalah
`C:\Users\ASUS\web_colorbox`.

---

## Langkah 2. Pastikan PHP bisa dipanggil dari terminal

1. Tekan tombol **Windows**, ketik `cmd`, tekan Enter.
2. Ketik perintah ini lalu Enter:

```
php -v
```

**Hasil yang benar:** muncul tulisan seperti `PHP 8.2.12 (cli)`.

**Kalau muncul** `'php' is not recognized as an internal or external command`, PHP-nya ada
tetapi Windows belum tahu tempatnya. Tambahkan ke PATH:

1. Tekan Windows, ketik `environment variables`, pilih **Edit the system environment
   variables**.
2. Klik tombol **Environment Variables**.
3. Pada kotak bawah (**System variables**), klik baris **Path**, lalu **Edit**.
4. Klik **New**, tuliskan `C:\xampp\php`, lalu **OK** sampai semua jendela tertutup.
5. **Tutup jendela cmd, lalu buka cmd yang baru.** Ini wajib, sebab jendela lama belum membaca
   PATH yang baru.
6. Coba lagi `php -v`.

---

## Langkah 3. Pastikan Composer bisa dipanggil dari terminal

Masih di cmd, ketik:

```
composer -V
```

**Hasil yang benar:** muncul tulisan seperti `Composer version 2.7.1`.

Kalau belum terpasang, unduh **Composer-Setup.exe** dari https://getcomposer.org/download lalu
pasang seperti biasa. Ketika pemasangan menanyakan letak PHP, tunjukkan `C:\xampp\php\php.exe`.
Sesudah selesai, tutup cmd dan buka cmd baru, lalu coba lagi.

---

## Langkah 4. Aktifkan ekstensi GD pada PHP

Ekstensi GD dipakai untuk mengolah foto produk. Biasanya XAMPP sudah mengaktifkannya, tetapi
perlu diperiksa.

1. Di cmd, ketik:

```
php -m
```

2. Cari tulisan `gd` pada daftar yang panjang itu.

**Kalau `gd` tidak ada:**

1. Buka berkas `C:\xampp\php\php.ini` dengan Notepad.
2. Tekan Ctrl+F, cari `extension=gd`.
3. Hapus tanda titik koma di depannya, sehingga menjadi `extension=gd`.
4. Simpan berkas, lalu tutup dan buka lagi cmd.

---

## Langkah 5. Nyalakan XAMPP

1. Buka **XAMPP Control Panel**.
2. Klik **Start** pada baris **Apache**.
3. Klik **Start** pada baris **MySQL**.

**Hasil yang benar:** kedua baris berubah menjadi hijau dan muncul nomor PID serta nomor port
di sebelahnya.

Apache sebetulnya hanya diperlukan untuk membuka phpMyAdmin pada langkah berikutnya. MySQL
yang wajib menyala setiap kali situsnya dijalankan.

---

## Langkah 6. Buat databasenya di phpMyAdmin

Laravel membuat tabel-tabelnya sendiri, tetapi databasenya harus Anda buat lebih dulu.

1. Buka peramban, masuk ke `http://localhost/phpmyadmin`.
2. Klik menu **Databases** di bagian atas.
3. Pada kotak **Create database**, tuliskan nama databasenya:

```
colorbox_pelanggan
```

4. Pada kotak sebelahnya pilih **utf8mb4_unicode_ci**.
5. Klik **Create**.

**Hasil yang benar:** nama `colorbox_pelanggan` muncul pada daftar database di sisi kiri, masih
kosong tanpa tabel.

---

## Langkah 7. Buka terminal di dalam folder project

Ini sering terlewat, padahal semua perintah berikutnya harus dijalankan dari dalam folder
project, bukan dari folder lain.

Cara paling mudah:

1. Buka folder `C:\Users\ASUS\web_colorbox` di File Explorer.
2. Klik kotak alamat di bagian atas jendela, sehingga alamatnya terpilih.
3. Ketik `cmd` di situ, lalu Enter.

**Hasil yang benar:** jendela cmd terbuka dan baris paling depannya sudah berbunyi
`C:\Users\ASUS\web_colorbox>`.

Kalau pakai VS Code: **File** lalu **Open Folder**, pilih folder project, lalu buka terminal
dengan **Terminal** lalu **New Terminal**.

---

## Langkah 8. Pasang paket Laravel

Di terminal yang sudah berada di folder project, ketik:

```
composer install
```

Tunggu sampai selesai. Pada jalur internet biasa prosesnya sekitar dua sampai lima menit,
sebab paket Laravel diunduh dari internet.

**Hasil yang benar:** muncul tulisan seperti `Generating optimized autoload files`, dan di
folder project muncul folder baru bernama `vendor`.

---

## Langkah 9. Salin berkas pengaturan

Masih di terminal yang sama:

```
copy .env.example .env
```

**Hasil yang benar:** muncul `1 file(s) copied.` dan di folder project ada berkas bernama
`.env` tanpa nama depan.

---

## Langkah 10. Isi berkas .env

Buka berkas `.env` dengan Notepad atau VS Code. Isi bagian berikut, sisanya biarkan apa adanya.

```env
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=colorbox_pelanggan
DB_USERNAME=root
DB_PASSWORD=

COLORBOX_STAFF_PIN=
```

Keterangan tiap baris:

- `DB_DATABASE` harus sama dengan nama database yang Anda buat di langkah 6.
- `DB_USERNAME` pada XAMPP adalah `root`.
- `DB_PASSWORD` dibiarkan **kosong**, sebab root di XAMPP memang tanpa kata sandi.
- `COLORBOX_STAFF_PIN` adalah PIN yang diketik kasir ketika memverifikasi pelanggan di toko.
  Boleh dibiarkan kosong, nanti memakai PIN bawaan 246810. Kalau mau diganti, isi 4 sampai 8
  angka.
- Kata sandi akun contoh tidak diatur di sini. Semua akun contoh memakai tanggal lahirnya
  sendiri sebagai kata sandi, jadi tidak ada yang perlu Anda karang dan ingat-ingat.

Kalau Anda juga ingin mencoba pembayarannya, isi dua baris Midtrans dengan kunci sandbox dari
akun Midtrans Anda sendiri. Tanpa itu, seluruh bagian lain situsnya tetap berjalan, hanya
halaman pembayaran yang tidak bisa dilanjutkan.

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxx
MIDTRANS_IS_PRODUCTION=false
```

Simpan berkasnya.

---

## Langkah 11. Buat kunci aplikasi

```
php artisan key:generate
```

**Hasil yang benar:** muncul `Application key set successfully.` dan baris `APP_KEY=base64:...`
di dalam `.env` sekarang sudah terisi.

---

## Langkah 12. Buat tabel dan isi data contoh

```
php artisan migrate:fresh --seed
```

Perintah ini membuat seluruh tabel lalu mengisinya dengan data contoh: 12 toko, 62 produk,
jadwal, pengaturan, dan beberapa transaksi contoh. Prosesnya sekitar satu menit.

**Hasil yang benar:** deretan baris `DONE`, lalu daftar akun contoh tercetak di terminal
seperti ini:

```
  Akun pelanggan: real.notmreli@gmail.com  |  kata sandi: 2003-05-14
  Akun admin: Admin Colorbox  |  kata sandi: 1995-08-17
  Akun kasir: Kasir Colorbox Central Park  |  kata sandi: 1991-02-02
  Akun kasir: Kasir Colorbox Mall Taman Anggrek  |  kata sandi: 1992-03-03
  ...
  Kata sandi akun contoh adalah tanggal lahirnya masing-masing.
  PIN petugas toko: 246810
```

**Salin daftar itu atau biarkan jendelanya terbuka.** Kata sandi tiap kasir adalah tanggal
lahirnya dan berbeda-beda, jadi lebih mudah dibaca dari terminal daripada ditebak.

Catatan: kata `fresh` berarti seluruh tabel dihapus lebih dulu. Jadi perintah ini dipakai saat
pemasangan pertama saja. Nanti kalau Anda sudah punya data yang ingin disimpan, pakai
`php artisan migrate` saja.

---

## Langkah 13. Ambil foto produk

Foto produk tidak disertakan di dalam zip karena ukurannya 123 MB. Pilih salah satu cara.

**Cara A, kalau Anda masih punya salinan project ini dari sebelumnya.** Buka folder lama itu,
masuk ke `public\images\products`, salin seluruh isinya, lalu tempelkan ke
`public\images\products` di folder project yang baru. Ini cara tercepat.

**Cara B, mengunduh dari situs resmi Colorbox.** Di terminal, jalankan:

```
php tools/unduh_gambar.php
```

Skrip ini membaca daftar produk lalu mengunduh fotonya dari `colorbox.co.id` dan menyimpannya
sebagai PNG. Butuh sambungan internet dan waktunya cukup lama karena fotonya banyak. Kalau
terputus di tengah jalan, jalankan lagi perintah yang sama, foto yang sudah ada akan dilewati.

**Hasil yang benar:** folder `public\images\products` terisi berkas dengan nama berpola
`{nama-produk}-{warna}-{nomor}.png`.

Kalau langkah ini dilewati, situsnya tetap berjalan dan semua alurnya bisa dicoba. Hanya saja
kartu produknya memakai gambar penampung bertuliskan bahwa fotonya belum diunduh.

---

## Langkah 14. Jalankan situsnya

```
php artisan serve
```

**Hasil yang benar:** terminal menampilkan

```
  INFO  Server running on [http://127.0.0.1:8000].

  Press Ctrl+C to stop the server
```

**Jendela terminal ini jangan ditutup.** Selama situsnya dipakai, jendela ini harus tetap
terbuka. Kalau ditutup, situsnya ikut berhenti.

---

## Langkah 15. Buka situsnya di peramban

Buka peramban, lalu masuk ke alamat:

```
http://127.0.0.1:8000
```

**Hasil yang benar:** halaman depan Colorbox terbuka beserta deretan produknya.

Tiga pintu masuk yang berbeda:

| Peran | Alamat | Nama pengguna | Kata sandi |
|---|---|---|---|
| Pelanggan | `http://127.0.0.1:8000/masuk` | real.notmreli@gmail.com | 2003-05-14 |
| Admin | `http://127.0.0.1:8000/admin/masuk` | Admin Colorbox | 1995-08-17 |
| Kasir | `http://127.0.0.1:8000/admin/masuk` | Kasir Colorbox Central Park | 1991-02-02 |

Admin dan kasir masuk lewat halaman yang sama, lalu diarahkan sendiri ke dashboardnya
masing-masing. Nama penggunanya adalah **nama lengkap**, bukan email, dan kata sandinya
**tanggal lahir** dengan penulisan tahun-bulan-tanggal. Email juga masih diterima.

---

## Langkah 16. Tambahan yang boleh dilewati

Kalau Anda ingin pesanan yang melewati batas waktu pembayaran dibatalkan otomatis, buka
**terminal kedua** di folder project yang sama lalu jalankan:

```
php artisan schedule:work
```

Biarkan terminal itu terbuka juga. Tanpa ini, semua hal lain tetap berjalan normal, hanya
pembatalan otomatisnya yang tidak jalan.

---

## Menjalankan lagi di lain hari

Langkah 1 sampai 13 cukup sekali saja. Untuk pemakaian berikutnya hanya tiga hal:

1. Buka XAMPP Control Panel, klik **Start** pada **MySQL**.
2. Buka terminal di folder project, jalankan `php artisan serve`.
3. Buka `http://127.0.0.1:8000`.

Untuk menghentikan: tekan **Ctrl+C** di terminal, lalu **Stop** pada MySQL di XAMPP.

---

## Memeriksa bahwa semuanya masih utuh

Dua perintah ini tidak wajib, tetapi berguna kalau Anda baru mengubah kode dan ingin tahu ada
yang rusak atau tidak.

```
php artisan test
```

Menjalankan 22 pengujian: aturan bisnisnya, seluruh halaman per peran, dan rantai pekerjaan
dari pelanggan ke admin dan kasir. Pengujian ini memakai database sementara di memori, jadi
tidak menyentuh data Anda di phpMyAdmin.

```
python3 tools/check.py
```

Memeriksa sintaks seluruh berkas PHP, keberadaan controller, nama route yang dipakai view, dan
keberadaan view yang dipanggil. Perintah ini butuh Python, jadi lewati saja kalau Python belum
terpasang.

---

## Kalau ada pesan error

| Yang muncul di layar | Sebabnya | Yang perlu dilakukan |
|---|---|---|
| `'php' is not recognized` | Windows belum tahu tempat PHP | ulangi langkah 2, dan ingat untuk membuka cmd yang baru |
| `'composer' is not recognized` | Composer belum terpasang | ulangi langkah 3 |
| `Could not open input file: artisan` | terminalnya tidak berada di folder project | ulangi langkah 7 |
| `SQLSTATE[HY000] [1049] Unknown database 'colorbox_pelanggan'` | databasenya belum dibuat | ulangi langkah 6 |
| `SQLSTATE[HY000] [2002] No connection could be made` | MySQL di XAMPP belum menyala | klik **Start** pada MySQL, lalu ulangi perintahnya |
| `SQLSTATE[HY000] [1045] Access denied for user 'root'` | `DB_USERNAME` atau `DB_PASSWORD` di `.env` tidak cocok | isi `root` dan kosongkan kata sandinya, lihat langkah 10 |
| `No application encryption key has been specified` | `APP_KEY` masih kosong | jalankan `php artisan key:generate` |
| `Ekstensi GD belum aktif` | ekstensi GD belum dinyalakan | ulangi langkah 4 |
| `Failed to listen on 127.0.0.1:8000` | port 8000 sudah dipakai program lain | jalankan `php artisan serve --port=8001` lalu buka `http://127.0.0.1:8001` |
| `MySQL shutdown unexpectedly` di XAMPP | biasanya port 3306 dipakai MySQL lain | matikan layanan MySQL yang lain, atau ganti port MySQL di XAMPP |
| Gambar produk kosong semua | foto produk belum diambil | ulangi langkah 13 |
| Halaman putih tanpa tulisan apa pun | ada kesalahan yang tercatat di log | buka `storage\logs\laravel.log`, lihat baris paling bawah |

Satu catatan tentang Virtual Try On. Halaman daftar dan Gaya Saya terbuka normal, tetapi
pemrosesan gambarnya belum selesai dikerjakan, jadi bagian itu memang belum bisa dipakai
sampai akhir. Seluruh bagian lain, yaitu belanja, checkout, pembayaran, Tap Try and Collect,
donasi, retur, keluhan, dashboard admin, dan meja kasir, sudah berjalan utuh.

---

## Tentang berkas .env

Berkas `.env` berisi kata sandi database dan kunci Midtrans Anda. Jangan diunggah ke mana pun,
jangan dilampirkan ke laporan, dan jangan dimasukkan ke dalam zip yang dikirim ke orang lain.
Yang boleh disebarkan adalah `.env.example`, sebab isinya hanya nama pengaturannya tanpa
nilainya.

---

## Kalau ditanya nanti

Daftar pertanyaan yang biasa muncul beserta jawaban singkatnya. Semuanya ditulis supaya bisa
langsung diucapkan apa adanya, tidak perlu dihafal kata per kata, yang penting isinya.

**"Kenapa pakai XAMPP?"**

"XAMPP dipakai untuk menjalankan MySQL di komputer saya, jadi databasenya bisa diuji secara
lokal tanpa perlu server sewaan. Situsnya sendiri dijalankan Laravel lewat `php artisan serve`."

**"Kenapa alamatnya 127.0.0.1:8000, bukan localhost?"**

"Keduanya sama saja, 127.0.0.1 itu alamat komputer sendiri. Angka 8000 adalah port yang dipakai
server bawaan Laravel. Kalau lewat `localhost` tanpa port, yang menjawab Apache milik XAMPP,
bukan Laravel."

**"Kenapa tidak ditaruh di folder htdocs?"**

"Karena situsnya tidak dijalankan Apache, melainkan server bawaan Laravel. Yang dibuka publik
hanya folder `public`, sedangkan kode lainnya tetap tertutup. Itu memang cara kerja Laravel."

**"Kenapa pelanggan, admin, dan kasir jadi satu project?"**

"Karena ketiganya memakai database yang sama. Kalau dipisah jadi tiga project, model dan
migrationnya harus digandakan dan setiap perubahan tabel dikerjakan tiga kali. Jadi yang saya
pisah adalah kodenya di dalam: route, controller, dan halamannya punya foldernya sendiri untuk
tiap peran."

**"Kenapa nama foldernya web_colorbox tapi databasenya colorbox_pelanggan?"**

"Foldernya saya beri nama `web_colorbox` karena isinya ketiga peran sekaligus. Nama
databasenya tetap `colorbox_pelanggan` mengikuti penamaan pada laporan dan ERD, supaya tidak
perlu diubah di mana-mana."

**"Kenapa pakai Laravel?"**

"Laravel memisahkan route, controller, model, dan tampilan secara rapi, jadi alur programnya
mudah ditunjukkan dan dicocokkan dengan diagram di laporan. Laravel juga sudah membawa
pengaman bawaan seperti token CSRF dan pengacakan kata sandi."

**"Datanya diambil dari mana?"**

"Nama produk, harga, warna, dan ukuran diambil dari katalog resmi Colorbox. Fotonya diunduh
dari situs resmi Colorbox, bukan dibuat ulang dan bukan dari situs gambar acak. Deskripsi,
bahan, berat, dan stok adalah data contoh untuk keperluan penelitian."

**"Pembayarannya asli?"**

"Memakai Midtrans mode sandbox, yaitu mode uji coba resmi dari Midtrans. Alurnya sama persis
dengan yang asli, hanya uangnya tidak benar-benar berpindah. Tidak ada tombol palsu atau
konfirmasi bayar buatan sendiri, statusnya dibaca dari Midtrans."

**"Kenapa admin dan kasir masuk lewat halaman yang sama?"**

"Karena keduanya sama-sama petugas yang akunnya dibuatkan perusahaan, bukan mendaftar sendiri.
Setelah masuk, sistem mengarahkan sendiri sesuai perannya, admin ke dashboard admin dan kasir
ke meja kasir tokonya."

**"Kenapa kata sandi akun contoh memakai tanggal lahir?"**

"Supaya tidak ada kata sandi yang ditulis sebagai teks di dalam kode dan tidak ada yang perlu
diisi di berkas pengaturan. Semua akun contoh memakai aturan yang sama, yaitu tanggal lahirnya
sendiri, dan tanggal itu memang tersimpan di tabel pengguna. Pada pemakaian sungguhan, kata
sandinya diganti sendiri oleh pemiliknya setelah masuk pertama kali."

**"Ada berapa tabelnya?"**

"Ada 32 tabel yang dibuat lewat 7 berkas migration. Seluruhnya tercantum pada ERD dan kamus
data di laporan."

**"Bagaimana memastikan sistemnya tidak error?"**

"Ada 22 pengujian otomatis yang dijalankan dengan satu perintah `php artisan test`. Pengujian
itu membuka seluruh halaman memakai akun pelanggan, admin, dan kasir, lalu mencoba rangkaian
pekerjaannya dari awal sampai selesai, misalnya keluhan dikirim pelanggan sampai dijawab
admin."

**"Kenapa Virtual Try On belum bisa dipakai?"**

"Halaman dan alurnya sudah ada, yang belum selesai adalah pemrosesan gambarnya karena
pengerjaannya memang panjang. Bagian itu saya tulis jujur sebagai yang masih dikerjakan, tidak
saya buat seolah-olah sudah jalan."

**"Kenapa folder vendor tidak ikut dikumpulkan?"**

"Folder `vendor` berisi paket buatan pihak lain yang diunduh otomatis, bukan kode saya, dan
ukurannya ratusan megabita. Siapa pun bisa membuatnya kembali dengan satu perintah
`composer install`."

**Kalau saat demo tiba-tiba error**

Jangan panik dan jangan menebak. Lihat tabel pesan error pada bagian sebelumnya, hampir semua
penyebabnya cuma dua: MySQL di XAMPP belum dinyalakan, atau terminalnya tidak berada di folder
project. Keduanya bisa diperbaiki dalam hitungan detik di depan penguji, dan itu wajar.
