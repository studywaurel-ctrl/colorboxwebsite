<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

/** Daftar produk yang disimpan pelanggan. */
class WishlistController extends Controller
{
    public function index()
    {
        $daftar = Wishlist::where('user_id', auth()->id())
            ->with(['produk.kategori', 'produk.gambar', 'produk.varian'])
            ->orderByDesc('id')
            ->get();

        return view('wishlist.index', ['daftar' => $daftar]);
    }

    /** Menyimpan produk ke wishlist. Kalau sudah ada, penandanya dilepas. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $tersimpan = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $data['product_id'])
            ->first();

        if ($tersimpan) {
            $tersimpan->delete();

            return back()->with('ingat', 'Produk dikeluarkan dari wishlist.');
        }

        Wishlist::create([
            'user_id' => auth()->id(),
            'product_id' => $data['product_id'],
        ]);

        return back()->with('berhasil', 'Produk disimpan ke wishlist.');
    }

    public function destroy(Product $product)
    {
        Wishlist::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->delete();

        return back()->with('berhasil', 'Produk dikeluarkan dari wishlist.');
    }
}
