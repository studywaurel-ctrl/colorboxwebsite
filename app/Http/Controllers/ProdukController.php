<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreStock;
use Illuminate\Http\Request;

/** Katalog produk: daftar, penyaring, halaman rincian, dan cek stok toko. */
class ProdukController extends Controller
{
    /** Semua produk beserta penyaringnya. */
    public function index(Request $request)
    {
        return $this->daftar($request, false);
    }

    /** Hanya produk yang ditandai baru (New Arrival). */
    public function baru(Request $request)
    {
        return $this->daftar($request, true);
    }

    /**
     * Menyusun daftar produk. Dipakai halaman katalog dan New Arrival
     * supaya penyaringnya tidak ditulis dua kali.
     */
    private function daftar(Request $request, bool $hanyaBaru)
    {
        $request->validate([
            'cari' => ['nullable', 'string', 'max:80'],
            'kategori' => ['nullable', 'string', 'max:80'],
            'koleksi' => ['nullable', 'string', 'max:80'],
            'warna' => ['nullable', 'string', 'max:40'],
            'ukuran' => ['nullable', 'string', 'max:15'],
            'harga_min' => ['nullable', 'integer', 'min:0'],
            'harga_max' => ['nullable', 'integer', 'min:0'],
            'urut' => ['nullable', 'in:terbaru,termurah,termahal,nama'],
        ]);

        $cari = trim((string) $request->query('cari'));
        $slugKategori = trim((string) $request->query('kategori'));
        $koleksi = trim((string) $request->query('koleksi'));
        $warna = trim((string) $request->query('warna'));
        $ukuran = trim((string) $request->query('ukuran'));
        $hargaMin = $request->query('harga_min');
        $hargaMax = $request->query('harga_max');
        $urut = $request->query('urut', 'terbaru');

        $daftar = Product::where('is_active', true)->with(['kategori', 'gambar', 'varian']);

        if ($hanyaBaru) {
            $daftar->where('is_new', true);
        }

        if ($cari !== '') {
            $daftar->where(function ($q) use ($cari) {
                $q->where('name', 'like', '%'.$cari.'%')
                    ->orWhere('sku', 'like', '%'.$cari.'%')
                    ->orWhere('description', 'like', '%'.$cari.'%');
            });
        }

        // Kategori dicari lewat slug. Kalau kategori induk yang dipilih,
        // produk pada kategori anaknya ikut ditampilkan.
        $kategoriTerpilih = null;
        if ($slugKategori !== '') {
            $kategoriTerpilih = Category::where('slug', $slugKategori)->first();
            if ($kategoriTerpilih) {
                $idKategori = Category::where('parent_id', $kategoriTerpilih->id)->pluck('id')->push($kategoriTerpilih->id);
                $daftar->whereIn('category_id', $idKategori);
            } else {
                $daftar->whereRaw('1 = 0');
            }
        }

        if ($koleksi !== '') {
            $daftar->where('collection', $koleksi);
        }

        if ($warna !== '') {
            $daftar->whereHas('varian', function ($q) use ($warna) {
                $q->where('color', $warna);
            });
        }

        if ($ukuran !== '') {
            $daftar->whereHas('varian', function ($q) use ($ukuran) {
                $q->where('size', $ukuran)->where('stock', '>', 0);
            });
        }

        // Harga yang dibandingkan adalah harga yang dibayar pelanggan
        if ($hargaMin !== null && $hargaMin !== '') {
            $daftar->whereRaw('COALESCE(discount_price, price) >= ?', [(int) $hargaMin]);
        }
        if ($hargaMax !== null && $hargaMax !== '') {
            $daftar->whereRaw('COALESCE(discount_price, price) <= ?', [(int) $hargaMax]);
        }

        if ($urut === 'termurah') {
            $daftar->orderByRaw('COALESCE(discount_price, price) ASC');
        } elseif ($urut === 'termahal') {
            $daftar->orderByRaw('COALESCE(discount_price, price) DESC');
        } elseif ($urut === 'nama') {
            $daftar->orderBy('name');
        } else {
            $daftar->orderByDesc('id');
        }

        $produk = $daftar->paginate(12)->withQueryString();

        return view('produk.index', [
            'produk' => $produk,
            'hanyaBaru' => $hanyaBaru,
            'kategoriTerpilih' => $kategoriTerpilih,
            'urut' => $urut,
            'kategoriInduk' => Category::whereNull('parent_id')->with('anak')->orderBy('sort_order')->get(),
            'daftarKoleksi' => Product::where('is_active', true)->whereNotNull('collection')->orderBy('collection')->distinct()->pluck('collection'),
        ]);
    }

    /** Halaman rincian satu produk. */
    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load(['kategori.induk', 'gambar', 'varian', 'ulasan.pengguna']);

        // Daftar warna diambil dari varian, satu baris untuk tiap warna
        $warna = $product->varian->unique('color')->values();
        $warnaAwal = $warna->first() ? $warna->first()->color : null;

        // Gambar dikelompokkan per warna supaya galeri bisa berganti
        $gambar = $product->gambar->sortBy('sort_order')->values();

        $rataRating = $product->ulasan->count() > 0
            ? round($product->ulasan->avg('rating'), 1)
            : 0;

        $terkait = Product::where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['kategori', 'gambar', 'varian'])
            ->orderByDesc('id')
            ->take(4)
            ->get();

        return view('produk.show', [
            'produk' => $product,
            'warna' => $warna,
            'warnaAwal' => $warnaAwal,
            'gambar' => $gambar,
            'rataRating' => $rataRating,
            'terkait' => $terkait,
        ]);
    }

    /**
     * Daftar stok satu varian di setiap toko. Dipanggil memakai fetch()
     */
    public function stokToko(Request $request, Product $product)
    {
        $idVarian = (int) $request->query('product_variant_id');
        $varian = $product->varian()->find($idVarian);

        if (! $varian) {
            return response()->json([
                'pesan' => 'Pilih warna dan ukuran terlebih dahulu.',
                'varian' => null,
                'toko' => [],
            ]);
        }

        // stok tiap toko untuk varian ini, dikumpulkan sekali supaya hemat query
        $stok = StoreStock::where('product_variant_id', $varian->id)->pluck('stock', 'store_id');

        $daftar = [];
        $toko = Store::where('is_active', true)->orderBy('city')->orderBy('name')->get();
        foreach ($toko as $t) {
            $jumlah = (int) ($stok[$t->id] ?? 0);
            $daftar[] = [
                'nama' => $t->name,
                'kota' => $t->city,
                'alamat' => $t->address,
                'lantai' => $t->floor,
                'jam' => jam_singkat($t->open_time).' - '.jam_singkat($t->close_time),
                'stok' => $jumlah,
                'keterangan' => $jumlah > 0 ? $jumlah.' tersedia' : 'Stok habis',
                'ttc' => (bool) $t->has_ttc,
                'tautan' => route('toko.show', $t),
            ];
        }

        return response()->json([
            'pesan' => null,
            'varian' => $varian->label(),
            'toko' => $daftar,
        ]);
    }
}
