<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pengelolaan produk beserta varian (warna dan ukuran) serta gambarnya.
 *
 * Varian dikirim dari formulir sebagai larik bertingkat, contoh:
 * varian[0][color], varian[0][size], varian[0][stock]. Baris yang warnanya
 * dan ukurannya sama-sama kosong dianggap baris kosong lalu dilewati.
 */
class ProdukAdminController extends Controller
{
    /** Daftar produk dengan pencarian dan tapis kategori. */
    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));
        $kategoriId = $request->query('kategori');

        $kueri = Product::with(['kategori', 'varian'])->orderByDesc('id');

        if ($cari !== '') {
            $kueri->where(function ($bagian) use ($cari) {
                $bagian->where('name', 'like', '%'.$cari.'%')
                    ->orWhere('sku', 'like', '%'.$cari.'%');
            });
        }

        if ($kategoriId) {
            $kueri->where('category_id', $kategoriId);
        }

        return view('admin.produk.index', [
            'daftar' => $kueri->paginate(15)->withQueryString(),
            'kategori' => Category::orderBy('name')->get(),
            'cari' => $cari,
            'kategoriId' => $kategoriId,
        ]);
    }

    /** Formulir produk baru. */
    public function create()
    {
        return view('admin.produk.form', [
            'produk' => null,
            'kategori' => Category::orderBy('name')->get(),
        ]);
    }

    /** Menyimpan produk baru beserta varian dan gambarnya. */
    public function store(Request $request)
    {
        $data = $request->validate($this->aturan(), $this->pesanAturan());

        $barisVarian = $this->varianTerisi($request->input('varian', []));
        if (count($barisVarian) === 0) {
            return back()->withInput()->with('gagal', 'Isi paling sedikit satu varian (warna dan ukuran).');
        }

        $produk = new Product();
        $produk->fill($this->isiProduk($request, $data));
        $produk->slug = $this->buatSlug($data['name']);
        $produk->save();

        foreach ($barisVarian as $baris) {
            ProductVariant::create([
                'product_id' => $produk->id,
                'color' => $baris['color'],
                'color_hex' => $baris['color_hex'],
                'size' => $baris['size'],
                'sku' => $baris['sku'],
                'stock' => $baris['stock'],
            ]);
        }

        $this->simpanGambar($request, $produk);

        return redirect()->route('admin.produk.show', $produk)
            ->with('berhasil', 'Produk '.$produk->name.' berhasil ditambahkan.');
    }

    /** Rincian produk: data pokok, varian, dan gambar. */
    public function show(Product $product)
    {
        $product->load(['kategori', 'varian', 'gambar']);

        return view('admin.produk.show', [
            'produk' => $product,
        ]);
    }

    /** Formulir ubah produk. */
    public function edit(Product $product)
    {
        $product->load(['varian', 'gambar']);

        return view('admin.produk.form', [
            'produk' => $product,
            'kategori' => Category::orderBy('name')->get(),
        ]);
    }

    /** Menyimpan perubahan produk. Varian lama yang tidak dikirim akan dihapus. */
    public function update(Request $request, Product $product)
    {
        $data = $request->validate($this->aturan(), $this->pesanAturan());

        $barisVarian = $this->varianTerisi($request->input('varian', []));
        if (count($barisVarian) === 0) {
            return back()->withInput()->with('gagal', 'Isi paling sedikit satu varian (warna dan ukuran).');
        }

        $product->fill($this->isiProduk($request, $data));
        $product->slug = $this->buatSlug($data['name'], $product->id);
        $product->save();

        $idDipakai = [];
        foreach ($barisVarian as $baris) {
            $isi = [
                'color' => $baris['color'],
                'color_hex' => $baris['color_hex'],
                'size' => $baris['size'],
                'sku' => $baris['sku'],
                'stock' => $baris['stock'],
            ];

            $lama = $baris['id']
                ? ProductVariant::where('product_id', $product->id)->find($baris['id'])
                : null;

            if ($lama) {
                $lama->update($isi);
                $idDipakai[] = $lama->id;
            } else {
                $baru = ProductVariant::create($isi + ['product_id' => $product->id]);
                $idDipakai[] = $baru->id;
            }
        }

        // Varian yang barisnya dihapus admin di formulir ikut dihapus
        ProductVariant::where('product_id', $product->id)
            ->whereNotIn('id', $idDipakai)
            ->delete();

        $this->simpanGambar($request, $product);

        return redirect()->route('admin.produk.show', $product)
            ->with('berhasil', 'Perubahan produk '.$product->name.' sudah disimpan.');
    }

    /** Menghapus produk beserta varian dan gambarnya. */
    public function destroy(Product $product)
    {
        $nama = $product->name;
        $product->delete();

        return redirect()->route('admin.produk.index')
            ->with('berhasil', 'Produk '.$nama.' sudah dihapus.');
    }

    /** Mengubah stok beberapa varian sekaligus dari satu formulir. */
    public function ubahStok(Request $request, Product $product)
    {
        $request->validate([
            'stok' => ['required', 'array'],
            'stok.*' => ['required', 'integer', 'min:0', 'max:100000'],
        ], [
            'stok.required' => 'Tidak ada stok yang dikirim.',
            'stok.*.integer' => 'Stok harus berupa angka bulat.',
            'stok.*.min' => 'Stok tidak boleh kurang dari 0.',
        ]);

        $jumlah = 0;
        foreach ($request->input('stok') as $idVarian => $stok) {
            $varian = ProductVariant::where('product_id', $product->id)->find($idVarian);
            if ($varian) {
                $varian->stock = (int) $stok;
                $varian->save();
                $jumlah++;
            }
        }

        return redirect()->route('admin.produk.show', $product)
            ->with('berhasil', 'Stok '.$jumlah.' varian sudah diperbarui.');
    }

    /* ------------------------------------------------------------------ */

    /** Aturan validasi produk, dipakai store dan update. */
    private function aturan(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'discount_price' => ['nullable', 'integer', 'min:0', 'lt:price'],
            'sku' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:5000'],
            'material' => ['nullable', 'string', 'max:150'],
            'care' => ['nullable', 'string', 'max:191'],
            'collection' => ['nullable', 'string', 'max:80'],
            'weight' => ['required', 'integer', 'min:1', 'max:50000'],
            'varian' => ['required', 'array', 'min:1'],
            'varian.*.id' => ['nullable', 'integer'],
            'varian.*.color' => ['nullable', 'string', 'max:40'],
            'varian.*.color_hex' => ['nullable', 'string', 'max:10'],
            'varian.*.size' => ['nullable', 'string', 'max:10'],
            'varian.*.sku' => ['nullable', 'string', 'max:50'],
            'varian.*.stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'gambar' => ['nullable', 'array', 'max:8'],
            'gambar.*' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /** Pesan kesalahan berbahasa Indonesia. */
    private function pesanAturan(): array
    {
        return [
            'name.required' => 'Nama produk wajib diisi.',
            'category_id.required' => 'Pilih kategori produk.',
            'category_id.exists' => 'Kategori yang dipilih tidak ada.',
            'price.required' => 'Harga wajib diisi.',
            'discount_price.lt' => 'Harga diskon harus lebih kecil daripada harga normal.',
            'weight.required' => 'Berat produk (gram) wajib diisi untuk menghitung ongkos kirim.',
            'varian.required' => 'Isi paling sedikit satu varian.',
            'gambar.*.image' => 'Berkas yang diunggah harus berupa gambar.',
            'gambar.*.max' => 'Ukuran setiap gambar paling besar 2MB.',
        ];
    }

    /** Menyusun kolom produk dari data yang sudah lolos validasi. */
    private function isiProduk(Request $request, array $data): array
    {
        return [
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'price' => $data['price'],
            'discount_price' => $data['discount_price'] ?? null,
            'description' => $data['description'] ?? null,
            'material' => $data['material'] ?? null,
            'care' => $data['care'] ?? null,
            'collection' => $data['collection'] ?? null,
            'weight' => $data['weight'],
            'is_new' => $request->boolean('is_new'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /** Menyaring baris varian yang benar-benar diisi. */
    private function varianTerisi($varian): array
    {
        $hasil = [];
        if (! is_array($varian)) {
            return $hasil;
        }

        foreach ($varian as $baris) {
            if (! is_array($baris)) {
                continue;
            }
            $warna = trim((string) ($baris['color'] ?? ''));
            $ukuran = trim((string) ($baris['size'] ?? ''));
            if ($warna === '' && $ukuran === '') {
                continue;
            }

            $hasil[] = [
                'id' => $baris['id'] ?? null,
                'color' => $warna !== '' ? $warna : '-',
                'color_hex' => trim((string) ($baris['color_hex'] ?? '')) ?: null,
                'size' => $ukuran !== '' ? $ukuran : 'ALL',
                'sku' => trim((string) ($baris['sku'] ?? '')) ?: null,
                'stock' => (int) ($baris['stock'] ?? 0),
            ];
        }

        return $hasil;
    }

    /** Membuat slug dari nama produk dan menjaganya tetap unik. */
    private function buatSlug(string $nama, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($nama);
        if ($dasar === '') {
            $dasar = 'produk';
        }

        $slug = $dasar;
        $nomor = 2;
        while (Product::where('slug', $slug)->when($abaikanId, function ($kueri) use ($abaikanId) {
            $kueri->where('id', '!=', $abaikanId);
        })->exists()) {
            $slug = $dasar.'-'.$nomor;
            $nomor++;
        }

        return $slug;
    }

    /** Menyimpan gambar yang diunggah ke public/images/products. */
    private function simpanGambar(Request $request, Product $produk): void
    {
        if (! $request->hasFile('gambar')) {
            return;
        }

        $urutan = (int) ProductImage::where('product_id', $produk->id)->max('sort_order');

        foreach ($request->file('gambar') as $berkas) {
            if (! $berkas || ! $berkas->isValid()) {
                continue;
            }

            $urutan++;
            $nama = 'produk-'.$produk->id.'-'.time().'-'.$urutan.'.'.$berkas->getClientOriginalExtension();
            $berkas->move(public_path('images/products'), $nama);

            ProductImage::create([
                'product_id' => $produk->id,
                'color' => null,
                'path' => 'images/products/'.$nama,
                'sort_order' => $urutan,
            ]);
        }
    }
}
