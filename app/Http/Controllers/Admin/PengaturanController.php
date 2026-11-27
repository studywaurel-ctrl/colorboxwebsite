<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Aturan bisnis yang boleh diubah admin, misalnya batas ongkir gratis,
 * poin per potong donasi, dan batas hari pengajuan retur.
 */
class PengaturanController extends Controller
{
    /** Kunci yang nilainya harus berupa angka. */
    private array $kunciAngka = [
        'free_shipping_threshold',
        'donation_points_per_item',
        'point_value',
        'points_max_percent',
        'return_window_days',
        'payment_expiry_hours',
        'ttc_slot_quota',
        'donation_slot_quota',
        'booking_window_days',
        'min_donation_items',
        'max_donation_items',
    ];

    public function index()
    {
        return view('admin.pengaturan.index', [
            'daftar' => Setting::orderBy('key')->get(),
            'kunciAngka' => $this->kunciAngka,
        ]);
    }

    /** Menyimpan seluruh nilai pengaturan dari satu formulir. */
    public function update(Request $request)
    {
        $kiriman = $request->input('nilai', []);

        if (! is_array($kiriman) || count($kiriman) === 0) {
            return back()->with('gagal', 'Tidak ada pengaturan yang dikirim.');
        }

        // Kunci berisi angka divalidasi lebih dahulu supaya tidak merusak hitungan
        $aturan = [];
        $pesan = [];
        foreach (array_keys($kiriman) as $kunci) {
            if (in_array($kunci, $this->kunciAngka)) {
                $aturan['nilai.'.$kunci] = ['required', 'numeric', 'min:0'];
                $pesan['nilai.'.$kunci.'.required'] = 'Nilai '.$kunci.' wajib diisi.';
                $pesan['nilai.'.$kunci.'.numeric'] = 'Nilai '.$kunci.' harus berupa angka.';
                $pesan['nilai.'.$kunci.'.min'] = 'Nilai '.$kunci.' tidak boleh kurang dari 0.';
            } else {
                $aturan['nilai.'.$kunci] = ['nullable', 'string', 'max:500'];
            }
        }

        $request->validate($aturan, $pesan);

        $jumlah = 0;
        foreach ($kiriman as $kunci => $nilai) {
            // Hanya kunci yang memang sudah ada di tabel settings yang disimpan
            if (! Setting::where('key', $kunci)->exists()) {
                continue;
            }
            Setting::simpan($kunci, $nilai);
            $jumlah++;
        }

        return redirect()->route('admin.pengaturan.index')
            ->with('berhasil', $jumlah.' pengaturan sudah disimpan.');
    }
}
