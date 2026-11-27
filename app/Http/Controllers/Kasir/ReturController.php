<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\ReturnRequest;
use App\Models\StoreStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Retur yang diantar sendiri ke toko, diurus KASIR TOKO.
 *
 * ---------------------------------------------------------------------
 * KENAPA KASIR, BUKAN ADMIN
 * ---------------------------------------------------------------------
 *
 * Pemeriksaan retur pakaian itu pekerjaan tangan, bukan pekerjaan layar.
 * Yang menentukan diterima atau tidak adalah hal hal yang cuma bisa
 * diketahui orang yang memegang bajunya: labelnya masih menempel atau
 * sudah dilepas, kainnya sudah pernah dicuci atau belum, ada bau atau
 * tidak, kancing dan ikat pinggangnya lengkap atau hilang.
 *
 * Admin kantor cuma melihat satu foto yang dikirim pelanggan. Dari foto
 * tidak ada yang bisa dicium dan tidak ada yang bisa diraba. Jadi kalau
 * admin yang memutuskan, keputusannya tebakan, dan pelanggan yang
 * barangnya baik bisa ditolak sedangkan yang sudah dipakai bisa diterima.
 *
 * Karena itu retur dengan cara Antar Sendiri ke Toko masuk ke antrean
 * kasir toko yang dipilih pelanggan. Retur yang dikirim lewat jasa kirim
 * tetap urusan admin, sebab paketnya memang sampai ke kantor.
 *
 * ---------------------------------------------------------------------
 * DUA LANGKAH, BERURUTAN
 * ---------------------------------------------------------------------
 *
 * Langkah 1  Periksa barangnya. Lima syarat dicentang satu per satu, dan
 *            semuanya harus terpenuhi. Ada satu saja yang tidak, retur
 *            ditolak beserta alasannya.
 * Langkah 2  Serahkan penggantinya. Stok toko berkurang di sini.
 * Langkah 3  Pelanggan mengonfirmasi di halaman returnya, dan baru pada
 *            saat itu pengajuannya ditutup. Kalau sampai tiga hari tidak
 *            dikonfirmasi, sistem yang menutupnya.
 *
 * Langkah ketiga bukan pekerjaan kasir, dan memang begitu maksudnya: yang
 * paling tahu barang penggantinya benar benar diterima adalah pelanggan.
 *
 * Urutannya dijaga server, jadi retur mustahil ditutup selesai padahal
 * barangnya belum pernah diperiksa.
 */
class ReturController extends Controller
{
    /**
     * Syarat fisik yang harus dipenuhi barang retur.
     *
     * Ditulis sebagai larik supaya daftar centang di layar dan pemeriksaan
     * di server membaca sumber yang sama. Kalau nanti syaratnya ditambah,
     * cukup ditambah di sini dan keduanya ikut berubah.
     */
    public const SYARAT = [
        'label' => 'Label harga masih menempel dan belum dilepas',
        'belum_dicuci' => 'Pakaian belum pernah dicuci',
        'tidak_bau' => 'Tidak berbau, tidak ada bekas parfum atau asap',
        'lengkap' => 'Kelengkapannya utuh, misalnya kancing, tali, dan ikat pinggang',
        'struk_cocok' => 'Barangnya cocok dengan pesanan pada struk',
    ];

    /**
     * Status yang berarti barangnya BELUM diperiksa kasir.
     *
     * Ditulis sebagai daftar, bukan satu status, sebab pengajuan bisa
     * terlanjur dimajukan admin sebelum pelanggan datang ke toko, misalnya
     * disetujui dari panel admin. Kalau yang diterima cuma
     * menunggu_validasi, pengajuan seperti itu tidak punya tombol apa apa di
     * layar kasir dan pekerjaannya berhenti di tengah jalan.
     */
    public const BELUM_DIPERIKSA = ['menunggu_validasi', 'disetujui', 'dikirim'];

    /** Status yang berarti barangnya sudah lolos periksa, tinggal diserahkan. */
    public const SIAP_DISERAHKAN = ['diperiksa'];

    /** Status akhir: tidak ada lagi yang perlu dikerjakan kasir. */
    public const SUDAH_DITUTUP = ['diproses', 'selesai', 'ditolak', 'dibatalkan'];

    private function pastikanMilikToko(ReturnRequest $retur): void
    {
        if (! $retur->diurusKasir() || $retur->store_id !== auth()->user()->store_id) {
            abort(403, 'Pengajuan retur ini bukan urusan toko Anda.');
        }
    }

    /** Antrean retur yang diantar ke toko ini. */
    public function index(Request $request)
    {
        $tokoId = auth()->user()->store_id;

        $daftar = ReturnRequest::where('method', 'toko')
            ->where('store_id', $tokoId)
            // Yang sudah ditutup tidak perlu menumpuk di antrean harian.
            // Yang berstatus diproses pun tidak, sebab penggantinya sudah
            // diserahkan dan yang ditunggu tinggal konfirmasi pelanggan,
            // bukan pekerjaan kasir.
            ->whereNotIn('status', self::SUDAH_DITUTUP)
            ->with(['pengguna', 'pesanan', 'item.varian.produk'])
            ->orderBy('return_date')
            ->paginate(20);

        return view('kasir.retur.index', [
            'daftar' => $daftar,
            // Yang sudah ditutup ditampilkan terpisah, sepuluh terakhir saja,
            // supaya kasir masih bisa menengok yang barusan dikerjakan.
            'sudahSelesai' => ReturnRequest::where('method', 'toko')
                ->where('store_id', $tokoId)
                ->whereIn('status', ['diproses', 'selesai', 'ditolak'])
                ->with('pengguna')
                ->latest('updated_at')->limit(10)->get(),
        ]);
    }

    public function show(ReturnRequest $returnRequest)
    {
        $this->pastikanMilikToko($returnRequest);

        $returnRequest->load(['pengguna', 'pesanan', 'toko', 'item.varian.produk']);

        /*
         * Stok penggantinya di toko ini, dihitung per barang yang diretur.
         *
         * Kasir perlu tahu ini SEBELUM menjanjikan apa pun kepada pelanggan
         * yang sedang berdiri di depannya. Kalau barangnya kosong di toko,
         * jangan sampai pelanggan disuruh menunggu lalu baru diberi tahu
         * barangnya tidak ada.
         */
        $stokPengganti = [];
        foreach ($returnRequest->item as $baris) {
            if (! $baris->product_variant_id) {
                continue;
            }

            $stok = StoreStock::where('store_id', $returnRequest->store_id)
                ->where('product_variant_id', $baris->product_variant_id)
                ->first();

            $stokPengganti[$baris->product_variant_id] = $stok ? $stok->tersedia() : 0;
        }

        return view('kasir.retur.show', [
            'retur' => $returnRequest,
            'syarat' => self::SYARAT,
            // Daftar alasan penolakan milik kasir, digambar sebagai pilihan.
            'alasanTolak' => ReturnRequest::ALASAN_TOLAK_KASIR,
            'stokPengganti' => $stokPengganti,
        ]);
    }

    /**
     * Langkah 1: kasir memeriksa barangnya di depan pelanggan.
     *
     * Seluruh syarat harus tercentang. Satu saja tidak, pengajuannya
     * ditolak dan alasannya wajib ditulis, sebab alasan itu yang dibaca
     * pelanggan di halaman returnya.
     */
    public function periksa(Request $request, ReturnRequest $returnRequest)
    {
        $this->pastikanMilikToko($returnRequest);

        if (! in_array($returnRequest->status, self::BELUM_DIPERIKSA, true)) {
            return back()->with('gagal', 'Retur ini sudah diperiksa sebelumnya.');
        }

        $data = $request->validate([
            'syarat' => ['nullable', 'array'],
            'catatan' => ['nullable', 'string', 'max:191'],
            // Alasan penolakan dipilih dari daftar milik kasir, yaitu
            // alasan yang memang bisa dinilai sesudah barangnya dipegang.
            // Daftarnya diperiksa lagi di sini sebab pilihan di layar bisa
            // diubah siapa saja lewat alat pengembang.
            'reject_reason' => ['nullable', 'string',
                'in:'.implode(',', array_keys(ReturnRequest::ALASAN_TOLAK_KASIR))],
        ]);

        $dicentang = array_keys($data['syarat'] ?? []);
        // array_diff() memberi syarat yang ADA di daftar tetapi TIDAK
        // tercentang. Lariknya kosong berarti semuanya terpenuhi.
        $belumTerpenuhi = array_diff(array_keys(self::SYARAT), $dicentang);

        if ($belumTerpenuhi !== []) {
            /*
             * ALASANNYA DIPILIH, BUKAN DIKETIK.
             *
             * Dulu kasir hanya diminta mengetik catatan bebas. Kasir yang
             * sedang dikejar antrean menulis satu dua kata, lalu pelanggan
             * membaca penolakan tanpa tahu apa yang harus diperbaiki, dan
             * tidak ada satu pun sebab penolakan yang bisa dihitung di
             * laporan. Sekarang alasannya dipilih dari daftar, dan catatan
             * bebas tinggal menjadi keterangan tambahan.
             */
            if (empty($data['reject_reason'])) {
                return back()->withInput()->with('gagal',
                    'Ada syarat yang belum terpenuhi, jadi alasan penolakannya wajib dipilih. '
                    .'Alasan itu yang dibaca pelanggan.');
            }

            $returnRequest->update([
                'status' => 'ditolak',
                'reject_reason' => $data['reject_reason'],
                'admin_note' => $data['catatan'] ?: null,
            ]);

            $alasan = ReturnRequest::labelAlasanTolak($data['reject_reason']);

            $this->beriTahu($returnRequest, 'Pengajuan retur ditolak',
                'Retur '.$returnRequest->return_code.' ditolak setelah barangnya diperiksa di toko. '
                .'Alasan: '.$alasan.'.'
                .($data['catatan'] ? ' '.$data['catatan'] : ''));

            return redirect()->route('kasir.retur.show', $returnRequest)
                ->with('berhasil', 'Pengajuan ditolak beserta alasannya.');
        }

        $returnRequest->update([
            'status' => 'diperiksa',
            // Tanda tanya ganda dipakai sebab isian yang boleh kosong
            // memang TIDAK ADA kuncinya di hasil validate() ketika formulir
            // dikirim tanpa catatan sama sekali.
            'admin_note' => ($data['catatan'] ?? null) ?: 'Barang lolos pemeriksaan di toko.',
            'approved_at' => now(),
        ]);

        $this->beriTahu($returnRequest, 'Barang retur lolos pemeriksaan',
            'Retur '.$returnRequest->return_code.' sudah diperiksa di toko dan diterima. '
            .'Penggantinya segera disiapkan.');

        return back()->with('berhasil', 'Barang lolos pemeriksaan. Lanjut ke penyerahan pengganti.');
    }

    /**
     * Langkah 2: penggantinya diserahkan, lalu retur ditutup.
     *
     * Stok toko berkurang satu untuk tiap barang pengganti yang benar benar
     * diserahkan, dan pengurangannya tercatat di kartu stok.
     */
    public function selesai(Request $request, ReturnRequest $returnRequest)
    {
        $this->pastikanMilikToko($returnRequest);

        if (! in_array($returnRequest->status, self::SIAP_DISERAHKAN, true)) {
            return back()->with('gagal',
                'Barangnya harus diperiksa lebih dahulu sebelum penggantinya diserahkan.');
        }

        $data = $request->validate([
            'catatan' => ['nullable', 'string', 'max:191'],
        ]);

        DB::transaction(function () use ($returnRequest, $data) {
            foreach ($returnRequest->item as $baris) {
                if (! $baris->product_variant_id) {
                    continue;
                }

                $stok = StoreStock::where('store_id', $returnRequest->store_id)
                    ->where('product_variant_id', $baris->product_variant_id)
                    ->first();

                // Stoknya dikurangi hanya kalau barangnya memang ada. Kalau
                // kosong, returnya tetap ditutup dan kasir menuliskannya di
                // catatan, sebab yang terjadi di meja kasir tidak boleh
                // tertahan hanya karena angka di layar tidak cocok.
                // Kolomnya bernama quantity, bukan qty. Dulu di sini
                // tertulis qty, yang pada Eloquent menghasilkan null, lalu
                // (int) null menjadi nol, sehingga stoknya TIDAK PERNAH
                // berkurang walaupun barangnya benar benar diserahkan.
                if ($stok && $stok->tersedia() >= (int) $baris->quantity) {
                    $stok->keluarTanpaPesanan((int) $baris->quantity,
                        'Barang pengganti retur '.$returnRequest->return_code);
                }
            }

            /*
             * Statusnya menjadi diproses, BUKAN langsung selesai.
             *
             * Yang menutup retur adalah pelanggan, lewat tombol konfirmasi
             * di halaman returnya, persis seperti pesanan biasa. Dengan
             * begitu ada bukti dari pihak pelanggan bahwa barang
             * penggantinya benar benar dia terima, bukan cuma catatan
             * sepihak dari meja kasir.
             *
             * Kalau sampai tiga hari tidak dikonfirmasi, sistem yang
             * menutupnya sendiri, jadi pengajuannya tetap mustahil
             * menggantung.
             */
            $returnRequest->update([
                'status' => 'diproses',
                'admin_note' => ($data['catatan'] ?? null) ?: $returnRequest->admin_note,
            ]);
        });

        $this->beriTahu($returnRequest, 'Barang pengganti sudah diserahkan',
            'Barang pengganti retur '.$returnRequest->return_code.' sudah diserahkan di toko. '
            .'Konfirmasikan di halaman retur supaya pengajuannya ditutup. Kalau tidak, '
            .'sistem menutupnya sendiri sesudah tiga hari.');

        return redirect()->route('kasir.retur.index')
            ->with('berhasil', 'Pengganti retur '.$returnRequest->return_code
                .' tercatat diserahkan. Pengajuannya ditutup sesudah pelanggan mengonfirmasi, '
                .'atau otomatis sesudah tiga hari.');
    }

    /**
     * Membatalkan JADWAL kedatangan retur, bukan menolak barangnya.
     *
     * ---------------------------------------------------------------------
     * KENAPA KASIR PERLU TOMBOL INI
     * ---------------------------------------------------------------------
     *
     * Pengajuan yang jadwalnya lewat tetapi pelanggannya tidak pernah
     * datang akan menggantung di antrean harian selamanya. Kasir
     * melihatnya tiap pagi, tidak bisa mengerjakan apa pun atasnya, dan
     * tidak bisa menyingkirkannya. Lebih buruk lagi, barang pengganti di
     * rak tetap dianggap dijanjikan kepada pengajuan itu.
     *
     * Dulu satu satunya jalan keluar adalah menolaknya memakai alasan
     * penolakan barang, padahal barangnya tidak pernah ada di meja dan
     * tidak ada yang pernah diperiksa. Catatan seperti itu menyesatkan
     * laporan: ia terbaca sebagai barang yang tidak layak, padahal yang
     * terjadi pelanggannya yang tidak datang.
     *
     * ---------------------------------------------------------------------
     * ALASANNYA DIPILIH, BUKAN DIKETIK
     * ---------------------------------------------------------------------
     *
     * Kasir yang sedang dikejar antrean tidak akan mengetik kalimat
     * panjang; dia akan mengetik satu dua kata atau melewatkannya. Jadi
     * alasannya dipilih dari daftar tertutup, cukup satu tekanan, dan
     * catatan bebasnya hanya pelengkap. Hasilnya alasan itu bisa
     * dihitung di laporan, dan kalimat yang dibaca pelanggan selalu
     * utuh.
     */
    // Dipanggil route POST /kasir/retur/{returnRequest}/batal bernama
    // kasir.retur.batal, di dalam kelompok middleware kasir dan hanya untuk
    // retur milik toko kasir itu sendiri.
    public function batal(Request $request, ReturnRequest $returnRequest)
    {
        $this->pastikanMilikToko($returnRequest);

        if (in_array($returnRequest->status, self::SUDAH_DITUTUP, true)) {
            return back()->with('gagal', 'Pengajuan ini sudah ditutup, jadi tidak bisa dibatalkan lagi.');
        }

        $data = $request->validate([
            // Daftarnya diperiksa lagi di server sebab pilihan di layar
            // bisa diubah siapa saja lewat alat pengembang.
            'alasan' => ['required', 'string',
                'in:'.implode(',', array_keys(ReturnRequest::ALASAN_BATAL_JADWAL))],
            'catatan' => ['nullable', 'string', 'max:191'],
        ], [
            'alasan.required' => 'Pilih dulu alasan pembatalannya. Alasan itu yang dibaca pelanggan.',
            'alasan.in' => 'Alasan pembatalan tidak dikenal.',
        ]);

        $alasan = ReturnRequest::labelAlasanBatal($data['alasan']);

        $returnRequest->update([
            'status' => 'dibatalkan',
            'reject_reason' => $data['alasan'],
            'admin_note' => trim($alasan.' '.($data['catatan'] ?? '')),
        ]);

        $this->beriTahu($returnRequest, 'Jadwal retur dibatalkan',
            'Jadwal antar retur '.$returnRequest->return_code.' dibatalkan di toko. '
            .'Alasan: '.$alasan.'.'
            .(($data['catatan'] ?? '') ? ' '.$data['catatan'] : '')
            .' Anda masih bisa mengajukan retur baru selama batas waktunya belum lewat.');

        return redirect()->route('kasir.retur.index')
            ->with('berhasil', 'Jadwal retur '.$returnRequest->return_code.' dibatalkan beserta alasannya.');
    }

    /** Satu baris pemberitahuan untuk pelanggannya. */
    private function beriTahu(ReturnRequest $retur, string $judul, string $isi): void
    {
        if (! $retur->user_id) {
            return;
        }

        Notification::create([
            'category' => 'retur',
            'user_id' => $retur->user_id,
            'title' => $judul,
            'body' => $isi,
            'url' => route('retur.show', $retur),
        ]);
    }
}
