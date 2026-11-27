<?php

namespace App\Console\Commands;

use App\Models\Donation;
use App\Models\Notification;
use App\Models\Order;
use App\Models\TapTryCollect;
use App\Models\TrackingEvent;
use App\Models\Voucher;
use Illuminate\Console\Command;

/**
 * Pekerjaan berkala:
 * 1. membatalkan pesanan yang melewati batas waktu pembayaran,
 * 2. menutup booking Tap Try and Collect yang tidak diambil sampai H+1,
 * 3. menandai pesanan Ambil di Toko yang sudah dibayar tetapi tidak diambil,
 * 4. menutup janji donasi yang pelanggannya tidak datang.
 *
 * Dijalankan otomatis lewat "php artisan schedule:work", atau sekali jalan
 * dengan "php artisan colorbox:perbarui".
 */
class PerbaruiStatus extends Command
{
    protected $signature = 'colorbox:perbarui';

    protected $description = 'Memperbarui status pesanan, donasi, dan Tap Try and Collect yang lewat waktu';

    public function handle(): int
    {
        $jumlahPesanan = $this->batalkanPesananBelumDibayar();
        $jumlahBooking = $this->tutupBookingTidakDiambil();
        $jumlahAmbil = $this->tandaiPesananTidakDiambil();
        $jumlahDonasi = $this->tutupDonasiTidakDatang();
        $jumlahKirim = $this->selaraskanPengiriman();
        $jumlahSelesai = $this->selesaikanYangTidakDikonfirmasi();
        $jumlahRetur = $this->tutupReturTidakDikonfirmasi();
        $jumlahJadwalRetur = $this->batalkanJadwalReturTidakDatang();
        $jumlahTtc = $this->konfirmasiTtcOtomatis();

        $this->info('Pesanan kedaluwarsa: '.$jumlahPesanan.
            ', booking tidak diambil: '.$jumlahBooking.
            ', pesanan toko tidak diambil: '.$jumlahAmbil.
            ', donasi tidak datang: '.$jumlahDonasi.
            ', pengiriman diperbarui: '.$jumlahKirim.
            ', pesanan ditutup otomatis: '.$jumlahSelesai.
            ', retur ditutup otomatis: '.$jumlahRetur.
            ', jadwal retur dibatalkan: '.$jumlahJadwalRetur.
            ', kunjungan dikonfirmasi otomatis: '.$jumlahTtc);

        return self::SUCCESS;
    }

    /**
     * 1. Pesanan yang belum dibayar sampai batas waktu dibatalkan.
     *
     * Aturannya sendiri tinggal di KedaluwarsaPesanan, bukan di sini, sebab
     * aturan yang sama juga dipakai ketika pelanggan membuka halaman
     * pesanannya. Kalau ditulis dua kali, salah satunya pasti ketinggalan
     * ketika aturannya berubah.
     */
    private function batalkanPesananBelumDibayar(): int
    {
        return (new \App\Services\KedaluwarsaPesanan)->sapu();
    }

    /**
     * 2. Booking Tap Try and Collect hangus H+1. Booking jam 14.00 tanggal 1
     * masih berlaku sepanjang tanggal 2, dan baru ditutup lewat tanggal 2.
     */
    /**
     * 2. Booking Tap Try and Collect yang tidak diambil ditutup sendiri.
     *
     * Batasnya jam tutup pengambilan pada hari toleransi, bawaannya pukul
     * 21.00 pada H+1 terhitung dari tanggal slotnya. Lewat dari itu booking
     * ditutup, tahanan stoknya dilepas, dan kuota slotnya dikembalikan tanpa
     * pelanggan perlu membuka situs untuk membatalkan sendiri.
     */
    private function tutupBookingTidakDiambil(): int
    {
        $hari = (int) setting('booking_expire_days', 1);
        $batasAmbil = (string) setting('ttc_pickup_until',
            config('colorbox.slot.batas_ambil_ttc', '21:00'));

        $booking = TapTryCollect::whereIn('status', ['SCHEDULED', 'READY'])
            ->with(['slot', 'item'])
            ->get()
            ->filter(function ($b) use ($hari, $batasAmbil) {
                return $b->slot && $b->slot->mulai()
                    ->addDays($hari)
                    ->setTimeFromTimeString($batasAmbil)
                    ->isPast();
            });

        foreach ($booking as $b) {
            $b->lepasStok();
            $b->update(['status' => 'NOT_PICKED_UP']);
            $b->slot?->decrement('booked_count');

            $this->beriTahu($b->user_id, 'Booking tidak diambil',
                'Booking '.$b->booking_code.' ditutup sendiri oleh sistem karena tidak diambil '
                .'sampai pukul '.jam_singkat($batasAmbil).' pada H+'.$hari.'. '
                .'Barang yang ditahan sudah dijual kembali.',
                route('ttc.show', $b));
        }

        return $booking->count();
    }

    /**
     * 3. Pesanan Ambil di Toko yang sudah dibayar tetapi tidak diambil. Uang
     * pelanggan sudah masuk, jadi pesanan tidak dibatalkan begitu saja
     * melainkan diserahkan ke admin untuk dikirim atau dikembalikan dananya.
     */
    private function tandaiPesananTidakDiambil(): int
    {
        $hari = (int) setting('booking_expire_days', 1);

        $pesanan = Order::where('fulfillment_method', 'STORE_PICKUP')
            ->where('status', 'READY_FOR_PICKUP')
            ->with('booking.slot')
            ->get()
            ->filter(function ($p) use ($hari) {
                $slot = $p->booking?->slot;
                $acuan = $slot ? $slot->mulai() : $p->paid_at;

                return $acuan && $acuan->copy()->addDays($hari)->endOfDay()->isPast();
            });

        foreach ($pesanan as $p) {
            $p->update(['status' => 'NOT_PICKED_UP']);
            TrackingEvent::create([
                'order_id' => $p->id,
                'status' => 'NOT_PICKED_UP',
                'description' => 'Pesanan tidak diambil sampai batas waktu, menunggu tindak lanjut',
                'happened_at' => now(),
            ]);

            $this->beriTahu($p->user_id, 'Pesanan belum diambil',
                'Pesanan '.$p->order_code.' belum diambil sampai batas waktu. Tim kami akan menghubungi Anda untuk pengiriman atau pengembalian dana.',
                route('pesanan.show', $p));
        }

        return $pesanan->count();
    }

    /** 4. Janji donasi yang pelanggannya tidak datang. */
    private function tutupDonasiTidakDatang(): int
    {
        $hari = (int) setting('booking_expire_days', 1);

        $donasi = Donation::where('status', 'SCHEDULED')
            ->with('slot')
            ->get()
            ->filter(function ($d) use ($hari) {
                return $d->slot && $d->slot->mulai()->addDays($hari)->endOfDay()->isPast();
            });

        foreach ($donasi as $d) {
            $d->update(['status' => 'NO_SHOW']);
            $d->slot?->decrement('booked_count');

            $this->beriTahu($d->user_id, 'Jadwal donasi terlewat',
                'Jadwal donasi '.$d->donation_code.' ditutup karena Anda tidak datang. Silakan menjadwalkan ulang kapan saja.',
                route('donasi.show', $d));
        }

        return $donasi->count();
    }

    /**
     * 5. Menanyakan kabar paket kepada penyedia jasa kirim pihak ketiga.
     *
     * Ini jaring pengaman. Jalan utamanya pemberitahuan yang dikirim sendiri
     * oleh penyedianya ke alamat /kurir/notifikasi. Tetapi pemberitahuan bisa
     * tidak sampai, misalnya ketika situsnya sempat mati, jadi di sini
     * kabarnya ditanyakan ulang untuk paket yang masih berjalan.
     *
     * Yang ditanyakan hanya pengiriman yang BELUM sampai dan yang memang
     * dibuat lewat penyedianya, supaya tidak ada panggilan sia sia.
     */
    private function selaraskanPengiriman(): int
    {
        $kurir = new \App\Services\KurirService();

        if (! $kurir->tersedia()) {
            return 0;
        }

        $daftar = \App\Models\Shipment::whereNull('delivered_at')
            ->whereNotNull('provider_order_id')
            // Pengiriman yang baru saja ditanyakan dilewati, supaya perintah
            // yang berjalan tiap menit tidak membanjiri penyedianya. Lima
            // belas menit sudah cukup rapat untuk paket yang berjalan berhari
            // hari. Yang belum pernah ditanyakan ikut terbawa oleh orWhereNull.
            ->where(fn ($q) => $q->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', now()->subMinutes(15)))
            ->with('pesanan')
            ->limit(30)
            ->get();

        $pengurus = new \App\Http\Controllers\KurirController();
        $jumlah = 0;

        foreach ($daftar as $kirim) {
            if ($pengurus->selaraskan($kirim, $kurir)) {
                $jumlah++;
            }
        }

        return $jumlah;
    }

    private function beriTahu(int $userId, string $judul, string $isi, string $tautan): void
    {
        Notification::create([
            'category' => 'pesanan',
            'user_id' => $userId,
            'title' => $judul,
            'body' => $isi,
            'url' => $tautan,
        ]);
    }

    /**
     * Pesanan yang sudah sampai tetapi tidak pernah dikonfirmasi pelanggan
     * ditutup sendiri sesudah tiga hari.
     *
     * ALASANNYA
     *
     * Pelanggan punya tombol Barang Sudah Diterima, dan menekannya membuka
     * ulasan serta pengajuan retur. Masalahnya banyak orang tidak pernah
     * menekan tombol itu: barangnya sudah di tangan, urusannya selesai, dan
     * situsnya tidak dibuka lagi.
     *
     * Kalau dibiarkan, pesanan seperti itu menggantung selamanya. Pelanggan
     * tidak bisa menulis ulasan, tidak bisa mengajukan retur, dan laporan
     * penjualan tidak pernah menganggapnya tuntas.
     *
     * Jadi sesudah tiga hari sejak barangnya sampai, sistem yang menutupnya.
     * Tiga hari dipilih sebab itu jeda yang masuk akal untuk membuka paket
     * dan memeriksa isinya. Batas tujuh hari untuk mengajukan retur baru
     * dihitung dari saat penutupan ini, jadi pelanggan tidak kehilangan
     * haknya gara gara lupa menekan tombol.
     *
     * Yang ditutup hanya pesanan yang barangnya MEMANG sudah sampai, yaitu
     * yang statusnya sedang dikirim atau sudah diambil di toko. Pesanan yang
     * masih dikemas tidak tersentuh.
     */
    private function selesaikanYangTidakDikonfirmasi(): int
    {
        $batas = now()->subDays(3);

        $pesanan = \App\Models\Order::whereIn('status', ['IN_TRANSIT', 'PICKED_UP'])
            // completed_at kosong berarti pelanggan belum pernah menekan
            // tombol Barang Sudah Diterima.
            ->whereNull('completed_at')
            ->where('updated_at', '<', $batas)
            ->get();

        foreach ($pesanan as $satu) {
            $satu->update(['status' => 'DELIVERED', 'completed_at' => now()]);

            if ($satu->pengiriman && $satu->pengiriman->status !== 'DELIVERED') {
                $satu->pengiriman->update(['status' => 'DELIVERED', 'delivered_at' => now()]);
            }

            \App\Models\TrackingEvent::create([
                'order_id' => $satu->id,
                'status' => 'DELIVERED',
                'description' => 'Pesanan ditutup sendiri oleh sistem sebab tidak dikonfirmasi '
                    .'sampai tiga hari setelah barangnya sampai',
                'happened_at' => now(),
            ]);

            if ($satu->user_id) {
                \App\Models\Notification::create([
                    'category' => 'pesanan',
                    'user_id' => $satu->user_id,
                    'title' => 'Pesanan ditutup otomatis',
                    'body' => 'Pesanan '.$satu->order_code.' ditutup sendiri sebab tidak '
                        .'dikonfirmasi sampai tiga hari. Ulasan dan pengajuan retur sudah terbuka.',
                    'url' => route('pesanan.show', $satu),
                ]);
            }
        }

        return $pesanan->count();
    }

    /**
     * 7. Retur yang barang penggantinya sudah diserahkan tetapi tidak pernah
     *    dikonfirmasi pelanggan.
     *
     * Retur ditutup oleh pelanggan, lewat tombol konfirmasi di halaman
     * returnya. Alasannya: yang paling tahu barang penggantinya benar benar
     * diterima adalah pelanggan, bukan admin yang tidak pernah memegangnya.
     *
     * Tetapi banyak orang tidak pernah menekan tombol itu. Barangnya sudah
     * di tangan, urusannya selesai, dan situsnya tidak dibuka lagi. Kalau
     * dibiarkan, pengajuan seperti itu menggantung selamanya di status
     * Menunggu Konfirmasi, dan itulah yang dulu terjadi.
     *
     * Jadi sesudah tiga hari, sistem yang menutupnya. Tiga hari dipakai
     * supaya seragam dengan penutupan pesanan biasa di method sebelumnya.
     *
     * Aturan penutupannya sendiri, termasuk pengembalian stok barang yang
     * diretur, tinggal di App\Services\PenutupanRetur, satu tempat saja,
     * supaya mustahil berbeda dengan penutupan yang dilakukan pelanggan.
     */
    private function tutupReturTidakDikonfirmasi(): int
    {
        $batas = now()->subDays(3);

        $daftar = \App\Models\ReturnRequest::where('status', 'diproses')
            ->whereNull('completed_at')
            ->where('updated_at', '<', $batas)
            ->with('item')
            ->get();

        $penutup = new \App\Services\PenutupanRetur;
        $jumlah = 0;

        foreach ($daftar as $retur) {
            if ($penutup->tutup($retur, 'sistem')) {
                $jumlah++;
            }
        }

        return $jumlah;
    }

    /**
     * 8. Jadwal antar retur ke toko yang pelanggannya tidak pernah datang.
     *
     * ---------------------------------------------------------------------
     * KENAPA HARUS DIBATALKAN SENDIRI OLEH SISTEM
     * ---------------------------------------------------------------------
     *
     * Pengajuan retur yang disetujui MENAHAN barang pengganti di rak toko.
     * Selama pengajuannya hidup, barang itu dianggap sudah dijanjikan
     * kepada orang tersebut, jadi pembeli lain tidak bisa membelinya.
     *
     * Kalau pelanggannya tidak pernah datang dan tidak ada yang menutup
     * pengajuannya, barang itu tertahan selamanya. Kasir memang punya
     * tombol untuk membatalkannya sendiri, tetapi mengandalkan ingatan
     * orang untuk membersihkan sisa pekerjaan tiap hari tidak pernah
     * bertahan lama; cepat atau lambat ada yang terlewat, dan rak toko
     * kehilangan barang tanpa sebab yang kelihatan.
     *
     * Jadi sesudah batas harinya lewat, sistem yang membatalkan. Tiga hari
     * dipakai sebagai nilai bawaan: cukup longgar untuk halangan biasa
     * seperti sakit atau lupa, tetapi tidak menggantung rak toko sampai
     * berminggu minggu. Angkanya dibaca dari pengaturan supaya admin bisa
     * mengubahnya tanpa menyentuh kode.
     *
     * Alasannya ditulis dengan kode yang SAMA dengan yang dipakai kasir,
     * yaitu tidak_datang, supaya laporan tidak perlu membedakan mana yang
     * dibatalkan orang dan mana yang dibatalkan sistem.
     */
    private function batalkanJadwalReturTidakDatang(): int
    {
        $hari = max(1, (int) setting('retur_batal_hari', 3));
        $batas = now()->subDays($hari)->toDateString();

        $daftar = \App\Models\ReturnRequest::where('method', 'toko')
            // Hanya yang masih menunggu kedatangan. Yang sudah diperiksa
            // berarti barangnya memang sampai, jadi bukan urusan di sini.
            ->whereIn('status', ['menunggu_validasi', 'disetujui'])
            ->whereNotNull('return_date')
            ->whereDate('return_date', '<', $batas)
            ->get();

        $jumlah = 0;

        foreach ($daftar as $retur) {
            $retur->update([
                'status' => 'dibatalkan',
                'reject_reason' => 'tidak_datang',
                'admin_note' => 'Dibatalkan sistem: barangnya tidak pernah diantar sampai '
                    .$hari.' hari sesudah jadwalnya.',
            ]);

            $this->beriTahu(
                (int) $retur->user_id,
                'Jadwal retur dibatalkan',
                'Retur '.$retur->return_code.' dibatalkan sebab barangnya tidak diantar sampai '
                .$hari.' hari sesudah jadwal yang Anda pilih. Anda masih bisa mengajukan retur '
                .'baru selama batas waktunya belum lewat.',
                route('retur.show', $retur)
            );

            $jumlah++;
        }

        return $jumlah;
    }

    /**
     * 9. Kunjungan Tap Try and Collect yang tidak pernah dikonfirmasi
     *    pelanggan.
     *
     * Pelanggan menyatakan sendiri bahwa bajunya dia bawa pulang, sepasang
     * dengan tombol Barang Sudah Diterima pada pesanan daring. Tetapi
     * banyak yang tidak pernah menekannya: bajunya sudah di tangan sejak
     * dari meja kasir, dan situsnya tidak dibuka lagi.
     *
     * Jadi sesudah tiga hari sistem yang menutupnya, seragam dengan
     * penutupan pesanan dan retur di dua method sebelumnya.
     */
    private function konfirmasiTtcOtomatis(): int
    {
        $batas = now()->subDays(3);

        $daftar = \App\Models\TapTryCollect::where('status', 'COLLECTED')
            ->whereNull('confirmed_at')
            ->where('updated_at', '<', $batas)
            ->get();

        foreach ($daftar as $satu) {
            $satu->update(['confirmed_at' => now()]);
        }

        return $daftar->count();
    }
}
