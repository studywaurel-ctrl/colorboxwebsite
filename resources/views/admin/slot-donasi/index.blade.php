@extends('layouts.admin')

@section('judul', __('Slot Donasi'))

@section('isi')

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Slot Donasi Pakaian') }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                {{ __('Jadwal ini cuma buat layanan donasi, terpisah dari slot Tap Try and Collect.') }}
            </p>
        </div>
        <a class="btn btn-primary btn-sm" href="{{ route('admin.slot-donasi.create') }}">
            <i class="bi bi-plus-lg"></i> {{ __('Bikin Slot') }}
        </a>
    </div>

    <form class="mt-3" action="{{ route('admin.slot-donasi.index') }}" method="GET">
        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="toko">{{ __('Toko') }}</label>
                <select class="form-control" id="toko" name="toko">
                    <option value="">{{ __('Semua toko') }}</option>
                    @foreach($daftarToko as $toko)
                        <option value="{{ $toko->id }}" @selected((string) $tokoId === (string) $toko->id)>{{ $toko->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="tanggal">{{ __('Tanggal') }}</label>
                <input class="form-control" type="date" id="tanggal" name="tanggal" value="{{ $tanggal }}">
            </div>
        </div>
        <div class="d-flex gap-2 mt-2">
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Filter') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.slot-donasi.index') }}">{{ __('Reset') }}</a>
        </div>
    </form>
</div>

<div class="ad-kartu">
    <div class="ad-gulir">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Tanggal') }}</th>
                <th>{{ __('Toko') }}</th>
                <th>{{ __('Jam') }}</th>
                <th>{{ __('Kuota') }}</th>
                <th>{{ __('Terisi') }}</th>
                <th>{{ __('Sisa') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $slot)
                <tr>
                    <td>{{ tanggal_id($slot->date, true) }}</td>
                    <td>{{ $slot->toko?->name ?? '-' }}</td>
                    <td>{{ jam_singkat($slot->start_time) }}–{{ jam_singkat($slot->end_time) }}</td>
                    <td>{{ $slot->quota }}</td>
                    <td>{{ $slot->booked_count }}</td>
                    <td>
                        @if($slot->sisaKuota() === 0)
                            <span class="status status-warning">{{ __('Penuh') }}</span>
                        @else
                            {{ $slot->sisaKuota() }}
                        @endif
                    </td>
                    <td>
                        @if($slot->status === 'aktif')
                            <span class="status status-success">{{ __('Aktif') }}</span>
                        @else
                            <span class="status status-warning">{{ __('Nonaktif') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.slot-donasi.edit', $slot) }}">{{ __('Edit') }}</a>
                            <form action="{{ route('admin.slot-donasi.destroy', $slot) }}" method="POST"
                                  data-konfirmasi="Hapus slot donasi {{ tanggal_id($slot->date) }} pukul {{ jam_singkat($slot->start_time) }}?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link btn-sm p-0" type="submit">{{ __('Hapus') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">{{ __('Belum ada slot donasi di filter ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
