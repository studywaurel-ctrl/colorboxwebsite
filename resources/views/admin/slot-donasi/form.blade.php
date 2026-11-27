@extends('layouts.admin')

@section('judul', __('Bikin Slot Donasi'))

@section('isi')

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Bikin Slot Donasi') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Slot dibuat sekaligus untuk rentang tanggal yang dipilih, maksimal 90 hari. Slot yang sudah ada nggak ditimpa, jadi booking-nya tetap aman. Jadwal ini cuma buat layanan donasi, terpisah dari slot Tap Try and Collect.') }}
    </p>

    <form class="mt-3" action="{{ route('admin.slot-donasi.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="store_id">{{ __('Toko penerima donasi') }}</label>
            <select class="form-control" id="store_id" name="store_id" required>
                <option value="">{{ __('Pilih toko') }}</option>
                @foreach($daftarToko as $toko)
                    <option value="{{ $toko->id }}" @selected((string) old('store_id') === (string) $toko->id)>
                        {{ $toko->name }} — {{ $toko->city }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="dari">{{ __('Tanggal mulai') }}</label>
                <input class="form-control" type="date" id="dari" name="dari" required value="{{ old('dari') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="sampai">{{ __('Tanggal akhir') }}</label>
                <input class="form-control" type="date" id="sampai" name="sampai" required value="{{ old('sampai') }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('Jam layanan') }}</label>
            <div class="d-flex flex-wrap gap-3">
                @forelse($daftarJam as $jam)
                    <label class="form-check-label">
                        <input type="checkbox" name="jam[]" value="{{ $jam }}"
                               @checked(is_array(old('jam')) && in_array($jam, old('jam')))>
                        {{ $jam }}
                    </label>
                @empty
                    <p class="mb-0">{{ __('Daftar jam layanan belum diatur di file konfigurasi.') }}</p>
                @endforelse
            </div>
            <p class="form-text">{{ __('Centang minimal satu jam. Tiap slot durasinya satu jam.') }}</p>
        </div>

        <div class="mb-3">
            <label class="form-label" for="quota">{{ __('Kuota tiap slot') }}</label>
            <input class="form-control" type="number" id="quota" name="quota" min="1" max="200" required
                   value="{{ old('quota', $kuotaBawaan) }}" style="max-width: 160px">
            <p class="form-text">{{ __('Jumlah donatur yang boleh datang di jam yang sama.') }}</p>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">{{ __('Simpan Slot') }}</button>
            <a class="btn btn-link p-0" href="{{ route('admin.slot-donasi.index') }}">{{ __('Batal') }}</a>
        </div>
    </form>
</div>

@endsection
