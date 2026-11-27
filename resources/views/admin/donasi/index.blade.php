@extends('layouts.admin')

@section('judul', __('Donasi'))

@section('isi')

<div class="ad-kartu">
    <div>
        <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Donasi Pakaian') }}</h2>
        <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
            {{ __('Ada :p1 donasi di filter ini.', ['p1' => $daftar->total()]) }}
        </p>
    </div>

    <form class="mt-3" action="{{ route('admin.donasi.index') }}" method="GET">
        <div class="row g-3">
            <div class="mb-3">
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
            <div class="mb-3">
                <label class="form-label" for="toko">{{ __('Toko') }}</label>
                <select class="form-control" id="toko" name="toko">
                    <option value="">{{ __('Semua toko') }}</option>
                    @foreach($daftarToko as $toko)
                        <option value="{{ $toko->id }}" @selected((string) $tokoId === (string) $toko->id)>{{ $toko->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-flex gap-2 mt-2">
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Filter') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.donasi.index') }}">{{ __('Reset') }}</a>
        </div>
    </form>
</div>

<div class="ad-kartu">
    <div class="ad-gulir">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Kode') }}</th>
                <th>{{ __('Donatur') }}</th>
                <th>{{ __('Toko') }}</th>
                <th>{{ __('Jadwal') }}</th>
                <th>{{ __('Perkiraan') }}</th>
                <th>{{ __('Diterima') }}</th>
                <th>{{ __('Poin') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $donasi)
                <tr>
                    <td>{{ $donasi->donation_code }}</td>
                    <td>{{ $donasi->pengguna?->name ?? '-' }}</td>
                    <td>{{ $donasi->toko?->name ?? '-' }}</td>
                    <td>
                        @if($donasi->slot)
                            {{ tanggal_id($donasi->slot->date) }}<br>
                            <span style="color: var(--cb-ink-3); font-size: .78rem">
                                {{ jam_singkat($donasi->slot->start_time) }}–{{ jam_singkat($donasi->slot->end_time) }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ __(':p1 pcs', ['p1' => $donasi->estimated_items]) }}</td>
                    <td>{{ $donasi->received_items !== null ? $donasi->received_items.' '.__('pcs') : '-' }}</td>
                    <td>{{ $donasi->points_awarded ?: 0 }}</td>
                    <td><span class="status status-accent">{{ __(ucfirst(str_replace('_', ' ', $donasi->status))) }}</span></td>
                    <td>
                        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.donasi.show', $donasi) }}">{{ __('Detail') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">{{ __('Belum ada donasi di filter ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
