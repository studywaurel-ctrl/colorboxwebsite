@extends('layouts.admin')

@section('judul', $toko ? __('Edit Toko') : __('Tambah Toko'))

@section('isi')

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ $toko ? __('Edit Toko:').' '.$toko->name : __('Tambah Toko Baru') }}</h2>

    <form class="mt-3" method="POST"
          action="{{ $toko ? route('admin.toko.update', $toko) : route('admin.toko.store') }}">
        @csrf
        @if($toko)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="code">{{ __('Kode toko') }}</label>
                <input class="form-control" type="text" id="code" name="code" maxlength="10" required
                       value="{{ old('code', $toko->code ?? '') }}" placeholder="CBX01">
            </div>
            <div class="mb-3">
                <label class="form-label" for="name">{{ __('Nama toko') }}</label>
                <input class="form-control" type="text" id="name" name="name" maxlength="120" required
                       value="{{ old('name', $toko->name ?? '') }}">
            </div>
        </div>

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="city">{{ __('Kota') }}</label>
                <input class="form-control" type="text" id="city" name="city" maxlength="60" required
                       value="{{ old('city', $toko->city ?? '') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="floor">{{ __('Lantai') }}</label>
                <input class="form-control" type="text" id="floor" name="floor" maxlength="40"
                       value="{{ old('floor', $toko->floor ?? '') }}" placeholder="{{ __('Lantai 2 Unit 21') }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="address">{{ __('Alamat') }}</label>
            <input class="form-control" type="text" id="address" name="address" maxlength="191" required
                   value="{{ old('address', $toko->address ?? '') }}">
        </div>

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="phone">{{ __('No. HP') }}</label>
                <input class="form-control" type="text" id="phone" name="phone" maxlength="20"
                       value="{{ old('phone', $toko->phone ?? '') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Jam layanan') }}</label>
                <div class="d-flex gap-2">
                    <input class="form-control" type="time" name="open_time" required
                           value="{{ old('open_time', $toko ? substr($toko->open_time, 0, 5) : '10:00') }}">
                    <input class="form-control" type="time" name="close_time" required
                           value="{{ old('close_time', $toko ? substr($toko->close_time, 0, 5) : '22:00') }}">
                </div>
                <p class="form-text">{{ __('Jam buka dan jam tutup.') }}</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="latitude">{{ __('Latitude') }}</label>
                <input class="form-control" type="text" id="latitude" name="latitude"
                       value="{{ old('latitude', $toko->latitude ?? '') }}" placeholder="-6.229700">
            </div>
            <div class="mb-3">
                <label class="form-label" for="longitude">{{ __('Longitude') }}</label>
                <input class="form-control" type="text" id="longitude" name="longitude"
                       value="{{ old('longitude', $toko->longitude ?? '') }}" placeholder="106.808700">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('Layanan dan status') }}</label>
            <div class="d-flex flex-wrap gap-3">
                <label class="form-check-label">
                    <input type="checkbox" name="has_ttc" value="1" @checked(old('has_ttc', $toko->has_ttc ?? true))>
                    {{ __('Ada Tap Try and Collect') }}
                </label>
                <label class="form-check-label">
                    <input type="checkbox" name="has_donation" value="1" @checked(old('has_donation', $toko->has_donation ?? true))>
                    {{ __('Terima donasi pakaian') }}
                </label>
                <label class="form-check-label">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $toko->is_active ?? true))>
                    {{ __('Toko aktif') }}
                </label>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">{{ $toko ? __('Simpan Perubahan') : __('Simpan Toko') }}</button>
            <a class="btn btn-link p-0" href="{{ route('admin.toko.index') }}">{{ __('Batal') }}</a>
        </div>
    </form>
</div>

@endsection
