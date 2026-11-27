@extends('layouts.admin')

@section('judul', $produk ? __('Edit Produk') : __('Tambah Produk'))

@section('isi')

@php
    // Baris varian yang ditampilkan: isian lama bila validasi gagal,
    // data produk bila sedang mengubah, atau satu baris kosong bila produk baru.
    $barisVarian = old('varian');
    if (! $barisVarian) {
        $barisVarian = [];
        if ($produk) {
            foreach ($produk->varian as $varian) {
                $barisVarian[] = [
                    'id' => $varian->id,
                    'color' => $varian->color,
                    'color_hex' => $varian->color_hex,
                    'size' => $varian->size,
                    'sku' => $varian->sku,
                    'stock' => $varian->stock,
                ];
            }
        }
        if (count($barisVarian) === 0) {
            $barisVarian[] = ['id' => null, 'color' => '', 'color_hex' => '', 'size' => '', 'sku' => '', 'stock' => 0];
        }
    }
@endphp

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ $produk ? __('Edit Produk:').' '.$produk->name : __('Tambah Produk Baru') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Alamat halaman produk (slug) dibuat otomatis dari nama produk.') }}
    </p>

    <form class="mt-3" method="POST" enctype="multipart/form-data"
          action="{{ $produk ? route('admin.produk.update', $produk) : route('admin.produk.store') }}">
        @csrf
        @if($produk)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="name">{{ __('Nama produk') }}</label>
                <input class="form-control" type="text" id="name" name="name" required maxlength="150"
                       value="{{ old('name', $produk->name ?? '') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="category_id">{{ __('Kategori') }}</label>
                <select class="form-control" id="category_id" name="category_id" required>
                    <option value="">{{ __('Pilih kategori') }}</option>
                    @foreach($kategori as $baris)
                        <option value="{{ $baris->id }}" @selected((string) old('category_id', $produk->category_id ?? '') === (string) $baris->id)>
                            {{ $baris->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="price">{{ __('Harga normal (Rp)') }}</label>
                <input class="form-control" type="number" id="price" name="price" min="0" required
                       value="{{ old('price', $produk->price ?? '') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="discount_price">{{ __('Harga diskon (Rp)') }}</label>
                <input class="form-control" type="number" id="discount_price" name="discount_price" min="0"
                       value="{{ old('discount_price', $produk->discount_price ?? '') }}">
                <p class="form-text">{{ __('Kosongin kalau produknya lagi nggak diskon.') }}</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="sku">{{ __('SKU') }}</label>
                <input class="form-control" type="text" id="sku" name="sku" maxlength="40"
                       value="{{ old('sku', $produk->sku ?? '') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="weight">{{ __('Berat (gram)') }}</label>
                <input class="form-control" type="number" id="weight" name="weight" min="1" required
                       value="{{ old('weight', $produk->weight ?? 300) }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="description">{{ __('Deskripsi') }}</label>
            <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $produk->description ?? '') }}</textarea>
        </div>

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="material">{{ __('Bahan') }}</label>
                <input class="form-control" type="text" id="material" name="material" maxlength="150"
                       value="{{ old('material', $produk->material ?? '') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="care">{{ __('Cara rawat') }}</label>
                <input class="form-control" type="text" id="care" name="care" maxlength="191"
                       value="{{ old('care', $produk->care ?? '') }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="collection">{{ __('Koleksi') }}</label>
            <input class="form-control" type="text" id="collection" name="collection" maxlength="80"
                   value="{{ old('collection', $produk->collection ?? '') }}">
            <p class="form-text">{{ __('Contoh: Colorbox X Liunic. Boleh dikosongin.') }}</p>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('Penanda') }}</label>
            <div class="d-flex flex-wrap gap-3">
                <label class="form-check-label">
                    <input type="checkbox" name="is_new" value="1" @checked(old('is_new', $produk->is_new ?? false))>
                    {{ __('Tandai sebagai produk baru') }}
                </label>
                <label class="form-check-label">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $produk->is_active ?? true))>
                    {{ __('Tampilkan di katalog (aktif)') }}
                </label>
            </div>
        </div>

        <hr>

        <h3 style="font-size: var(--cb-h5)">{{ __('Varian Warna dan Ukuran') }}</h3>
        <p style="color: var(--cb-ink-2); font-size: .875rem">
            {{ __('Tiap baris itu satu kombinasi warna dan ukuran. Baris yang warna dan ukurannya kosong akan dilewati.') }}
        </p>

        <div class="ad-gulir">
            <table class="ad-tabel" id="tabel-varian">
                <thead>
                <tr>
                    <th>{{ __('Warna') }}</th>
                    <th>{{ __('Kode Warna (hex)') }}</th>
                    <th>{{ __('Ukuran') }}</th>
                    <th>{{ __('SKU Varian') }}</th>
                    <th>{{ __('Stok') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="isi-varian">
                @foreach($barisVarian as $nomor => $baris)
                    <tr>
                        <td>
                            <input type="hidden" name="varian[{{ $nomor }}][id]" value="{{ $baris['id'] ?? '' }}">
                            <input class="form-control" type="text" maxlength="40" name="varian[{{ $nomor }}][color]"
                                   value="{{ $baris['color'] ?? '' }}" placeholder="OFF WHITE">
                        </td>
                        <td>
                            <input class="form-control" type="text" maxlength="10" name="varian[{{ $nomor }}][color_hex]"
                                   value="{{ $baris['color_hex'] ?? '' }}" placeholder="#F5F0E8">
                        </td>
                        <td>
                            <input class="form-control" type="text" maxlength="10" name="varian[{{ $nomor }}][size]"
                                   value="{{ $baris['size'] ?? '' }}" placeholder="M">
                        </td>
                        <td>
                            <input class="form-control" type="text" maxlength="50" name="varian[{{ $nomor }}][sku]"
                                   value="{{ $baris['sku'] ?? '' }}">
                        </td>
                        <td>
                            <input class="form-control" type="number" min="0" name="varian[{{ $nomor }}][stock]"
                                   value="{{ $baris['stock'] ?? 0 }}" style="max-width: 110px">
                        </td>
                        <td>
                            <button class="btn btn-link btn-sm p-0" type="button" data-hapus-baris>{{ __('Hapus') }}</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Contoh baris kosong yang disalin JavaScript ketika tombol di bawah ditekan --}}
        <template id="contoh-varian">
            <tr>
                <td>
                    <input type="hidden" name="varian[__i__][id]" value="">
                    <input class="form-control" type="text" maxlength="40" name="varian[__i__][color]" placeholder="OFF WHITE">
                </td>
                <td><input class="form-control" type="text" maxlength="10" name="varian[__i__][color_hex]" placeholder="#F5F0E8"></td>
                <td><input class="form-control" type="text" maxlength="10" name="varian[__i__][size]" placeholder="M"></td>
                <td><input class="form-control" type="text" maxlength="50" name="varian[__i__][sku]"></td>
                <td><input class="form-control" type="number" min="0" name="varian[__i__][stock]" value="0" style="max-width: 110px"></td>
                <td><button class="btn btn-link btn-sm p-0" type="button" data-hapus-baris>{{ __('Hapus') }}</button></td>
            </tr>
        </template>

        <button class="btn btn-outline-dark btn-sm mt-2" type="button" id="tambah-varian"
                data-mulai="{{ count($barisVarian) }}">
            <i class="bi bi-plus-lg"></i> {{ __('Tambah Baris Varian') }}
        </button>

        <hr>

        <div class="mb-3">
            <label class="form-label" for="gambar">{{ __('Gambar produk (boleh lebih dari satu)') }}</label>
            <input class="form-control" type="file" id="gambar" name="gambar[]" accept="image/*" multiple>
            <p class="form-text">{{ __('Ukuran tiap file maksimal 2MB. Boleh dikosongin.') }}</p>
        </div>

        @if($produk && $produk->gambar->count() > 0)
            <div class="d-flex flex-wrap gap-2 mt-2">
                @foreach($produk->gambar->sortBy('sort_order') as $gambar)
                    <img src="{{ gambar_produk($gambar->path) }}" alt="{{ $produk->name }}" width="90" height="120" style="object-fit: cover">
                @endforeach
            </div>
        @endif

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">{{ $produk ? __('Simpan Perubahan') : __('Simpan Produk') }}</button>
            <a class="btn btn-link p-0" href="{{ route('admin.produk.index') }}">{{ __('Batal') }}</a>
        </div>
    </form>
</div>

@endsection

@push('skrip')
<script>
// Menyalin satu baris varian kosong. Nomor baris baru dilanjutkan dari
// jumlah baris yang sudah ada, dibaca dari atribut data-mulai.
document.addEventListener('DOMContentLoaded', function () {
    var tombol = document.getElementById('tambah-varian');
    var contoh = document.getElementById('contoh-varian');
    var isi = document.getElementById('isi-varian');
    if (!tombol || !contoh || !isi) { return; }

    var nomor = parseInt(tombol.dataset.mulai || '0', 10);

    tombol.addEventListener('click', function () {
        var tulisan = contoh.innerHTML.split('__i__').join(String(nomor));
        var pembungkus = document.createElement('tbody');
        pembungkus.innerHTML = tulisan;
        while (pembungkus.firstElementChild) {
            isi.appendChild(pembungkus.firstElementChild);
        }
        nomor = nomor + 1;
    });

    // Tombol hapus baris cukup menghilangkan baris dari formulir
    isi.addEventListener('click', function (peristiwa) {
        var sasaran = peristiwa.target.closest('[data-hapus-baris]');
        if (!sasaran) { return; }
        if (isi.querySelectorAll('tr').length <= 1) {
            window.alert('Paling sedikit harus ada satu baris varian.');
            return;
        }
        sasaran.closest('tr').remove();
    });
});
</script>
@endpush
