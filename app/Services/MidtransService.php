<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Penghubung sederhana ke Midtrans Snap (mode sandbox).
 *
 * Hanya ada tiga pekerjaan di sini:
 * 1. membuat transaksi Snap dan mengambil token pembayarannya,
 * 2. memeriksa tanda tangan notifikasi yang dikirim server Midtrans,
 * 3. menanyakan status sebuah transaksi ke Midtrans.
 *
 * Server Key hanya dibaca dari .env dan tidak pernah dikirim ke peramban.
 */
class MidtransService
{
    public function aktif(): bool
    {
        return (bool) config('midtrans.server_key') && (bool) config('midtrans.client_key');
    }

    /**
     * Membuat transaksi Snap untuk sebuah pesanan.
     * Mengembalikan ['token' => ..., 'redirect_url' => ...] atau null bila gagal.
     */
    public function buatTransaksi(Order $pesanan): ?array
    {
        if (! $this->aktif()) {
            Log::warning('Midtrans belum diatur: MIDTRANS_SERVER_KEY atau MIDTRANS_CLIENT_KEY kosong.');

            return null;
        }

        $pelanggan = $pesanan->pengguna;
        $barang = [];
        foreach ($pesanan->item as $item) {
            $barang[] = [
                'id' => 'VAR-'.$item->product_variant_id,
                'price' => (int) $item->price,
                'quantity' => (int) $item->quantity,
                'name' => substr($item->product_name.' '.$item->color.' '.$item->size, 0, 50),
            ];
        }
        if ($pesanan->shipping_cost > 0) {
            $barang[] = ['id' => 'ONGKIR', 'price' => (int) $pesanan->shipping_cost, 'quantity' => 1, 'name' => 'Ongkos kirim'];
        }
        $potongan = (int) $pesanan->discount + (int) ($pesanan->points_used * setting('point_value', 100));
        if ($potongan > 0) {
            $barang[] = ['id' => 'POTONGAN', 'price' => -$potongan, 'quantity' => 1, 'name' => 'Potongan harga'];
        }

        $isi = [
            'transaction_details' => [
                'order_id' => $pesanan->order_code,
                'gross_amount' => (int) $pesanan->total,
            ],
            'item_details' => $barang,
            'customer_details' => [
                'first_name' => $pelanggan->name,
                'email' => $pelanggan->email,
                'phone' => $pelanggan->phone ?: '08000000000',
            ],
            'expiry' => [
                'unit' => 'hour',
                'duration' => (int) config('midtrans.expiry_hours', 24),
            ],
            'callbacks' => [
                'finish' => route('pembayaran.selesai', ['payment' => $pesanan->pembayaran?->id ?? 0]),
            ],
        ];

        try {
            $jawaban = Http::withBasicAuth(config('midtrans.server_key'), '')
                ->acceptJson()
                ->timeout(30)
                ->post(config('midtrans.snap_url'), $isi);
        } catch (\Throwable $e) {
            Log::error('Gagal menghubungi Midtrans: '.$e->getMessage());

            return null;
        }

        if (! $jawaban->successful()) {
            Log::error('Midtrans menolak permintaan: '.$jawaban->body());

            return null;
        }

        return [
            'token' => $jawaban->json('token'),
            'redirect_url' => $jawaban->json('redirect_url'),
        ];
    }

    /**
     * Memeriksa tanda tangan notifikasi Midtrans.
     * signature_key = sha512(order_id + status_code + gross_amount + server_key)
     */
    public function tandaTanganSah(array $notifikasi): bool
    {
        $tanda = $notifikasi['signature_key'] ?? '';
        $hitung = hash('sha512',
            ($notifikasi['order_id'] ?? '').
            ($notifikasi['status_code'] ?? '').
            ($notifikasi['gross_amount'] ?? '').
            config('midtrans.server_key')
        );

        return hash_equals($hitung, $tanda);
    }

    /** Menanyakan status sebuah transaksi langsung ke Midtrans. */
    public function ambilStatus(string $kodePesanan): ?array
    {
        if (! $this->aktif()) {
            return null;
        }

        try {
            $jawaban = Http::withBasicAuth(config('midtrans.server_key'), '')
                ->acceptJson()
                ->timeout(20)
                ->get(rtrim(config('midtrans.api_url'), '/').'/'.$kodePesanan.'/status');
        } catch (\Throwable $e) {
            Log::error('Gagal menanyakan status ke Midtrans: '.$e->getMessage());

            return null;
        }

        return $jawaban->successful() ? $jawaban->json() : null;
    }

    /**
     * Menerjemahkan transaction_status Midtrans menjadi status pembayaran
     * yang dipakai Colorbox. Untuk kartu kredit, status "capture" masih
     * perlu melihat fraud_status.
     */
    public function statusColorbox(array $data): string
    {
        $status = $data['transaction_status'] ?? 'pending';
        $fraud = $data['fraud_status'] ?? 'accept';

        if ($status === 'capture' && $fraud !== 'accept') {
            return 'menunggu';
        }

        return \App\Models\Payment::petaStatus($status);
    }
}
