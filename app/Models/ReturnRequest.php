<?php

// Menandai berkas ini berada di ruang nama App\Models, jadi nama lengkap
// kelasnya App\Models\ReturnRequest.
namespace App\Models;

// Kelompok import. Model adalah kelas induk Eloquent, sedangkan BelongsTo dan
// HasMany adalah tipe kembalian relasi "milik satu" dan "punya banyak".
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Kelas ReturnRequest adalah model satu pengajuan retur, yaitu permintaan
// pelanggan menukar atau mengembalikan barang. extends Model membuatnya
// mewarisi kemampuan Eloquent.
class ReturnRequest extends Model
{
    // Menunjuk tabel database yang dipegang model ini, yaitu return_requests.
    protected $table = 'return_requests';

    // Kolom yang boleh diisi serentak lewat create() atau update(), sebagai
    // pengaman mass assignment.
    //
    // Isinya: return_code (nomor pengajuan yang dilihat pelanggan), user_id
    // (pengaju), order_id (pesanan yang direturkan), store_id (toko yang
    // mengurus), purchase_code (bukti pembelian untuk belanja di toko),
    // type (selalu tukar, sebab Colorbox tidak mengembalikan uang),
    // exchange_type dan exchange_note (mau ditukar dengan apa), method
    // (dibawa ke toko atau dikirim), return_date dan return_time (kapan
    // pelanggan datang ke toko), courier, tracking_number, dan shipped_at
    // (jasa kirim serta nomor resi yang diisi pelanggan), received_at (kapan
    // barangnya sampai di Colorbox), replacement_courier dan
    // replacement_tracking_number (pengiriman barang penggantinya), reason
    // dan photo (alasan serta bukti foto), status (keadaan pengajuan),
    // admin_note (catatan petugas), lalu approved_at dan completed_at yang
    // mencatat kapan disetujui dan kapan selesai.
    protected $fillable = ['return_code', 'user_id', 'order_id', 'store_id', 'purchase_code',
        'type', 'exchange_type', 'exchange_note', 'method', 'return_date', 'return_time',
        'courier', 'tracking_number', 'shipped_at', 'received_at',
        'replacement_courier', 'replacement_tracking_number',
        'reason', 'photo', 'photos', 'video_path', 'status', 'admin_note', 'reject_reason',
        'approved_at', 'arrive_by', 'completed_at'];

    /**
     * Alasan penolakan yang bisa dipilih ADMIN.
     *
     * Admin memutuskan sebelum barangnya ada di tangan. Yang dia punya cuma
     * formulir pengajuan, fotonya, dan riwayat pesanan pelanggan. Jadi
     * seluruh alasan di bawah ini bisa dinilai dari layar, tanpa memegang
     * barangnya.
     */
    public const ALASAN_TOLAK_ADMIN = [
        'bukan_produk_colorbox' => 'Produk bukan produk Colorbox',
        'foto_tidak_sesuai' => 'Foto yang dikirim tidak sesuai dengan produk pesanan',
        'tanpa_video_unboxing' => 'Tidak menyertakan video unboxing',
        'tidak_sesuai_bukti' => 'Produk tidak sesuai dengan bukti pembelian',
        'kerusakan_tidak_memenuhi' => 'Kerusakan tidak memenuhi ketentuan retur',
        'lewat_batas_waktu' => 'Pengajuan lewat batas waktu retur',
        'bukan_cacat_produksi' => 'Kerusakan terjadi setelah barang diterima',
    ];

    /**
     * Alasan penolakan yang bisa dipilih KASIR.
     *
     * Kasir memutuskan sesudah barangnya dipegang dan diperiksa di meja,
     * jadi alasannya tentang keadaan barang yang nyata, bukan tentang
     * berkas. Alasan yang hanya bisa dinilai dari layar, misalnya video
     * unboxing, sengaja tidak ada di sini.
     */
    public const ALASAN_TOLAK_KASIR = [
        'label_dilepas' => 'Label harga sudah dilepas',
        'sudah_dipakai' => 'Barang terlihat sudah dipakai atau dicuci',
        'rusak_oleh_pemakaian' => 'Kerusakan akibat pemakaian, bukan cacat produksi',
        'barang_tidak_lengkap' => 'Barang tidak lengkap, ada bagian yang hilang',
        'bukan_produk_colorbox' => 'Produk bukan produk Colorbox',
        'tidak_sesuai_pengajuan' => 'Barang yang dibawa tidak sama dengan yang diajukan',
    ];

    /**
     * Alasan membatalkan JADWAL kedatangan, bukan menolak barangnya.
     *
     * ---------------------------------------------------------------------
     * BEDANYA DENGAN ALASAN_TOLAK_KASIR DI ATAS
     * ---------------------------------------------------------------------
     *
     * Dua daftar itu menolak BARANG yang sudah ada di meja: labelnya
     * dilepas, terlihat sudah dipakai, dan seterusnya. Daftar di bawah
     * ini dipakai ketika barangnya tidak pernah sampai ke meja sama
     * sekali, jadi tidak ada yang bisa diperiksa.
     *
     * Keduanya harus terpisah sebab laporannya menjawab pertanyaan yang
     * berbeda. "Berapa pengajuan yang ditolak karena barangnya tidak
     * layak" adalah soal mutu barang; "berapa yang batal karena
     * pelanggannya tidak datang" adalah soal perilaku pelanggan. Kalau
     * digabung, keduanya saling menutupi.
     *
     * Dibuat daftar tertutup, bukan isian bebas, supaya kasir cukup
     * menekan satu kali di meja yang sedang antre, dan supaya alasannya
     * bisa dihitung. Catatan bebasnya tetap ada sebagai pelengkap.
     */
    public const ALASAN_BATAL_JADWAL = [
        'tidak_datang' => 'Pelanggan tidak datang sampai batas waktu',
        'dibatalkan_pelanggan' => 'Pelanggan membatalkan sendiri lewat telepon atau pesan',
        'datang_tanpa_barang' => 'Pelanggan datang tetapi tidak membawa barangnya',
        'salah_toko' => 'Pelanggan salah toko, jadwalnya bukan di toko ini',
        'jadwal_lewat' => 'Jadwalnya sudah lewat dan tidak diperbarui',
    ];

    /** Tulisan alasan pembatalan jadwal untuk ditampilkan. */
    public static function labelAlasanBatal(?string $kode): string
    {
        if (! $kode) {
            return '';
        }

        return self::ALASAN_BATAL_JADWAL[$kode] ?? ucfirst(str_replace('_', ' ', $kode));
    }

    /** Tulisan alasan penolakan untuk ditampilkan. */
    public static function labelAlasanTolak(?string $kode): string
    {
        if (! $kode) {
            return '';
        }

        // Dua daftar digabung dulu, sebab satu kode bisa ada di keduanya,
        // misalnya bukan_produk_colorbox, dan tulisannya memang sama.
        $semua = self::ALASAN_TOLAK_ADMIN + self::ALASAN_TOLAK_KASIR;

        return $semua[$kode] ?? ucfirst(str_replace('_', ' ', $kode));
    }

    // Pengubahan tipe data otomatis. Kolom waktu dibaca sebagai objek Carbon,
    // jadi di view bisa langsung dipakai ->format('d M Y H:i'). return_date
    // dibaca sebagai tanggal saja tanpa jam, sebab jamnya ada di kolom
    // tersendiri.
    protected $casts = [
        'approved_at' => 'datetime',
        'arrive_by' => 'datetime',
        'completed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'return_date' => 'date',
        // Larik jalur foto, disimpan sebagai JSON. Cast ini membuatnya
        // terbaca sebagai larik PHP biasa, jadi tidak ada satu pun
        // tempat yang perlu memanggil json_decode() sendiri lalu lupa
        // menanganinya ketika isinya null.
        'photos' => 'array',
    ];

    /**
     * Seluruh foto bukti pengajuan ini.
     *
     * Membaca kolom photos yang baru, dan kalau kosong, jatuh kembali ke
     * kolom photo yang lama. Pengajuan yang dibuat sebelum kolom photos
     * ada tetap punya buktinya di kolom lama, dan halaman yang
     * menampilkannya tidak perlu tahu bedanya.
     */
    public function daftarFoto(): array
    {
        if (is_array($this->photos) && $this->photos !== []) {
            return $this->photos;
        }

        return $this->photo ? [$this->photo] : [];
    }

    /** Pengajuan ini membawa foto atau video. */
    public function punyaBukti(): bool
    {
        return $this->daftarFoto() !== [] || $this->video_path !== null;
    }

    /**
     * Barangnya sampai melewati batas waktu?
     *
     * Dipakai admin ketika paket retur dibuka. Aturannya tujuh hari
     * sejak pelanggan menerima barang, dihitung sampai barangnya SUDAH SAMPAI,
     * bukan sampai pelanggan mengirim. Jadi paket yang dikirim mepet lalu tiba
     * terlambat memang lewat batas, dan petugas perlu tahu itu sebelum
     * memutuskan.
     *
     * Yang dibandingkan waktu kedatangannya kalau sudah dicatat, kalau belum
     * dipakai waktu sekarang, supaya daftar retur yang menunggu pun sudah
     * menunjukkan mana yang terancam telat.
     */
    public function terlambatTiba(): bool
    {
        if (! $this->arrive_by) {
            // Batasnya belum terisi, misalnya pengajuan lama. Tidak ada dasar
            // untuk menyebutnya terlambat, jadi dianggap masih sempat.
            return false;
        }

        return ($this->received_at ?: now())->gt($this->arrive_by);
    }

    /** Tulisan jenis penukaran yang diminta pelanggan. */
    public static function labelPenukaran(string $jenis): string
    {
        return match ($jenis) {
            'ganti_ukuran' => 'Tukar ukuran',
            'ganti_warna' => 'Tukar warna',
            'ganti_produk' => 'Tukar produk lain seharga',
            'barang_rusak' => 'Barang rusak atau cacat',
            default => ucfirst(str_replace('_', ' ', $jenis)),
        };
    }

    // Relasi satu pengajuan retur milik satu pengguna. belongsTo()
    // menyambungkan return_requests.user_id ke users.id, jadi $retur->pengguna
    // menghasilkan satu objek User, yaitu pengajunya.
    public function pengguna(): BelongsTo
    {
        // User::class = kelas tujuannya, 'user_id' = kolom kunci asing di tabel return_requests
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi satu pengajuan retur menunjuk satu pesanan. belongsTo()
    // menyambungkan return_requests.order_id ke orders.id, jadi
    // $retur->pesanan menghasilkan satu objek Order. Ini pasangan kebalikan
    // dari relasi retur() di model Order.
    public function pesanan(): BelongsTo
    {
        // Order::class = kelas tujuannya, 'order_id' = kolom kunci asing di tabel return_requests
        return $this->belongsTo(Order::class, 'order_id');
    }

    // Relasi satu pengajuan retur diurus satu toko. belongsTo()
    // menyambungkan return_requests.store_id ke stores.id, jadi $retur->toko
    // menghasilkan satu objek Store tempat barangnya diserahkan.
    public function toko(): BelongsTo
    {
        // Store::class = kelas tujuannya, 'store_id' = kolom kunci asing di tabel return_requests
        return $this->belongsTo(Store::class, 'store_id');
    }

    // Relasi satu pengajuan retur punya banyak baris barang. hasMany()
    // menyambungkan return_requests.id ke return_items.return_request_id,
    // jadi $retur->item menghasilkan kumpulan ReturnItem, sebab satu
    // pengajuan bisa memuat beberapa barang sekaligus.
    public function item(): HasMany
    {
        // ReturnItem::class = kelas tujuannya, 'return_request_id' = kolom kunci asing di tabel return_items
        return $this->hasMany(ReturnItem::class, 'return_request_id');
    }

    /**
     * Peralihan status retur yang boleh dikerjakan admin.
     *
     * Bentuknya array asosiatif: kuncinya status sekarang, nilainya array
     * status yang boleh dituju dari situ. Array kosong berarti keadaan akhir
     * yang tidak bisa dimajukan lagi.
     *
     * Urutannya mengikuti cara kerja sesungguhnya: pengajuan diperiksa dulu
     * keabsahannya, kalau disetujui pelanggan mengirim atau mengantar
     * barangnya, barangnya diperiksa petugas, baru diputuskan. Jadi retur
     * mustahil ditandai selesai padahal barangnya belum pernah diperiksa,
     * dan admin tidak perlu menghafal urutannya sendiri.
     *
     * Dibaca dari luar memakai ReturnRequest::PERALIHAN_ADMIN.
     */
    public const PERALIHAN_ADMIN = [
        'menunggu_validasi' => ['disetujui', 'ditolak'],
        'disetujui' => ['dikirim', 'diperiksa'],
        'dikirim' => ['diperiksa'],
        'diperiksa' => ['diproses', 'ditolak'],
        // LANGKAH TERAKHIRNYA BUKAN MILIK ADMIN.
        //
        // Sesudah barang penggantinya diserahkan atau dikirim, yang paling
        // tahu urusannya beres adalah pelanggan, sebab dialah yang memegang
        // barangnya. Jadi yang menutup pengajuan ini pelanggan, lewat
        // tombol konfirmasi di halaman returnya, sama seperti pesanan biasa.
        //
        // Kalau sampai tiga hari tidak dikonfirmasi, sistem yang menutup,
        // lihat PerbaruiStatus::tutupReturTidakDikonfirmasi(). Jadi
        // pengajuan tetap mustahil menggantung selamanya tanpa admin perlu
        // menekan apa pun.
        'diproses' => [],
        'ditolak' => [],
        'selesai' => [],
        'dibatalkan' => [],
    ];

    /**
     * Barang penggantinya sudah di tangan pelanggan, tinggal dikonfirmasi.
     *
     * Dipakai halaman pelanggan menggambar tombol konfirmasinya, dan dipakai
     * halaman petugas menerangkan kenapa tidak ada tombol apa apa lagi di
     * sana.
     */
    public function menungguKonfirmasi(): bool
    {
        return $this->status === 'diproses';
    }

    /**
     * Retur ini diurus KASIR TOKO, bukan admin kantor.
     *
     * Berlaku untuk pengajuan yang cara mengembalikannya diantar sendiri ke
     * toko. Yang memeriksa barangnya kasir, sebab kasirlah yang memegang
     * bajunya: dia yang bisa melihat labelnya masih menempel atau tidak,
     * mencium baunya, dan meraba apakah sudah pernah dicuci.
     *
     * Admin kantor tidak pernah memegang barang itu, jadi apa pun yang dia
     * tekan untuk retur jenis ini hanya tebakan. Karena itu tombolnya tidak
     * digambar di panel admin, dan permintaannya pun ditolak di server.
     *
     * Retur yang dikirim lewat jasa kirim tetap urusan admin, sebab
     * paketnya memang sampai ke kantor.
     */
    public function diurusKasir(): bool
    {
        return $this->method === 'toko' && $this->store_id !== null;
    }

    /** Status berikutnya yang boleh dipilih admin dari keadaan sekarang. */
    public function lanjutanStatus(): array
    {
        // Dua tanda tanya menjaga supaya status yang tidak terdaftar
        // menghasilkan array kosong, bukan error, jadi view aman melakukan
        // foreach terhadapnya.
        return self::PERALIHAN_ADMIN[$this->status] ?? [];
    }

    /** Tulisan status untuk ditampilkan, dipakai admin maupun pelanggan. */
    public static function labelStatus(string $status): string
    {
        // match adalah pencocokan nilai milik PHP 8, lebih ringkas daripada
        // rangkaian if. Cabang default menyiapkan cadangan untuk kode yang
        // belum terdaftar: garis bawah ditukar spasi lalu huruf pertamanya
        // dibesarkan.
        return match ($status) {
            'menunggu_validasi' => 'Menunggu Validasi',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'dikirim' => 'Barang Dikirim Pelanggan',
            'diperiksa' => 'Sedang Diperiksa',
            // Bukan lagi "Sedang Diproses". Pada status ini barang
            // penggantinya SUDAH diserahkan atau dikirim, dan yang ditunggu
            // tinggal konfirmasi pelanggan. Tulisan lama membuat pelanggan
            // mengira masih ada pekerjaan Colorbox yang belum selesai,
            // padahal justru dialah yang ditunggu.
            'diproses' => 'Menunggu Konfirmasi',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Kalimat tombol untuk tiap peralihan, ditulis sebagai perbuatan.
     *
     * Bedanya dengan labelStatus(): yang ini jawaban atas pertanyaan "tombol
     * ini mengerjakan apa", sedangkan labelStatus() jawaban atas pertanyaan
     * "keadaannya sekarang apa". Tombol bertuliskan perbuatan lebih cepat
     * dipahami petugas daripada tombol bertuliskan kata benda.
     */
    public static function labelPeralihan(string $status): string
    {
        return [
            'disetujui' => 'Setujui Pengajuan',
            'ditolak' => 'Tolak Pengajuan',
            'dikirim' => 'Tandai Barang Dikirim Pelanggan',
            'diperiksa' => 'Barang Sudah Sampai, Mulai Periksa',
            // Pada langkah inilah pengiriman barang penggantinya dipesan ke
            // jasa kirim, jadi tulisannya perbuatan itu, bukan "siapkan".
            'diproses' => 'Kirim Barang Pengganti',
            'selesai' => 'Tandai Retur Selesai',
        ][$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Isian tambahan yang diperlukan satu langkah, bukan daftar lengkapnya.
     *
     * Bentuknya sama dengan Order::isianPeralihan() dan
     * TapTryCollect::isianPeralihan(), supaya komponen tombol satu klik
     * x-tombol-langkah bisa dipakai bertiga tanpa perlu dibedakan.
     */
    public static function isianPeralihan(string $status): array
    {
        /*
         * Penolakan wajib diberi alasan, sebab alasannya ikut terkirim
         * sebagai pemberitahuan dan dibaca pelanggan.
         *
         * Alasannya DIPILIH dari daftar, bukan diketik bebas. Dengan
         * daftar, penolakan yang sebabnya sama selalu terbaca sama oleh
         * pelanggan, dan jumlah tiap sebab bisa dihitung di laporan.
         * Catatan bebas tetap disediakan sebagai keterangan tambahan,
         * misalnya menyebut bagian mana yang tidak cocok.
         */
        if ($status === 'ditolak') {
            return [
                ['nama' => 'reject_reason', 'label' => 'Alasan penolakan', 'wajib' => true,
                    'pilihan' => self::ALASAN_TOLAK_ADMIN,
                    'petunjuk' => 'Alasan ini yang dibaca pelanggan pada pemberitahuannya.'],
                ['nama' => 'admin_note', 'label' => 'Keterangan tambahan', 'wajib' => false,
                    'petunjuk' => 'Boleh dikosongkan. Dipakai menyebut rincian, misalnya bagian mana yang tidak cocok.'],
            ];
        }

        // Hasil pemeriksaan barang sebaiknya dicatat, tetapi tidak dipaksa,
        // sebab kadang barangnya memang sesuai pengajuan dan tidak ada yang
        // perlu diterangkan.
        /*
         * Langkah terakhir TIDAK lagi meminta jasa kirim dan nomor resi.
         *
         * Dulu admin mengetik keduanya dengan tangan. Rancangan itu salah
         * tempat: nomor resi diterbitkan jasa kirim, bukan dikarang admin,
         * dan admin yang mengetiknya cuma menyalin, atau lebih buruk,
         * menebak. Nama jasa kirimnya pun sudah diketahui sistem dari
         * pilihan pelanggan pada pesanan aslinya.
         *
         * Sekarang pengiriman barang penggantinya dipesan sendiri ke jasa
         * kirim ketika returnya ditandai selesai, dan nomor resinya datang
         * dari sana. Yang tersisa untuk diketik admin hanya catatan, dan
         * itupun boleh dikosongkan.
         */
        if ($status === 'selesai') {
            return [
                ['nama' => 'admin_note', 'label' => 'Catatan untuk pelanggan', 'wajib' => false,
                    'petunjuk' => 'Bila dikosongkan, keterangan bawaan yang dikirim.'],
            ];
        }

        if (in_array($status, ['diperiksa', 'diproses'], true)) {
            return [
                ['nama' => 'admin_note', 'label' => 'Catatan untuk pelanggan', 'wajib' => false,
                    'petunjuk' => 'Bila dikosongkan, keterangan bawaan yang dikirim.'],
            ];
        }

        return [];
    }
}
