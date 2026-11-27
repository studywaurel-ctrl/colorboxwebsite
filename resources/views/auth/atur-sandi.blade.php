@extends('layouts.app')

@section('judul', 'Atur Kata Sandi Baru')

@section('isi')
    <div class="container-xl section-sm" style="max-width: 520px">
        <form method="post" action="{{ route('password.update') }}" class="cb-card" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <h1 class="h4">Atur Kata Sandi Baru</h1>
            <p class="small text-muted">
                Halaman ini dibuka dari tautan atur ulang kata sandi. Tautannya berlaku
                selama 60 menit sejak dibuat.
            </p>

            <div class="alert alert-warning d-flex gap-2" role="status">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <div class="small">
                    Tautan atur ulang ditulis ke berkas <code>storage/logs/laravel.log</code>
                    karena email pada pemasangan ini memakai <strong>MAIL_MAILER=log</strong>.
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-accent" aria-hidden="true">*</span></label>
                <input type="email" id="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $email) }}" maxlength="100" autocomplete="email" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Kata sandi baru <span class="text-accent" aria-hidden="true">*</span></label>
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       autocomplete="new-password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Paling sedikit 8 karakter.</div>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Ulangi kata sandi baru <span class="text-accent" aria-hidden="true">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Simpan Kata Sandi Baru</button>
            <a href="{{ route('login') }}" class="btn btn-outline-dark w-100 mt-2">Kembali ke halaman masuk</a>
        </form>
    </div>
@endsection
