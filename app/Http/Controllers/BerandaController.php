<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

/** Halaman depan situs Colorbox. */
class BerandaController extends Controller
{
    public function index()
    {
        // Delapan produk terbaru yang ditandai sebagai koleksi baru
        $produkBaru = Product::where('is_active', true)
            ->where('is_new', true)
            ->with(['kategori', 'gambar', 'varian'])
            ->orderByDesc('id')
            ->take(8)
            ->get();

        // Kategori pilihan diambil dari kategori anak supaya tautannya spesifik
        $kategoriPilihan = Category::whereNotNull('parent_id')
            ->orderBy('sort_order')
            ->take(8)
            ->get();

        // Produk yang masuk koleksi kolaborasi (kolom collection terisi)
        $produkKolaborasi = Product::where('is_active', true)
            ->whereNotNull('collection')
            ->with(['kategori', 'gambar', 'varian'])
            ->orderByDesc('id')
            ->take(4)
            ->get();

        // Nama koleksi kolaborasi yang tersedia, dipakai untuk tautan katalog
        $namaKoleksi = Product::where('is_active', true)
            ->whereNotNull('collection')
            ->orderBy('collection')
            ->distinct()
            ->pluck('collection');

        return view('beranda', [
            'produkBaru' => $produkBaru,
            'kategoriPilihan' => $kategoriPilihan,
            'produkKolaborasi' => $produkKolaborasi,
            'namaKoleksi' => $namaKoleksi,
        ]);
    }
}
