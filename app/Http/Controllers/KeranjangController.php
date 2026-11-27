<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

/**
 * Keranjang belanja.
 *
 * Keranjang boleh dipakai pengunjung yang belum masuk. Kalau pengguna sudah
 * masuk, keranjangnya disimpan memakai user_id. Kalau belum, dipakai session_id
 * supaya isinya tetap ada selama peramban belum ditutup.
 */
class KeranjangController extends Controller
{
    public function index()
    {
        $keranjang = $this->keranjangAktif();

        $daftar = $keranjang->item()
            ->with(['varian.produk.gambar', 'varian.produk.kategori'])
            ->orderBy('id')
            ->get();

        // Subtotal hanya dihitung dari barang yang dicentang pelanggan
        $subtotal = 0;
        $jumlahDipilih = 0;
        foreach ($daftar as $baris) {
            if ($baris->selected && $baris->varian && $baris->varian->produk) {
                $subtotal += $baris->varian->produk->hargaJual() * $baris->quantity;
                $jumlahDipilih += $baris->quantity;
            }
        }

        return view('keranjang.index', [
            'daftar' => $daftar,
            'subtotal' => $subtotal,
            'jumlahDipilih' => $jumlahDipilih,
            'batasGratisOngkir' => (int) setting('free_shipping_threshold', 350000),
        ]);
    }

    /** Memasukkan satu varian produk ke keranjang. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ], [
            'product_variant_id.required' => 'Pilih dulu warna dan ukuran produknya.',
            'quantity.required' => 'Isi jumlah barang yang ingin dibeli.',
            'quantity.min' => 'Jumlah barang paling sedikit 1.',
        ]);

        $varian = ProductVariant::find($data['product_variant_id']);
        $keranjang = $this->keranjangAktif();

        $item = $keranjang->item()->where('product_variant_id', $varian->id)->first();
        $jumlahBaru = $item ? $item->quantity + (int) $data['quantity'] : (int) $data['quantity'];

        if ($varian->stock < $jumlahBaru) {
            return back()->with('gagal', 'Stok '.$varian->label().' tinggal '.$varian->stock.' potong.');
        }

        if ($item) {
            $item->update(['quantity' => $jumlahBaru, 'selected' => true]);
        } else {
            CartItem::create([
                'cart_id' => $keranjang->id,
                'product_variant_id' => $varian->id,
                'quantity' => $jumlahBaru,
                'selected' => true,
            ]);
        }

        return back()->with('berhasil', 'Barang dimasukkan ke keranjang.');
    }

    /** Mengubah jumlah satu baris keranjang. */
    public function update(Request $request, CartItem $item)
    {
        $keranjang = $this->keranjangAktif();
        if ($item->cart_id !== $keranjang->id) {
            abort(403);
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ], [
            'quantity.min' => 'Jumlah barang paling sedikit 1.',
        ]);

        if (! $item->varian) {
            return back()->with('gagal', 'Varian produk ini sudah tidak tersedia.');
        }

        if ($item->varian->stock < (int) $data['quantity']) {
            return back()->with('gagal', 'Stok '.$item->varian->label().' tinggal '.$item->varian->stock.' potong.');
        }

        $item->update(['quantity' => (int) $data['quantity']]);

        return back()->with('berhasil', 'Jumlah barang diperbarui.');
    }

    /** Membalik centang satu baris keranjang. */
    public function pilih(CartItem $item)
    {
        $keranjang = $this->keranjangAktif();
        if ($item->cart_id !== $keranjang->id) {
            abort(403);
        }

        $item->update(['selected' => ! $item->selected]);

        return back();
    }

    /** Mencentang atau melepas centang seluruh barang di keranjang. */
    public function pilihSemua(Request $request)
    {
        $data = $request->validate([
            'pilih' => ['required', 'in:0,1'],
        ]);

        $keranjang = $this->keranjangAktif();
        $keranjang->item()->update(['selected' => (bool) $data['pilih']]);

        return back();
    }

    public function destroy(CartItem $item)
    {
        $keranjang = $this->keranjangAktif();
        if ($item->cart_id !== $keranjang->id) {
            abort(403);
        }

        $item->delete();

        return back()->with('berhasil', 'Barang dikeluarkan dari keranjang.');
    }

    /**
     * Mengambil keranjang yang sedang dipakai. Kalau belum ada, baris carts
     * dibuat lebih dulu supaya barangnya punya tempat menempel.
     */
    private function keranjangAktif(): Cart
    {
        if (auth()->check()) {
            return Cart::firstOrCreate(['user_id' => auth()->id()]);
        }

        return Cart::firstOrCreate(['user_id' => null, 'session_id' => session()->getId()]);
    }
}
