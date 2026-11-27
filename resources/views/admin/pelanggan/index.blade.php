@extends('layouts.admin')

@section('judul', __('Pelanggan'))

@section('isi')

<div class="ad-kartu">
    <div>
        <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Daftar Pelanggan') }}</h2>
        <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
            {{ __('Ada :p1 pelanggan. Halaman ini cuma buat lihat data.', ['p1' => $daftar->total()]) }}
        </p>
    </div>

    <form class="mt-3" action="{{ route('admin.pelanggan.index') }}" method="GET">
        <div class="d-flex gap-2 flex-wrap align-items-end">
            <div class="mb-3 mb-0">
                <label class="form-label" for="cari">{{ __('Cari nama, email, atau nomor HP') }}</label>
                <input class="form-control" type="search" id="cari" name="cari" value="{{ $cari }}">
            </div>
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Filter') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.pelanggan.index') }}">{{ __('Reset') }}</a>
        </div>
    </form>
</div>

<div class="ad-kartu">
    <div class="ad-gulir">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Nama') }}</th>
                <th>{{ __('Email') }}</th>
                <th>{{ __('No. HP') }}</th>
                <th>{{ __('Level') }}</th>
                <th>{{ __('Poin') }}</th>
                <th>{{ __('Pesanan') }}</th>
                <th>{{ __('Gabung') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $pelanggan)
                <tr>
                    <td>{{ $pelanggan->name }}</td>
                    <td>{{ $pelanggan->email }}</td>
                    <td>{{ $pelanggan->phone ?: '-' }}</td>
                    <td>{{ $pelanggan->member_level ?: '-' }}</td>
                    <td>{{ $pelanggan->points }}</td>
                    <td>{{ $pelanggan->pesanan_count }}</td>
                    <td>{{ tanggal_id($pelanggan->created_at) }}</td>
                    <td>
                        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.pelanggan.show', $pelanggan) }}">{{ __('Detail') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">{{ __('Pelanggan nggak ketemu.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
