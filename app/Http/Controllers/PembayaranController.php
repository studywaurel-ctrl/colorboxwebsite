<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TrackingEvent;
use App\Services\MidtransService;
use Illuminate\Http\Request;

/**
 * Pembayaran memakai Midtrans Snap.
 *
 * Status pembayaran tidak pernah diubah pelanggan. Perubahannya hanya berasal
 * dari notifikasi server Midtrans atau dari jawaban Midtrans ketika status
 * ditanyakan, jadi tidak ada tombol "Saya sudah membayar" di sini.
 */
class PembayaranController extends Controller
{
    /** Halaman pembayaran dengan tombol yang membuka jendela Snap. */
    public function show(Payment $payment)
    {
        $pesanan = $payment->pesanan;
        if (! $pesanan || $pesanan->user_id !== auth()->id()) {
            abort(403);
        }

        $midtrans = new MidtransService();

        return view('pembayaran.show', [
            'pembayaran' => $payment,
            'pesanan' => $pesanan->load(['item', 'alamat', 'toko']),
            'midtransAktif' => $midtrans->aktif(),
        ]);
    }

    /**
     * Ditanyakan berkala oleh halaman pembayaran memakai fetch().
     * Jawabannya berupa JSON berisi status pembayaran terakhir.
     */
    public function status(Payment $payment)
    {
        $pesanan = $payment->pesanan;
        if (! $pesanan || $pesanan->user_id !== auth()->id()) {
            abort(403);
        }

        $midtrans = new MidtransService();
        $data = $midtrans->ambilStatus($pesanan->order_code);

        if (is_array($data) && isset($data['transaction_status'])) {
            $this->terapkanStatus($payment, $data);
        }

        return response()->json([
            'status' => $payment->fresh()->payment_status,
            'pesanan' => $pesanan->fresh()->status,
        ]);
    }

    /** Halaman yang dibuka setelah pelanggan menutup jendela Snap. */
    public function selesai(Payment $payment)
    {
        $pesanan = $payment->pesanan;
        if (! $pesanan || $pesanan->user_id !== auth()->id()) {
            abort(403);
        }

        $midtrans = new MidtransService();
        $data = $midtrans->ambilStatus($pesanan->order_code);
        if (is_array($data) && isset($data['transaction_status'])) {
            $this->terapkanStatus($payment, $data);
        }

        return view('pembayaran.selesai', [
            'pembayaran' => $payment->fresh(),
            'pesanan' => $pesanan->fresh(['item']),
        ]);
    }

    /**
     * Notifikasi yang dikirim server Midtrans. Tidak memakai token CSRF,
     * karena itu tanda tangannya wajib diperiksa lebih dulu.
     */
    public function notifikasi(Request $request)
    {
        $midtrans = new MidtransService();

        if (! $midtrans->tandaTanganSah($request->all())) {
            abort(403);
        }

        $pesanan = Order::where('order_code', $request->input('order_id'))->first();
        if (! $pesanan || ! $pesanan->pembayaran) {
            abort(404);
        }

        $this->terapkanStatus($pesanan->pembayaran, $request->all());

        return response()->json(['pesan' => 'ok']);
    }

    /**
     * Menyimpan status terbaru dari Midtrans ke tabel payments, lalu
     * menyesuaikan status pesanannya. Dipakai bersama oleh status() dan
     * notifikasi() supaya aturannya hanya ditulis satu kali.
     */
    private function terapkanStatus(Payment $pembayaran, array $data): void
    {
        $midtrans = new MidtransService();
        $status = $midtrans->statusColorbox($data);

        $pembayaran->update([
            'transaction_status' => $data['transaction_status'] ?? $pembayaran->transaction_status,
            'payment_status' => $status,
            'payment_method' => $data['payment_type'] ?? $pembayaran->payment_method,
            'transaction_id' => $data['transaction_id'] ?? $pembayaran->transaction_id,
            'raw_response' => json_encode($data),
            'paid_at' => $status === 'lunas' ? ($pembayaran->paid_at ?: now()) : $pembayaran->paid_at,
        ]);

        $pesanan = $pembayaran->pesanan;
        if (! $pesanan) {
            return;
        }

        // Pembayaran berhasil: pesanan menunggu diproses toko
        if ($status === 'lunas' && $pesanan->status === 'menunggu_pembayaran') {
            $pesanan->update(['status' => 'dibayar', 'paid_at' => now()]);

            TrackingEvent::create([
                'order_id' => $pesanan->id,
                'status' => 'dibayar',
                'description' => 'Pembayaran diterima, pesanan segera diproses',
                'happened_at' => now(),
            ]);

            Notification::create([
                'user_id' => $pesanan->user_id,
                'title' => 'Pembayaran diterima',
                'body' => 'Pembayaran pesanan '.$pesanan->order_code.' sudah kami terima.',
                'url' => route('pesanan.show', $pesanan),
            ]);
        }

        // Pembayaran gagal atau lewat waktu: stok dan poin dikembalikan
        if (in_array($status, ['kedaluwarsa', 'gagal', 'dibatalkan']) && $pesanan->status === 'menunggu_pembayaran') {
            $pesanan->update(['status' => 'dibatalkan', 'cancelled_at' => now()]);

            foreach ($pesanan->item as $baris) {
                if ($baris->varian) {
                    $baris->varian->increment('stock', $baris->quantity);
                }
            }

            if ($pesanan->points_used > 0 && $pesanan->pengguna) {
                $pesanan->pengguna->ubahPoin(
                    (int) $pesanan->points_used,
                    'pesanan',
                    'Poin dikembalikan karena pesanan '.$pesanan->order_code.' dibatalkan'
                );
            }

            // kuota voucher dilepas kembali
            if ($pesanan->voucher_id) {
                \App\Models\Voucher::where('id', $pesanan->voucher_id)
                    ->where('used', '>', 0)->decrement('used');
            }

            TrackingEvent::create([
                'order_id' => $pesanan->id,
                'status' => 'dibatalkan',
                'description' => 'Pembayaran tidak diselesaikan, pesanan dibatalkan',
                'happened_at' => now(),
            ]);

            Notification::create([
                'user_id' => $pesanan->user_id,
                'title' => 'Pesanan dibatalkan',
                'body' => 'Pesanan '.$pesanan->order_code.' dibatalkan karena pembayarannya tidak diselesaikan.',
                'url' => route('pesanan.show', $pesanan),
            ]);
        }
    }
}
