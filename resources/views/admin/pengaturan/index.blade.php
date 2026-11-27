@extends('layouts.admin')

@section('judul', __('Pengaturan'))

@section('isi')

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Aturan Bisnis') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Nilai di bawah ini dipakai di semua halaman, misalnya batas gratis ongkir, poin per pakaian donasi, dan batas hari buat ajukan retur. Kolom bertanda angka harus diisi angka dan nggak boleh kosong.') }}
    </p>

    @if($daftar->count() === 0)
        <p class="mb-0">{{ __('Belum ada pengaturan di database.') }}</p>
    @else
        <form class="mt-3" action="{{ route('admin.pengaturan.update') }}" method="POST">
            @csrf
            @method('PUT')

            @foreach($daftar as $baris)
                <div class="mb-3">
                    <label class="form-label" for="nilai-{{ $baris->key }}">
                        {{ $baris->key }}
                        @if(in_array($baris->key, $kunciAngka))
                            <span class="status status-accent">{{ __('Angka') }}</span>
                        @endif
                    </label>
                    @if(in_array($baris->key, $kunciAngka))
                        <input class="form-control" type="number" step="any" min="0"
                               id="nilai-{{ $baris->key }}" name="nilai[{{ $baris->key }}]" required
                               value="{{ old('nilai.'.$baris->key, $baris->value) }}" style="max-width: 260px">
                    @else
                        <input class="form-control" type="text" maxlength="500"
                               id="nilai-{{ $baris->key }}" name="nilai[{{ $baris->key }}]"
                               value="{{ old('nilai.'.$baris->key, $baris->value) }}">
                    @endif
                    <p class="form-text">{{ $baris->description ?: __('Nggak ada keterangan.') }}</p>
                </div>
            @endforeach

            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit">{{ __('Simpan Pengaturan') }}</button>
                <a class="btn btn-link p-0" href="{{ route('admin.pengaturan.index') }}">{{ __('Batal') }}</a>
            </div>
        </form>
    @endif
</div>

@endsection
