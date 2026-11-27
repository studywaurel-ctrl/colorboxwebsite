<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;

/** Ulasan produk yang ditulis pelanggan dari halaman rincian pesanan. */
class UlasanController extends Controller
{
    public function store(Request $request, Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ], [
            'order_item_id.required' => 'Pilih barang yang ingin diulas.',
            'rating.required' => 'Beri penilaian bintang 1 sampai 5.',
            'rating.min' => 'Penilaian paling rendah 1 bintang.',
            'rating.max' => 'Penilaian paling tinggi 5 bintang.',
        ]);

        if (! in_array($order->status, ['diterima', 'selesai'])) {
            return back()->with('gagal', 'Ulasan dapat ditulis setelah barang pesanan diterima.');
        }

        $barang = $order->item()->where('id', $data['order_item_id'])->first();
        if (! $barang) {
            return back()->with('gagal', 'Barang tersebut tidak ada pada pesanan ini.');
        }

        if ($barang->reviewed) {
            return back()->with('ingat', 'Barang ini sudah pernah Anda ulas.');
        }

        if (! $barang->varian || ! $barang->varian->product_id) {
            return back()->with('gagal', 'Produk ini sudah tidak tersedia sehingga belum dapat diulas.');
        }

        Review::create([
            'user_id' => auth()->id(),
            'product_id' => $barang->varian->product_id,
            'order_item_id' => $barang->id,
            'rating' => (int) $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $barang->update(['reviewed' => true]);

        return back()->with('berhasil', 'Terima kasih, ulasan Anda sudah tersimpan.');
    }
}
