@extends('layouts.admin')

@section('judul', __('Toko'))

@section('isi')

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Daftar Toko') }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                {{ __('Ada :p1 toko.', ['p1' => $daftar->total()]) }}
            </p>
        </div>
        <a class="btn btn-primary btn-sm" href="{{ route('admin.toko.create') }}">
            <i class="bi bi-plus-lg"></i> {{ __('Tambah Toko') }}
        </a>
    </div>

    <form class="mt-3" action="{{ route('admin.toko.index') }}" method="GET">
        <div class="d-flex gap-2 flex-wrap align-items-end">
            <div class="mb-3 mb-0">
                <label class="form-label" for="cari">{{ __('Cari nama, kota, atau kode') }}</label>
                <input class="form-control" type="search" id="cari" name="cari" value="{{ $cari }}">
            </div>
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Filter') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.toko.index') }}">{{ __('Reset') }}</a>
        </div>
    </form>
</div>

<div class="ad-kartu">
    <div class="ad-gulir">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Kode') }}</th>
                <th>{{ __('Nama') }}</th>
                <th>{{ __('Kota') }}</th>
                <th>{{ __('Jam') }}</th>
                <th>{{ __('Layanan') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $toko)
                <tr>
                    <td>{{ $toko->code }}</td>
                    <td>
                        {{ $toko->name }}<br>
                        <span style="color: var(--cb-ink-3); font-size: .78rem">
                            {{ $toko->address }}{{ $toko->floor ? ', '.$toko->floor : '' }}
                        </span>
                    </td>
                    <td>{{ $toko->city }}</td>
                    <td>{{ jam_singkat($toko->open_time) }}–{{ jam_singkat($toko->close_time) }}</td>
                    <td>
                        @if($toko->has_ttc)<span class="status status-accent">{{ __('Tap Try and Collect') }}</span>@endif
                        @if($toko->has_donation)<span class="status status-accent">{{ __('Donasi') }}</span>@endif
                        @if(! $toko->has_ttc && ! $toko->has_donation)-@endif
                    </td>
                    <td>
                        @if($toko->is_active)
                            <span class="status status-success">{{ __('Aktif') }}</span>
                        @else
                            <span class="status status-warning">{{ __('Nonaktif') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.toko.edit', $toko) }}">{{ __('Edit') }}</a>
                            <form action="{{ route('admin.toko.destroy', $toko) }}" method="POST"
                                  data-konfirmasi="Hapus toko {{ $toko->name }}?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link btn-sm p-0" type="submit">{{ __('Hapus') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('Toko nggak ketemu.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
