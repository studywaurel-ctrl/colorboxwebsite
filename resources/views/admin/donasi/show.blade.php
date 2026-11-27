@extends('layouts.admin')

@section('judul', __('Donasi').' '.$donasi->donation_code)

@section('isi')

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Donasi :p1', ['p1' => $donasi->donation_code]) }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                {{ __('Didaftarkan :p1.', ['p1' => tanggal_id($donasi->created_at, true)]) }}
            </p>
        </div>
        <a class="btn btn-link btn-sm p-0" href="{{ route('admin.donasi.index') }}">{{ __('Kembali ke Daftar') }}</a>
    </div>

    <div class="ad-angka mt-3">
        <div>
            <p class="ad-angka__nilai mb-0">{{ __(ucfirst(str_replace('_', ' ', $donasi->status))) }}</p>
            <p class="ad-angka__label mb-0">{{ __('Status donasi') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $donasi->estimated_items }}</p>
            <p class="ad-angka__label mb-0">{{ __('Perkiraan pakaian (pcs)') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $donasi->received_items !== null ? $donasi->received_items : '-' }}</p>
            <p class="ad-angka__label mb-0">{{ __('Diterima staf toko (pcs)') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $donasi->points_awarded ?: 0 }}</p>
            <p class="ad-angka__label mb-0">{{ __('Poin yang diberikan') }}</p>
        </div>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Info Donasi') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <tbody>
            <tr>
                <th>{{ __('Donatur') }}</th>
                <td>
                    {{ $donasi->pengguna?->name ?? '-' }}
                    @if($donasi->pengguna)
                        <br><span style="color: var(--cb-ink-3); font-size: .78rem">{{ $donasi->pengguna->email }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ __('Toko') }}</th>
                <td>{{ $donasi->toko?->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ __('Jadwal') }}</th>
                <td>
                    @if($donasi->slot)
                        {{ __(':p1, jam :p2–:p3', ['p1' => tanggal_id($donasi->slot->date, true), 'p2' => jam_singkat($donasi->slot->start_time), 'p3' => jam_singkat($donasi->slot->end_time)]) }}
                    @else
                        {{ __('Jadwalnya udah nggak ada.') }}
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ __('Check in') }}</th>
                <td>{{ $donasi->checked_in_at ? tanggal_id($donasi->checked_in_at, true) : __('Belum check in') }}</td>
            </tr>
            <tr>
                <th>{{ __('Catatan kondisi pakaian') }}</th>
                <td>{{ $donasi->condition_note ?: '-' }}</td>
            </tr>
            <tr>
                <th>{{ __('Dicek oleh') }}</th>
                <td>{{ $donasi->verified_by ?: __('Belum dicek') }}</td>
            </tr>
            <tr>
                <th>{{ __('Dicek pada') }}</th>
                <td>{{ $donasi->verified_at ? tanggal_id($donasi->verified_at, true) : '-' }}</td>
            </tr>
            <tr>
                <th>{{ __('Alasan ditolak') }}</th>
                <td>{{ $donasi->reject_reason ?: '-' }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Cek Donasi') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Tiap 1 pakaian yang diterima bernilai :p1 poin. Poin baru masuk ke akun donatur setelah donasi disetujui.', ['p1' => $poinPerPotong]) }}
    </p>

    @if(in_array($donasi->status, ['disetujui', 'ditolak', 'dibatalkan']))
        <p class="alert alert-warning mb-0">
            {{ __('Donasi ini udah pernah dicek, jadi nggak bisa dicek ulang.') }}
        </p>
    @else
        <form class="mt-2" action="{{ route('admin.donasi.validasi', $donasi) }}" method="POST">
            @csrf
            @method('PUT')


            <div class="row g-3">
                <div class="mb-3">
                    <label class="form-label" for="received_items">{{ __('Jumlah pakaian yang diterima') }}</label>
                    <input class="form-control" type="number" id="received_items" name="received_items" min="0" max="500"
                           value="{{ old('received_items', $donasi->received_items ?? $donasi->estimated_items) }}">
                    <p class="form-text">{{ __('Wajib diisi minimal 1 kalau donasi disetujui.') }}</p>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="condition_note">{{ __('Catatan kondisi pakaian') }}</label>
                    <input class="form-control" type="text" id="condition_note" name="condition_note" maxlength="191"
                           value="{{ old('condition_note', $donasi->condition_note) }}" placeholder="{{ __('Contoh: semua layak dipakai') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="reject_reason">{{ __('Alasan ditolak') }}</label>
                <input class="form-control" type="text" id="reject_reason" name="reject_reason" maxlength="191"
                       value="{{ old('reject_reason') }}" placeholder="{{ __('Isi kalau donasinya ditolak aja') }}">
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" type="submit" name="keputusan" value="setujui"
                        data-confirm="{{ __('Setujui donasi ini? Poin langsung masuk ke akun donatur dan nggak bisa dibatalin sendiri.') }}">{{ __('Setujui Donasi') }}</button>
                <button class="btn btn-outline-danger btn-sm" type="submit" name="keputusan" value="tolak"
                        data-confirm="{{ __('Tolak donasi ini? Alasan wajib diisi dan akan dibaca donatur.') }}">{{ __('Tolak Donasi') }}</button>
            </div>
        </form>
    @endif
</div>

@endsection
