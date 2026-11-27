@extends('layouts.admin')

@section('judul', __('Edit Slot Donasi'))

@section('isi')

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Edit Slot Donasi') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Tanggal, toko, dan jam slot nggak bisa diubah. Kalau jadwalnya salah, matikan slot ini lalu bikin slot baru.') }}
    </p>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <tbody>
            <tr><th>{{ __('Toko') }}</th><td>{{ $slot->toko?->name ?? '-' }}</td></tr>
            <tr><th>{{ __('Tanggal') }}</th><td>{{ tanggal_id($slot->date, true) }}</td></tr>
            <tr><th>{{ __('Jam') }}</th><td>{{ jam_singkat($slot->start_time) }}–{{ jam_singkat($slot->end_time) }}</td></tr>
            <tr><th>{{ __('Sudah dipesan') }}</th><td>{{ __(':p1 donatur', ['p1' => $slot->booked_count]) }}</td></tr>
            <tr>
                <th>{{ __('Sisa kuota') }}</th>
                <td>
                    @if($slot->sisaKuota() === 0)
                        <span class="status status-warning">{{ __('Penuh') }}</span>
                    @else
                        {{ $slot->sisaKuota() }}
                    @endif
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <form class="mt-3" action="{{ route('admin.slot-donasi.update', $slot) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="quota">{{ __('Kuota') }}</label>
                <input class="form-control" type="number" id="quota" name="quota" min="0" max="200" required
                       value="{{ old('quota', $slot->quota) }}">
                <p class="form-text">{{ __('Nggak boleh lebih kecil dari :p1 booking yang udah masuk.', ['p1' => $slot->booked_count]) }}</p>
            </div>
            <div class="mb-3">
                <label class="form-label" for="status">{{ __('Status slot') }}</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="aktif" @selected(old('status', $slot->status) === 'aktif')>{{ __('Aktif') }}</option>
                    <option value="nonaktif" @selected(old('status', $slot->status) === 'nonaktif')>{{ __('Nonaktif') }}</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">{{ __('Simpan Perubahan') }}</button>
            <a class="btn btn-link p-0" href="{{ route('admin.slot-donasi.index') }}">{{ __('Batal') }}</a>
        </div>
    </form>
</div>

@endsection
