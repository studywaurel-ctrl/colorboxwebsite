@extends('layouts.admin')

@section('judul', __('Detail Produk'))

@section('isi')

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">{{ $produk->name }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                {{ __(':p1 · slug: :p2 · SKU: :p3', ['p1' => $produk->kategori?->name ?? __('Tanpa kategori'), 'p2' => $produk->slug, 'p3' => $produk->sku ?: '-']) }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.produk.edit', $produk) }}">{{ __('Edit Produk') }}</a>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.produk.index') }}">{{ __('Kembali') }}</a>
        </div>
    </div>

    <div class="ad-angka mt-3">
        <div>
            <p class="ad-angka__nilai mb-0">{{ rupiah($produk->hargaJual()) }}</p>
            <p class="ad-angka__label mb-0">{{ __('Harga jual') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $produk->discount_price ? rupiah($produk->price) : '-' }}</p>
            <p class="ad-angka__label mb-0">{{ __('Harga sebelum diskon') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $produk->totalStok() }}</p>
            <p class="ad-angka__label mb-0">{{ __('Total stok') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $produk->weight }} g</p>
            <p class="ad-angka__label mb-0">{{ __('Berat') }}</p>
        </div>
    </div>

    <div class="mt-3" style="font-size: .875rem">
        <p class="mb-1"><strong>{{ __('Status:') }}</strong>
            @if($produk->is_active)
                <span class="status status-success">{{ __('Aktif') }}</span>
            @else
                <span class="status status-warning">{{ __('Nonaktif') }}</span>
            @endif
            @if($produk->is_new)<span class="status status-info">{{ __('Produk Baru') }}</span>@endif
        </p>
        <p class="mb-1"><strong>{{ __('Koleksi:') }}</strong> {{ $produk->collection ?: '-' }}</p>
        <p class="mb-1"><strong>{{ __('Bahan:') }}</strong> {{ $produk->material ?: '-' }}</p>
        <p class="mb-1"><strong>{{ __('Cara rawat:') }}</strong> {{ $produk->care ?: '-' }}</p>
        <p class="mb-0"><strong>{{ __('Deskripsi:') }}</strong> {{ $produk->description ?: '-' }}</p>
    </div>
</div>

<div class="ad-kartu">
    <h3 style="font-size: var(--cb-h5)">{{ __('Gambar Produk') }}</h3>
    @if($produk->gambar->count() > 0)
        <div class="d-flex flex-wrap gap-2 mt-2">
            @foreach($produk->gambar->sortBy('sort_order') as $gambar)
                <figure class="m-0">
                    <img src="{{ gambar_produk($gambar->path) }}" alt="{{ $produk->name }}"
                         width="120" height="160" style="object-fit: cover">
                    <figcaption style="font-size: .78rem; color: var(--cb-ink-3)">
                        {{ $gambar->color ?: __('tanpa warna') }}
                    </figcaption>
                </figure>
            @endforeach
        </div>
    @else
        <p style="color: var(--cb-ink-2); font-size: .875rem">{{ __('Belum ada gambar untuk produk ini.') }}</p>
    @endif
</div>

<div class="ad-kartu">
    <h3 style="font-size: var(--cb-h5)">{{ __('Varian dan Stok') }}</h3>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Ubah beberapa stok sekaligus, lalu tekan Simpan Stok.') }}
    </p>

    <form action="{{ route('admin.produk.stok', $produk) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="ad-gulir">
            <table class="ad-tabel">
                <thead>
                <tr>
                    <th>{{ __('Warna') }}</th>
                    <th>{{ __('Kode Warna') }}</th>
                    <th>{{ __('Ukuran') }}</th>
                    <th>{{ __('SKU') }}</th>
                    <th>{{ __('Stok') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($produk->varian as $varian)
                    <tr>
                        <td>{{ $varian->color }}</td>
                        <td>{{ $varian->color_hex ?: '-' }}</td>
                        <td>{{ $varian->size }}</td>
                        <td>{{ $varian->sku ?: '-' }}</td>
                        <td>
                            <input class="form-control" type="number" min="0" style="max-width: 120px"
                                   name="stok[{{ $varian->id }}]" value="{{ $varian->stock }}">
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ __('Produk ini belum punya varian.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($produk->varian->count() > 0)
            <button class="btn btn-primary btn-sm mt-3" type="submit">{{ __('Simpan Stok') }}</button>
        @endif
    </form>
</div>

@endsection
