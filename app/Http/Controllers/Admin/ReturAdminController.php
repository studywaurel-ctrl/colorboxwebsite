<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pengajuan retur dan penukaran barang dari pelanggan.
 *
 * Stok hanya dikembalikan ketika retur selesai dan jenisnya tukar,
 * karena barang tukar yang lama masuk kembali ke stok.
 */
class ReturAdminController extends Controller
{
    /** Status retur yang boleh dipilih admin. */
    private array $statusBoleh = [
        'menunggu_validasi', 'disetujui', 'ditolak', 'dikirim',
        'diperiksa', 'diproses', 'selesai', 'dibatalkan',
    ];

    public function index(Request $request)
    {
        $status = $request->query('status');

        $kueri = ReturnRequest::with(['pengguna', 'pesanan', 'toko'])->orderByDesc('id');

        if ($status && in_array($status, $this->statusBoleh)) {
            $kueri->where('status', $status);
        }

        return view('admin.retur.index', [
            'daftar' => $kueri->paginate(20)->withQueryString(),
            'daftarStatus' => $this->statusBoleh,
            'status' => $status,
        ]);
    }

    public function show(ReturnRequest $returnRequest)
    {
        $returnRequest->load(['pengguna', 'pesanan', 'toko', 'item.varian']);

        return view('admin.retur.show', [
            'retur' => $returnRequest,
            'daftarStatus' => ['disetujui', 'ditolak', 'diperiksa', 'diproses', 'selesai'],
        ]);
    }

    /** Mengubah status retur dan mencatat catatan admin. */
    public function ubahStatus(Request $request, ReturnRequest $returnRequest)
    {
        $data = $request->validate([
            'status' => ['required', 'in:disetujui,ditolak,diperiksa,diproses,selesai'],
            'admin_note' => ['nullable', 'string', 'max:191'],
        ], [
            'status.required' => 'Pilih status baru pengajuan retur.',
            'status.in' => 'Status retur yang dipilih tidak dikenal.',
        ]);

        if (in_array($returnRequest->status, ['selesai', 'dibatalkan'])) {
            return back()->with('gagal', 'Pengajuan retur ini sudah ditutup, statusnya tidak bisa diubah lagi.');
        }

        if ($data['status'] === 'ditolak' && empty($data['admin_note'])) {
            return back()->with('gagal', 'Tuliskan alasan penolakan pada catatan admin.');
        }

        $statusBaru = $data['status'];

        DB::transaction(function () use ($returnRequest, $statusBaru, $data) {
            $returnRequest->status = $statusBaru;
            $returnRequest->admin_note = $data['admin_note'] ?? $returnRequest->admin_note;

            if ($statusBaru === 'disetujui' && ! $returnRequest->approved_at) {
                $returnRequest->approved_at = now();
            }

            if ($statusBaru === 'selesai') {
                $returnRequest->completed_at = now();

                // Barang tukar yang dikembalikan masuk lagi ke stok
                if ($returnRequest->type === 'tukar') {
                    foreach ($returnRequest->item as $baris) {
                        if (! $baris->product_variant_id) {
                            continue;
                        }
                        $varian = ProductVariant::find($baris->product_variant_id);
                        if ($varian) {
                            $varian->stock = $varian->stock + (int) $baris->quantity;
                            $varian->save();
                        }
                    }
                }
            }

            $returnRequest->save();

            Notification::create([
                'user_id' => $returnRequest->user_id,
                'title' => 'Retur '.$returnRequest->return_code.' '.$this->labelStatus($statusBaru),
                'body' => $returnRequest->admin_note ?: $this->keteranganBawaan($statusBaru),
                'url' => route('retur.show', $returnRequest->id),
            ]);
        });

        return redirect()->route('admin.retur.show', $returnRequest)
            ->with('berhasil', 'Status retur '.$returnRequest->return_code.' diubah menjadi '.$this->labelStatus($statusBaru).'.');
    }

    /* ------------------------------------------------------------------ */

    private function labelStatus(string $status): string
    {
        return match ($status) {
            'menunggu_validasi' => 'Menunggu Validasi',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'dikirim' => 'Barang Dikirim Pelanggan',
            'diperiksa' => 'Sedang Diperiksa',
            'diproses' => 'Sedang Diproses',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function keteranganBawaan(string $status): string
    {
        return match ($status) {
            'disetujui' => 'Pengajuan retur Anda disetujui. Silakan ikuti petunjuk pengiriman barang.',
            'ditolak' => 'Pengajuan retur Anda belum dapat kami setujui.',
            'diperiksa' => 'Barang retur Anda sedang kami periksa.',
            'diproses' => 'Retur Anda sedang diproses.',
            'selesai' => 'Retur Anda sudah selesai. Terima kasih atas kesabarannya.',
            default => 'Status pengajuan retur Anda diperbarui.',
        };
    }
}
