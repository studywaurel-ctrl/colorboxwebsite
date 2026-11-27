<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Voucher;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pesanan pelanggan dan pembayarannya.
 *
 * Catatan penting: status pembayaran tidak pernah diubah dengan tangan.
 * Admin hanya boleh menanyakan ulang status ke Midtrans, lalu jawabannya
 * yang disimpan.
 */
class PesananAdminController extends Controller
{
    /** Status pesanan yang boleh dipilih admin. */
    private array $statusBoleh = [
        'PENDING_PAYMENT', 'PAYMENT_CONFIRMED', 'PROCESSING', 'PACKED',
        'PICKED_UP_BY_COURIER', 'IN_TRANSIT', 'OUT_FOR_DELIVERY', 'DELIVERED',
        'REFUND_PROCESSING', 'REFUNDED', 'CANCELLED',
    ];

    /**
     * Pengelompokan status menurut pekerjaan yang perlu dikerjakan admin.
     *
     * Status pesanan ada sebelas, dan menaruh sebelas tombol penyaring di
     * atas tabel membuat halamannya penuh sebelum isinya terbaca. Padahal
     * yang ditanyakan admin tiap pagi cuma empat hal: mana yang harus saya
     * kerjakan sekarang, mana yang sedang di jalan, mana yang sudah beres,
     * dan mana yang bermasalah.
     *
     * Jadi sebelas status itu dikelompokkan jadi empat tombol. Tombol status
     * satu per satu tetap ada, tetapi disembunyikan di balik tautan "status
     * rinci", sebab yang memerlukannya hanya sesekali.
     *
     * Kuncinya dipakai pada alamat halaman, misalnya ?kelompok=dikerjakan.
     */
    public const KELOMPOK = [
        'dikerjakan' => [
            'label' => 'Perlu Dikerjakan',
            'ikon' => 'bi-hourglass-split',
            // Pesanan yang menunggu tindakan admin hari ini juga.
            'status' => ['PAYMENT_CONFIRMED', 'PROCESSING', 'PACKED'],
        ],
        'jalan' => [
            'label' => 'Sedang Dikirim',
            'ikon' => 'bi-truck',
            // Sudah diserahkan ke jasa kirim, tinggal dipantau.
            'status' => ['PICKED_UP_BY_COURIER', 'IN_TRANSIT', 'OUT_FOR_DELIVERY'],
        ],
        'menunggu' => [
            'label' => 'Menunggu Bayar',
            'ikon' => 'bi-clock',
            // Belum dibayar, jadi belum boleh disiapkan.
            'status' => ['PENDING_PAYMENT'],
        ],
        'selesai' => [
            'label' => 'Selesai',
            'ikon' => 'bi-check2-circle',
            'status' => ['DELIVERED'],
        ],
        'masalah' => [
            'label' => 'Batal dan Refund',
            'ikon' => 'bi-exclamation-triangle',
            'status' => ['CANCELLED', 'REFUND_PROCESSING', 'REFUNDED'],
        ],
    ];

    /**
     * Daftar kelompok beserta jumlah pesanan di dalamnya.
     *
     * Jumlahnya dihitung dari larik jumlah per status yang sudah diambil
     * sekali di atas, bukan dengan kueri baru per kelompok. Jadi seluruh
     * angka di halaman ini hanya memerlukan satu kueri hitung.
     */
    private function kelompokDenganJumlah(array $jumlahStatus): array
    {
        $hasil = [];

        foreach (self::KELOMPOK as $kunci => $isi) {
            // array_sum() menjumlahkan isi larik. array_intersect_key()
            // menyisakan jumlah milik status yang masuk kelompok ini saja.
            $isi['jumlah'] = array_sum(array_intersect_key(
                $jumlahStatus,
                array_flip($isi['status'])
            ));

            $hasil[$kunci] = $isi;
        }

        return $hasil;
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $cari = trim((string) $request->query('cari', ''));
        // Pengelompokan cepat, dipakai tombol baris pertama. Isinya bukan
        // satu status melainkan sekumpulan status yang pekerjaannya sejenis.
        $kelompok = (string) $request->query('kelompok', '');

        // Diurutkan menurut waktu pesanan dibuat, bukan nomor barisnya,
        // supaya pesanan yang paling baru benar benar berada di atas.
        // Nomor baris dipakai sebagai pemisah kalau tanggalnya sama persis.
        $kueri = Order::with(['pengguna', 'pembayaran'])
            ->orderByDesc('created_at')->orderByDesc('id');

        if ($status && in_array($status, $this->statusBoleh)) {
            $kueri->where('status', $status);
        }

        // Kelompok hanya dipakai kalau status rinci tidak sedang dipilih,
        // supaya dua penyaring tidak saling bertabrakan dan menghasilkan
        // tabel kosong yang membingungkan.
        if (! $status && isset(self::KELOMPOK[$kelompok])) {
            // whereIn() memberi syarat kolom status harus salah satu dari
            // isi larik kelompoknya.
            $kueri->whereIn('status', self::KELOMPOK[$kelompok]['status']);
        }

        if ($cari !== '') {
            $kueri->where(function ($bagian) use ($cari) {
                $bagian->where('order_code', 'like', '%'.$cari.'%')
                    ->orWhereHas('pengguna', function ($pemilik) use ($cari) {
                        $pemilik->where('name', 'like', '%'.$cari.'%')
                            ->orWhere('email', 'like', '%'.$cari.'%');
                    });
            });
        }

        return view('admin.pesanan.index', [
            'daftar' => $kueri->paginate(20)->withQueryString(),
            // Pasangan kode status dengan tulisannya, dipakai deretan tautan
            // penyaring di atas tabel. Dulu di sini dikirim daftar kode saja
            // dan view menampilkannya apa adanya, sehingga admin membaca
            // tulisan seperti PICKED_UP_BY_COURIER.
            'daftarStatus' => $this->labelStatus(),
            'status' => $status,
            'cari' => $cari,
            'kelompok' => $kelompok,
            // Banyaknya pesanan pada tiap status, supaya admin tahu bagian
            // mana yang menunggu dikerjakan tanpa membukanya satu per satu.
            'jumlahStatus' => $jumlahStatus = $this->jumlahPerStatus(),
            // Daftar kelompok beserta jumlahnya, dipakai tombol baris pertama.
            'daftarKelompok' => $this->kelompokDenganJumlah($jumlahStatus),
        ]);
    }

    /**
     * Pasangan kode status dengan tulisan yang dibaca admin.
     *
     * Diambil dari Order::daftarStatus() supaya tulisannya sama persis
     * dengan yang dibaca pelanggan di halaman Pesanan Saya, dan tidak perlu
     * ditulis dua kali di dua tempat. array_intersect_key() menyisakan hanya
     * status yang memang boleh disaring admin.
     */
    private function labelStatus(): array
    {
        $daftar = array_intersect_key(Order::daftarStatus(), array_flip($this->statusBoleh));

        // Dua tulisan diganti khusus untuk admin. Daftar aslinya ditulis
        // untuk dibaca pelanggan, jadi memakai kata Anda yang menunjuk
        // pelanggan itu sendiri. Di panel admin kata itu menunjuk orang yang
        // salah, sebab yang membacanya petugas, bukan pelanggannya.
        $daftar['OUT_FOR_DELIVERY'] = 'Menuju Alamat Pelanggan';
        $daftar['DELIVERED'] = 'Diterima Pelanggan';

        return $daftar;
    }

    /**
     * Banyaknya pesanan pada tiap status.
     *
     * selectRaw() dan groupBy() membuat penghitungannya dikerjakan MySQL
     * dalam satu kueri, bukan sebelas kueri terpisah. pluck() dengan dua
     * argumen mengubah hasilnya menjadi larik pasangan kode status dengan
     * angkanya, jadi view bisa mencarinya langsung lewat kode statusnya.
     */
    private function jumlahPerStatus(): array
    {
        return Order::selectRaw('status, COUNT(*) AS jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status')
            ->all();
    }

    public function show(Order $order)
    {
        $order->load(['pengguna', 'alamat', 'toko', 'item', 'pembayaran', 'pengiriman']);

        return view('admin.pesanan.show', [
            'pesanan' => $order,
            'riwayat' => $order->riwayat()->orderByDesc('happened_at')->orderByDesc('id')->get(),
            // Bukan seluruh daftar status, hanya langkah berikutnya yang masuk
            // akal dari keadaan pesanan sekarang.
            'lanjutan' => $order->lanjutanStatus(),
        ]);
    }

    /** Mengubah status pesanan dan mencatat riwayatnya. */
    public function ubahStatus(Request $request, Order $order)
    {
        // Status yang boleh dikirim dibatasi oleh keadaan pesanan sekarang,
        // bukan oleh daftar lengkap. Pemeriksaannya dikerjakan di server, jadi
        // tetap berlaku walaupun tombolnya diubah lewat peramban.
        $lanjutan = $order->lanjutanStatus();

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', $lanjutan ?: ['-'])],
            'keterangan' => ['nullable', 'string', 'max:191'],
            'courier' => ['nullable', 'string', 'max:40'],
            'service' => ['nullable', 'string', 'max:60'],
            'tracking_number' => ['nullable', 'string', 'max:60'],
        ], [
            'status.required' => 'Pilih langkah berikutnya untuk pesanan ini.',
            'status.in' => 'Pesanan berstatus '.$order->labelStatus().' tidak bisa langsung '
                .'dipindahkan ke status itu. Jalankan langkahnya berurutan.',
        ]);

        $statusBaru = $data['status'];

        if ($statusBaru === $order->status) {
            return back()->with('ingat', 'Status pesanan sudah '.$order->labelStatus().', tidak ada yang diubah.');
        }

        // Isian yang diwajibkan tiap langkah diperiksa lagi di server, bukan
        // hanya di peramban. Daftarnya dibaca dari method yang sama yang
        // dipakai view menggambar tombolnya, yaitu Order::isianPeralihan(),
        // jadi tombol dan pemeriksaannya mustahil berbeda aturan.
        foreach (Order::isianPeralihan($statusBaru, $order) as $satu) {
            if (($satu['wajib'] ?? false) && empty($data[$satu['nama']])) {
                return back()->withInput()->with('gagal',
                    $satu['label'].' wajib diisi untuk langkah '.Order::labelPeralihan($statusBaru).'.');
            }
        }

        DB::transaction(function () use ($order, $statusBaru, $data) {
            $order->status = $statusBaru;

            if ($statusBaru === 'PAYMENT_CONFIRMED' && ! $order->paid_at) {
                $order->paid_at = now();
            }
            if ($statusBaru === 'DELIVERED' && ! $order->completed_at) {
                $order->completed_at = now();
            }
            if ($statusBaru === 'CANCELLED') {
                $order->cancelled_at = now();
                // Janji stoknya dilepas mengikuti jalur pemenuhannya: jalur
                // kirim melepas janji di gudang pusat, jalur ambil di toko
                // melepas janji di rak toko pengambilan. Yang berkurang hanya
                // kolom committed, angka fisik on_hand tidak disentuh, sebab
                // barangnya memang belum pernah keluar rak.
                $order->lepaskanJanjiStok();
            }

            // Paket diserahkan kepada kurir: barangnya benar benar keluar dari
            // rak gudang. Inilah satu satunya titik pada jalur kirim di mana
            // angka fisik gudang berkurang. keluarkanStok() menurunkan on_hand
            // dan committed sekaligus lalu menulis kartu stok berjenis KIRIM
            // untuk tiap barisnya, jadi angka di sistem kembali sama dengan
            // angka di rak.
            //
            // Pesanan ambil di toko tidak lewat sini. Barangnya keluar dari rak
            // toko ketika kasir menutup kunjungannya, dan itu diurus method
            // keluarkanStok() di model TapTryCollect.
            // Dulu langkah ini bernama PICKED_UP_BY_COURIER, satu dari tiga
            // langkah perjalanan yang digambar sendiri oleh sistem. Ketiganya
            // digabung menjadi satu langkah Track Order, dan IN_TRANSIT
            // itulah kodenya. Jadi penyerahan paketnya sekarang terjadi di
            // sini.
            if ($statusBaru === 'IN_TRANSIT' && ! $order->ambilDiToko()) {
                $order->keluarkanStok();
            }

            $order->save();

            // Paket diserahkan kepada kurir. Di sinilah pesanan kirim dibuat
            // di penyedia jasa kirim, dan nomor resinya datang dari sana.
            // Petugas tidak mengetik apa apa.
            if ($statusBaru === 'IN_TRANSIT' && ! $order->ambilDiToko()) {
                $this->buatPengirimanOtomatis($order, $data);

                // Kalau penyedianya tersambung, baris pengirimannya sudah
                // terisi beserta nomor resinya. Isian manual di bawah hanya
                // terpakai ketika penyedianya belum tersambung: nomor resinya
                // DISALIN petugas dari struk yang diberikan kurir, bukan
                // dikarang sistem, sebab nomor itulah yang nanti dicari
                // pelanggan di situs jasa kirimnya.
                $kirim = Shipment::firstOrNew(['order_id' => $order->id]);

                /*
                 * JASA KIRIMNYA PILIHAN PELANGGAN, TITIK.
                 *
                 * Pelanggan memilih JNE Reguler di checkout dan membayar
                 * ongkos JNE Reguler. Kalau admin boleh mengetik nama lain
                 * di sini, catatan ongkos dan catatan kurirnya jadi berbeda
                 * dan tidak ada yang tahu mana yang benar. Jadi namanya
                 * disalin dari pilihan pelanggan, bukan dari isian admin.
                 *
                 * Isian courier dan service hanya diterima untuk pesanan
                 * lama yang memang tidak punya pilihan pelanggan, dan untuk
                 * pesanan seperti itu Order::isianPeralihan() memang masih
                 * menampilkan kotak isiannya.
                 *
                 * Nomor resi tetap boleh diketik, sebab selama penyedia
                 * jasa kirim belum tersambung, nomor itu hanya ada di struk
                 * yang dipegang petugas gudang.
                 */
                $kirim->fill(array_filter([
                    'courier' => $order->jasaKirim?->courier
                        ?: (($data['courier'] ?? null) ?: $kirim->courier),
                    'service' => $order->jasaKirim?->service
                        ?: (($data['service'] ?? null) ?: $kirim->service),
                    'tracking_number' => ($data['tracking_number'] ?? null) ?: $kirim->tracking_number,
                ], fn ($nilai) => $nilai !== null));

                $kirim->cost = (int) $order->shipping_cost;
                $kirim->status = 'IN_TRANSIT';
                $kirim->shipped_at = $kirim->shipped_at ?: now();
                $kirim->save();
            }

            $keterangan = $data['keterangan'] ?? '';
            if ($keterangan === '') {
                $keterangan = $this->keteranganBawaan($statusBaru);
            }

            TrackingEvent::create([
                'order_id' => $order->id,
                'status' => $statusBaru,
                'description' => $keterangan,
                'happened_at' => now(),
            ]);

            Notification::create([
                'category' => 'pesanan',
                'user_id' => $order->user_id,
                'title' => 'Pesanan '.$order->order_code.' '.$order->labelStatus(),
                'body' => $keterangan,
                'url' => route('pesanan.show', $order->order_code),
            ]);
        });

        return redirect()->route('admin.pesanan.show', $order)
            ->with('berhasil', 'Status pesanan '.$order->order_code.' diubah menjadi '.$order->labelStatus().'.');
    }

    /** Daftar pembayaran beserta status terakhir dari Midtrans. */
    public function pembayaran(Request $request, MidtransService $midtrans)
    {
        $status = $request->query('status');
        $cari = trim((string) $request->query('cari', ''));

        $kueri = Payment::with('pesanan.pengguna')->orderByDesc('id');

        if ($status) {
            $kueri->where('payment_status', $status);
        }

        if ($cari !== '') {
            $kueri->whereHas('pesanan', function ($pesanan) use ($cari) {
                $pesanan->where('order_code', 'like', '%'.$cari.'%');
            });
        }

        return view('admin.pembayaran.index', [
            'daftar' => $kueri->paginate(20)->withQueryString(),
            // Pasangan kode status pembayaran dengan tulisannya, dipakai
            // deretan tautan penyaring. Kode CANCELLED yang dulu ada di sini
            // dibuang, sebab kolom payment_status tidak pernah berisi nilai
            // itu: pembatalan tercatat pada kolom status milik pesanan, bukan
            // pada pembayarannya, jadi tombolnya selalu menghasilkan tabel
            // kosong dan hanya membingungkan admin.
            'daftarStatus' => [
                'menunggu' => 'Menunggu Dibayar',
                'lunas' => 'Lunas',
                'gagal' => 'Gagal',
                'kedaluwarsa' => 'Kedaluwarsa',
            ],
            'jumlahStatus' => Payment::selectRaw('payment_status, COUNT(*) AS jumlah')
                ->groupBy('payment_status')->pluck('jumlah', 'payment_status')->all(),
            'status' => $status,
            'cari' => $cari,
            // Selama kunci Midtrans kosong, halaman ini berpindah ke mode demo:
            // tombol Periksa Status diganti tombol penandaan manual yang namanya
            // menyebut mode demo secara terang-terangan.
            'midtransAktif' => $midtrans->aktif(),
        ]);
    }

    /**
     * Menandai sebuah pembayaran lunas ketika Midtrans memang belum tersambung.
     *
     * Tombolnya hanya muncul selama MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY
     * masih kosong, yaitu keadaan yang disebut mode demo. Gunanya supaya alur
     * pesanan tetap dapat diperagakan dari awal sampai akhir pada sidang,
     * walaupun uang sungguhan tidak pernah berpindah.
     *
     * Supaya tidak menyesatkan, penandaan ini tidak menyamar sebagai jawaban
     * Midtrans. Kolom transaction_status diisi demo, payment_method diisi demo,
     * raw_response diisi keterangan lengkap beserta nama admin yang menandai,
     * dan riwayat pesanannya menyebutkan dengan jelas bahwa pembayarannya
     * ditandai dalam mode demo. Pelanggan tidak punya tombol semacam ini.
     */
    public function tandaiDemo(Payment $payment, MidtransService $midtrans)
    {
        // Begitu kuncinya diisi, mode demo mati dan jalur ini ditutup supaya
        // pembayaran sungguhan tidak mungkin ditandai dengan tangan.
        if ($midtrans->aktif()) {
            return back()->with('gagal',
                'Midtrans sudah tersambung, jadi status pembayaran hanya boleh mengikuti jawaban Midtrans.');
        }

        $pesanan = $payment->pesanan;

        if (! $pesanan) {
            return back()->with('gagal', 'Pembayaran ini tidak punya pesanan, jadi tidak bisa ditandai.');
        }

        if ($payment->payment_status === 'lunas') {
            return back()->with('ingat', 'Pembayaran ini sudah lunas.');
        }

        if ($pesanan->status !== 'PENDING_PAYMENT') {
            return back()->with('gagal', 'Pesanan ini tidak sedang menunggu pembayaran.');
        }

        // Nama petugas ikut dicatat supaya jelas siapa yang menandai
        $petugas = auth()->user()?->name ?? 'admin';

        DB::transaction(function () use ($payment, $pesanan, $petugas) {
            $payment->update([
                'transaction_status' => 'demo',
                'payment_status' => 'lunas',
                'payment_method' => 'demo',
                'paid_at' => now(),
                'raw_response' => json_encode([
                    'sumber' => 'mode demo Colorbox',
                    'keterangan' => 'Ditandai lunas oleh '.$petugas.' karena kunci Midtrans '
                        .'belum diisi pada berkas .env. Bukan jawaban dari Midtrans.',
                    'ditandai_pada' => now()->toDateTimeString(),
                ]),
            ]);

            $pesanan->update([
                'status' => 'PAYMENT_CONFIRMED',
                'paid_at' => $pesanan->paid_at ?: now(),
            ]);

            TrackingEvent::create([
                'order_id' => $pesanan->id,
                'status' => 'PAYMENT_CONFIRMED',
                'description' => 'Pembayaran ditandai lunas oleh '.$petugas
                    .' dalam mode demo, sebab Midtrans belum tersambung.',
                'happened_at' => now(),
            ]);

            Notification::create([
                'category' => 'pembayaran',
                'user_id' => $pesanan->user_id,
                'title' => 'Pembayaran pesanan '.$pesanan->order_code.' ditandai lunas',
                'body' => 'Pembayaran pesanan ini ditandai lunas oleh petugas dalam mode demo, '
                    .'sebab layanan pembayaran belum tersambung.',
                'url' => route('pesanan.show', $pesanan->order_code),
            ]);
        });

        return back()->with('berhasil',
            'Pembayaran '.$pesanan->order_code.' ditandai lunas dalam mode demo.');
    }

    /**
     * Menanyakan ulang status sebuah pembayaran ke Midtrans.
     * Nilai yang disimpan hanya berasal dari jawaban Midtrans.
     */
    public function periksaPembayaran(Payment $payment, MidtransService $midtrans)
    {
        $pesanan = $payment->pesanan;

        if (! $pesanan) {
            return back()->with('gagal', 'Pembayaran ini tidak punya pesanan, tidak bisa diperiksa.');
        }

        $jawaban = $midtrans->ambilStatus($pesanan->order_code);

        if (! $jawaban) {
            return back()->with('gagal', 'Midtrans belum bisa dihubungi atau transaksi ini belum terdaftar di sana. Silakan coba lagi sebentar lagi.');
        }

        $statusColorbox = $midtrans->statusColorbox($jawaban);

        $payment->transaction_status = $jawaban['transaction_status'] ?? $payment->transaction_status;
        $payment->payment_status = $statusColorbox;
        $payment->transaction_id = $jawaban['transaction_id'] ?? $payment->transaction_id;
        $payment->payment_method = $jawaban['payment_type'] ?? $payment->payment_method;
        $payment->raw_response = json_encode($jawaban);
        if ($statusColorbox === 'lunas' && ! $payment->paid_at) {
            $payment->paid_at = now();
        }
        $payment->save();

        // Status pesanan ikut menyesuaikan jawaban Midtrans
        if ($statusColorbox === 'lunas' && $pesanan->status === 'PENDING_PAYMENT') {
            $pesanan->status = 'PAYMENT_CONFIRMED';
            $pesanan->paid_at = $pesanan->paid_at ?: now();
            $pesanan->save();

            TrackingEvent::create([
                'order_id' => $pesanan->id,
                'status' => 'PAYMENT_CONFIRMED',
                'description' => 'Pembayaran diterima menurut jawaban Midtrans.',
                'happened_at' => now(),
            ]);

            Notification::create([
                'category' => 'pembayaran',
                'user_id' => $pesanan->user_id,
                'title' => 'Pembayaran pesanan '.$pesanan->order_code.' diterima',
                'body' => 'Terima kasih, pembayaran Anda sudah kami terima dan pesanan segera diproses.',
                'url' => route('pesanan.show', $pesanan->order_code),
            ]);
        }

        if (in_array($statusColorbox, ['kedaluwarsa', 'dibatalkan', 'gagal']) && $pesanan->status === 'PENDING_PAYMENT') {
            $pesanan->status = 'CANCELLED';
            $pesanan->cancelled_at = now();
            $pesanan->save();
            $pesanan->lepaskanJanjiStok();

            TrackingEvent::create([
                'order_id' => $pesanan->id,
                'status' => 'CANCELLED',
                'description' => 'Pesanan dibatalkan karena pembayaran '.$statusColorbox.'.',
                'happened_at' => now(),
            ]);
        }

        return back()->with('berhasil', 'Status dari Midtrans: '.($jawaban['transaction_status'] ?? '-').' (dicatat sebagai '.$statusColorbox.').');
    }

    /* ------------------------------------------------------------------ */

    /**
     * Mengembalikan stok varian ketika pesanan dibatalkan, sekaligus
     * mengembalikan poin yang dipakai dan melepas kuota vouchernya.
     */

    /** Keterangan bawaan untuk riwayat status bila admin tidak menulis apa pun. */
    /**
     * Membuat pesanan pengiriman di penyedia jasa kirim pihak ketiga.
     *
     * Inilah yang menghapus dua pekerjaan ketik dari meja admin: nama jasa
     * kirim dan nomor resi. Keduanya datang dari jawaban penyedianya, lalu
     * langsung disimpan ke tabel shipments.
     *
     * Kurir yang dipakai adalah kurir yang DIPILIH PELANGGAN saat checkout,
     * bukan yang dipilih admin. Kodenya dibaca dari kolom provider_courier
     * dan provider_service pada baris tarif yang dipilih pelanggan itu.
     *
     * Kalau penyedianya belum tersambung, atau panggilannya gagal, method ini
     * TIDAK menghentikan perpindahan status pesanan. Barisnya tetap dibuat
     * dengan nomor resi kosong, dan sebab kegagalannya disimpan di kolom
     * status supaya terbaca petugas. Dengan begitu paket yang sudah terlanjur
     * diserahkan kepada kurir tidak membuat seluruh langkahnya macet.
     */
    /**
     * Pengesahan akhir: admin menutup berkas pesanan.
     *
     * ----------------------------------------------------------------
     * KENAPA ADA LANGKAH SESUDAH PELANGGAN MENGONFIRMASI
     * ----------------------------------------------------------------
     *
     * Konfirmasi pelanggan itu satu pihak. Dia menyatakan barangnya sampai,
     * dan itu memang yang paling penting. Tetapi berkas penjualan baru
     * tuntas kalau pihak penjualnya juga memeriksa: barangnya benar yang
     * dipesan, tidak ada retur yang sedang berjalan, dan uangnya memang
     * sudah masuk.
     *
     * Jadi sesudah pelanggan menekan Barang Sudah Diterima, kabarnya masuk
     * ke admin, admin memeriksa, lalu mengesahkan di sini. Yang disimpan
     * cuma penanda waktu verified_at. Status pesanannya tetap DELIVERED,
     * supaya seluruh kueri yang sudah ada, misalnya laporan penjualan dan
     * syarat pengajuan retur, tidak perlu diubah satu per satu.
     *
     * Dipanggil route PUT /admin/pesanan/{order}/sahkan bernama
     * admin.pesanan.sahkan.
     */
    public function sahkan(Order $order)
    {
        if (! $order->menungguPengesahan()) {
            return back()->with('gagal',
                'Pesanan ini belum sampai pada tahap pengesahan. Tombolnya muncul sesudah '
                .'pelanggan menyatakan barangnya diterima.');
        }

        $order->update(['verified_at' => now()]);

        TrackingEvent::create([
            'order_id' => $order->id,
            'status' => 'DELIVERED',
            'description' => 'Pesanan disahkan Colorbox sesudah dikonfirmasi pelanggan.',
            'happened_at' => now(),
        ]);

        if ($order->user_id) {
            Notification::create([
                'category' => 'pesanan',
                'user_id' => $order->user_id,
                'title' => 'Pesanan '.$order->order_code.' selesai',
                'body' => 'Pesanan Anda sudah kami sahkan. Terima kasih sudah berbelanja di Colorbox.',
                'url' => route('pesanan.show', $order->order_code),
            ]);
        }

        return back()->with('berhasil', 'Pesanan '.$order->order_code.' disahkan dan berkasnya ditutup.');
    }

    /**
     * Mengganti nomor resi peragaan dengan nomor sungguhan dari struk kurir.
     *
     * ---------------------------------------------------------------------
     * KENAPA TERPISAH DARI LANGKAH PESANAN
     * ---------------------------------------------------------------------
     *
     * Alur utamanya harus satu tekanan: admin menekan "Serahkan ke kurir",
     * lalu jasa kirim, nomor resi, pemberitahuan, dan email terbit sendiri.
     * Kalau nomor resi dijadikan isian wajib pada langkah itu, alurnya
     * berhenti menunggu admin mengetik, dan itulah yang dulu dikeluhkan.
     *
     * Tetapi ada satu keadaan nyata yang tetap harus dilayani: penyedia
     * jasa kirim belum tersambung, nomor yang terbit baru nomor peragaan
     * berawalan SIM-, lalu kurir datang membawa struk berisi nomor
     * sungguhan. Nomor itu harus bisa masuk, sebab nomor peragaan tidak
     * akan pernah ketemu di situs jasa kirimnya.
     *
     * Jadi penggantiannya ditaruh di sini: tombol kecil tersendiri pada
     * kartu Pengiriman, muncul hanya ketika nomornya memang masih nomor
     * peragaan, dan hilang begitu nomor sungguhannya masuk.
     */
    public function gantiResi(Request $request, Order $order)
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'min:6', 'max:60'],
        ], [
            'tracking_number.required' => 'Nomor resi wajib diisi.',
            'tracking_number.min' => 'Nomor resi terlalu pendek, salin apa adanya dari struk kurir.',
        ]);

        $kirim = $order->pengiriman;

        if (! $kirim) {
            return back()->with('gagal', 'Pesanan ini belum punya baris pengiriman.');
        }

        // Nomor yang sudah sungguhan tidak boleh ditimpa lagi dari sini.
        // Kalau nomornya salah, itu urusan pembetulan data, bukan alur
        // sehari hari, dan tidak pantas dibuka lewat satu kotak isian.
        if (! \App\Services\PenyerahanPaket::simulasi($kirim->tracking_number)) {
            return back()->with('gagal',
                'Nomor resi pesanan ini sudah berupa nomor sungguhan, jadi tidak diganti lagi.');
        }

        $nomorBaru = trim($data['tracking_number']);

        // Nomor resi dan kode pesanan adalah dua hal yang berbeda. Yang satu
        // diterbitkan jasa kirim, yang satu diterbitkan Colorbox. Petugas
        // yang keliru menyalin kode pesanan ke sini dicegah di tempat.
        if (strcasecmp($nomorBaru, (string) $order->order_code) === 0) {
            return back()->with('gagal',
                'Itu kode pesanan Colorbox, bukan nomor resi. Nomor resi diterbitkan jasa '
                .'kirim dan tertulis di struk yang diberikan kurir.');
        }

        $kirim->update([
            'tracking_number' => $nomorBaru,
            'tracking_url' => \App\Services\TautanResi::untuk($kirim->courier, $nomorBaru),
        ]);

        TrackingEvent::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'description' => 'Nomor resi diperbarui menjadi '.$nomorBaru.'.',
            'happened_at' => now(),
        ]);

        // Pelanggan dikabari, sebab nomor yang dia simpan sebelumnya sudah
        // tidak berlaku. Pemberitahuannya sekalian terkirim ke emailnya.
        if ($order->user_id) {
            Notification::create([
                'category' => 'pengiriman',
                'user_id' => $order->user_id,
                'title' => 'Nomor resi pesanan '.$order->order_code.' diperbarui',
                'body' => 'Nomor resi dari jasa kirim sudah terbit: '.$nomorBaru
                    .'. Nomor inilah yang dipakai melacak paket di situs jasa kirimnya.',
                'url' => route('pesanan.show', $order->order_code),
            ]);
        }

        return back()->with('berhasil', 'Nomor resi diperbarui menjadi '.$nomorBaru.'.');
    }

    private function buatPengirimanOtomatis(Order $order, array $data): void
    {
        $kurir = new \App\Services\KurirService();

        // Baris tarif yang dipilih pelanggan saat checkout. Dari sinilah kode
        // kurir untuk penyedianya dibaca.
        $tarif = $order->jasaKirim;

        $barisKirim = [
            'order_id' => $order->id,
            'cost' => (int) $order->shipping_cost,
            'status' => 'DISIAPKAN',
            // Nama yang dibaca pelanggan diambil dari tarif pilihannya, jadi
            // sudah benar bahkan sebelum penyedianya menjawab.
            'courier' => $tarif?->courier,
            'service' => $tarif?->service,
        ];

        if (! $kurir->tersedia() || ! $tarif || ! $tarif->provider_courier) {
            /*
             * Mode cadangan: penyedianya belum tersambung, atau tarif
             * pilihan pelanggan belum punya kode penyedianya.
             *
             * ------------------------------------------------------------
             * KENAPA NOMOR PERAGAAN DIBUAT DI SINI
             * ------------------------------------------------------------
             *
             * Dulu baris pengirimannya dibuat dengan tracking_number
             * KOSONG, lalu diisi dari isian yang diketik petugas.
             * Masalahnya, petugas tidak pernah mengetik apa apa: tombol
             * di halaman admin hanya mengubah status, tanpa kotak nomor
             * resi, sebab nomor sungguhan memang belum ada.
             *
             * Akibatnya status pesanan pelanggan berpindah ke Track
             * Order sementara halaman rinciannya tetap bertuliskan
             * "Nomor resinya muncul di sini begitu paketnya diserahkan
             * kepada kurir". Dua kalimat yang saling bertentangan di
             * satu layar.
             *
             * Jalur satunya, App\Services\PenyerahanPaket, sejak awal
             * sudah membuat nomor peragaan untuk keadaan yang sama
             * persis. Jadi yang salah bukan aturannya, melainkan satu
             * jalur yang tertinggal. Di sini dipanggil method yang
             * SAMA, bukan disalin rumusnya, supaya kedua jalur mustahil
             * menghasilkan bentuk nomor yang berbeda.
             *
             * Nomor ini tidak berpura pura menjadi resi sungguhan:
             * awalannya SIM- dan komponen x-lacak-resi menuliskannya
             * apa adanya sebagai nomor peragaan yang belum terdaftar di
             * jasa kirim mana pun.
             */
            $adaSebelumnya = Shipment::where('order_id', $order->id)->value('tracking_number');

            Shipment::updateOrCreate(['order_id' => $order->id], array_merge($barisKirim, [
                'courier' => $barisKirim['courier'] ?: ($data['courier'] ?? null),
                'service' => $barisKirim['service'] ?: ($data['service'] ?? null),
                // Urutannya: nomor yang diketik petugas menang, lalu nomor
                // yang sudah tersimpan, baru nomor peragaan. Dengan begitu
                // resi sungguhan yang sudah disalin dari struk kurir tidak
                // pernah tertimpa nomor buatan sistem.
                'tracking_number' => ($data['tracking_number'] ?? null)
                    ?: ($adaSebelumnya ?: \App\Services\PenyerahanPaket::nomorSimulasi($order->order_code)),
            ]));

            return;
        }

        try {
            $hasil = $kurir->buatPengiriman($order, $tarif->provider_courier, $tarif->provider_service);
        } catch (\Throwable $e) {
            // Kegagalan dicatat ke kolom status, bukan dilempar ke atas.
            // Petugas membacanya di halaman rincian pesanan lalu bisa mencoba
            // lagi, sementara status pesanannya tetap berjalan.
            Shipment::updateOrCreate(['order_id' => $order->id], array_merge($barisKirim, [
                'status' => 'GAGAL_BUAT_KIRIMAN',
            ]));

            // Log dipakai supaya sebab lengkapnya tersimpan di berkas log,
            // tanpa menampilkan rincian teknis kepada petugas.
            \Illuminate\Support\Facades\Log::warning('Pembuatan kiriman gagal untuk pesanan '
                .$order->order_code.': '.$e->getMessage());

            return;
        }

        Shipment::updateOrCreate(['order_id' => $order->id], array_merge($barisKirim, [
            'provider_order_id' => $hasil['provider_order_id'] ?: null,
            'tracking_id' => $hasil['tracking_id'] ?: null,
            // Nomor resi dari penyedianya. Inilah angka yang dulu diketik
            // tangan dan sekarang tidak lagi.
            'tracking_number' => $hasil['waybill_id'] ?: null,
            'tracking_url' => $hasil['link'] ?: null,
            'driver_name' => $hasil['driver_name'] ?: null,
            'driver_phone' => $hasil['driver_phone'] ?: null,
            'status' => 'DIJEMPUT_KURIR',
            'last_synced_at' => now(),
        ]));
    }

    private function keteranganBawaan(string $status): string
    {
        return match ($status) {
            'PENDING_PAYMENT' => 'Pesanan menunggu pembayaran.',
            'PAYMENT_CONFIRMED' => 'Pembayaran sudah diterima.',
            'PROCESSING' => 'Pesanan sedang disiapkan.',
            'PACKED' => 'Pesanan sedang dikemas.',
            'IN_TRANSIT' => 'Paket sudah diserahkan kepada kurir. Perjalanannya bisa dilihat '
                .'di situs jasa kirim memakai nomor resinya.',
            'OUT_FOR_DELIVERY' => 'Paket sedang menuju alamat pelanggan.',
            'DELIVERED' => 'Pesanan sudah diterima pelanggan.',
            'CANCELLED' => 'Pesanan dibatalkan.',
            default => 'Status pesanan diperbarui.',
        };
    }
}
