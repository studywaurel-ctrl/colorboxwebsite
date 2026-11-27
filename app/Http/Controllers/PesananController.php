<?php

namespace App\Http\Controllers;

// Baris use mendaftarkan kelas yang dipakai di berkas ini supaya cukup ditulis
// nama pendeknya. Cart mewakili tabel carts, CartItem tabel cart_items, Order
// tabel orders, TrackingEvent tabel tracking_events, Request membawa data
// permintaan masuk, dan DB dipakai untuk transaksi basis data.
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\TrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Daftar dan rincian pesanan milik pelanggan yang sedang masuk. */
class PesananController extends Controller
{
    /** Daftar status yang dipakai untuk menyaring pesanan. */
    // Ini properti kelas, yaitu variabel milik objek controllernya. Ditulis
    // private supaya hanya bisa dipakai dari dalam kelas ini. Daftar status
    // disimpan di satu tempat begini supaya kalau ada status baru, cukup
    // ditambahkan di sini dan semua yang memakainya ikut berubah.
    private array $daftarStatus = [
        'PENDING_PAYMENT', 'PAYMENT_CONFIRMED', 'PROCESSING', 'PACKED',
        'IN_TRANSIT', 'READY_FOR_PICKUP', 'DELIVERED', 'PICKED_UP', 'CANCELLED',
    ];

    // Dipanggil route GET /pesanan bernama pesanan.index, di dalam kelompok
    // middleware auth jadi hanya untuk pelanggan yang sudah masuk.
    // Menghasilkan view pesanan.index.
    public function index(Request $request)
    {
        // Pesanan pelanggan ini yang sudah lewat batas bayar dibatalkan dulu,
        // baru daftarnya diambil.
        //
        // Pembatalannya memang sudah ada di perintah berkala colorbox:perbarui,
        // tetapi perintah itu baru jalan kalau ada yang menjalankannya. Tanpa
        // baris ini, pesanan bulan lalu yang belum dibayar masih menampilkan
        // tombol Bayar Sekarang, dan dari luar sistemnya terlihat tidak punya
        // aturan batas waktu sama sekali. Yang diperiksa hanya pesanan milik
        // pelanggan yang sedang membuka halaman, jadi bebannya ringan.
        (new \App\Services\KedaluwarsaPesanan)->sapu(auth()->id());

        // query() membaca satu nilai dari query pada alamat, misalnya
        // /pesanan?status=DELIVERED. Hasilnya null kalau tidak dikirim.
        $status = $request->query('status');

        /*
         * SATU HALAMAN UNTUK DUA JENIS PEMBELIAN.
         *
         * Dulu pesanan daring ada di Pesanan Saya, sedangkan pembelian lewat
         * Tap Try and Collect ada di menu terpisah bernama Riwayat Tap Try
         * and Collect. Dari sisi pelanggan keduanya sama saja: dia membeli
         * baju di Colorbox. Memisahkannya ke dua menu membuat dia harus
         * ingat dulu dia dapat bajunya lewat cara yang mana sebelum bisa
         * mencarinya.
         *
         * Sekarang keduanya di satu halaman, dibedakan penyaring:
         *
         *   semua   dua duanya, diurutkan menurut waktunya
         *   online  pesanan yang dibayar di situs
         *   ttc     kunjungan Tap Try and Collect ke toko
         */
        /*
         * Dua tab, sama seperti pembagian yang dipakai aplikasi ritel lain:
         *
         *   aplikasi  pesanan yang dibayar di situs, dikirim atau diambil
         *   toko      pembelian lewat Tap Try and Collect di meja kasir
         *
         * Nama lamanya, yaitu online dan ttc, tetap diterima supaya tautan
         * yang terlanjur tersimpan di penanda peramban tidak mati.
         */
        $jenis = (string) $request->query('jenis', 'online');

        /*
         * Nama resminya sekarang online, bukan aplikasi.
         *
         * Colorbox dijual lewat situs, bukan lewat aplikasi yang dipasang
         * di ponsel. Menulis "Transaksi di Aplikasi" pada halaman yang
         * dibuka di peramban menjanjikan sesuatu yang tidak ada, dan
         * pelanggan yang membacanya akan mencari aplikasinya di toko
         * aplikasi lalu tidak menemukan apa apa.
         *
         * Nama lamanya tetap diterima supaya tautan yang terlanjur
         * tersimpan di penanda peramban tidak mati.
         */
        $samaArtinya = ['aplikasi' => 'online', 'ttc' => 'toko', 'semua' => 'online'];
        $jenis = $samaArtinya[$jenis] ?? $jenis;

        if (! in_array($jenis, ['online', 'toko'], true)) {
            $jenis = 'online';
        }

        /*
         * PENYARING TANGGAL.
         *
         * Pelanggan yang sudah setahun belanja punya daftar panjang, dan
         * yang dia cari biasanya pesanan minggu lalu. Tanpa penyaring
         * tanggal, satu satunya cara menemukannya adalah menggulir halaman
         * demi halaman. Pilihannya dibuat sedikit dan sudah jadi, persis
         * cara aplikasi ritel lain, supaya tidak perlu mengetik tanggal
         * untuk keperluan yang paling sering.
         *
         *   kosong  semua tanggal
         *   7       tujuh hari terakhir
         *   30      tiga puluh hari terakhir
         *   pilih   rentang yang diketik sendiri pada isian dari dan sampai
         */
        $tanggal = (string) $request->query('tanggal', '');
        if (! in_array($tanggal, ['', '7', '30', 'pilih'], true)) {
            $tanggal = '';
        }

        $dari = $this->tanggalAman($request->query('dari'));
        $sampai = $this->tanggalAman($request->query('sampai'));

        // Batas awal dan akhir yang benar benar dipakai menyaring. Keduanya
        // null berarti tidak ada batas, jadi semua tanggal ikut.
        $mulai = null;
        $akhir = null;

        if ($tanggal === '7' || $tanggal === '30') {
            // subDays() mundur sekian hari dari sekarang, startOfDay()
            // menggesernya ke pukul 00:00 supaya hari itu ikut utuh.
            $mulai = now()->subDays((int) $tanggal)->startOfDay();
        } elseif ($tanggal === 'pilih') {
            $mulai = $dari ? \Illuminate\Support\Carbon::parse($dari)->startOfDay() : null;
            // endOfDay() ke pukul 23:59:59 supaya pesanan sore di hari
            // terakhir tidak terpotong.
            $akhir = $sampai ? \Illuminate\Support\Carbon::parse($sampai)->endOfDay() : null;

            // Kalau tanggalnya terbalik, keduanya ditukar supaya hasilnya
            // tetap masuk akal, bukan kosong tanpa penjelasan.
            if ($mulai && $akhir && $mulai->greaterThan($akhir)) {
                [$mulai, $akhir] = [$akhir->copy()->startOfDay(), $mulai->copy()->endOfDay()];
            }
        }

        $pesanan = collect();

        if ($jenis !== 'toko') {
            $kueri = Order::where('user_id', auth()->id())
                // Eager loading supaya view tidak memanggil basis data
                // berulang kali untuk tiap pesanan di dalam loop.
                ->with(['item', 'pembayaran'])
                ->orderByDesc('created_at')->orderByDesc('id');

            // in_array() memastikan statusnya memang salah satu yang dikenal.
            // Pemeriksaan ini penting karena nilainya datang dari alamat.
            if ($status && in_array($status, $this->daftarStatus)) {
                $kueri->where('status', $status);
            }

            // when() menjalankan fungsi di dalamnya hanya kalau syaratnya
            // benar, jadi batas yang tidak diisi tidak ikut menyaring.
            $kueri->when($mulai, fn ($k) => $k->where('created_at', '>=', $mulai))
                ->when($akhir, fn ($k) => $k->where('created_at', '<=', $akhir));

            $pesanan = $kueri->get();
        }

        $kunjungan = collect();

        if ($jenis === 'toko') {
            // Penyaring status sengaja hanya berlaku untuk tab aplikasi,
            // sebab kode status kunjungan memang berbeda dan menyamakannya
            // berarti mengarang arti yang tidak ada.
            $kunjungan = \App\Models\TapTryCollect::where('user_id', auth()->id())
                ->with(['slot', 'toko', 'item.varian.produk'])
                // Penyaring tanggalnya berlaku di kedua tab, sebab "belanja
                // bulan lalu" artinya sama saja mau di situs atau di toko.
                ->when($mulai, fn ($k) => $k->where('created_at', '>=', $mulai))
                ->when($akhir, fn ($k) => $k->where('created_at', '<=', $akhir))
                ->orderByDesc('id')->get();
        }

        /*
         * Dua kumpulan digabung lalu diurutkan menurut waktunya.
         *
         * Diurutkan di PHP, bukan di basis data, sebab keduanya tabel yang
         * berbeda dan menggabungkannya lewat SQL memerlukan union yang
         * kolomnya harus dipaksa sama. Jumlah barisnya milik satu pelanggan
         * saja, jadi menggabungkannya di PHP tetap ringan.
         */
        $gabungan = $pesanan->concat($kunjungan)
            ->sortByDesc(fn ($satu) => $satu->created_at)
            ->values();

        // Dipenggal sendiri menjadi halaman berisi sepuluh baris.
        $perHalaman = 10;
        $halaman = \Illuminate\Pagination\Paginator::resolveCurrentPage();

        $daftar = new \Illuminate\Pagination\LengthAwarePaginator(
            $gabungan->forPage($halaman, $perHalaman)->values(),
            $gabungan->count(),
            $perHalaman,
            $halaman,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('pesanan.index', [
            'daftar' => $daftar->withQueryString(),
            'status' => $status,
            'jenis' => $jenis,
            'daftarStatus' => $this->daftarStatus,
            'tanggal' => $tanggal,
            'dari' => $dari,
            'sampai' => $sampai,
        ]);
    }

    /**
     * Menerima tanggal hanya dalam bentuk tahun-bulan-hari.
     *
     * Nilainya datang dari alamat, jadi bisa diketik apa saja oleh siapa
     * saja. Dicocokkan dulu dengan pola yang tegas, bukan langsung
     * diserahkan ke Carbon::parse(), sebab parse() menerima tulisan bebas
     * seperti "next monday" dan hasilnya jadi sulit ditebak.
     */
    private function tanggalAman($nilai): ?string
    {
        $nilai = trim((string) $nilai);

        // preg_match() mencocokkan teks dengan pola. Polanya: empat angka,
        // garis, dua angka, garis, dua angka, dan tidak ada apa apa lagi.
        if ($nilai === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }

        // checkdate() memastikan tanggalnya benar benar ada di kalender,
        // jadi 2026-02-31 ditolak walaupun polanya sudah cocok.
        [$tahun, $bulan, $hari] = array_map('intval', explode('-', $nilai));

        return checkdate($bulan, $hari, $tahun) ? $nilai : null;
    }

    // Dipanggil route GET /pesanan/{order} bernama pesanan.show, di dalam
    // kelompok middleware auth dan hanya pemilik pesanannya. Menghasilkan
    // view pesanan.show.
    //
    // Parameter Order $order itu route model binding: nama variabelnya sama
    // dengan nama parameter {order} di routenya, jadi Laravel otomatis mencari
    // baris tabel orders yang idnya ada di alamat lalu menyerahkannya sebagai
    // objek. Kalau barisnya tidak ada, Laravel menampilkan 404 sendiri.
    public function show(Order $order)
    {
        // Binding hanya memastikan barisnya ADA, bukan pemiliknya. Pemeriksaan
        // ini mencegah orang melihat pesanan pelanggan lain cukup dengan
        // mengganti angka di alamat.
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Pesanan ini dibatalkan dulu kalau sudah lewat batas bayar, baru
        // halamannya digambar.
        //
        // Pemeriksaan yang sama sudah ada di daftar pesanan, tetapi halaman
        // rincian bisa dibuka LANGSUNG dari tautan atau dari pemberitahuan,
        // tanpa melewati daftar. Tanpa baris ini, halaman rincian pesanan
        // Oktober masih menampilkan spanduk kuning beserta tombol Bayar
        // Sekarang pada bulan November, padahal batas waktunya lewat sehari
        // sesudah pesanan dibuat.
        (new \App\Services\KedaluwarsaPesanan)->batalkan($order);
        $order->refresh();

        // Titik di dalam load() berarti relasi bertingkat: item, lalu varian
        // dari item itu, lalu produk dari variannya. Semuanya diambil sekaligus
        // supaya view tidak memanggil basis data berulang kali.
        $order->load(['item.varian.produk', 'alamat', 'toko', 'pembayaran', 'pengiriman']);

        // Ulasan hanya dibuka setelah barang diterima
        // DELIVERED berarti sudah diantar, PICKED_UP sudah diambil di toko.
        $bisaUlas = in_array($order->status, ['DELIVERED', 'PICKED_UP']);

        return view('pesanan.show', [
            'pesanan' => $order,
            // riwayat() relasi ke tabel tracking_events. Diurutkan dua kali:
            // berdasarkan waktu kejadiannya, lalu berdasarkan id untuk
            // memisahkan kejadian yang waktunya persis sama. Yang terbaru di atas.
            'riwayat' => $order->riwayat()->orderByDesc('happened_at')->orderByDesc('id')->get(),
            'bisaUlas' => $bisaUlas,
            // Retur punya syarat TAMBAHAN dibanding ulasan, yaitu batas waktunya
            // belum lewat. Jadi barang bisa diulas kapan saja, tetapi returnya
            // hanya dalam beberapa hari sesudah diterima.
            'bisaRetur' => $bisaUlas && ! $this->lewatBatasRetur($order),
            'hariRetur' => (int) setting('return_window_days', 7),
        ]);
    }

    /** Pembatalan oleh pelanggan, stok dan poin dikembalikan. */
    // Dipanggil route PUT /pesanan/{order}/batal bernama pesanan.batal, di
    // dalam kelompok middleware auth dan hanya pemilik pesanannya. Selalu
    // kembali ke halaman sebelumnya.
    public function batal(Request $request, Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Alasan pembatalan. Isiannya memang sudah ada di layar sejak dulu,
        // tetapi nilainya tidak pernah dibaca sama sekali, jadi pelanggan
        // memilih alasan yang kemudian dibuang begitu saja. Sekarang alasannya
        // ikut tercatat pada riwayat pesanan, supaya tim Colorbox tahu apa
        // yang paling sering membuat pesanan batal dan bisa membetulkannya.
        //
        // max:120 mengikuti panjang kalimat alasan terpanjang yang ditawarkan.
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:120'],
        ]);

        $alasan = trim((string) ($data['reason'] ?? ''));

        // bisaDibatalkan() method di model Order. Biasanya hanya pesanan yang
        // belum diproses toko yang boleh dibatalkan sendiri oleh pelanggan.
        if (! $order->bisaDibatalkan()) {
            // labelStatus() menerjemahkan kode status seperti IN_TRANSIT
            // menjadi kalimat yang bisa dibaca pelanggan.
            return back()->with('gagal', 'Pesanan dengan status '.$order->labelStatus().' tidak dapat dibatalkan lagi.');
        }

        // DB::transaction() menjalankan seluruh isi fungsi sebagai satu
        // kesatuan. Ini PENTING di sini karena ada banyak hal yang berubah
        // sekaligus: status, stok, pembayaran, poin, dan kuota voucher. Kalau
        // salah satu gagal, semuanya dibatalkan, jadi tidak ada keadaan
        // setengah jadi seperti stok sudah kembali tetapi pesanannya masih aktif.
        DB::transaction(function () use ($order, $alasan) {
            $order->update(['status' => 'CANCELLED', 'cancelled_at' => now()]);

            // Stok dikembalikan mengikuti jalur pemenuhan pesanan
            // lepaskanJanjiStok() method di model Order. Jalur kirim mengembalikan
            // stok online, jalur ambil di toko melepas barang yang ditahan di
            // tabel store_stocks. Aturannya ditaruh di model supaya controller
            // lain yang juga membatalkan pesanan memakai cara yang sama.
            $order->lepaskanJanjiStok();

            // Hanya tagihan yang masih menunggu yang diubah. Tagihan yang
            // sudah lunas tidak disentuh, karena pengembalian dananya diurus
            // tim lewat jalur lain, bukan otomatis dari sini.
            if ($order->pembayaran && $order->pembayaran->payment_status === 'menunggu') {
                $order->pembayaran->update([
                    // transaction_status memakai istilah Midtrans,
                    // payment_status istilah sendiri untuk pelanggan.
                    'transaction_status' => 'cancel',
                    'payment_status' => 'dibatalkan',
                ]);
            }

            // Poin yang terpakai dikembalikan. Pemeriksaan pengguna menjaga
            // kalau akun pelanggannya sudah dihapus.
            if ($order->points_used > 0 && $order->pengguna) {
                // Angkanya POSITIF di sini, kebalikan dari saat checkout yang
                // memakai minus. Jadi poinnya bertambah kembali. ubahPoin()
                // juga mencatat riwayatnya supaya pelanggan tahu sebabnya.
                $order->pengguna->ubahPoin(
                    (int) $order->points_used,
                    'pesanan',
                    'Poin dikembalikan karena pesanan '.$order->order_code.' dibatalkan'
                );
            }

            // kuota voucher dilepas kembali
            if ($order->voucher_id) {
                // Garis miring terbalik di depan App\Models\Voucher menunjuk
                // kelas dari akar namespace. Ditulis panjang begini karena
                // Voucher tidak ada di daftar use di atas berkas ini.
                \App\Models\Voucher::where('id', $order->voucher_id)
                    // where('used', '>', 0) menjaga kolomnya tidak pernah jadi
                    // minus kalau method ini entah bagaimana jalan dua kali.
                    ->where('used', '>', 0)->decrement('used');
            }

            // Satu baris riwayat di tabel tracking_events, yang nantinya
            // terbaca pelanggan di halaman rincian dan lacak pesanan.
            TrackingEvent::create([
                'order_id' => $order->id,
                'status' => 'CANCELLED',
                // Alasannya ditempelkan ke keterangan riwayat, bukan disimpan
                // di kolom baru, supaya pelanggan dan admin membacanya di
                // tempat yang sama dengan jejak pesanan lainnya.
                'description' => 'Pesanan dibatalkan oleh pelanggan'
                    .($alasan !== '' ? '. Alasan: '.$alasan : ''),
                'happened_at' => now(),
            ]);
        });

        // Pesanan yang sudah TERLANJUR DIBAYAR perlu kalimat tersendiri,
        // sebab yang ditunggu pelanggan sesudah ini bukan barangnya melainkan
        // uangnya. Tanpa kalimat ini dia tidak tahu harus menunggu siapa.
        $sudahDibayar = $order->pembayaran && $order->pembayaran->payment_status === 'lunas';

        return back()->with('berhasil', 'Pesanan '.$order->order_code.' dibatalkan.'
            .($sudahDibayar
                ? ' Pesanan ini sudah dibayar, jadi pengembalian dananya diurus tim Colorbox '
                    .'dan masuk dalam 1 sampai 3 hari kerja.'
                : ' Stok barangnya sudah dikembalikan ke katalog.'));
    }

    /** Pelanggan menyatakan barangnya sudah diterima. */
    // Dipanggil route PUT /pesanan/{order}/terima bernama pesanan.terima, di
    // dalam kelompok middleware auth dan hanya pemilik pesanannya. Selalu
    // kembali ke halaman sebelumnya.
    public function terima(Request $request, Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Tombol ini hanya berlaku untuk pesanan yang sedang di jalan atau
        // sudah siap diambil. Pemeriksaan ini mencegah pelanggan menandai
        // pesanan yang bahkan belum dikirim sebagai sudah diterima.
        if (! in_array($order->status, ['IN_TRANSIT', 'READY_FOR_PICKUP'])) {
            return back()->with('gagal', 'Tombol ini hanya dipakai setelah pesanan dikirim atau siap diambil di toko.');
        }

        /*
         * FOTO BARANGNYA DARI PELANGGAN, BUKAN DARI KURIR.
         *
         * Bukti foto penyerahan paket itu milik jasa kirim: kurirnyalah
         * yang memotret di depan pintu, dan fotonya tersimpan di sistem
         * mereka. Selama Colorbox belum berlangganan APInya, foto itu
         * tidak pernah sampai ke sini, dan mengarangnya berarti memajang
         * gambar yang tidak pernah diambil siapa pun.
         *
         * Yang bisa dikerjakan tanpa berbohong: meminta pelanggan memotret
         * barangnya sendiri ketika dia menyatakan paketnya sudah sampai.
         * Fotonya nyata, pemiliknya jelas, dan waktunya tercatat.
         *
         * Diwajibkan pada jalur KIRIM saja. Pesanan yang diambil sendiri
         * di toko sudah diserahkan di depan kasir dan tercatat di sana,
         * jadi tidak perlu bukti tambahan.
         */
        $wajibFoto = $order->fulfillment_method === 'DELIVERY';

        $data = $request->validate([
            'receipt_photo' => [$wajibFoto ? 'required' : 'nullable', 'image', 'max:4096'],
        ], [
            'receipt_photo.required' => 'Lampirkan foto barangnya sebagai bukti paket sudah Anda terima.',
            'receipt_photo.image' => 'Berkas yang dilampirkan harus berupa foto.',
            'receipt_photo.max' => 'Ukuran fotonya paling besar 4 MB.',
        ]);

        $foto = null;

        if ($request->hasFile('receipt_photo')) {
            $berkas = $request->file('receipt_photo');
            // Namanya dirangkai dari kode pesanan dan jamnya, supaya dua
            // pelanggan yang mengunggah pada detik yang sama tidak saling
            // menimpa fotonya.
            $nama = 'terima-'.strtolower(str_replace('-', '', $order->order_code))
                .'-'.now()->format('His').'.'.$berkas->getClientOriginalExtension();
            $folder = public_path('images/terima');

            if (! file_exists($folder)) {
                mkdir($folder, 0777, true);
            }

            $berkas->move($folder, $nama);
            $foto = 'images/terima/'.$nama;

            /*
             * Cap waktu dibubuhkan KE ATAS gambarnya.
             *
             * Waktunya memang sudah tersimpan di kolom completed_at, dan
             * halaman rincian menuliskannya di bawah gambar. Tetapi
             * tulisan itu terpisah dari gambarnya: begitu fotonya
             * diunduh atau dikirim lewat pesan, keterangan waktunya
             * tertinggal dan yang tersisa gambar tanpa keterangan.
             *
             * Capnya menyatakan kapan berkasnya SAMPAI, menurut jam
             * server, bukan kapan gambarnya diambil, dan kalimat pada
             * capnya sengaja tidak mengaku lebih dari itu.
             *
             * Kegagalan membubuhkan cap sengaja diabaikan. Foto tanpa
             * cap tetap jauh lebih berguna daripada konfirmasi
             * penerimaan yang batal hanya karena GD tidak mengenali
             * satu jenis berkas.
             */
            \App\Services\CapWaktuFoto::bubuhkan(public_path($foto));
        }

        // Transaksi dipakai supaya status pesanan, status pengiriman, dan
        // kedua catatan riwayatnya tersimpan bersamaan.
        DB::transaction(function () use ($order, $foto) {
            // Catatan pertama: barangnya sampai ke tangan pelanggan.
            TrackingEvent::create([
                'order_id' => $order->id,
                'status' => 'DELIVERED',
                'description' => 'Barang diterima pelanggan',
                'happened_at' => now(),
            ]);

            // Setelah diterima, pesanan langsung ditutup sebagai selesai
            // sehingga ulasan dan pengajuan retur terbuka.
            // completed_at inilah yang jadi acuan hitungan batas waktu retur
            // di lewatBatasRetur() nanti.
            $order->update([
                'status' => 'DELIVERED',
                'completed_at' => now(),
                'receipt_photo' => $foto,
            ]);

            // Pesanan ambil di toko tidak punya baris pengiriman, jadi
            // relasinya diperiksa dulu sebelum dipakai.
            if ($order->pengiriman) {
                $order->pengiriman->update(['status' => 'DELIVERED', 'delivered_at' => now()]);
            }

            // Catatan kedua: pesanannya ditutup sebagai selesai. Dipisah dari
            // catatan pertama supaya pelanggan melihat dua langkah yang jelas
            // di halaman lacak, yaitu barang diterima lalu pesanan selesai.
            TrackingEvent::create([
                'order_id' => $order->id,
                'status' => 'DELIVERED',
                'description' => 'Pesanan selesai, ulasan dan pengajuan retur dibuka',
                'happened_at' => now(),
            ]);
        });

        /*
         * Admin kantor diberi tahu bahwa pelanggan sudah mengonfirmasi.
         *
         * Inilah yang memulai langkah terakhir: admin memeriksa lalu
         * mengesahkan pesanannya. Dengan begitu yang menyatakan pesanan
         * tuntas ada dua pihak, pelanggan dan Colorbox, bukan satu pihak
         * saja. Tanpa kabar ini admin tidak pernah tahu harus memeriksa
         * pesanan yang mana.
         */
        foreach (\App\Models\User::where('role', 'admin')->pluck('id') as $idAdmin) {
            \App\Models\Notification::create([
                'category' => 'pesanan',
                'user_id' => $idAdmin,
                'title' => 'Pesanan '.$order->order_code.' dikonfirmasi pelanggan',
                'body' => 'Pelanggan menyatakan barangnya sudah diterima. '
                    .'Periksa lalu sahkan pesanan ini untuk menutup berkasnya.',
                'url' => route('admin.pesanan.show', $order),
            ]);
        }

        return back()->with('berhasil', 'Terima kasih. Pesanan '.$order->order_code.' ditandai sudah diterima.');
    }

    /** Memasukkan lagi seluruh barang pesanan ini ke keranjang. */
    // Dipanggil route POST /pesanan/{order}/beli-lagi bernama
    // pesanan.beli-lagi, di dalam kelompok middleware auth dan hanya pemilik
    // pesanannya. Mengalihkan ke keranjang.index kalau ada yang masuk, atau
    // kembali dengan pesan gagal.
    public function beliLagi(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $keranjang = $this->keranjangAktif();
        // Penghitung berapa barang yang berhasil masuk keranjang.
        $masuk = 0;
        // Penghitung berapa barang yang dilewati karena stoknya habis.
        $kosong = 0;

        foreach ($order->item as $baris) {
            $varian = $baris->varian;
            // Produknya bisa sudah dihapus admin, atau stoknya habis. Keduanya
            // dilewati saja, bukan membatalkan seluruh prosesnya.
            if (! $varian || $varian->tersedia() < 1) {
                $kosong++;

                // continue melompat ke putaran berikutnya.
                continue;
            }

            // Memeriksa apakah varian ini sudah ada di keranjang.
            $item = $keranjang->item()->where('product_variant_id', $varian->id)->first();
            // Kalau sudah ada, jumlah lamanya ditambah dengan jumlah pesanan ini.
            $jumlah = $item ? $item->quantity + $baris->quantity : $baris->quantity;
            // min() memotong supaya jumlahnya tidak melebihi angka available
            // yang ada sekarang. Jadi pelanggan tetap dapat sebanyak yang
            // masih bisa dijual, bukan ditolak sama sekali karena
            // persediaannya berkurang sejak pesanan dahulu.
            $jumlah = min($jumlah, $varian->tersedia());

            if ($item) {
                // selected true supaya barangnya otomatis tercentang di checkout.
                $item->update(['quantity' => $jumlah, 'selected' => true]);
            } else {
                // Baris baru di tabel cart_items.
                CartItem::create([
                    'cart_id' => $keranjang->id,
                    'product_variant_id' => $varian->id,
                    'quantity' => $jumlah,
                    'selected' => true,
                ]);
            }
            $masuk++;
        }

        if ($masuk === 0) {
            return back()->with('gagal', 'Barang pada pesanan ini sedang tidak tersedia.');
        }

        $pesan = $masuk.' barang dimasukkan lagi ke keranjang.';
        if ($kosong > 0) {
            // Tanda titik sama dengan menambahkan teks ke ujung teks yang
            // sudah ada. Jadi pelanggan tahu mana yang masuk dan mana yang
            // tidak dalam satu pesan saja.
            $pesan .= ' '.$kosong.' barang dilewati karena stoknya habis.';
        }

        return redirect()->route('keranjang.index')->with('berhasil', $pesan);
    }

    /** Memeriksa apakah batas waktu pengajuan retur sudah lewat. */
    // Kata private berarti hanya bisa dipanggil dari dalam kelas ini. Tulisan
    // bool berarti hasilnya pasti true atau false.
    private function lewatBatasRetur(Order $pesanan): bool
    {
        $hari = (int) setting('return_window_days', 7);
        // Tanya titik dua tanpa bagian tengah: pakai completed_at, tapi kalau
        // kolomnya masih kosong pakai updated_at sebagai acuan waktunya.
        $diterima = $pesanan->completed_at ?: $pesanan->updated_at;

        // Tidak ada acuan waktu sama sekali. Dianggap sudah lewat, yaitu
        // pilihan yang aman: lebih baik menolak lalu pelanggan menghubungi
        // toko, daripada membuka retur yang waktunya tidak bisa dibuktikan.
        if (! $diterima) {
            return true;
        }

        // copy() membuat salinan objek tanggalnya DULU. Penting, karena
        // addDays() mengubah objek aslinya. Tanpa copy(), tanggal yang
        // tersimpan di model pesanan ikut berubah dan perhitungan di tempat
        // lain bisa jadi salah. isPast() true kalau waktunya sudah lewat.
        return $diterima->copy()->addDays($hari)->isPast();
    }

    /** Keranjang milik pengguna yang sedang masuk. */
    private function keranjangAktif(): Cart
    {
        // firstOrCreate() mencari baris carts yang user_id-nya sama, dan kalau
        // tidak ada LANGSUNG membuatnya. Berbeda dengan KeranjangController,
        // di sini tidak ada jalur tamu karena seluruh route pesanan sudah ada
        // di dalam kelompok middleware auth.
        return Cart::firstOrCreate(['user_id' => auth()->id()]);
    }
}
