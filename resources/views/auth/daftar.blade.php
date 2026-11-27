@extends('layouts.app')

@section('judul', 'Daftar Akun Baru')

@section('isi')
    <div class="container-xl section-sm" style="max-width: 560px">
        <form method="post" action="{{ route('register.store') }}" class="cb-card" novalidate>
            @csrf

            <h1 class="h4">Daftar Akun Baru</h1>
            <p class="small text-muted">
                Sudah punya akun? <a href="{{ route('login') }}" class="fw-600">Masuk di sini</a>
            </p>

            <div class="mb-3">
                <label for="name" class="form-label">Nama lengkap <span class="text-accent" aria-hidden="true">*</span></label>
                <input type="text" id="name" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" maxlength="100" autocomplete="name" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-accent" aria-hidden="true">*</span></label>
                <input type="email" id="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" maxlength="100" autocomplete="email" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Nomor telepon</label>
                <input type="tel" id="phone" name="phone"
                       class="form-control @error('phone') is-invalid @enderror"
                       value="{{ old('phone') }}" maxlength="20" placeholder="08xxxxxxxxxx" autocomplete="tel">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Boleh dikosongkan. Dipakai kurir saat mengantar pesanan.</div>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">Kata sandi <span class="text-accent" aria-hidden="true">*</span></label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="new-password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Paling sedikit 8 karakter.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Ulangi kata sandi <span class="text-accent" aria-hidden="true">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control" autocomplete="new-password" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Daftar</button>

            <p class="form-text mt-3 mb-0">
                Dengan mendaftar, Anda menyetujui
                <a href="{{ route('bantuan.show', 'cara-belanja') }}">cara belanja</a> dan
                <a href="{{ route('bantuan.show', 'retur-dan-penukaran') }}">ketentuan retur</a> Colorbox.
            </p>
        </form>
    </div>
@endsection
