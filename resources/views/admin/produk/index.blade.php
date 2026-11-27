@extends('layouts.admin')

@section('judul', __('Produk dan Stok'))

@section('isi')

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Produk dan Stok') }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                {{ __('Ada :p1 produk.', ['p1' => $daftar->total()]) }}
            </p>
        </div>
        <a class="btn btn-primary btn-sm" href="{{ route('admin.produk.create') }}">
            <i class="bi bi-plus-lg"></i> {{ __('Tambah Produk') }}
        </a>
    </div>

    <form class="mt-3" action="{{ route('admin.produk.index') }}" method="GET">
        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="cari">{{ __('Cari nama atau SKU') }}</label>
                <input class="form-control" type="search" id="cari" name="cari" value="{{ $cari }}" placeholder="{{ __('Contoh: kemeja') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="kategori">{{ __('Kategori') }}</label>
                <select class="form-control" id="kategori" name="kategori">
                    <option value="">{{ __('Semua kategori') }}</option>
                    @foreach($kategori as $baris)
                        <option value="{{ $baris->id }}" @selected((string) $kategoriId === (string) $baris->id)>{{ $baris->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-flex gap-2 mt-2">
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Filter') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.produk.index') }}">{{ __('Reset') }}</a>
        </div>
    </form>
</div>

<div class="ad-kartu">
    <div class="ad-gulir">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Nama') }}</th>
                <th>{{ __('Kategori') }}</th>
                <th>{{ __('Harga') }}</th>
                <th>{{ __('Total Stok') }}</th>
                <th>{{ __('Penanda') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $produk)
                <tr>
                    <td>
                        <a href="{{ route('admin.produk.show', $produk) }}">{{ $produk->name }}</a><br>
                        <span style="color: var(--cb-ink-3); font-size: .78rem">{{ $produk->sku ?: __('tanpa SKU') }}</span>
                    </td>
                    <td>{{ $produk->kategori?->name ?? '-' }}</td>
                    <td>
                        {{ rupiah($produk->hargaJual()) }}
                        @if($produk->discount_price)
                            <br><span class="was">{{ rupiah($produk->price) }}</span>
                        @endif
                    </td>
                    <td>{{ $produk->totalStok() }}</td>
                    <td>
                        @if($produk->is_new)<span class="status status-info">{{ __('Baru') }}</span>@endif
                        @if($produk->is_active)
                            <span class="status status-success">{{ __('Aktif') }}</span>
                        @else
                            <span class="status status-warning">{{ __('Nonaktif') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.produk.edit', $produk) }}">{{ __('Edit') }}</a>
                            <form action="{{ route('admin.produk.destroy', $produk) }}" method="POST"
                                  data-konfirmasi="Hapus produk {{ $produk->name }} beserta varian dan gambarnya?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link btn-sm p-0" type="submit">{{ __('Hapus') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('Produk nggak ketemu.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
