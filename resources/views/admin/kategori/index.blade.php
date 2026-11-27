@extends('layouts.admin')

@section('judul', __('Kategori'))

@section('isi')

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Tambah Kategori') }}</h2>
    <form class="mt-2" action="{{ route('admin.kategori.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="name">{{ __('Nama kategori') }}</label>
                <input class="form-control" type="text" id="name" name="name" maxlength="60" required value="{{ old('name') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="parent_id">{{ __('Kategori induk') }}</label>
                <select class="form-control" id="parent_id" name="parent_id">
                    <option value="">{{ __('Tanpa induk') }}</option>
                    @foreach($semua as $baris)
                        <option value="{{ $baris->id }}" @selected((string) old('parent_id') === (string) $baris->id)>{{ $baris->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="sort_order">{{ __('Urutan tampil') }}</label>
            <input class="form-control" type="number" id="sort_order" name="sort_order" min="0" max="999" value="{{ old('sort_order', 0) }}" style="max-width: 160px">
            <p class="form-text">{{ __('Angka kecil tampil duluan.') }}</p>
        </div>
        <button class="btn btn-primary btn-sm" type="submit">{{ __('Simpan Kategori') }}</button>
    </form>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Daftar Kategori') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Ada :p1 kategori. Kategori yang masih ada produknya nggak bisa dihapus.', ['p1' => $daftar->total()]) }}
    </p>

    <form class="mt-2" action="{{ route('admin.kategori.index') }}" method="GET">
        <div class="d-flex gap-2 flex-wrap align-items-end">
            <div class="mb-3 mb-0">
                <label class="form-label" for="cari">{{ __('Cari nama kategori') }}</label>
                <input class="form-control" type="search" id="cari" name="cari" value="{{ $cari }}">
            </div>
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Filter') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.kategori.index') }}">{{ __('Reset') }}</a>
        </div>
    </form>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Nama') }}</th>
                <th>{{ __('Induk') }}</th>
                <th>{{ __('Urutan') }}</th>
                <th>{{ __('Produk') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $kategori)
                <tr>
                    <td>
                        <form class="d-flex gap-2 flex-wrap align-items-center"
                              action="{{ route('admin.kategori.update', $kategori) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input class="form-control" type="text" name="name" maxlength="60" required
                                   value="{{ $kategori->name }}" style="max-width: 200px">
                            <select class="form-control" name="parent_id" style="max-width: 180px">
                                <option value="">{{ __('Tanpa induk') }}</option>
                                @foreach($semua as $pilihan)
                                    @if($pilihan->id !== $kategori->id)
                                        <option value="{{ $pilihan->id }}" @selected($kategori->parent_id === $pilihan->id)>{{ $pilihan->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <input class="form-control" type="number" name="sort_order" min="0" max="999"
                                   value="{{ $kategori->sort_order }}" style="max-width: 90px">
                            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Simpan') }}</button>
                        </form>
                    </td>
                    <td>{{ $kategori->induk?->name ?? '-' }}</td>
                    <td>{{ $kategori->sort_order }}</td>
                    <td>{{ $kategori->produk_count }}</td>
                    <td>
                        <form action="{{ route('admin.kategori.destroy', $kategori) }}" method="POST"
                              data-konfirmasi="Hapus kategori {{ $kategori->name }}?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link btn-sm p-0" type="submit">{{ __('Hapus') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('Belum ada kategori.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
