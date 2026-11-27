<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Store;
use App\Models\TapTryCollect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Booking Tap Try and Collect: pelanggan memesan waktu mencoba baju di toko,
 * lalu membelinya di kasir bila cocok.
 */
class TtcAdminController extends Controller
{
    private array $statusBoleh = ['dijadwalkan', 'siap', 'selesai', 'dibatalkan', 'kedaluwarsa'];

    public function index(Request $request)
    {
        $status = $request->query('status');
        $tokoId = $request->query('toko');
        $tanggal = $request->query('tanggal');

        $kueri = TapTryCollect::with(['pengguna', 'toko', 'slot'])->orderByDesc('id');

        if ($status && in_array($status, $this->statusBoleh)) {
            $kueri->where('status', $status);
        }

        if ($tokoId) {
            $kueri->where('store_id', $tokoId);
        }

        if ($tanggal) {
            $kueri->whereHas('slot', function ($slot) use ($tanggal) {
                $slot->whereDate('date', $tanggal);
            });
        }

        return view('admin.ttc.index', [
            'daftar' => $kueri->paginate(20)->withQueryString(),
            'daftarStatus' => $this->statusBoleh,
            'daftarToko' => Store::where('has_ttc', true)->orderBy('name')->get(),
            'status' => $status,
            'tokoId' => $tokoId,
            'tanggal' => $tanggal,
        ]);
    }

    public function show(TapTryCollect $tapTryCollect)
    {
        $tapTryCollect->load(['pengguna', 'toko', 'slot', 'item.varian.produk']);

        return view('admin.ttc.show', [
            'booking' => $tapTryCollect,
            'daftarStatus' => ['siap', 'selesai', 'dibatalkan'],
        ]);
    }

    /** Mengubah status booking. Pembatalan mengembalikan kuota slotnya. */
    public function ubahStatus(Request $request, TapTryCollect $tapTryCollect)
    {
        $data = $request->validate([
            'status' => ['required', 'in:siap,selesai,dibatalkan'],
            'purchase_code' => ['nullable', 'string', 'max:40'],
            'total_paid' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
        ], [
            'status.required' => 'Pilih status baru booking.',
            'status.in' => 'Status booking hanya boleh siap, selesai, atau dibatalkan.',
        ]);

        if (in_array($tapTryCollect->status, ['selesai', 'dibatalkan', 'kedaluwarsa'])) {
            return back()->with('gagal', 'Booking ini sudah ditutup, statusnya tidak bisa diubah lagi.');
        }

        $statusBaru = $data['status'];
        $admin = auth()->user();

        DB::transaction(function () use ($tapTryCollect, $statusBaru, $data, $admin) {
            $tapTryCollect->status = $statusBaru;

            if ($statusBaru === 'selesai') {
                $tapTryCollect->completed_at = now();
                $tapTryCollect->purchase_code = $data['purchase_code'] ?? $tapTryCollect->purchase_code;
                $tapTryCollect->total_paid = (int) ($data['total_paid'] ?? $tapTryCollect->total_paid);
                $tapTryCollect->verified_by = $admin->name;
            }

            if ($statusBaru === 'dibatalkan') {
                // Kuota slot dikembalikan supaya pelanggan lain bisa memakainya
                $slot = $tapTryCollect->slot;
                if ($slot && $slot->booked_count > 0) {
                    $slot->booked_count = $slot->booked_count - 1;
                    $slot->save();
                }
            }

            $tapTryCollect->save();

            Notification::create([
                'user_id' => $tapTryCollect->user_id,
                'title' => 'Booking '.$tapTryCollect->booking_code.' '.$this->labelStatus($statusBaru),
                'body' => $this->keteranganBawaan($statusBaru),
                'url' => route('ttc.show', $tapTryCollect->id),
            ]);
        });

        return redirect()->route('admin.ttc.show', $tapTryCollect)
            ->with('berhasil', 'Status booking '.$tapTryCollect->booking_code.' diubah menjadi '.$this->labelStatus($statusBaru).'.');
    }

    /* ------------------------------------------------------------------ */

    private function labelStatus(string $status): string
    {
        return match ($status) {
            'dijadwalkan' => 'Dijadwalkan',
            'siap' => 'Siap Dicoba di Toko',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            'kedaluwarsa' => 'Kedaluwarsa',
            default => ucfirst($status),
        };
    }

    private function keteranganBawaan(string $status): string
    {
        return match ($status) {
            'siap' => 'Barang pesanan Anda sudah disiapkan di toko dan bisa dicoba pada jam yang dipilih.',
            'selesai' => 'Terima kasih sudah mampir. Kunjungan Tap Try and Collect Anda sudah selesai.',
            'dibatalkan' => 'Booking Tap Try and Collect Anda dibatalkan.',
            default => 'Status booking Anda diperbarui.',
        };
    }
}
