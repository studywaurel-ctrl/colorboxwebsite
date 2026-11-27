@extends('layouts.admin')

@section('judul', __('Retur'))

@section('isi')

<div class="ad-kartu">
    <div>
        <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Retur dan Tukar Barang') }}</h2>
        <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
            {{ __('Ada :p1 pengajuan di filter ini.', ['p1' => $daftar->total()]) }}
        </p>
    </div>

    <form class="mt-3" action="{{ route('admin.retur.index') }}" method="GET">
        <div class="d-flex gap-2 flex-wrap align-items-end">
            <div class="mb-3 mb-0">
                <span class="form-label d-block">{{ __('Status') }}</span>
                <div class="d-flex flex-wrap gap-1" role="group" aria-label="{{ __('Status') }}">
                    <input class="btn-check" type="radio" name="status" id="status-semua" value="" autocomplete="off" @checked(blank($status))>
                    <label class="btn btn-outline-dark btn-sm" for="status-semua">{{ __('Semua') }}</label>
                    @foreach($daftarStatus as $pilihan)
                        <input class="btn-check" type="radio" name="status" id="status-{{ $pilihan }}" value="{{ $pilihan }}" autocomplete="off" @checked($status === $pilihan)>
                        <label class="btn btn-outline-dark btn-sm" for="status-{{ $pilihan }}">{{ __(ucfirst(str_replace('_', ' ', $pilihan))) }}</label>
                    @endforeach
                </div>
            </div>
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Filter') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.retur.index') }}">{{ __('Reset') }}</a>
        </div>
    </form>
</div>

<div class="ad-kartu">
    <div class="ad-gulir">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Kode') }}</th>
                <th>{{ __('Pelanggan') }}</th>
                <th>{{ __('Pesanan') }}</th>
                <th>{{ __('Jenis') }}</th>
                <th>{{ __('Cara') }}</th>
                <th>{{ __('Toko') }}</th>
                <th>{{ __('Tanggal') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $retur)
                <tr>
                    <td>{{ $retur->return_code }}</td>
                    <td>{{ $retur->pengguna?->name ?? '-' }}</td>
                    <td>{{ $retur->pesanan?->order_code ?? ($retur->purchase_code ?: '-') }}</td>
                    <td>{{ $retur->type === 'tukar' ? __('Tukar barang') : __('Refund') }}</td>
                    <td>{{ $retur->method === 'toko' ? __('Dibawa ke toko') : __('Dikirim') }}</td>
                    <td>{{ $retur->toko?->name ?? '-' }}</td>
                    <td>{{ tanggal_id($retur->created_at) }}</td>
                    <td><span class="status status-accent">{{ __(ucfirst(str_replace('_', ' ', $retur->status))) }}</span></td>
                    <td>
                        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.retur.show', $retur) }}">{{ __('Detail') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">{{ __('Belum ada pengajuan retur di filter ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
