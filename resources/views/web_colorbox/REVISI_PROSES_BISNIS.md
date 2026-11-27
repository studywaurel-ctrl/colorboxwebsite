# Revisi Proses Bisnis Website Colorbox

Tanggal: 25 September 2026
Project: `web_colorbox`
Dokumen ini menjawab 13 poin revisi. Keputusan yang Anda pilih ditandai **Keputusan**.
Belum ada kode yang diubah sampai dokumen ini disetujui.

---

## 1. Produk Tap Try and Collect tidak diambil pelanggan

**Keputusan: booking hangus H+1.**

Masalah yang harus dijawab: barang sudah disiapkan kasir, stok toko tertahan, tetapi
pelanggan tidak datang. Kalau tidak ada aturan, stok tertahan selamanya dan slot
esok hari ikut penuh.

### Siklus status booking

| Status | Artinya | Siapa yang mengubah |
|---|---|---|
| `SCHEDULED` | Pelanggan sudah memesan slot, stok toko ditahan | sistem, saat pelanggan memesan |
| `READY` | Barang sudah disiapkan kasir di meja jemput | kasir |
| `COLLECTED` | Pelanggan datang, QR dipindai, barang diambil | kasir |
| `NOT_PICKED_UP` | Pelanggan tidak datang sampai batas waktu | sistem atau kasir |
| `CANCELLED` | Dibatalkan pelanggan sendiri | pelanggan |

### Aturan waktu

1. **Pelanggan boleh membatalkan sendiri** selama status masih `SCHEDULED` atau
   `READY` dan jam slotnya belum lewat. Setelah jam slot lewat, tombol batal hilang.
2. **Kasir boleh menandai `NOT_PICKED_UP`** kapan saja setelah jam slot berakhir,
   misalnya saat tutup toko. Tidak perlu menunggu sistem.
3. **Sistem menutup otomatis pada H+1 pukul 23.59** terhadap booking yang jam
   slotnya sudah lewat sehari penuh dan statusnya masih `SCHEDULED` atau `READY`.

Jadi booking jam 14.00 tanggal 1 Oktober masih berlaku sepanjang tanggal 2 Oktober,
dan baru hangus pada 2 Oktober pukul 23.59.

### Barangnya ke mana

Begitu status menjadi `NOT_PICKED_UP` atau `CANCELLED`, stok toko yang ditahan
dilepas kembali dan langsung bisa dipesan pelanggan lain. Barang fisiknya
dikembalikan kasir ke rak. Tidak ada barang yang hilang dari catatan, karena yang
ditahan hanya kolom `reserved`, bukan `stock`.

### Kalau barangnya sudah dibayar lunas (Store Pickup)

Beda kasus. Di sini uang pelanggan sudah masuk, jadi barang tidak boleh sekadar
dilepas. Alurnya:

1. Lewat H+1, status pesanan menjadi `Not Picked Up`.
2. Pesanan masuk daftar **Perlu Ditindaklanjuti** di dashboard admin.
3. Admin memilih salah satu: **kirim ke alamat pelanggan** (pesanan berubah jadi
   pengiriman, pelanggan membayar ongkir) atau **refund** (masuk alur
   `Refund Processing` lalu `Refunded`).
4. Pelanggan diberi tahu lewat notifikasi, dan statusnya terlihat di halaman pesanan.

Dengan begitu tidak ada uang pelanggan yang mengambang tanpa kejelasan.

---

## 2. Sequence diagram tidak boleh ada dua aktor

**Keputusan: setiap serah terima antar peran dijembatani oleh halaman dan catatan,
bukan oleh percakapan langsung antar aktor.**

Aturan ini bukan sekadar aturan gambar, tetapi punya akibat langsung pada sistem.
Kalau kasir tidak boleh digambar berbicara kepada pelanggan, maka sistem harus
menyediakan perantaranya:

| Kejadian | Cara lama (dua aktor) | Cara baru (satu aktor per diagram) |
|---|---|---|
| Barang sudah siap | Kasir menelepon pelanggan | Kasir menekan **Tandai Siap** → sistem membuat notifikasi → pelanggan membuka halaman **Booking Saya** dan melihat status Siap Diambil |
| Donasi diverifikasi | Kasir memberi tahu jumlah yang diterima | Kasir mengisi form verifikasi → poin masuk → pelanggan melihat di halaman **Poin Saya** |
| Retur ditolak | Admin menghubungi pelanggan | Admin mengisi alasan → pelanggan membaca di halaman **Rincian Retur** |

Sehingga setiap sequence diagram hanya berisi satu aktor, sistem, dan basis data.
Diagram pelanggan berhenti di "sistem menyimpan permintaan"; diagram kasir dimulai
dari "kasir membuka daftar antrean".

Sequence diagram yang akan digambar (masing-masing satu aktor):

1. Pelanggan memesan Tap Try and Collect
2. Pelanggan membayar pesanan lewat Midtrans
3. Pelanggan mengajukan retur
4. Pelanggan menjadwalkan donasi
5. Pelanggan memakai Virtual Try On
6. Kasir menyiapkan dan menyerahkan booking
7. Kasir memverifikasi donasi
8. Admin mengelola produk dan stok
9. Admin memproses retur
10. Admin menindaklanjuti pesanan tidak diambil

---

## 3. Waktu Tap Try and Collect dan asal stoknya

**Keputusan: stok diambil dari stok toko, pemesanan hari yang sama ditutup pukul 15.00.**

### Aturan jam

1. Slot dibuat per jam mengikuti jam buka toko, **10.00 sampai 22.00 WIB**.
2. **Jeda siap 30 menit.** Slot paling cepat yang boleh dipilih adalah 30 menit dari
   sekarang. Memesan pukul 13.00 berarti slot paling awal adalah 13.30, dan karena
   slotnya per jam, yang muncul adalah 14.00.
3. **Batas pemesanan hari yang sama pukul 15.00 WIB.** Lewat pukul 15.00, seluruh
   slot hari ini tertutup dan pelanggan hanya bisa memilih tanggal berikutnya.
   Alasannya sama seperti di Uniqlo: staf toko perlu waktu menyiapkan pesanan
   tanpa mengganggu pelayanan sore yang ramai.
4. Slot yang jamnya sudah lewat tidak pernah ditampilkan.
5. Kalender hanya membuka 14 hari ke depan (bisa diubah di Pengaturan).

Pesan yang muncul ke pelanggan dibuat jelas, bukan sekadar slot hilang:

> Pemesanan untuk hari ini sudah ditutup pukul 15.00. Silakan pilih tanggal berikutnya.

> Jadwal ini sudah lewat batas penyiapan produk. Silakan pilih jam lain.

### Stok toko, bukan stok gudang

Tidak ada konsep gudang di sistem ini. Yang dipakai adalah stok fisik di toko yang
dipilih, tercatat di tabel `store_stocks`.

Supaya stok yang sama tidak terjual dua kali, ditambahkan kolom `reserved`:

```
tersedia = stock - reserved
```

| Kejadian | Akibat pada stok toko |
|---|---|
| Booking dibuat | `reserved` bertambah |
| Booking `COLLECTED` (pelanggan membeli) | `stock` berkurang, `reserved` berkurang |
| Booking `CANCELLED` atau `NOT_PICKED_UP` | `reserved` berkurang |
| Pelanggan mencoba tetapi tidak jadi beli | `reserved` berkurang, `stock` tetap |

Produk yang `tersedia` nya nol tidak bisa dipesan di toko itu, dan pada halaman
pemilihan toko diberi keterangan **Stok habis di toko ini**, bukan disembunyikan,
supaya pelanggan tahu ada pilihan toko lain.

Pemeriksaan stok dilakukan **dua kali**: saat halaman dibuka, dan sekali lagi di
server tepat sebelum booking disimpan, di dalam `DB::transaction` dengan
`lockForUpdate()`. Jadi dua pelanggan yang menekan tombol di detik yang sama tidak
mungkin sama-sama berhasil kalau stoknya tinggal satu.

---

## 4. Sistem admin dan kasir

**Keputusan: tiga peran, yaitu pelanggan, kasir, dan admin.** Kasir terikat pada satu toko.

### Pembagian tugas

| Pekerjaan | Pelanggan | Kasir | Admin |
|---|:---:|:---:|:---:|
| Belanja, booking, donasi, retur | ya | | |
| Antrean Tap Try and Collect hari ini | | ya (tokonya) | ya (semua toko) |
| Memindai QR dan menandai Siap, Diambil, Tidak Diambil | | ya | |
| Memverifikasi donasi dan memberi poin | | ya | |
| Mencari dan melihat data member | | ya | ya |
| Kelola produk, harga, kategori | | | ya |
| Kelola stok toko | | ya (tokonya) | ya (semua toko) |
| Kelola slot dan kuota | | | ya |
| Proses retur dan refund | | | ya |
| Tindak lanjut pesanan tidak diambil | | | ya |
| Voucher, laporan, pengaturan | | | ya |

Kasir sengaja tidak boleh mengubah harga dan produk. Itu pemisahan wewenang yang
lazim di ritel dan mudah dipertahankan saat sidang.

### Alur kerja kasir sehari

1. Masuk ke `/kasir`, langsung melihat **antrean hari ini** yang dikelompokkan per jam.
2. Menekan **Tandai Siap** setelah barang diambil dari rak dan ditaruh di meja jemput.
3. Pelanggan datang, kasir memindai QR (atau mengetik kode booking).
4. Layar menampilkan nama member, poin, dan daftar barang.
5. Kasir menandai **Diambil** kalau jadi dibeli, atau **Tidak Jadi** kalau pelanggan
   hanya mencoba lalu tidak membeli.
6. Untuk donasi: memindai QR donasi, menghitung pakaian, mengisi jumlah layak dan
   tidak layak, menekan **Verifikasi**. Poin langsung masuk ke akun pelanggan.
7. Akhir shift: menandai booking yang tidak datang sebagai **Tidak Diambil**.

---

## 5. Statechart diagram

**Keputusan: cukup dua statechart besar, bukan banyak diagram kecil.**

### Statechart 1: Pesanan

Satu diagram yang memuat kedua jalur pemenuhan beserta cabang retur dan refund.

```
Pending Payment
   ├─ (Midtrans settlement) → Payment Confirmed → Processing
   │      ├─ [DELIVERY]  → Packed → Picked Up by Courier → In Transit
   │      │                 → Out for Delivery → Delivered
   │      └─ [STORE_PICKUP] → Ready for Pickup → Picked Up
   │                            └─ (lewat H+1) → Not Picked Up
   │                                  ├─ (admin kirim) → Packed ...
   │                                  └─ (admin refund) → Refund Processing → Refunded
   ├─ (expire / deny / cancel) → Cancelled
   └─ dari Delivered atau Picked Up, dalam 7 hari:
          Return Requested → Under Review
               ├─ Return Approved → Returned → Inspection
               │      ├─ Exchange Processing → Completed
               │      └─ Refund Processing → Refunded
               └─ Return Rejected
```

### Statechart 2: Kunjungan ke toko

Satu diagram dengan dua wilayah: booking Tap Try and Collect dan janji donasi.

```
[Tap Try and Collect]
Scheduled → Ready → Collected
    │         │        └─ (dalam 7 hari) → pengajuan retur
    │         └─ (lewat H+1) → Not Picked Up
    └─ (dibatalkan pelanggan) → Cancelled

[Donasi]
Scheduled → Checked In → Verified → Completed
    │                        └─ poin masuk sesuai jumlah layak
    └─ (tidak datang, lewat H+1) → No Show
```

Statechart lain yang dulu direncanakan (pembayaran, retur, donasi terpisah) dilebur
ke dalam dua diagram di atas sebagai substate, sehingga laporan tidak dipenuhi
diagram kecil yang isinya tiga kotak.

---

## 6. Kelola alamat di checkout seperti Shopee

**Keputusan: seluruh pengelolaan alamat dilakukan di halaman checkout, tanpa pindah halaman.**

Bentuknya:

1. Di bagian paling atas checkout ada kartu **Alamat Pengiriman** berisi nama
   penerima, nomor telepon, dan alamat lengkap, dengan tombol **Ubah** di kanan.
2. Menekan **Ubah** membuka jendela berisi daftar alamat berbentuk pilihan bulat,
   masing-masing dengan label (Rumah, Kantor), penanda **Utama**, serta tautan
   **Ubah** dan **Hapus**.
3. Di bawah daftar ada tombol **Tambah Alamat Baru** yang membuka formulir di
   jendela yang sama: penerima, telepon, provinsi, kota, kecamatan, kode pos,
   detail alamat, patokan, label, dan centang **Jadikan alamat utama**.
4. Menyimpan alamat baru langsung memilihnya untuk pesanan ini dan menutup jendela.
   Halaman checkout tidak pernah berpindah, isian lain tidak hilang.
5. Kalau pelanggan belum punya alamat sama sekali, kartu digantikan tombol besar
   **Tambah Alamat Pengiriman**, dan tombol bayar dikunci sampai alamat terisi.

Untuk jalur ambil di toko, kartu alamat diganti kartu **Toko Pengambilan** dengan
pola yang sama: toko terpilih, tombol Ubah, daftar toko beserta jaraknya.

---

## 7. Poin donasi sebagai potongan harga

**Keputusan: poin donasi masuk ke saldo poin yang sama dan bisa dipakai di checkout.**

| Hal | Nilai | Bisa diubah di |
|---|---|---|
| Poin per pakaian layak | 1 poin | Pengaturan admin |
| Nilai satu poin | Rp 1.000 | Pengaturan admin |
| Batas pemakaian poin | 30 persen dari subtotal | Pengaturan admin |

Catatan: nilai poin sebelumnya Rp 100, sehingga donasi 5 potong hanya bernilai
Rp 500 dan tidak terasa manfaatnya. Saya naikkan nilai bawaannya menjadi Rp 1.000
supaya donasi 5 potong bernilai Rp 5.000. Angka ini tetap bisa Anda ubah sendiri
lewat menu Pengaturan tanpa menyentuh kode.

Di halaman checkout ditampilkan apa adanya:

> Poin Anda 12 poin, setara Rp 12.000. Maksimal yang bisa dipakai pada pesanan ini
> Rp 9.000 (30 persen dari subtotal).

Setiap perubahan poin dicatat di `point_transactions` dengan sumber yang jelas
(`donasi`, `pesanan`, `pembatalan`), sehingga halaman Poin Saya bisa menampilkan
riwayat lengkap dan tidak ada poin yang muncul tanpa asal usul.

---

## 8. Real time dan zona waktu WIB

**Keputusan: seluruh sistem memakai Asia/Jakarta, dan seluruh angka yang berubah
diambil saat itu juga dari basis data.**

1. `config/app.php` disetel ke `Asia/Jakarta`, dan setiap jam yang ditampilkan
   diberi keterangan **WIB**.
2. Daftar jam pada halaman Tap Try and Collect dan donasi diambil lewat permintaan
   ke server setiap kali tanggal atau toko diganti, bukan dari data yang dicetak
   saat halaman dimuat.
3. Daftar jam menyegarkan dirinya sendiri setiap 30 detik selama halaman terbuka,
   dan menampilkan keterangan **Diperbarui pukul 14.32 WIB** supaya pelanggan tahu
   angkanya baru.
4. Sisa kuota ditulis apa adanya: **3 slot tersisa**, atau **Penuh** kalau habis.
5. Stok toko pada halaman detail produk juga diambil saat itu juga dan ikut
   menyegarkan diri.
6. Pemeriksaan terakhir tetap di server. Kalau slot baru saja penuh di detik
   terakhir, pesannya jujur:

   > Maaf, slot baru saja penuh. Silakan pilih jam lainnya.

---

## 9. Donasi: poin, barang tidak layak, dan siapa yang mengurus

**Keputusan menurut penjelasan Anda:**

1. **Barang donasi tidak pernah ditolak dan tidak dikembalikan ke pelanggan.**
   Pakaian bekas yang sudah dibawa ke toko tetap diterima. Yang dinilai kasir
   adalah berapa potong yang **layak pakai** dan berapa potong yang **tidak layak**.
2. **Satu pakaian layak bernilai 1 poin.** Menitipkan 5 potong dan 4 di antaranya
   layak berarti mendapat 4 poin, bukan 5.
3. **Tidak ada pengurangan poin.** Poin belum pernah diberikan sebelum verifikasi,
   jadi tidak ada yang perlu dikurangi. Pelanggan hanya menerima lebih sedikit dari
   perkiraannya.
4. **Kasir toko yang memverifikasi, bukan admin.** Admin hanya melihat laporannya.

### Alur donasi

| Tahap | Pelaku | Yang terjadi |
|---|---|---|
| Menjadwalkan | pelanggan | pilih toko, tanggal, jam donasi (slot donasi terpisah dari slot Tap Try and Collect), isi perkiraan jumlah potong |
| Datang ke toko | pelanggan | menunjukkan QR donasi |
| Check in | kasir | memindai QR, status menjadi `CHECKED_IN` |
| Memeriksa | kasir | menghitung pakaian, mengisi **jumlah layak** dan **jumlah tidak layak**, boleh menambahkan catatan |
| Verifikasi | kasir | menekan Verifikasi, status `VERIFIED`, poin sebanyak jumlah layak masuk ke akun pelanggan |
| Selesai | sistem | pelanggan menerima notifikasi dan melihat rinciannya di halaman Donasi Saya |

Yang ditampilkan ke pelanggan dibuat transparan, misalnya:

> Anda menitipkan 5 potong. Layak pakai 4 potong, tidak layak 1 potong (bahan sobek).
> Poin yang Anda terima: 4 poin.

Pakaian dalam tetap tidak boleh didonasikan. Keterangannya ditulis jelas di halaman
donasi sebelum pelanggan menjadwalkan, bukan baru diberitahu saat sudah di toko.

---

## 10. Beli langsung tanpa keranjang

**Keputusan: ada tombol Beli Sekarang di halaman detail produk.**

1. Pelanggan memilih warna dan ukuran, lalu menekan **Beli Sekarang**.
2. Sistem menyimpan pilihan itu di sesi pembelian langsung, **tanpa menyentuh isi
   keranjang**. Barang yang sudah ada di keranjang tetap utuh.
3. Pelanggan langsung dibawa ke halaman checkout berisi satu barang itu saja.
4. Kalau belum masuk, pelanggan diminta masuk dulu, dan setelah masuk langsung
   kembali ke checkout dengan barang yang sama, tidak perlu mengulang dari awal.
5. Sesi pembelian langsung terhapus begitu pesanan dibuat atau pelanggan
   meninggalkan halaman checkout.

Tombol **Tambah ke Keranjang** tetap ada di sebelahnya untuk pelanggan yang mau
belanja beberapa barang sekaligus.

---

## 11. Virtual Try On dengan AI sungguhan

Anda meminta saya mencarikan layanannya. Berikut hasil penelusuran per September 2026,
lengkap dengan harganya.

### Perbandingan layanan

| Layanan | Model | Harga per gambar | Waktu proses | Catatan |
|---|---|---|---|---|
| **Replicate** | `cuuupid/idm-vton` | **sekitar 0,023 dolar**, atau 43 gambar per 1 dolar | sekitar 17 detik | model sumber terbuka khusus pakaian, paling murah, bisa disebutkan di laporan |
| **fal.ai** | `image-apps-v2/virtual-try-on` | 0,04 dolar | beberapa detik | paling sederhana, cukup kirim dua tautan gambar |
| **FASHN AI** | API resmi | 0,08 sampai 0,38 dolar | cepat | kualitas paling rapi, isi saldo minimal 7,5 dolar |
| **Google Gemini Flash Image** | model gambar umum | sekitar 0,067 dolar pada 1K | cepat | bukan khusus pakaian, dan **tidak ada paket gratis** |

Tidak ada satu pun yang menyediakan paket gratis tanpa kartu. Itu kenyataannya, dan
saya tidak akan berpura-pura ada.

### Saran saya: Replicate dengan model IDM-VTON

Alasannya:

1. **Paling murah jauh.** Isi saldo 5 dolar (sekitar Rp 80.000) sudah cukup untuk
   sekitar 215 kali coba. Untuk demo dan sidang, itu berlebih.
2. **Model khusus pakaian**, bukan model gambar umum yang disuruh menebak. Hasilnya
   lebih masuk akal untuk kemeja, kaus, dan celana.
3. **Sumber terbuka dan ada makalahnya**, jadi di laporan Anda bisa menuliskan dasar
   ilmiah fiturnya, bukan sekadar "memakai AI".
4. Kalau suatu saat mau pindah ke fal.ai atau FASHN, integrasinya saya buat berbentuk
   penyetel sederhana di `.env`, tinggal ganti nilainya.

### Yang akan saya bangun

```
AI_VTO_DRIVER=replicate        # atau fal
AI_VTO_API_KEY=...
AI_VTO_MODEL=cuuupid/idm-vton
```

1. Halaman Virtual Try On dengan **kamera langsung** (izin kamera diminta baik-baik,
   ada tombol ambil ulang) **dan pilihan unggah foto** untuk pelanggan yang tidak
   mau memakai kamera.
2. Pelanggan memilih produk dari katalog, bukan mengetik. Foto produk yang dipakai
   adalah foto asli dari database.
3. Proses dijalankan di latar belakang dengan keadaan yang jelas: **Mengunggah,
   Sedang diproses, Berhasil, Gagal**. Ada penghitung waktu, bukan layar diam.
4. Kalau layanan gagal atau kunci belum diisi, pesannya jujur dan menyebutkan
   sebabnya, bukan menampilkan gambar palsu.
5. Hasilnya tersimpan di **My Looks** dan bisa dihapus pelanggan.
6. **Tidak akan pernah ada gambar manusia buatan yang ditampilkan seolah-olah hasil
   AI.** Kalau kuncinya belum diisi, halamannya menyatakan bahwa layanan belum
   tersambung.

Yang perlu Anda lakukan: daftar di replicate.com, isi saldo paling sedikit 5 dolar,
salin API token-nya, lalu tempel ke `.env`. Fiturnya langsung hidup tanpa perlu
mengubah kode.

**Catatan jujur:** saya tidak bisa menguji hasil gambarnya dari sini karena saya
tidak punya kunci API Anda. Yang bisa saya jamin adalah alur, penanganan gagal, dan
penyimpanannya benar. Hasil akhir gambarnya baru bisa dinilai setelah Anda mengisi kunci.

---

## 12. Database pelanggan untuk admin dan kasir

**Keputusan: ada halaman Member yang bisa dibuka admin dan kasir.**

Isi halaman daftar:

- Pencarian menurut nama, email, nomor telepon, atau kode member
- Kolom: nama, email, telepon, level member, poin, jumlah pesanan, kunjungan terakhir
- Saringan: level member, punya donasi, punya booking

Isi halaman rincian satu member:

| Bagian | Isi |
|---|---|
| Profil | nama, email, telepon, tanggal lahir, jenis kelamin, tanggal bergabung |
| Poin | saldo sekarang dan riwayat penambahan serta pemakaian |
| Pesanan | daftar pesanan beserta status dan totalnya |
| Tap Try and Collect | riwayat booking beserta statusnya |
| Donasi | riwayat donasi, jumlah layak dan tidak layak, poin yang diterima |
| Alamat | daftar alamat pengiriman |

Perbedaan hak: **admin melihat seluruh riwayat**, sedangkan **kasir melihat profil,
poin, dan hanya riwayat yang terjadi di tokonya sendiri**. Kasir tidak bisa melihat
alamat rumah pelanggan, karena tidak ada keperluannya di meja kasir dan itu data pribadi.

Level member ditentukan dari total belanja, dan aturannya disimpan di Pengaturan
sehingga bisa diubah tanpa mengubah kode.

---

## 13. Kelengkapan database produk

**Keputusan: dibuat alat pemeriksa, bukan sekadar percaya data sudah lengkap.**

Keadaan sekarang: 62 produk, 340 varian warna dan ukuran, 267 foto asli, 11 kategori,
12 toko, 1.700 baris stok toko.

Setiap produk wajib memiliki:

| Wajib | Keterangan |
|---|---|
| kategori | tidak boleh kosong |
| nama dan slug | slug unik |
| harga | lebih dari nol |
| minimal satu varian | warna dan ukuran |
| stok varian | angka, boleh nol, tidak boleh kosong |
| minimal satu foto | berkas PNG-nya benar-benar ada di folder |
| foto per warna | setiap warna varian punya fotonya |
| SKU | unik antar produk |
| material dan cara perawatan | untuk halaman detail |
| berat | untuk perhitungan ongkir |
| stok toko | ada barisnya di setiap toko yang aktif |

Akan dibuat perintah:

```
php artisan colorbox:periksa-produk
```

Perintah ini menelusuri seluruh produk dan menuliskan daftar yang kurang, misalnya:

```
[kurang foto]     Kemeja Oxford Lengan Panjang, warna Navy
[sku ganda]       CBX-1042 dipakai 2 produk
[tanpa stok toko] Celana Chino Slim Fit, ukuran 30, di Colorbox Bintaro
```

Selama masih ada baris yang dilaporkan, data belum boleh dianggap siap pakai.
Pemeriksaan ini juga dijalankan setiap kali seeder selesai, sehingga tidak ada data
yang lolos tanpa ketahuan.

---

## Ringkasan perubahan basis data

| Tabel | Perubahan |
|---|---|
| `users` | `role` menerima nilai `kasir`, `store_id` dipakai untuk mengikat kasir ke toko, tambah `member_code` |
| `store_stocks` | tambah `reserved` |
| `orders` | `method` menjadi `fulfillment_method` berisi `DELIVERY` atau `STORE_PICKUP`, status memakai daftar Inggris, tambah `tap_try_collect_id`, `picked_up_at`, `follow_up_action` |
| `tap_try_collects` | status baru `SCHEDULED`, `READY`, `COLLECTED`, `NOT_PICKED_UP`, `CANCELLED`, tambah `ready_at`, `source` |
| `donations` | `estimated_items`, tambah `accepted_items`, `rejected_items`, `reject_note`, status baru, `verified_by_user_id` |
| `addresses` | tambah `landmark` |
| `virtual_try_ons` | `product_id` boleh kosong, tambah `look_name`, `saved`, `driver`, `error_message` |
| `virtual_try_on_items` | tabel baru untuk Mix and Match |
| `settings` | kunci baru: `ttc_same_day_cutoff`, `ttc_prep_minutes`, `booking_expire_days`, `donation_point_per_item`, `point_value` |

---

## Urutan pengerjaan

| Tahap | Isi |
|---|---|
| 1 | Migration dan model: peran kasir, `reserved`, status baru, kolom donasi |
| 2 | Alur Tap Try and Collect: jeda 30 menit, batas 15.00, hold stok, hangus H+1 |
| 3 | Dashboard kasir: antrean, QR, tandai siap dan diambil, verifikasi donasi |
| 4 | Halaman Member untuk admin dan kasir |
| 5 | Checkout: alamat ala Shopee, beli langsung, potongan poin donasi |
| 6 | Zona waktu WIB dan penyegaran slot serta stok |
| 7 | Tindak lanjut pesanan tidak diambil di dashboard admin |
| 8 | Integrasi Virtual Try On dengan Replicate |
| 9 | Perintah pemeriksa kelengkapan produk |
| 10 | Dua statechart dan sepuluh sequence diagram untuk laporan |

Zip dikirim bertahap setiap beberapa tahap selesai, tidak menunggu semuanya rampung.

---

## Sumber harga layanan AI

- [IDM-VTON di Replicate](https://replicate.com/cuuupid/idm-vton)
- [Virtual Try-on di fal.ai](https://fal.ai/models/fal-ai/image-apps-v2/virtual-try-on)
- [Harga API FASHN AI](https://help.fashn.ai/plans-and-pricing/api-pricing)
- [Harga Gemini API](https://ai.google.dev/gemini-api/docs/pricing)
