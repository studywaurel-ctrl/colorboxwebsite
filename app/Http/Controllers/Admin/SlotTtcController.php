<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\TapTryCollectSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Jadwal Tap Try and Collect di toko.
 *
 * Tabel yang dipakai hanya tap_try_collect_slots. Slot donasi punya tabel dan
 * controller sendiri supaya kuotanya tidak pernah tercampur.
 */
class SlotTtcController extends Controller
{
    public function index(Request $request)
    {
        $tokoId = $request->query('toko');
        $tanggal = $request->query('tanggal');

        $kueri = TapTryCollectSlot::with('toko')
            ->orderBy('date')
            ->orderBy('start_time');

        if ($tokoId) {
            $kueri->where('store_id', $tokoId);
        }

        if ($tanggal) {
            $kueri->whereDate('date', $tanggal);
        }

        return view('admin.slot-ttc.index', [
            'daftar' => $kueri->paginate(30)->withQueryString(),
            'daftarToko' => Store::where('has_ttc', true)->orderBy('name')->get(),
            'tokoId' => $tokoId,
            'tanggal' => $tanggal,
        ]);
    }

    /** Formulir pembuatan slot secara massal. */
    public function create()
    {
        return view('admin.slot-ttc.form', [
            'daftarToko' => Store::where('has_ttc', true)->orderBy('name')->get(),
            'daftarJam' => config('colorbox.slot.jam_ttc', []),
            'kuotaBawaan' => (int) setting('ttc_slot_quota', config('colorbox.slot.kuota_ttc', 4)),
        ]);
    }

    /** Membuat banyak slot sekaligus untuk rentang tanggal yang dipilih. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'dari' => ['required', 'date'],
            'sampai' => ['required', 'date', 'after_or_equal:dari'],
            'jam' => ['required', 'array', 'min:1'],
            'jam.*' => ['required', 'date_format:H:i'],
            'quota' => ['required', 'integer', 'min:1', 'max:200'],
        ], [
            'store_id.required' => 'Pilih toko yang melayani Tap Try and Collect.',
            'dari.required' => 'Tanggal mulai wajib diisi.',
            'sampai.required' => 'Tanggal akhir wajib diisi.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal daripada tanggal mulai.',
            'jam.required' => 'Centang paling sedikit satu jam layanan.',
            'quota.required' => 'Kuota slot wajib diisi.',
        ]);

        $mulai = Carbon::parse($data['dari'])->startOfDay();
        $akhir = Carbon::parse($data['sampai'])->startOfDay();

        if ($mulai->diffInDays($akhir) > 90) {
            return back()->withInput()->with('gagal', 'Rentang tanggal paling panjang 90 hari sekali buat.');
        }

        $dibuat = 0;
        $dilewati = 0;

        for ($hari = $mulai->copy(); $hari->lte($akhir); $hari->addDay()) {
            foreach ($data['jam'] as $jam) {
                $mulaiJam = $jam.':00';
                $selesaiJam = Carbon::createFromFormat('H:i', $jam)->addHour()->format('H:i:s');

                $sudahAda = TapTryCollectSlot::where('store_id', $data['store_id'])
                    ->whereDate('date', $hari->toDateString())
                    ->where('start_time', $mulaiJam)
                    ->exists();

                // Slot yang sudah ada tidak ditimpa supaya booked_count tetap aman
                TapTryCollectSlot::updateOrCreate(
                    [
                        'store_id' => $data['store_id'],
                        'date' => $hari->toDateString(),
                        'start_time' => $mulaiJam,
                    ],
                    $sudahAda ? [] : [
                        'end_time' => $selesaiJam,
                        'quota' => $data['quota'],
                        'booked_count' => 0,
                        'status' => 'aktif',
                    ]
                );

                if ($sudahAda) {
                    $dilewati++;
                } else {
                    $dibuat++;
                }
            }
        }

        return redirect()->route('admin.slot-ttc.index')
            ->with('berhasil', $dibuat.' slot Tap Try and Collect baru dibuat, '.$dilewati.' slot dilewati karena sudah ada.');
    }

    public function edit(TapTryCollectSlot $tapTryCollectSlot)
    {
        $tapTryCollectSlot->load('toko');

        return view('admin.slot-ttc.edit', ['slot' => $tapTryCollectSlot]);
    }

    /** Mengubah kuota dan status satu slot. */
    public function update(Request $request, TapTryCollectSlot $tapTryCollectSlot)
    {
        $data = $request->validate([
            'quota' => ['required', 'integer', 'min:0', 'max:200'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ], [
            'quota.required' => 'Kuota wajib diisi.',
            'status.in' => 'Status slot hanya boleh aktif atau nonaktif.',
        ]);

        // Kuota tidak boleh lebih kecil daripada booking yang sudah masuk
        if ((int) $data['quota'] < (int) $tapTryCollectSlot->booked_count) {
            return back()->withInput()->with('gagal',
                'Kuota tidak boleh lebih kecil dari jumlah booking yang sudah masuk ('.$tapTryCollectSlot->booked_count.').');
        }

        $tapTryCollectSlot->update([
            'quota' => $data['quota'],
            'status' => $data['status'],
        ]);

        return redirect()->route('admin.slot-ttc.index')
            ->with('berhasil', 'Slot Tap Try and Collect tanggal '.tanggal_id($tapTryCollectSlot->date).' pukul '.jam_singkat($tapTryCollectSlot->start_time).' sudah diperbarui.');
    }

    /** Menghapus slot. Ditolak bila sudah ada yang memesan. */
    public function destroy(TapTryCollectSlot $tapTryCollectSlot)
    {
        if ((int) $tapTryCollectSlot->booked_count > 0) {
            return back()->with('gagal',
                'Slot ini tidak bisa dihapus karena sudah dipesan '.$tapTryCollectSlot->booked_count.' pelanggan. Ubah statusnya menjadi nonaktif saja.');
        }

        $tapTryCollectSlot->delete();

        return redirect()->route('admin.slot-ttc.index')
            ->with('berhasil', 'Slot Tap Try and Collect sudah dihapus.');
    }
}
