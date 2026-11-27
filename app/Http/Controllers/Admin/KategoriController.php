<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Kategori produk. Daftar dan formulir tambah berada di satu halaman
 * supaya admin tidak perlu berpindah-pindah.
 */
class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));

        $kueri = Category::withCount('produk')
            ->with('induk')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($cari !== '') {
            $kueri->where('name', 'like', '%'.$cari.'%');
        }

        return view('admin.kategori.index', [
            'daftar' => $kueri->paginate(30)->withQueryString(),
            'semua' => Category::orderBy('name')->get(),
            'cari' => $cari,
        ]);
    }

    /** Menambah kategori baru. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'parent_id.exists' => 'Kategori induk yang dipilih tidak ada.',
        ]);

        Category::create([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $this->buatSlug($data['name']),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.kategori.index')
            ->with('berhasil', 'Kategori '.$data['name'].' berhasil ditambahkan.');
    }

    /** Mengubah satu kategori. */
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        // Sebuah kategori tidak boleh menjadi induk dirinya sendiri
        if ((int) ($data['parent_id'] ?? 0) === $category->id) {
            return back()->with('gagal', 'Kategori tidak boleh menjadi induk dirinya sendiri.');
        }

        $category->update([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $this->buatSlug($data['name'], $category->id),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.kategori.index')
            ->with('berhasil', 'Kategori '.$category->name.' sudah diperbarui.');
    }

    /** Menghapus kategori. Ditolak bila masih dipakai produk atau punya anak. */
    public function destroy(Category $category)
    {
        if ($category->produk()->count() > 0) {
            return back()->with('gagal', 'Kategori '.$category->name.' masih dipakai '.$category->produk()->count().' produk. Pindahkan produknya dahulu.');
        }

        if ($category->anak()->count() > 0) {
            return back()->with('gagal', 'Kategori '.$category->name.' masih punya sub kategori. Hapus sub kategorinya dahulu.');
        }

        $nama = $category->name;
        $category->delete();

        return redirect()->route('admin.kategori.index')
            ->with('berhasil', 'Kategori '.$nama.' sudah dihapus.');
    }

    /** Slug kategori dibuat dari namanya dan dijaga unik. */
    private function buatSlug(string $nama, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($nama);
        if ($dasar === '') {
            $dasar = 'kategori';
        }

        $slug = $dasar;
        $nomor = 2;
        while (Category::where('slug', $slug)->when($abaikanId, function ($kueri) use ($abaikanId) {
            $kueri->where('id', '!=', $abaikanId);
        })->exists()) {
            $slug = $dasar.'-'.$nomor;
            $nomor++;
        }

        return $slug;
    }
}
