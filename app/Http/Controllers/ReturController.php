<?php

namespace App\Http\Controllers;

// Baris use mendaftarkan kelas yang dipakai di berkas ini supaya cukup ditulis
// nama pendeknya. Order sampai TapTryCollect model Eloquent yang masing masing
// mewakili satu tabel, Request membawa data permintaan masuk, dan DB dipakai
// untuk menjalankan transaksi basis data.
use App\Models\Notification;
use App\Models\Order;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Models\TapTryCollect;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Retur dan penukaran barang.
 *
 * Colorbox TIDAK mengembalikan uang. Yang dilayani hanya penukaran barang:
 * tukar ukuran, tukar warna, atau tukar produk lain yang harganya sama.
 * Karena itu pengajuan retur selalu bertipe tukar, dan yang ditanyakan
 * kepada pelanggan adalah mau ditukar dengan apa.
 *
 * Barangnya bisa sampai ke Colorbox lewat dua jalan:
 *
 * 1. Diantar sendiri ke toko. Pelanggan memilih toko beserta hari dan jam
 *    kedatangannya, lalu petugas toko memeriksa barangnya langsung.
 *
 * 2. Dikirim lewat jasa kirim. Sesudah pengajuannya disetujui, pelanggan
 *    membawa paketnya ke kantor jasa kirim, lalu memasukkan nomor resi yang
 *    didapat dari kantor itu ke halaman rincian retur. Nomor resi itulah
 *    bukti barangnya benar benar dikirim, dan gudang Colorbox memakainya
 *    untuk menanti kedatangan paketnya. Ongkos kirimnya ditanggung Colorbox
 *    dan diganti bersama barang penggantinya.
 *
 * Ada dua asal pembelian yang dilayani:
 * 1. pesanan daring, dikenali dari kode pesanan (contoh CBX-20260720-0001),
 * 2. pembelian di toko lewat Tap Try and Collect, dikenali dari kode pembelian
 *    yang tertera pada struk.
 *
 * Karena keduanya memakai satu alamat formulir yang sama, kode pada alamat
 * halaman dibaca sebagai teks biasa lalu dicari sendiri di controller ini.
 */
class ReturController extends Controller
{
    /**
     * Foto bukti paling banyak empat berkas.
     *
     * Dinaikkan dari satu, sebab keluhan yang paling sering, yaitu
     * ukuran tidak sesuai dan jahitan lepas, menuntut sudut pandang yang
     * berbeda beda: label ukurannya, bagian yang rusak, dan keseluruhan
     * barangnya. Empat sudah cukup untuk itu, dan lebih dari empat hanya
     * membuat petugas menggulir tanpa belajar apa apa yang baru.
     */
    public const FOTO_PALING_BANYAK = 4;

    // Dipanggil route GET /retur bernama retur.index, di dalam kelompok
    // middleware auth jadi hanya untuk pelanggan yang sudah masuk.
    // Menghasilkan view retur.index.
    public function index()
    {
        // auth()->user() mengambil baris tabel users milik pelanggan yang
        // sedang masuk sebagai objek model User.
        $pengguna = auth()->user();

        $pesanan = Order::where('user_id', $pengguna->id)
            // whereIn() berarti statusnya salah satu dari dua teks itu.
            // DELIVERED berarti sudah diantar, PICKED_UP sudah diambil di
            // toko. Pesanan yang belum sampai tidak bisa diretur.
            ->whereIn('status', ['DELIVERED', 'PICKED_UP'])
            ->with('item')
            ->orderByDesc('id')
            ->get()
            // filter() dipanggil SESUDAH get(), jadi penyaringannya terjadi di
            // PHP bukan di basis data. Dipakai begitu karena batas waktu retur
            // dihitung dari pengaturan, bukan dari kolom tabel.
            // Tanya titik dua tanpa bagian tengah: pakai completed_at, tapi
            // kalau kosong pakai updated_at sebagai acuan waktunya.
            ->filter(fn ($p) => ! $this->lewatBatasRetur($p->completed_at ?: $p->updated_at))
            // values() menomori ulang kunci lariknya dari 0, karena filter()
            // meninggalkan lubang pada nomor kunci yang lama.
            ->values();

        // view() mencari berkas resources/views/retur/index.blade.php
        return view('retur.index', [
            // Daftar pengajuan retur yang pernah dibuat pelanggan ini.
            'daftar' => ReturnRequest::where('user_id', $pengguna->id)
                // Eager loading supaya view tidak memanggil basis data
                // berulang kali di dalam loop.
                ->with(['pesanan', 'toko', 'item'])
                ->orderByDesc('id')
                ->get(),
            'pesananBisaRetur' => $pesanan,
            // setting() membaca pengaturan dari tabel settings, angka 7 nilai
            // cadangan kalau admin belum mengisinya.
            'hariRetur' => (int) setting('return_window_days', 7),
        ]);
    }

    /** Mencari struk pembelian di toko memakai kode pembelian. */
    // Dipanggil route POST /retur/cari-struk bernama retur.cari-struk, di
    // dalam kelompok middleware auth. Mengalihkan ke retur.create kalau
    // kodenya ditemukan, atau kembali ke halaman retur dengan pesan gagal.
    public function cariStruk(Request $request)
    {
        // string berarti harus teks, max:40 batas panjangnya 40 huruf.
        $data = $request->validate([
            'purchase_code' => ['required', 'string', 'max:40'],
        ], [
            'purchase_code.required' => 'Masukkan kode pembelian yang tertera pada struk.',
        ]);

        // trim() membuang spasi di awal dan akhir, strtoupper() mengubahnya
        // jadi huruf besar semua. Jadi pelanggan boleh menulis huruf kecil
        // atau terbawa spasi saat mengetik dari struk.
        $kode = strtoupper(trim($data['purchase_code']));

        // where('user_id', auth()->id()) inilah yang mencegah pelanggan
        // mencari struk pembelian milik orang lain.
        $booking = TapTryCollect::where('user_id', auth()->id())
            ->where('purchase_code', $kode)
            ->first();

        if (! $booking) {
            return back()->with('gagal', 'Kode pembelian '.$kode.' tidak ditemukan pada akun Anda. Periksa kembali struk Anda.');
        }

        // Yang dikirim ke routenya KODEnya, bukan idnya. Karena itu parameter
        // create() di bawah bertipe teks biasa, bukan route model binding.
        return redirect()->route('retur.create', $kode);
    }

    /**
     * Formulir pengajuan retur. Parameter $order berisi kode pesanan daring
     * atau kode pembelian di toko.
     */
    // Dipanggil route GET /retur/ajukan/{order} bernama retur.create, di
    // dalam kelompok middleware auth. Menghasilkan view retur.form, atau
    // mengalihkan ke retur.index kalau sudah lewat batas waktu returnya.
    //
    // Perhatikan: parameternya string $order, BUKAN Order $order. Jadi TIDAK
    // ada route model binding di sini. Itu disengaja, karena nilai di alamat
    // bisa berupa kode pesanan daring maupun kode pembelian di toko, yang
    // berasal dari dua tabel berbeda. Jadi pencariannya dilakukan sendiri.
    public function create(Request $request, string $order)
    {
        $kode = strtoupper(trim($order));
        // Dicoba dulu sebagai kode pesanan daring di tabel orders.
        $pesanan = $this->cariPesanan($kode);
        // Kalau pesanannya ketemu, pencarian booking dilewati dan isinya null.
        // Kalau tidak ketemu, baru dicoba sebagai kode pembelian di toko.
        $booking = $pesanan ? null : $this->cariBooking($kode);

        // Tidak ketemu di kedua tabel. Dipakai 404 supaya orang luar tidak
        // bisa menebak kode pesanan mana saja yang ada di sistem. Kedua method
        // pencarian itu sudah menyaring berdasarkan pemiliknya.
        if (! $pesanan && ! $booking) {
            abort(404);
        }

        // periksaBatas() memulangkan teks pesan penolakan, atau null kalau
        // pembeliannya masih boleh diretur.
        $penolakan = $this->periksaBatas($pesanan, $booking);
        if ($penolakan) {
            return redirect()->route('retur.index')->with('gagal', $penolakan);
        }

        // Tanggal yang sedang dilihat jadwalnya. Dibaca dari alamat halaman,
        // jadi memilih tanggal cukup memuat ulang halaman ini dengan tanggal
        // yang baru, sama seperti Tap Try and Collect. Kalau tanggal yang
        // diminta tidak ada di daftar, misalnya karena hari ini jamnya sudah
        // habis sejak halaman tadi dibuka, yang dipakai tanggal pertama yang
        // masih boleh dipilih.
        $daftarTanggal = $this->tanggalAntar();
        $tanggalTerpilih = $request->query('tanggal');

        if (! in_array($tanggalTerpilih, $daftarTanggal, true)) {
            $tanggalTerpilih = $daftarTanggal[0] ?? null;
        }

        // view() mencari berkas resources/views/retur/form.blade.php
        return view('retur.form', [
            'kode' => $kode,
            // Salah satu dari keduanya pasti null. View memakai itu untuk
            // menentukan tampilan asal pembeliannya.
            'pesanan' => $pesanan,
            'booking' => $booking,
            // daftarBarang() menyeragamkan bentuk data dari kedua asal
            // pembelian, jadi viewnya cukup satu bentuk saja.
            'barang' => $this->daftarBarang($pesanan, $booking),
            // Toko tujuan pengembalian.
            //
            // Pembelian lewat Tap Try and Collect WAJIB dikembalikan ke toko
            // tempat barangnya dulu diambil. Alasannya bukan soal aturan,
            // melainkan soal angka: barang itu dulu keluar dari rak toko
            // tersebut. Kalau dikembalikan ke toko lain, rak toko asal
            // kekurangan satu dan rak toko penerima kelebihan satu, lalu
            // selisihnya tidak bisa ditelusuri siapa pun.
            //
            // Pesanan daring barangnya keluar dari gudang pusat, jadi boleh
            // dikembalikan ke toko mana saja yang paling dekat.
            'tokoWajib' => $booking?->toko,
            'daftarToko' => $booking?->toko
                ? collect([$booking->toko])
                : Store::urutkanTerdekat(
                    Store::where('is_active', true)->get(),
                    titik_pelanggan(),
                    $request->user()?->alamatUtama()?->city
                ),
            // Tanggal yang boleh dipilih untuk mengantar barang ke toko.
            'daftarTanggal' => $daftarTanggal,
            /*
             * Hari ini hilang dari daftar tanggal karena batas pemesanan.
             *
             * Tanpa keterangan ini, tanggal hari ini sekadar TIDAK ADA di
             * layar, dan pelanggan yang membuka halaman pukul enam sore
             * tidak punya cara mengetahui kenapa. Dia akan mengira
             * halamannya rusak, atau mengira Colorbox tidak melayani
             * retur hari itu sama sekali.
             *
             * Jadi ketiadaannya dijelaskan: pemesanannya yang ditutup
             * pukul 15.00, bukan tokonya yang tutup. Tokonya tetap
             * melayani sampai pukul 21.00 untuk jadwal yang sudah
             * dipesan sebelumnya.
             */
            'pesanHariIniDitutup' => ! in_array(now()->toDateString(), $daftarTanggal, true),
            'batasPesanRetur' => (string) setting('retur_same_day_cutoff', '15:00'),
            'layananReturSampai' => (string) setting('retur_last_slot', '21:00'),
            // Tanggal yang sedang dilihat jadwalnya. Diambil dari alamat
            // halaman supaya memilih tanggal cukup memuat ulang halaman,
            // sama seperti Tap Try and Collect.
            'tanggalTerpilih' => $tanggalTerpilih,
            // Jam kedatangan untuk tanggal itu, lengkap dengan keadaannya.
            // Jam yang sudah lewat ikut dikirim tetapi ditandai tertutup,
            // supaya pelanggan melihatnya tercoret dan tahu sebabnya.
            'jadwal' => $this->jadwalAntar($tanggalTerpilih),
            'hariRetur' => (int) setting('return_window_days', 7),
            // Tanggal paling akhir barangnya harus SUDAH SAMPAI di Colorbox.
            'batasTiba' => $this->batasTiba($pesanan, $booking),
        ]);
    }

    /** Menyimpan pengajuan retur. */
    // Dipanggil route POST /retur/ajukan/{order} bernama retur.store, di
    // dalam kelompok middleware auth. Mengalihkan ke retur.show kalau
    // berhasil, atau kembali ke formulirnya kalau ada yang salah.
    public function store(Request $request, string $order)
    {
        // Pencarian dan pemeriksaan batas DIULANG di sini, tidak percaya pada
        // hasil create() tadi. Sebab antara formulirnya dibuka dan dikirim,
        // batas waktu returnya bisa sudah lewat.
        $kode = strtoupper(trim($order));
        $pesanan = $this->cariPesanan($kode);
        $booking = $pesanan ? null : $this->cariBooking($kode);

        if (! $pesanan && ! $booking) {
            abort(404);
        }

        $penolakan = $this->periksaBatas($pesanan, $booking);
        if ($penolakan) {
            return redirect()->route('retur.index')->with('gagal', $penolakan);
        }

        $data = $request->validate([
            // array berarti kirimannya harus larik, min:1 paling sedikit satu
            // barang dicentang.
            'item' => ['required', 'array', 'min:1'],
            // Tanda bintang sesudah titik berarti aturannya berlaku untuk
            // SETIAP anggota larik itu.
            // Kuncinya sekarang berbentuk teks, misalnya "48-2", yaitu nomor
            // baris pesanan diikuti nomor satuannya. Jadi dua potong baju
            // yang sama persis tetap punya kunci yang berbeda.
            'item.*' => ['required', 'string', 'max:30'],
            'jumlah' => ['nullable', 'array'],
            'jumlah.*' => ['nullable', 'integer', 'min:1', 'max:20'],
            'reason' => ['required', 'string', 'max:191'],
            // Pilihan pengembalian dana sudah tidak ada. Yang ditanyakan
            // sekarang mau ditukar dengan apa.
            'exchange_type' => ['required', 'in:ganti_ukuran,ganti_warna,ganti_produk,barang_rusak'],
            'exchange_note' => ['nullable', 'string', 'max:191'],
            'method' => ['required', 'in:kurir,toko'],
            // required_if:method,toko berarti isian itu wajib HANYA kalau
            // barangnya diantar sendiri ke toko.
            'store_id' => ['required_if:method,toko', 'nullable', 'integer', 'exists:stores,id'],
            'return_date' => ['required_if:method,toko', 'nullable', 'date_format:Y-m-d'],
            'return_time' => ['required_if:method,toko', 'nullable', 'date_format:H:i'],
            // Nama jasa kirim TIDAK ditanyakan di sini, dan itu disengaja.
            // Jasa kirim yang ada berbeda di tiap daerah, dan pelanggan baru
            // tahu dia memakai yang mana ketika sudah sampai di kantornya.
            // Memaksanya memilih sekarang hanya membuat catatannya salah.
            // Nama jasa kirimnya diisi belakangan bersama nomor resi, pada
            // halaman rincian retur, sesudah paketnya benar benar dikirim.
            'courier' => ['nullable', 'string', 'max:40'],
            /*
             * BUKTI: SATU SAMPAI EMPAT FOTO, DAN SATU VIDEO UNBOXING.
             *
             * Keduanya WAJIB, masing masing karena alasan yang berbeda.
             *
             * Fotonya wajib sebab tanpa gambar, petugas tidak punya
             * dasar apa pun untuk menilai pengajuannya, dan pelanggan
             * harus bolak balik ditanya. Jumlahnya dinaikkan dari satu
             * menjadi paling banyak empat, sebab keluhan yang paling
             * sering menuntut sudut pandang yang berbeda: label
             * ukurannya, bagian yang rusak, dan keseluruhan barangnya.
             *
             * Videonya wajib sebab foto barang rusak TIDAK BISA
             * membedakan dua hal yang akibatnya sangat berbeda: barang
             * yang memang datang rusak, dan barang yang rusak sesudah
             * dipakai. Rekaman pembukaan paket dari kardus masih utuh
             * sampai barangnya terlihat menjawab itu sekali jalan.
             *
             * Batas ukurannya ditulis dalam kilobita: 2048 sama dengan
             * 2 MB untuk tiap foto, dan 51200 sama dengan 50 MB untuk
             * videonya.
             */
            'photos' => ['required', 'array', 'min:1', 'max:'.self::FOTO_PALING_BANYAK],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'video' => ['required', 'file', 'mimetypes:video/mp4', 'max:51200'],
        ], [
            'item.required' => 'Pilih dulu barang yang ingin diretur.',
            'reason.required' => 'Tuliskan alasan pengajuan retur.',
            'exchange_type.required' => 'Pilih mau ditukar dengan apa.',
            'method.required' => 'Pilih cara mengembalikan barangnya.',
            'store_id.required_if' => 'Pilih toko tujuan pengembalian.',
            'return_date.required_if' => 'Pilih tanggal kedatangan Anda ke toko.',
            'return_time.required_if' => 'Pilih jam kedatangan Anda ke toko.',
            'photos.required' => 'Lampirkan foto barangnya supaya bisa diperiksa.',
            'photos.min' => 'Lampirkan paling sedikit satu foto barangnya.',
            'photos.max' => 'Foto paling banyak '.self::FOTO_PALING_BANYAK.' berkas.',
            'photos.*.image' => 'Berkas yang dilampirkan harus berupa foto.',
            'photos.*.mimes' => 'Foto harus berjenis JPG atau PNG.',
            'photos.*.max' => 'Satu foto paling besar 2 MB.',
            'video.required' => 'Lampirkan video pembukaan paketnya. Tanpa itu, barang yang '
                .'datang rusak tidak bisa dibedakan dari barang yang rusak sesudah dipakai.',
            'video.mimetypes' => 'Video harus berjenis MP4.',
            'video.max' => 'Video paling besar 50 MB.',
        ]);

        // Tanggal dan jam kedatangan diperiksa lagi di sini, tidak cukup
        // percaya pada bentuk tulisannya saja. Pelanggan bisa saja mengubah
        // nilai formulir lewat peramban, jadi tanggalnya wajib benar benar
        // termasuk tanggal yang ditawarkan, begitu pula jamnya.
        if ($data['method'] === 'toko') {
            // Pembelian Tap Try and Collect wajib dikembalikan ke toko tempat
            // barangnya dulu diambil. Diperiksa di server, bukan hanya
            // dikunci di layar, sebab nilai pilihan di formulir bisa diubah
            // siapa saja lewat peramban. Kalau pemeriksaan ini dilewati,
            // rak toko asal kekurangan satu barang tanpa sebab yang bisa
            // ditelusuri.
            if ($booking && (int) $data['store_id'] !== (int) $booking->store_id) {
                return back()->withInput()->with('gagal',
                    'Pembelian ini diambil di '.($booking->toko?->name ?: 'toko asalnya')
                    .', jadi pengembaliannya juga harus ke toko itu supaya catatan stoknya tetap cocok.');
            }

            if (! in_array($data['return_date'], $this->tanggalAntar(), true)) {
                return back()->withInput()->with('gagal', 'Tanggal kedatangan yang dipilih tidak tersedia. Silakan pilih tanggal lain.');
            }
            // Diperiksa terhadap TANGGAL yang dipilih, bukan terhadap daftar
            // jam begitu saja. Sebab jam yang sah untuk besok bisa saja sudah
            // lewat untuk hari ini, dan pemeriksaan di halaman tadi dilakukan
            // ketika halamannya dibuka, bukan ketika tombolnya ditekan.
            if (! $this->jamMasihTerbuka($data['return_date'], $data['return_time'])) {
                return back()->withInput()->with('gagal',
                    'Jam kedatangan yang dipilih sudah lewat atau tidak tersedia. Silakan pilih jam lain.');
            }
        }

        $barang = $this->daftarBarang($pesanan, $booking);
        $dipilih = [];

        // array_unique() menjaga supaya satu satuan barang tidak terkirim dua
        // kali, misalnya kalau tombolnya tertekan berulang.
        foreach (array_unique($data['item']) as $kunci) {
            // firstWhere() mencari di Collection yang sudah diambil, jadi
            // tidak menambah kueri. Pencocokan ini PENTING untuk keamanan:
            // barangnya wajib ada di pembelian INI, jadi orang tidak bisa
            // meretur barang pembelian lain dengan mengubah nilai form.
            $baris = $barang->firstWhere('kunci', (string) $kunci);
            if (! $baris) {
                // withInput() mengembalikan isian form supaya pelanggan tidak
                // perlu mengisi ulang alasan returnya dari awal.
                return back()->withInput()->with('gagal', 'Ada barang yang dipilih tidak terdapat pada pembelian ini.');
            }
            // Satu baris pilihan sama dengan SATU potong baju. Isian jumlah
            // sudah tidak ada lagi di formulirnya: pelanggan mencentang
            // satuan mana yang diretur, bukan mengetik angka.
            $baris['jumlah'] = 1;
            $dipilih[] = $baris;
        }

        // Foto dan video bukti disimpan ke folder public/images/retur.
        //
        // Langkah menyimpannya dikerjakan App\Services\SimpanBerkas, kelas
        // yang sama yang dipakai ulasan berfoto, supaya nama berkas, letak
        // folder, dan pembuatan foldernya tidak ditulis ulang dengan cara
        // yang sedikit berbeda di dua tempat.
        $daftarFoto = \App\Services\SimpanBerkas::simpanBanyak(
            $request->file('photos', []),
            'retur',
            'retur',
            self::FOTO_PALING_BANYAK
        );

        // Kolom photo yang lama tetap diisi foto pertama. Kolomnya belum
        // dihapus sebab pengajuan yang sudah ada menyimpan buktinya di
        // sana, dan slip retur masih membacanya.
        $foto = $daftarFoto[0] ?? null;

        $video = $request->hasFile('video')
            ? \App\Services\SimpanBerkas::simpan($request->file('video'), 'retur', 'retur-video')
            : null;

        // DB::transaction() menjalankan seluruh isi fungsi sebagai satu
        // kesatuan. Kalau gagal di tengah jalan, SEMUA perubahan dibatalkan,
        // jadi tidak ada pengajuan retur yang tercatat tanpa daftar barangnya.
        // Kata use membawa variabel dari luar ke dalam fungsi anonimnya.
        // Dihitung di luar transaksi supaya rumusnya cuma ada satu tempat,
        // yaitu method batasTiba() yang juga dipakai halaman formulirnya.
        $batasTiba = $this->batasTiba($pesanan, $booking);

        $retur = DB::transaction(function () use ($data, $dipilih, $pesanan, $booking, $foto, $daftarFoto, $video, $batasTiba) {
            // Kode retur: RTR-YYYYMMDD-0001 dari jumlah pengajuan hari ini + 1
            // whereDate() membandingkan hanya bagian tanggalnya, count()
            // menghitung jumlah barisnya, lalu ditambah satu.
            $urut = ReturnRequest::whereDate('created_at', now()->toDateString())->count() + 1;
            // str_pad() menambah nol di depan sampai panjangnya 4 huruf, jadi
            // 1 menjadi 0001. Hasilnya berpola RTR-20261025-0001.
            $kodeRetur = 'RTR-'.now()->format('Ymd').'-'.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);

            $retur = ReturnRequest::create([
                'return_code' => $kodeRetur,
                'user_id' => auth()->id(),
                // Tanda tanya diikuti panah ini operator aman null. Kalau
                // $pesanan bernilai null hasilnya langsung null, bukan
                // kesalahan. Isinya null untuk retur pembelian di toko.
                'order_id' => $pesanan?->id,
                // Dibaca: kalau pengembaliannya lewat toko pakai toko yang
                // dipilih pelanggan, kalau lewat jalur daring pakai toko asal
                // pembeliannya kalau ada.
                'store_id' => $data['method'] === 'toko' ? (int) $data['store_id'] : $booking?->store_id,
                // Isinya null untuk retur pesanan daring.
                'purchase_code' => $booking?->purchase_code,
                // Selalu tukar. Colorbox tidak melayani pengembalian uang,
                // jadi kolom ini tidak lagi diisi dari pilihan pelanggan.
                'type' => 'tukar',
                'exchange_type' => $data['exchange_type'],
                'exchange_note' => $data['exchange_note'] ?? null,
                'method' => $data['method'],
                // Tanggal dan jam hanya terisi untuk jalur antar ke toko.
                'return_date' => $data['method'] === 'toko' ? $data['return_date'] : null,
                'return_time' => $data['method'] === 'toko' ? $data['return_time'] : null,
                // Nama jasa kirim sengaja dikosongkan pada pengajuan baru.
                // Pelanggan bebas memakai jasa kirim mana saja, dan dia baru
                // tahu memakai yang mana ketika sudah sampai di kantornya.
                // Nama itu diisi belakangan bersama nomor resi.
                'courier' => null,
                'reason' => $data['reason'],
                'photo' => $foto,
                'photos' => $daftarFoto !== [] ? $daftarFoto : null,
                'video_path' => $video,
                // Tanggal paling akhir barangnya harus SUDAH SAMPAI di
                // Colorbox. Disalin ke baris ini, bukan dihitung ulang tiap
                // halaman dibuka, supaya pengajuan yang sudah berjalan tidak
                // ikut berubah batasnya kalau admin mengubah pengaturannya.
                'arrive_by' => $batasTiba,
                // Pengajuan baru selalu menunggu diperiksa tim dulu.
                'status' => 'menunggu_validasi',
            ]);

            foreach ($dipilih as $baris) {
                // Satu baris tabel return_items untuk tiap barang yang diretur.
                ReturnItem::create([
                    'return_request_id' => $retur->id,
                    // Isinya null untuk pembelian di toko, karena pembelian di
                    // toko tidak punya baris di tabel order_items.
                    'order_item_id' => $baris['order_item_id'],
                    'product_variant_id' => $baris['product_variant_id'],
                    // Nama produknya DISALIN ke sini, bukan cuma disimpan
                    // idnya. Jadi kalau admin mengubah nama produknya nanti,
                    // catatan retur lama tetap menunjukkan barang apa dulu.
                    'product_name' => $baris['nama'],
                    'quantity' => $baris['jumlah'],
                    'note' => $baris['ket'],
                ]);
            }

            // Objek returnya dipulangkan keluar dari transaksi.
            return $retur;
        });

        $this->kabariPengajuanBaru($retur);

        return redirect()->route('retur.show', $retur)
            ->with('berhasil', 'Pengajuan retur '.$retur->return_code.' terkirim dan sedang menunggu validasi.');
    }

    /**
     * Mengabari pelanggan dan petugas begitu pengajuan retur masuk.
     *
     * ---------------------------------------------------------------------
     * KENAPA KASIR TOKO TUJUAN IKUT DIKABARI
     * ---------------------------------------------------------------------
     *
     * Pelanggan yang memilih mengantar barangnya sendiri akan muncul di meja
     * kasir pada tanggal dan jam yang dia pilih. Kalau kasir baru tahu
     * ketika orangnya sudah berdiri di depan meja, dia harus membuka
     * pengajuannya dari nol sambil ditunggu, dan antrean di belakang ikut
     * menunggu.
     *
     * Jadi pemberitahuannya dikirim begitu formulirnya terkirim, lengkap
     * dengan tanggal dan jam kedatangannya, ke SELURUH kasir toko tujuan.
     * Dikirim ke semua kasir toko itu, bukan ke satu orang, sebab yang
     * berjaga pada jam kedatangannya belum tentu yang sedang bertugas
     * sekarang.
     *
     * Retur jalur jasa kirim tidak mengabari kasir mana pun, sebab barangnya
     * memang tidak menuju toko melainkan ke gudang pusat.
     */
    private function kabariPengajuanBaru(ReturnRequest $retur): void
    {
        // Tanda terima untuk pelanggan. Pengajuan yang terkirim tanpa jejak
        // apa pun membuat pelanggan ragu formulirnya benar benar masuk.
        Notification::create([
            'category' => 'retur',
            'user_id' => $retur->user_id,
            'title' => 'Pengajuan retur '.$retur->return_code.' terkirim',
            'body' => $retur->method === 'toko'
                ? 'Kami tunggu kedatangan Anda di '.($retur->toko?->name ?: 'toko tujuan')
                    .' pada '.tanggal_id($retur->return_date).' pukul '.$retur->return_time.' WIB.'
                : 'Pengajuan Anda sedang diperiksa tim kami. Kami kabari lagi begitu hasilnya keluar.',
            'url' => route('retur.show', $retur),
        ]);

        if ($retur->method !== 'toko' || ! $retur->store_id) {
            return;
        }

        $kasir = User::where('role', 'kasir')
            ->where('store_id', $retur->store_id)
            ->get();

        foreach ($kasir as $petugas) {
            Notification::create([
                'category' => 'retur',
                'user_id' => $petugas->id,
                'title' => 'Pengajuan retur baru '.$retur->return_code,
                'body' => ($retur->pengguna?->name ?: 'Pelanggan').' mengajukan retur '
                    .$retur->item->count().' barang, datang '
                    .tanggal_id($retur->return_date).' pukul '.$retur->return_time.' WIB. '
                    .'Alasan: '.$retur->reason,
                'url' => route('kasir.retur.show', $retur),
            ]);
        }
    }

    // Dipanggil route GET /retur/{returnRequest} bernama retur.show, di dalam
    // kelompok middleware auth dan hanya pemilik pengajuannya. Menghasilkan
    // view retur.show.
    //
    // Parameter ReturnRequest $returnRequest itu route model binding: nama
    // variabelnya sama dengan nama parameter di routenya, jadi Laravel
    // otomatis mencari baris tabel return_requests yang idnya ada di alamat.
    public function show(ReturnRequest $returnRequest)
    {
        // Binding hanya memastikan barisnya ADA, bukan pemiliknya. Pemeriksaan
        // ini mencegah orang melihat pengajuan retur pelanggan lain cukup
        // dengan mengganti angka di alamat.
        if ($returnRequest->user_id !== auth()->id()) {
            abort(403);
        }

        // load() mengambil relasi sesudah objeknya ada, bedanya dengan with()
        // yang dipakai saat kueri pertama.
        $returnRequest->load(['pesanan', 'toko', 'item']);

        return view('retur.show', [
            'retur' => $returnRequest,
            // riwayatStatus() menyusun daftar perjalanan pengajuan ini dari
            // kolom kolom waktunya.
            'riwayat' => $this->riwayatStatus($returnRequest),
        ]);
    }

    // Dipanggil route PUT /retur/{returnRequest}/batal bernama retur.batal, di
    // dalam kelompok middleware auth dan hanya pemilik pengajuannya. Selalu
    // kembali ke halaman sebelumnya.
    public function batal(ReturnRequest $returnRequest)
    {
        if ($returnRequest->user_id !== auth()->id()) {
            abort(403);
        }

        // Pengajuan yang sudah diperiksa tim tidak boleh dibatalkan sendiri
        // oleh pelanggan, karena prosesnya mungkin sudah berjalan di toko.
        if ($returnRequest->status !== 'menunggu_validasi') {
            return back()->with('gagal', 'Pengajuan yang sudah diperiksa tim Colorbox tidak dapat dibatalkan sendiri.');
        }

        // update() langsung menyimpan perubahan ke tabel return_requests.
        // Barisnya TIDAK dihapus, hanya statusnya diubah, supaya riwayat
        // pengajuannya tetap terlihat pelanggan.
        $returnRequest->update(['status' => 'dibatalkan']);

        return back()->with('berhasil', 'Pengajuan retur '.$returnRequest->return_code.' dibatalkan.');
    }

    /**
     * Pelanggan menutup sendiri pengajuan returnya.
     *
     * ----------------------------------------------------------------
     * KENAPA PELANGGAN YANG MENUTUP
     * ----------------------------------------------------------------
     *
     * Dulu retur berhenti di status Sedang Diproses dan tidak pernah
     * berlanjut. Langkah sesudahnya tidak punya pemilik: admin dilarang
     * menutup retur yang diurus kasir toko, sedangkan kasir sudah selesai
     * bekerja begitu barang penggantinya diserahkan. Akibatnya pengajuan
     * menggantung di layar pelanggan tanpa ujung.
     *
     * Yang paling tahu urusannya benar benar beres adalah pelanggan, sebab
     * dialah yang memegang barang penggantinya. Jadi dialah yang menutup,
     * persis seperti pesanan biasa yang dikonfirmasi lewat tombol Barang
     * Sudah Diterima.
     *
     * Kalau sampai tiga hari tidak ditekan, sistem yang menutupnya lewat
     * perintah colorbox:perbarui, jadi retur tetap mustahil menggantung.
     *
     * Dipanggil route PUT /retur/{returnRequest}/konfirmasi bernama
     * retur.konfirmasi.
     */
    public function konfirmasi(ReturnRequest $returnRequest)
    {
        if ($returnRequest->user_id !== auth()->id()) {
            abort(403);
        }

        // Hanya pengajuan yang barang penggantinya memang sudah diserahkan
        // atau dikirim. Sebelum itu tidak ada yang bisa dikonfirmasi.
        if (! $returnRequest->menungguKonfirmasi()) {
            return back()->with('gagal',
                'Pengajuan ini belum sampai pada tahap konfirmasi. '
                .'Tombolnya muncul sesudah barang pengganti Anda diserahkan atau dikirim.');
        }

        $returnRequest->load('item');

        // Aturan penutupannya, termasuk pengembalian stoknya, tinggal di
        // satu tempat supaya mustahil berbeda dengan penutupan otomatis.
        (new \App\Services\PenutupanRetur)->tutup($returnRequest, 'pelanggan');

        return back()->with('berhasil',
            'Terima kasih. Retur '.$returnRequest->return_code.' sudah ditutup.');
    }

    /**
     * Slip pengembalian yang dicetak lalu ditempel di bungkus paket.
     *
     * Gunanya sederhana tetapi penting: paket retur yang sampai di gudang
     * tanpa keterangan apa apa tidak bisa dicocokkan dengan pengajuan siapa
     * pun. Admin hanya melihat sebuah kardus. Dengan slip ini,
     * nomor pengajuan, daftar barangnya, dan alamat pengirimnya menempel di
     * luar bungkus, jadi begitu paketnya dibuka sudah ketahuan milik siapa.
     *
     * Halaman ini sengaja berdiri sendiri, tanpa header dan footer situs,
     * supaya hasil cetaknya bersih dan muat satu halaman A4.
     *
     * Dipanggil route GET /retur/{returnRequest}/slip bernama retur.slip, di
     * dalam kelompok middleware auth dan hanya pemilik pengajuannya.
     */
    public function slip(\Illuminate\Http\Request $request, ReturnRequest $returnRequest)
    {
        if ($returnRequest->user_id !== auth()->id()) {
            abort(403);
        }

        // ?unduh=1 berarti pelanggan menekan tombol Unduh, bukan Cetak.
        // Bedanya cuma pada cara halaman yang sama ini diserahkan: dibuka
        // di tab baru, atau diturunkan sebagai berkas.
        $unduh = $request->boolean('unduh');

        // Slip hanya untuk jalur jasa kirim. Barang yang diantar sendiri ke
        // toko diserahkan langsung ke petugas, jadi tidak ada bungkus yang
        // perlu ditempeli apa apa.
        if ($returnRequest->method !== 'kurir') {
            return redirect()->route('retur.show', $returnRequest)
                ->with('ingat', 'Pengajuan ini diantar sendiri ke toko, jadi tidak perlu slip pengembalian.');
        }

        // Dan hanya sesudah disetujui. Mencetak slip lebih awal berarti
        // pelanggan berpeluang mengirim barangnya sebelum pengajuannya
        // diperiksa, lalu paketnya harus dibalikkan lagi.
        if (in_array($returnRequest->status, ['menunggu_validasi', 'ditolak', 'dibatalkan'], true)) {
            return redirect()->route('retur.show', $returnRequest)
                ->with('ingat', 'Slip pengembalian terbit setelah pengajuan Anda disetujui.');
        }

        // Titik titik relasi berarti relasi bertingkat, diambil sekaligus
        // supaya view tidak memanggil basis data berulang kali di dalam loop.
        $returnRequest->load(['pengguna', 'pesanan.alamat', 'item.varian.produk']);

        $halaman = view('retur.slip', [
            'retur' => $returnRequest,
            // Alamat pengirim diambil dari alamat pengiriman pesanannya,
            // sebab ke situlah barangnya dulu diantar. Kalau pesanannya dari
            // toko dan tidak punya alamat, dipakai alamat utama akunnya.
            'pengirim' => $returnRequest->pesanan?->alamat ?: $returnRequest->pengguna?->alamatUtama(),
            // Alamat gudang tujuan dibaca dari pengaturan, jadi admin bisa
            // mengubahnya tanpa menyentuh kode.
            'tujuan' => [
                'nama' => setting('return_name', 'Gudang Retur Colorbox'),
                'alamat' => setting('return_address',
                    'Jl. Daan Mogot KM 12 No. 9, Cengkareng, Jakarta Barat 11730'),
                'telepon' => setting('return_phone', '021 5555 1234'),
            ],
        ]);

        if (! $unduh) {
            return $halaman;
        }

        /*
         * DIUNDUH SEBAGAI BERKAS, BUKAN CUMA DIBUKA DI TAB BARU.
         *
         * Pelanggan yang diminta "unduh formulir retur" mengharapkan ada
         * berkas yang benar benar turun ke folder unduhannya, bisa dibuka
         * lagi besok, dan bisa dikirim ke orang lain. Tombol yang cuma
         * membuka halaman baru menyelesaikan setengah pekerjaan: begitu
         * tabnya ditutup, dokumennya hilang.
         *
         * Yang diturunkan berkas HTML, bukan PDF. Alasannya jujur saja:
         * PDF menuntut pustaka tambahan yang harus ikut dipasang di
         * komputer mana pun proyek ini dijalankan, dan menambah
         * ketergantungan baru menjelang peragaan lebih berisiko daripada
         * manfaatnya. Berkas HTML ini sudah membawa seluruh gayanya di
         * dalam, terbuka di peramban mana pun tanpa sambungan internet,
         * dan sudah disiapkan untuk dicetak lewat aturan media print di
         * dalamnya. Dari sana siapa pun bisa menyimpannya sebagai PDF
         * lewat menu cetak peramban.
         *
         * Nama berkasnya memakai nomor returnya supaya pelanggan yang
         * punya beberapa pengajuan tidak tertukar.
         */
        return response($halaman->render(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Formulir-Retur-'
                .$returnRequest->return_code.'.html"',
        ]);
    }

    /**
     * Pelanggan memasukkan nomor resi sesudah mengirim barangnya sendiri.
     *
     * Inilah yang membuat jalur jasa kirim bisa berjalan tanpa petugas
     * menebak nebak. Urutannya: pengajuan disetujui, pelanggan membawa
     * paketnya ke kantor jasa kirim, kantornya memberi nomor resi, lalu nomor itu
     * dimasukkan di sini. Begitu nomornya masuk, status pengajuan berpindah
     * menjadi dikirim dan gudang Colorbox tahu ada paket yang ditunggu.
     *
     * Dipanggil route PUT /retur/{returnRequest}/resi bernama retur.resi, di
     * dalam kelompok middleware auth dan hanya pemilik pengajuannya.
     */
    public function resi(Request $request, ReturnRequest $returnRequest)
    {
        // Binding hanya memastikan barisnya ada, pemiliknya diperiksa di sini.
        if ($returnRequest->user_id !== auth()->id()) {
            abort(403);
        }

        // Nomor resi hanya masuk akal untuk jalur jasa kirim. Barang yang
        // diantar sendiri ke toko tidak punya nomor resi sama sekali.
        if ($returnRequest->method !== 'kurir') {
            return back()->with('gagal', 'Pengajuan ini diantar sendiri ke toko, jadi tidak perlu nomor resi.');
        }

        // Nomor resi baru boleh dimasukkan sesudah pengajuannya disetujui.
        // Kalau belum, barangnya bisa saja dikirim percuma dan harus dibalikkan.
        if ($returnRequest->status !== 'disetujui') {
            return back()->with('gagal', 'Nomor resi baru bisa dimasukkan setelah pengajuan Anda disetujui.');
        }

        $data = $request->validate([
            // Nomor resi jasa kirim panjangnya bermacam macam, jadi yang
            // dibatasi hanya panjang paling sedikit dan paling banyaknya.
            'tracking_number' => ['required', 'string', 'min:6', 'max:50'],
            // Nama jasa kirim ditanyakan DI SINI, bukan pada formulir
            // pengajuan. Baru pada titik inilah pelanggan tahu pasti dia
            // memakai jasa kirim yang mana, sebab paketnya sudah dikirim dan
            // struknya ada di tangan.
            'courier' => ['required', 'string', 'max:40'],
        ], [
            'tracking_number.required' => 'Masukkan nomor resi dari kantor jasa kirim.',
            'tracking_number.min' => 'Nomor resi sepertinya kurang lengkap. Periksa lagi struk dari kantornya.',
            'courier.required' => 'Tuliskan nama jasa kirim yang Anda pakai, sesuai struknya.',
        ]);

        // strtoupper() dan trim() merapikan tulisannya, sebab nomor resi
        // biasanya disalin dari struk dan sering terbawa spasi.
        $nomor = strtoupper(trim($data['tracking_number']));

        $returnRequest->update([
            'tracking_number' => $nomor,
            'courier' => trim($data['courier']),
            'shipped_at' => now(),
            // Status berpindah sendiri, pelanggan tidak perlu menunggu admin
            // menekan apa apa. Admin baru turun tangan ketika paketnya sampai.
            'status' => 'dikirim',
        ]);

        // Petugas perlu tahu ada paket yang sedang menuju gudang. Pemberitahuan
        // ini dikirim ke pelanggan sebagai tanda terima, sedangkan daftar retur
        // berstatus dikirim sudah terlihat sendiri di dashboard admin.
        Notification::create([
            'category' => 'retur',
            'user_id' => $returnRequest->user_id,
            'title' => 'Nomor resi retur tersimpan',
            'body' => 'Nomor resi '.$nomor.' untuk retur '.$returnRequest->return_code
                .' sudah tersimpan. Kami kabari lagi begitu paketnya sampai.',
            'url' => route('retur.show', $returnRequest),
        ]);

        return back()->with('berhasil', 'Nomor resi '.$nomor.' tersimpan. Kami tunggu paketnya.');
    }

    /**
     * Tanggal yang boleh dipilih untuk mengantar barang retur ke toko.
     *
     * Hari ini ikut ditawarkan selama masih ada satu jam yang belum lewat,
     * persis seperti Tap Try and Collect. Dulu daftarnya selalu mulai besok,
     * dan itu menolak pelanggan yang datang pagi hari padahal toko masih buka
     * sampai malam. Begitu jam terakhir hari ini sudah lewat, hari ini hilang
     * sendiri dari daftarnya.
     *
     * Banyaknya hari mengikuti pengaturan yang sama dengan jadwal layanan
     * lain, jadi tidak ada angka terpisah yang bisa ketinggalan.
     */
    private function tanggalAntar(): array
    {
        $jumlahHari = max(1, (int) setting('booking_window_days', 14));

        $daftar = [];

        // Hari ini hanya masuk daftar kalau jadwalnya masih menyisakan jam
        // yang bisa dipilih. terbuka() di bawah memeriksa satu per satu.
        $hariIni = now()->toDateString();

        foreach ($this->jadwalAntar($hariIni) as $jam) {
            if (! $jam['tertutup']) {
                $daftar[] = $hariIni;

                break;
            }
        }

        for ($i = 1; $i <= $jumlahHari; $i++) {
            $daftar[] = now()->addDays($i)->toDateString();
        }

        return $daftar;
    }

    /**
     * Jadwal jam kedatangan untuk satu tanggal, lengkap dengan keadaannya.
     *
     * Bentuk hasilnya sengaja disamakan dengan jadwal Tap Try and Collect,
     * yaitu daftar berisi jam, sampai, tertutup, dan sebab. Dengan begitu
     * tampilannya bisa memakai tampilan yang sama, dan jam yang sudah lewat
     * ikut tercoret sendiri.
     *
     * Bedanya dengan Tap Try and Collect cuma satu: di sini TIDAK ada kuota.
     * Menerima satu kantong barang retur tidak perlu antrean sendiri, dan
     * kuota palsu hanya akan menolak pelanggan tanpa alasan yang nyata.
     */
    private function jadwalAntar(?string $tanggal): array
    {
        if (! $tanggal) {
            return [];
        }

        // Jam yang masih boleh dipilih hari ini dihitung dari sekarang
        // ditambah waktu penyiapan, supaya petugas toko sempat bersiap dan
        // pelanggan tidak memilih jam yang tinggal beberapa menit lagi.
        $siap = max(0, (int) setting('ttc_prep_minutes', 30));
        $palingCepat = now()->addMinutes($siap);
        $hariIni = $tanggal === now()->toDateString();

        /*
         * BATAS PEMESANAN HARI YANG SAMA, pukul 15.00.
         *
         * -----------------------------------------------------------------
         * KENAPA BATAS PESANNYA BERBEDA DARI BATAS LAYANANNYA
         * -----------------------------------------------------------------
         *
         * Toko melayani retur sampai pukul 21.00, dan slot kedatangan di
         * bawah memang ditawarkan sampai jam itu. Tetapi memesannya
         * ditutup pukul 15.00.
         *
         * Dua angka yang berbeda itu disengaja. Menerima barang retur
         * bukan sekadar menerima kantong: kasir harus memeriksa barang
         * yang dibawa, mencocokkannya dengan pengajuan, lalu menyiapkan
         * barang penggantinya dari rak. Kalau pesanannya masuk pukul
         * 20.30 untuk kedatangan pukul 21.00, pekerjaan itu baru dimulai
         * menjelang toko tutup, dan yang terjadi di lapangan selalu sama:
         * pelanggan datang, barangnya belum siap, dan dia pulang dengan
         * tangan kosong.
         *
         * Jadi yang dibatasi KAPAN pelanggan boleh memesan, bukan sampai
         * jam berapa toko melayaninya. Yang memesan pukul 14.00 tetap
         * boleh memilih kedatangan pukul 20.00 di hari yang sama.
         *
         * Sesudah pukul 15.00, seluruh jam HARI INI tertutup, dan
         * pelanggan memilih tanggal berikutnya yang jamnya masih utuh.
         */
        $batasPesan = (string) setting('retur_same_day_cutoff', '15:00');
        $lewatBatasPesan = $hariIni
            && now()->gt(\Illuminate\Support\Carbon::parse(now()->toDateString().' '.$batasPesan));

        $daftar = [];

        foreach ($this->jamAntar() as $jam) {
            $mulai = \Illuminate\Support\Carbon::parse($tanggal.' '.$jam);

            // Hanya hari ini yang punya jam lewat. Tanggal berikutnya seluruh
            // jamnya masih di depan, jadi tidak ada yang perlu dicoret.
            $lewat = $hariIni && $mulai->lt($palingCepat);

            // Dua sebab yang berbeda, dan tulisannya memang harus berbeda.
            // "Sudah lewat" berarti jamnya sudah berlalu, dan pelanggan
            // bisa memilih jam yang lebih sore. "Pemesanan hari ini sudah
            // ditutup" berarti tidak ada jam hari ini yang bisa dipilih
            // sama sekali, dan pelanggan harus berpindah tanggal. Kalau
            // keduanya ditulis sama, orang akan terus mencoba jam yang
            // lebih malam dan selalu ditolak tanpa tahu sebabnya.
            if ($lewatBatasPesan) {
                $tertutup = true;
                $sebab = 'Pemesanan hari ini sudah ditutup pukul '.$batasPesan;
            } else {
                $tertutup = $lewat;
                $sebab = $lewat ? 'Sudah lewat' : null;
            }

            $daftar[] = [
                'jam' => $jam,
                'sampai' => sprintf('%02d:00', ((int) substr($jam, 0, 2)) + 1),
                'tertutup' => $tertutup,
                'sebab' => $sebab,
            ];
        }

        return $daftar;
    }

    /** Jam itu masih boleh dipilih pada tanggal itu? */
    private function jamMasihTerbuka(string $tanggal, string $jam): bool
    {
        foreach ($this->jadwalAntar($tanggal) as $satu) {
            if ($satu['jam'] === $jam) {
                return ! $satu['tertutup'];
            }
        }

        return false;
    }

    /**
     * Jam kedatangan yang ditawarkan, per satu jam dari jam buka sampai jam
     * tutup toko.
     *
     * Berbeda dengan Tap Try and Collect, jam di sini TIDAK memakai kuota.
     * Menerima satu kantong barang retur tidak perlu antrean sendiri, dan
     * kuota palsu hanya akan menolak pelanggan tanpa alasan yang nyata.
     */
    private function jamAntar(): array
    {
        $cadangan = config('colorbox.slot.jam_ttc_cadangan', ['buka' => '10:00', 'tutup' => '22:00']);
        $buka = \Illuminate\Support\Carbon::parse($cadangan['buka']);
        $tutup = \Illuminate\Support\Carbon::parse($cadangan['tutup']);

        // Jam mulai slot terakhir, dibaca dari pengaturan supaya admin bisa
        // memendekkan jam layanan retur tanpa mengubah kode. Dibatasi juga
        // oleh jam tutup toko, jadi pengaturan yang keliru diisi lewat dari
        // jam tutup tidak menghasilkan slot yang tokonya sudah gelap.
        $slotTerakhir = \Illuminate\Support\Carbon::parse((string) setting('retur_last_slot', '21:00'));

        $jam = [];
        $jalan = $buka->copy();
        // Slot terakhir dimulai satu jam sebelum toko tutup, jadi penyerahan
        // barangnya tidak mepet dengan waktu tutup.
        while ($jalan->copy()->addHour()->lte($tutup) && $jalan->lte($slotTerakhir)) {
            $jam[] = $jalan->format('H:i');
            $jalan->addHour();
        }

        return $jam;
    }

    /** Mencari pesanan daring milik pengguna dari kodenya. */
    // Kata private berarti hanya bisa dipanggil dari dalam kelas ini. Tanda
    // tanya di depan Order berarti hasilnya boleh objek Order ATAU null.
    private function cariPesanan(string $kode): ?Order
    {
        // Syarat user_id inilah penjaga haknya: pencariannya hanya di antara
        // pesanan milik pelanggan yang sedang masuk. Jadi orang tidak bisa
        // meretur pesanan orang lain walaupun kodenya ketahuan.
        return Order::where('user_id', auth()->id())
            // Dicari lewat kolom order_code, bukan kolom id.
            ->where('order_code', $kode)
            ->with(['item.varian', 'toko'])
            ->first();
    }

    /** Mencari pembelian di toko milik pengguna dari kode pembeliannya. */
    private function cariBooking(string $kode): ?TapTryCollect
    {
        // Penjaga hak yang sama seperti cariPesanan(), hanya tabelnya berbeda
        // yaitu tap_try_collects dengan kolom purchase_code.
        return TapTryCollect::where('user_id', auth()->id())
            ->where('purchase_code', $kode)
            ->with(['item.varian.produk', 'toko'])
            ->first();
    }

    /** Menyusun daftar barang yang boleh diretur dari kedua asal pembelian. */
    // Dua parameternya boleh null, dan memang hanya salah satu yang terisi.
    // Tugas method ini menyeragamkan bentuk data dari dua asal pembelian yang
    // berbeda, supaya view dan store() cukup mengurus satu bentuk saja.
    /**
     * Berapa potong tiap baris pesanan yang SUDAH pernah diretur.
     *
     * Hasilnya larik berkunci nomor baris pesanan, nilainya jumlah potong
     * yang sudah diajukan dan pengajuannya belum ditolak atau dibatalkan.
     *
     * Gunanya: pelanggan yang membeli dua potong baju yang sama lalu
     * mereturkan satu, masih boleh mereturkan yang satunya, tetapi tidak
     * boleh mereturkan potong yang sama dua kali. Tanpa perhitungan ini,
     * satu baju bisa ditukar berkali kali dan stoknya bertambah dari
     * barang yang tidak pernah ada.
     *
     * Pengajuan yang ditolak dan dibatalkan sengaja TIDAK dihitung, sebab
     * bajunya memang tidak jadi ditukar dan masih di tangan pelanggan.
     */
    private function sudahDiretur(Order $pesanan): array
    {
        return \App\Models\ReturnItem::query()
            ->whereNotNull('order_item_id')
            ->whereIn('order_item_id', $pesanan->item->pluck('id'))
            ->whereHas('retur', fn ($q) => $q->whereNotIn('status', ['ditolak', 'dibatalkan']))
            ->selectRaw('order_item_id, SUM(quantity) AS jumlah')
            ->groupBy('order_item_id')
            ->pluck('jumlah', 'order_item_id')
            ->map(fn ($jumlah) => (int) $jumlah)
            ->all();
    }

    private function daftarBarang(?Order $pesanan, ?TapTryCollect $booking)
    {
        // collect() tanpa isi memberi Collection kosong yang siap diisi.
        $barang = collect();

        if ($pesanan) {
            // Satuan yang SUDAH pernah diretur dan returnya tidak ditolak.
            // Dihitung per baris pesanan, supaya baju kedua tetap bisa
            // diretur sesudah baju pertama diajukan, tetapi baju yang sama
            // tidak bisa diajukan dua kali.
            $sudahDiretur = $this->sudahDiretur($pesanan);

            foreach ($pesanan->item as $baris) {
                $sisa = (int) $baris->quantity - (int) ($sudahDiretur[$baris->id] ?? 0);

                /*
                 * SATU BARIS SAMA DENGAN SATU POTONG BAJU.
                 *
                 * Dulu satu baris pesanan berisi dua potong tampil sebagai
                 * satu baris saja beserta isian jumlah. Pelanggan yang cuma
                 * ingin menukar salah satu potongnya bingung: yang mana yang
                 * dia maksud, dan apakah yang satunya ikut terbawa.
                 *
                 * Sekarang tiap potongnya digambar sendiri sendiri dengan
                 * kunci yang berbeda, yaitu nomor baris pesanan diikuti nomor
                 * urut potongnya. Jadi dua baju yang jenisnya sama persis
                 * tetap bisa dibedakan, dan yang diretur benar benar yang
                 * dicentang.
                 */
                for ($nomor = 1; $nomor <= $sisa; $nomor++) {
                    $barang->push([
                        // Kunci penanda yang dikirim form lalu dicocokkan lagi
                        // di store(). Bentuknya "48-2": baris pesanan nomor 48,
                        // potong kedua.
                        'kunci' => $baris->id.'-'.$nomor,
                        'order_item_id' => $baris->id,
                        'product_variant_id' => $baris->product_variant_id,
                        // Nama produknya dibaca dari order_items, bukan dari
                        // tabel products, karena di situ tersimpan nama saat
                        // dulu dibeli.
                        'nama' => $baris->product_name,
                        // trim() dengan argumen kedua membuang huruf yang
                        // disebut itu dari ujung teks, jadi ukuran yang kosong
                        // tidak meninggalkan garis miring menggantung.
                        'ket' => trim($baris->color.' / '.$baris->size, ' /'),
                        'gambar' => $baris->image_path,
                        'maks' => 1,
                        // Dipakai view menulis "potong ke-2 dari 3".
                        'nomor' => $nomor,
                        'dari' => (int) $baris->quantity,
                    ]);
                }
            }

            // return lebih awal, jadi blok booking di bawah tidak dijalankan.
            return $barang;
        }

        if ($booking) {
            foreach ($booking->item as $baris) {
                // Hanya barang yang BENAR BENAR dibeli yang bisa diretur.
                // Pada Tap Try and Collect, barang bisa cuma dicoba lalu tidak
                // dibeli, dan yang seperti itu tidak ada yang perlu diretur.
                if (! $baris->purchased || ! $baris->varian) {
                    continue;
                }
                $produk = $baris->varian->produk;

                // Dipecah per satuan, sama seperti pesanan daring di atas.
                for ($nomor = 1; $nomor <= (int) $baris->quantity; $nomor++) {
                    $barang->push([
                        // Pembelian di toko tidak punya baris order_items,
                        // jadi yang dipakai nomor variannya diikuti nomor
                        // urut potongnya.
                        'kunci' => $baris->product_variant_id.'-'.$nomor,
                        'order_item_id' => null,
                        'product_variant_id' => $baris->product_variant_id,
                        // Dirangkai: ambil nama produknya, tapi kalau
                        // produknya sudah dihapus pakai tulisan umum.
                        'nama' => $produk?->name ?: 'Produk Colorbox',
                        // label() menggabungkan warna dan ukuran jadi satu
                        // tulisan, misalnya Hitam / L.
                        'ket' => $baris->varian->label(),
                        'gambar' => $produk?->gambarUtama()?->path,
                        'maks' => 1,
                        'nomor' => $nomor,
                        'dari' => (int) $baris->quantity,
                    ]);
                }
            }
        }

        return $barang;
    }

    /**
     * Memeriksa apakah pembelian masih boleh diretur. Mengembalikan pesan
     * penolakan, atau null bila masih boleh.
     */
    // Memulangkan TEKS pesan penolakan, atau null kalau returnya masih boleh.
    // Jadi pemanggilnya cukup memeriksa: ada isinya berarti ditolak.
    private function periksaBatas(?Order $pesanan, ?TapTryCollect $booking): ?string
    {
        $hari = (int) setting('return_window_days', 7);

        if ($pesanan) {
            // sudahDiterima() method di model Order. Retur hanya bisa diajukan
            // sesudah barangnya benar benar sampai ke pelanggan.
            if (! $pesanan->sudahDiterima()) {
                return 'Retur dapat diajukan setelah pesanan '.$pesanan->order_code.' diterima.';
            }
            // Tanya titik dua tanpa bagian tengah: pakai completed_at, tapi
            // kalau kolomnya kosong pakai updated_at sebagai acuan waktunya.
            if ($this->lewatBatasRetur($pesanan->completed_at ?: $pesanan->updated_at)) {
                return 'Batas pengajuan retur '.$hari.' hari untuk pesanan '.$pesanan->order_code.' sudah lewat.';
            }

            // null berarti lolos semua pemeriksaan, returnya boleh dilanjutkan.
            return null;
        }

        if ($booking) {
            // COLLECTED berarti barangnya sudah diambil dan transaksinya
            // selesai di toko.
            if ($booking->status !== 'COLLECTED') {
                return 'Pembelian '.$booking->purchase_code.' belum tercatat selesai di toko.';
            }
            if ($this->lewatBatasRetur($booking->completed_at ?: $booking->updated_at)) {
                return 'Batas pengajuan retur '.$hari.' hari untuk pembelian '.$booking->purchase_code.' sudah lewat.';
            }
        }

        return null;
    }

    /** Batas waktu retur dihitung sejak barang diterima pelanggan. */
    private function lewatBatasRetur($tanggal): bool
    {
        // Tanggal kosong berarti tidak ada acuan kapan barangnya diterima.
        // Dianggap sudah lewat, yaitu pilihan yang aman: lebih baik menolak
        // lalu pelanggan menghubungi toko, daripada membuka retur yang
        // waktunya tidak bisa dibuktikan.
        if (! $tanggal) {
            return true;
        }

        $hari = (int) setting('return_window_days', 7);

        // copy() membuat salinan objek tanggalnya DULU. Penting, karena
        // addDays() mengubah objek aslinya. Tanpa copy(), tanggal yang
        // tersimpan di model pesanan ikut berubah dan perhitungan di tempat
        // lain bisa jadi salah. isPast() true kalau waktunya sudah lewat.
        return $tanggal->copy()->addDays($hari)->isPast();
    }

    /**
     * Tanggal paling akhir barang retur harus SUDAH SAMPAI di Colorbox.
     *
     * Aturannya tujuh hari sejak pelanggan menerima barang. Yang perlu
     * diperhatikan: tujuh hari itu dihitung sampai barangnya tiba, bukan
     * sampai pelanggan mengajukan dan bukan pula sampai pelanggan menyerahkan
     * paketnya ke kantor jasa kirim.
     *
     * Dibuat satu method tersendiri supaya rumusnya cuma ada di satu tempat.
     * Tanggal ini dipakai di tiga layar sekaligus: formulir pengajuan,
     * halaman rincian retur, dan slip yang ditempel di paket. Kalau
     * dihitung sendiri sendiri di tiap layar, cepat atau lambat ketiganya
     * akan menunjukkan tanggal yang berbeda.
     *
     * endOfDay() memundurkan jamnya ke 23:59, jadi paket yang tiba pada hari
     * terakhir tetap terhitung sempat, tidak dipotong tepat di jam
     * pengajuannya dulu.
     */
    private function batasTiba(?Order $pesanan, ?TapTryCollect $booking): ?\Illuminate\Support\Carbon
    {
        // Waktu barangnya diterima pelanggan, dari salah satu asal pembelian.
        $diterima = $pesanan
            ? ($pesanan->completed_at ?: $pesanan->updated_at)
            : ($booking ? ($booking->completed_at ?: $booking->updated_at) : null);

        if (! $diterima) {
            return null;
        }

        $hari = (int) setting('return_window_days', 7);

        // copy() dipanggil dulu supaya addDays() tidak mengubah tanggal yang
        // tersimpan di model pesanannya.
        return $diterima->copy()->addDays($hari)->endOfDay();
    }

    /** Riwayat status sederhana untuk halaman rincian retur. */
    // Riwayatnya disusun dari kolom kolom waktu di tabel return_requests,
    // bukan dari tabel riwayat terpisah. Jadi datanya tidak perlu dicatat dua
    // kali, cukup dibaca ulang setiap halaman dibuka.
    private function riwayatStatus(ReturnRequest $retur): array
    {
        // Dua tanda kurung siku berurutan berarti larik DI DALAM larik.
        // Anggota pertama ini pasti ada, karena setiap pengajuan tentu pernah
        // dikirim. Sisanya ditambahkan hanya kalau waktunya terisi.
        $riwayat = [[
            'status' => 'Pengajuan dikirim',
            'keterangan' => 'Pengajuan '.$retur->return_code.' diterima sistem.',
            'waktu' => $retur->created_at,
        ]];

        // Kolom approved_at hanya terisi kalau admin sudah menyetujuinya.
        if ($retur->approved_at) {
            $riwayat[] = [
                'status' => 'Pengajuan disetujui',
                'keterangan' => 'Tim Colorbox menyetujui pengajuan ini.',
                'waktu' => $retur->approved_at,
            ];
        }

        // Jalur jasa kirim: pelanggan sendiri yang mencatat kapan paketnya
        // dikirim, yaitu saat nomor resinya dimasukkan.
        if ($retur->shipped_at) {
            $riwayat[] = [
                'status' => 'Barang dikirim pelanggan',
                'keterangan' => 'Nomor resi '.$retur->tracking_number
                    .($retur->courier ? ' lewat '.$retur->courier : '').'.',
                'waktu' => $retur->shipped_at,
            ];
        }

        if ($retur->received_at) {
            $riwayat[] = [
                'status' => 'Barang diterima Colorbox',
                'keterangan' => 'Barangnya sudah sampai dan masuk antrean pemeriksaan.',
                'waktu' => $retur->received_at,
            ];
        }

        if ($retur->completed_at) {
            $riwayat[] = [
                'status' => 'Retur selesai',
                'keterangan' => $retur->replacement_tracking_number
                    ? 'Barang pengganti dikirim dengan resi '.$retur->replacement_tracking_number.'.'
                    : 'Penukaran barang sudah diselesaikan.',
                'waktu' => $retur->completed_at,
            ];
        }

        // Barang penggantinya sudah diserahkan atau dikirim, dan yang
        // ditunggu tinggal konfirmasi pelanggan. Ditulis sebagai barisnya
        // sendiri supaya jelas yang ditunggu siapa.
        if ($retur->menungguKonfirmasi()) {
            $riwayat[] = [
                'status' => 'Barang pengganti diserahkan',
                'keterangan' => $retur->replacement_tracking_number
                    ? 'Dikirim dengan resi '.$retur->replacement_tracking_number
                        .'. Konfirmasikan begitu barangnya sampai.'
                    : 'Konfirmasikan lewat tombol di halaman ini supaya pengajuannya ditutup.',
                'waktu' => $retur->updated_at,
            ];
        }

        // Menangkap status lain yang bukan selesai dan bukan menunggu, misalnya
        // ditolak atau dibatalkan, supaya pelanggan tetap melihat keterangannya.
        if (! $retur->completed_at && ! $retur->menungguKonfirmasi()
            && $retur->status !== 'menunggu_validasi') {
            $riwayat[] = [
                // str_replace() mengganti garis bawah dengan spasi, lalu
                // ucfirst() membesarkan huruf pertamanya. Jadi teks di basis
                // data menunggu_validasi ditampilkan Menunggu validasi.
                'status' => ucfirst(str_replace('_', ' ', $retur->status)),
                // Catatan admin dipakai kalau ada, kalau tidak pakai kalimat
                // umum supaya keteranganya tidak kosong.
                'keterangan' => $retur->admin_note ?: 'Status terakhir pengajuan ini.',
                'waktu' => $retur->updated_at,
            ];
        }

        // array_reverse() membalik urutan lariknya, jadi kejadian TERBARU
        // tampil di paling atas pada halaman rinciannya.
        return array_reverse($riwayat);
    }
}
