<?php

// Menandai berkas ini berada di ruang nama App\Models, jadi nama lengkap
// kelasnya App\Models\Complaint.
namespace App\Models;

// Kelompok import. Model adalah kelas induk Eloquent yang memberi kemampuan
// baca tulis tabel, BelongsTo tipe kembalian relasi "milik satu", dan HasMany
// tipe kembalian relasi "punya banyak".
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu keluhan pelanggan beserta percakapannya dengan admin.
 *
 * ---------------------------------------------------------------------
 * BEDANYA DENGAN RETUR
 * ---------------------------------------------------------------------
 *
 * Retur mengurus BARANG: ada baju yang harus kembali ke Colorbox dan ada
 * baju pengganti yang harus dikirim. Keluhan mengurus hal yang tidak
 * berupa barang, misalnya uang terpotong dua kali, kurir yang tidak
 * sopan, atau toko yang tutup padahal janji kunjungannya sudah disetujui.
 *
 * Karena itu keluhan tidak pernah mengubah stok dan tidak pernah membuat
 * pesanan baru. Yang dihasilkannya jawaban tertulis dari admin.
 *
 * ---------------------------------------------------------------------
 * TIGA KEADAAN SAJA
 * ---------------------------------------------------------------------
 *
 * baru     pelanggan sudah menulis, admin belum menjawab. Inilah yang
 *          dihitung sebagai antrean kerja admin.
 * dijawab  admin sudah menjawab. Pelanggan boleh menyahut lagi, dan
 *          begitu dia menyahut tiketnya kembali menjadi baru supaya tidak
 *          terlewat.
 * selesai  urusannya ditutup. Tidak ada lagi yang bisa menulis di sini.
 *
 * Tiga sudah cukup. Keadaan yang lebih banyak hanya membuat admin ragu
 * harus memilih yang mana, dan pelanggan tidak bisa membedakan artinya.
 */
class Complaint extends Model
{
    // Menunjuk tabel database yang dipegang model ini, yaitu complaints.
    protected $table = 'complaints';

    /** Keadaan tiket beserta tulisannya di layar. */
    public const STATUS = [
        'baru' => 'Belum dijawab',
        'dijawab' => 'Sudah dijawab',
        'selesai' => 'Selesai',
    ];

    /**
     * Pokok urusan yang bisa dipilih pelanggan.
     *
     * Daftarnya sengaja pendek dan memakai kata sehari hari, bukan istilah
     * perusahaan. Pelanggan yang paketnya belum sampai tahu dia harus
     * memilih Pengiriman; dia tidak tahu apa itu fulfillment.
     */
    public const KATEGORI = [
        'pesanan' => 'Pesanan',
        'pengiriman' => 'Pengiriman',
        'produk' => 'Barang yang diterima',
        'pembayaran' => 'Pembayaran dan voucher',
        'toko' => 'Pelayanan di toko',
        'akun' => 'Akun dan data diri',
        'lainnya' => 'Lainnya',
    ];

    /**
     * Balasan cepat yang bisa disisipkan admin ke kotak jawaban.
     *
     * ---------------------------------------------------------------------
     * KENAPA ADA
     * ---------------------------------------------------------------------
     *
     * Sebagian besar keluhan yang masuk berbunyi sama: paket belum
     * bergerak, voucher tidak terpakai, ukuran tidak sesuai. Admin
     * mengetik jawaban yang isinya sama berulang kali, dan setiap
     * pengetikan ulang itu satu kesempatan baru untuk jawabannya menjadi
     * lebih pendek, lebih ketus, atau lupa menyebut langkah yang
     * seharusnya diberitahukan.
     *
     * Dengan teks siap pakai, jawaban untuk keluhan yang sama selalu
     * memuat keterangan yang sama lengkapnya, sekalipun admin sedang
     * mengerjakan dua puluh tiket sekaligus.
     *
     * ---------------------------------------------------------------------
     * DISISIPKAN, BUKAN LANGSUNG DIKIRIM
     * ---------------------------------------------------------------------
     *
     * Menekan satu pilihan hanya MENGISI kotak jawaban. Admin tetap harus
     * membaca, menyesuaikan, lalu menekan Kirim. Ini disengaja: jawaban
     * siap pakai yang terkirim sendiri tanpa dibaca cepat atau lambat
     * akan mendarat pada keluhan yang tidak cocok, dan pelanggan membaca
     * jawaban yang jelas jelas tidak menjawab pertanyaannya.
     *
     * Dikelompokkan menurut kategori keluhan supaya yang muncul di layar
     * cuma yang masuk akal untuk tiket yang sedang dibuka.
     */
    public const BALASAN_CEPAT = [
        'pengiriman' => [
            'Paket Anda sudah diserahkan kepada jasa kirim. Nomor resinya ada di halaman '
                .'rincian pesanan, dan perjalanannya bisa dilihat di situs jasa kirimnya.',
            'Kami sedang menanyakan posisi paket Anda kepada jasa kirim. Jawabannya kami '
                .'sampaikan di tiket ini paling lambat besok.',
            'Alamat pada pesanan Anda kurang lengkap sehingga kurir kesulitan. Mohon balas '
                .'pesan ini dengan patokan terdekat supaya bisa kami teruskan.',
        ],
        'produk' => [
            'Mohon maaf atas ketidaknyamanannya. Silakan ajukan penukaran lewat menu Pesanan '
                .'Saya, lampirkan foto barangnya dan video pembukaan paket, lalu tim kami periksa.',
            'Untuk barang yang ukurannya tidak sesuai, penukaran bisa diajukan selama tujuh '
                .'hari sejak barang diterima lewat tombol Ajukan Penukaran Barang.',
            'Terima kasih atas laporannya. Kami teruskan ke tim mutu supaya tidak terulang.',
        ],
        'pembayaran' => [
            'Pembayaran Anda sudah kami terima dan pesanannya sedang diproses. Mohon maaf '
                .'kalau statusnya sempat terlambat berubah.',
            'Voucher tidak bisa digabung dengan diskon yang sedang berjalan pada barang yang '
                .'sama. Silakan pilih salah satu yang potongannya lebih besar.',
            'Dana yang terpotong tanpa pesanan yang jadi akan kembali sendiri ke rekening Anda '
                .'mengikuti jadwal bank penerbit. Kalau lewat dari itu, balas pesan ini.',
        ],
        'pesanan' => [
            'Pesanan Anda sedang disiapkan. Kami kabari lagi di tiket ini begitu paketnya '
                .'diserahkan kepada kurir.',
            'Pembatalan hanya bisa dilakukan selama pesanan belum diserahkan kepada kurir. '
                .'Mohon konfirmasi apakah Anda tetap ingin membatalkannya.',
        ],
        'toko' => [
            'Terima kasih atas masukannya. Laporan ini kami teruskan kepada kepala toko yang '
                .'bersangkutan.',
            'Mohon maaf atas pelayanan yang kurang berkenan. Boleh kami tahu hari dan jam '
                .'kejadiannya supaya bisa kami telusuri?',
        ],
        'akun' => [
            'Untuk mengubah data diri, silakan buka Akun Saya lalu pilih Profil. Kalau emailnya '
                .'yang ingin diganti, balas pesan ini dengan email barunya.',
            'Kata sandi bisa diatur ulang lewat tautan Lupa Sandi di halaman masuk. Tautannya '
                .'dikirim ke email yang terdaftar.',
        ],
        'lainnya' => [
            'Terima kasih sudah menghubungi Colorbox. Keluhan Anda sedang kami periksa dan '
                .'jawabannya kami sampaikan di tiket ini.',
            'Keluhan Anda sudah selesai kami tangani. Kalau masih ada yang mengganjal, balas '
                .'pesan ini dan tiketnya kami buka lagi.',
        ],
    ];

    /**
     * Balasan cepat untuk satu kategori, ditambah yang umum.
     *
     * Yang umum selalu ikut, sebab kalimat pembuka dan kalimat penutup
     * dipakai pada kategori mana pun.
     */
    public static function balasanCepat(?string $kategori): array
    {
        $khusus = self::BALASAN_CEPAT[$kategori] ?? [];

        return array_values(array_unique(array_merge($khusus, self::BALASAN_CEPAT['lainnya'])));
    }

    // Kolom yang boleh diisi serentak lewat create() atau update(), sebagai
    // pengaman mass assignment. Isinya: ticket_code (nomor tiket yang dibaca
    // orang), user_id (pengeluhnya), order_id (pesanan yang dikeluhkan, boleh
    // kosong), category dan subject (pokok urusan serta judulnya), status
    // (keadaan tiket), handled_by (admin yang menjawab), last_reply_at (waktu
    // pesan terakhir), dan closed_at (waktu tiketnya ditutup).
    protected $fillable = ['ticket_code', 'user_id', 'order_id', 'category', 'subject',
        'status', 'handled_by', 'last_reply_at', 'closed_at'];

    // Pengubahan tipe data otomatis. Dua kolom waktu dibaca menjadi objek
    // Carbon, jadi di view bisa langsung diformat atau dihitung selisihnya
    // dengan ->diffForHumans().
    protected $casts = [
        'last_reply_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relasi satu keluhan milik satu pengguna. belongsTo() menyambungkan
    // complaints.user_id ke users.id, jadi $keluhan->pengguna menghasilkan
    // satu objek User, yaitu pelanggan yang mengeluh.
    public function pengguna(): BelongsTo
    {
        // User::class = kelas tujuannya, 'user_id' = kolom kunci asing di tabel complaints
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke pesanan yang dikeluhkan. Hasilnya bisa null, sebab tidak
    // semua keluhan berkaitan dengan pesanan.
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // Relasi ke admin yang memegang tiket ini. Juga bisa null, selama belum
    // ada admin yang menjawab.
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Seluruh pesan di dalam tiket ini, dari yang paling awal.
     *
     * Diurutkan menurut id, bukan created_at. Alasannya: dua pesan yang
     * dikirim pada detik yang sama punya created_at persis sama, dan
     * urutannya jadi acak. Nomor id selalu naik, jadi urutannya pasti.
     */
    public function balasan(): HasMany
    {
        // ComplaintReply::class = kelas tujuannya, 'complaint_id' = kolom
        // kunci asing di tabel complaint_replies
        return $this->hasMany(ComplaintReply::class, 'complaint_id')->orderBy('id');
    }

    /** Tulisan keadaan tiket di layar, misalnya Belum dijawab. */
    public static function labelStatus(?string $kode): string
    {
        return self::STATUS[$kode] ?? '-';
    }

    /** Tulisan pokok urusan di layar, misalnya Pengiriman. */
    public static function labelKategori(?string $kode): string
    {
        return self::KATEGORI[$kode] ?? 'Lainnya';
    }

    /** Tiketnya masih boleh ditulisi? */
    public function masihTerbuka(): bool
    {
        return $this->status !== 'selesai';
    }

    /**
     * Nomor tiket berikutnya, misalnya KEL-20260824-0007.
     *
     * Nomornya dihitung ulang tiap hari, jadi angka urutnya tetap pendek
     * walaupun situsnya sudah berjalan bertahun tahun. Kodenya memuat
     * tanggal supaya admin langsung tahu umurnya tanpa membuka tiketnya.
     */
    public static function kodeBaru(): string
    {
        $tanggal = now()->format('Ymd');

        // Dihitung dari tiket yang kodenya berawalan tanggal hari ini.
        // count() cukup, sebab tiket tidak pernah dihapus: nomornya hanya
        // naik, tidak pernah dipakai ulang.
        $urutan = self::where('ticket_code', 'like', 'KEL-'.$tanggal.'-%')->count() + 1;

        // str_pad menambahkan nol di depan sampai panjangnya empat angka,
        // jadi 7 ditulis 0007 dan daftarnya rapi ketika diurutkan.
        return 'KEL-'.$tanggal.'-'.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
    }
}
