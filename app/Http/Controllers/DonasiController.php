<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\DonationSlot;
use App\Models\Notification;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Donasi pakaian: pelanggan menjadwalkan kunjungan ke toko, menyerahkan
 * pakaian, lalu mendapat poin setelah staf toko memeriksanya.
 * Slot donasi memakai tabel donation_slots sendiri, terpisah dari slot
 * Tap Try and Collect (kuota dan jamnya berbeda).
 */
class DonasiController extends Controller
{
    /** Halaman penjadwalan donasi. Boleh dilihat tamu. */
    public function index()
    {
        // Batas tanggal yang boleh dipesan, dipakai kalender pada halaman donasi
        $jumlahHari = max(1, (int) setting('booking_window_days', 14));

        return view('donasi.index', [
            'toko' => Store::where('is_active', true)->where('has_donation', true)->orderBy('city')->orderBy('name')->get(),
            'tanggalAwal' => now()->toDateString(),
            'tanggalAkhir' => now()->addDays($jumlahHari - 1)->toDateString(),
            'minItem' => (int) setting('min_donation_items', 1),
            'maksItem' => (int) setting('max_donation_items', 50),
            'poinPerPotong' => (int) setting('donation_points_per_item', 1),
        ]);
    }

    /**
     * Daftar jam donasi pada satu toko dan satu tanggal, dibaca dari tabel
     * donation_slots. Dipanggil halaman index memakai fetch.
     */
    public function slot(Request $request)
    {
        $data = $request->validate([
            'toko' => ['required', 'integer', 'exists:stores,id'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
        ]);

        $daftar = DonationSlot::where('store_id', $data['toko'])
            ->whereDate('date', $data['tanggal'])
            ->where('status', 'aktif')
            ->orderBy('start_time')
            ->get();

        $slot = [];
        foreach ($daftar as $satu) {
            $slot[] = [
                'id' => $satu->id,
                'jam' => jam_singkat($satu->start_time).' - '.jam_singkat($satu->end_time),
                'sisa' => $satu->sisaKuota(),
                'penuh' => $satu->sisaKuota() < 1,
            ];
        }

        return response()->json(['slot' => $slot]);
    }

    /** Menyimpan jadwal donasi. Kuota diperiksa ulang di dalam transaksi. */
    public function store(Request $request)
    {
        $minItem = (int) setting('min_donation_items', 1);
        $maksItem = (int) setting('max_donation_items', 50);

        $data = $request->validate([
            'donation_slot_id' => ['required', 'integer', 'exists:donation_slots,id'],
            'estimated_items' => ['required', 'integer', 'min:'.$minItem, 'max:'.$maksItem],
            'condition_note' => ['nullable', 'string', 'max:191'],
        ], [
            'donation_slot_id.required' => 'Pilih tanggal dan jam penyerahan terlebih dahulu.',
            'estimated_items.min' => 'Jumlah pakaian paling sedikit '.$minItem.' potong.',
            'estimated_items.max' => 'Jumlah pakaian paling banyak '.$maksItem.' potong.',
        ]);

        $slot = DonationSlot::with('toko')->find($data['donation_slot_id']);

        if (! $slot || $slot->status !== 'aktif') {
            return back()->withInput()->with('gagal', 'Jam yang dipilih sudah tidak dilayani. Silakan pilih waktu lainnya.');
        }

        if ($slot->date->toDateString() < now()->toDateString()) {
            return back()->withInput()->with('gagal', 'Tanggal yang dipilih sudah lewat. Silakan pilih tanggal lainnya.');
        }

        if (! $slot->toko || ! $slot->toko->is_active || ! $slot->toko->has_donation) {
            return back()->withInput()->with('gagal', 'Toko tersebut belum menerima donasi pakaian.');
        }

        // Satu pelanggan hanya boleh punya satu jadwal aktif pada slot yang sama
        $sudahAda = Donation::where('user_id', auth()->id())
            ->where('donation_slot_id', $slot->id)
            ->whereIn('status', ['dijadwalkan', 'check_in', 'menunggu_validasi'])
            ->exists();

        if ($sudahAda) {
            return back()->withInput()->with('gagal', 'Anda sudah memiliki jadwal donasi pada jam tersebut.');
        }

        // Kuota diperiksa ULANG di dalam transaksi, bukan hanya di JavaScript
        $donasi = DB::transaction(function () use ($slot, $data) {
            $terkunci = DonationSlot::where('id', $slot->id)->lockForUpdate()->first();

            if ($terkunci->booked_count >= $terkunci->quota) {
                return null;
            }

            $urut = Donation::whereDate('created_at', now()->toDateString())->count() + 1;

            $baru = Donation::create([
                'donation_code' => 'DON-'.now()->format('Ymd').'-'.str_pad($urut, 4, '0', STR_PAD_LEFT),
                'user_id' => auth()->id(),
                'donation_slot_id' => $terkunci->id,
                'store_id' => $terkunci->store_id,
                'estimated_items' => (int) $data['estimated_items'],
                'condition_note' => $data['condition_note'] ?? null,
                'status' => 'dijadwalkan',
                'points_awarded' => 0,
            ]);

            $terkunci->booked_count = $terkunci->booked_count + 1;
            $terkunci->save();

            return $baru;
        });

        if (! $donasi) {
            return back()->withInput()->with('gagal', 'Maaf, slot baru saja penuh. Silakan pilih waktu lainnya.');
        }

        Notification::create([
            'user_id' => auth()->id(),
            'title' => 'Jadwal donasi tercatat',
            'body' => 'Kode '.$donasi->donation_code.' pada '.tanggal_id($slot->date, true)
                .' pukul '.jam_singkat($slot->start_time).' di '.$slot->toko->name.'.',
            'url' => route('donasi.show', $donasi),
        ]);

        return redirect()->route('donasi.show', $donasi)
            ->with('berhasil', 'Jadwal donasi '.$donasi->donation_code.' berhasil dibuat.');
    }

    /** Riwayat donasi milik pelanggan yang sedang masuk. */
    public function riwayat()
    {
        $daftar = Donation::where('user_id', auth()->id())
            ->with(['slot', 'toko'])
            ->orderByDesc('id')
            ->get();

        return view('donasi.riwayat', [
            'daftar' => $daftar,
            'poinPerPotong' => (int) setting('donation_points_per_item', 1),
        ]);
    }

    /** Rincian satu jadwal donasi beserta QR untuk staf toko. */
    public function show(Donation $donation)
    {
        abort_unless($donation->user_id === auth()->id(), 404);

        $donation->load(['slot', 'toko']);

        return view('donasi.show', [
            'donasi' => $donation,
            'poinPerPotong' => (int) setting('donation_points_per_item', 1),
            'alamatStaf' => route('staf.verifikasi', ['jenis' => 'donasi', 'kode' => $donation->donation_code]),
        ]);
    }

    /** Pelanggan menekan "Saya sudah di toko" pada hari jadwalnya. */
    public function checkIn(Donation $donation)
    {
        abort_unless($donation->user_id === auth()->id(), 404);

        if ($donation->status !== 'dijadwalkan') {
            return back()->with('gagal', 'Jadwal donasi ini sudah tidak bisa di-check-in.');
        }

        $donation->load('slot');

        if (! $donation->slot || $donation->slot->date->toDateString() !== now()->toDateString()) {
            return back()->with('gagal', 'Check-in hanya bisa dilakukan pada hari jadwal donasi Anda.');
        }

        $donation->status = 'check_in';
        $donation->checked_in_at = now();
        $donation->save();

        return back()->with('berhasil', 'Check-in tercatat. Silakan tunjukkan QR kepada staf toko.');
    }

    /** Membatalkan jadwal donasi dan mengembalikan kuota slotnya. */
    public function batal(Donation $donation)
    {
        abort_unless($donation->user_id === auth()->id(), 404);

        if (! in_array($donation->status, ['dijadwalkan', 'check_in'])) {
            return back()->with('gagal', 'Jadwal donasi ini sudah tidak bisa dibatalkan.');
        }

        DB::transaction(function () use ($donation) {
            $slot = DonationSlot::where('id', $donation->donation_slot_id)->lockForUpdate()->first();

            if ($slot && $slot->booked_count > 0) {
                $slot->booked_count = $slot->booked_count - 1;
                $slot->save();
            }

            $donation->status = 'dibatalkan';
            $donation->save();
        });

        return redirect()->route('donasi.show', $donation)
            ->with('berhasil', 'Jadwal donasi '.$donation->donation_code.' dibatalkan.');
    }
}
