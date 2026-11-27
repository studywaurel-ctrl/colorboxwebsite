@extends('layouts.app')

@section('judul', 'Lupa Kata Sandi')

@section('isi')
    <div class="container-xl section-sm" style="max-width: 520px">
        <form method="post" action="{{ route('password.email') }}" class="cb-card" novalidate>
            @csrf

            <h1 class="h4">Lupa Kata Sandi</h1>
            <p class="small text-muted">
                Masukkan email yang dipakai saat mendaftar. Kami akan membuat tautan untuk
                mengatur kata sandi baru.
            </p>

            <div class="alert alert-warning d-flex gap-2" role="status">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <div class="small">
                    Pada pemasangan ini pengaturan email memakai <strong>MAIL_MAILER=log</strong>,
                    sehingga email tidak benar-benar terkirim. Isi emailnya ditulis ke berkas
                    <code>storage/logs/laravel.log</code> &mdash; buka berkas tersebut, lalu salin
                    tautan atur ulang kata sandi yang muncul di bagian paling bawah.
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-accent" aria-hidden="true">*</span></label>
                <input type="email" id="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" maxlength="100" autocomplete="email" required autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">Kirim Tautan Atur Ulang</button>
            <a href="{{ route('login') }}" class="btn btn-outline-dark w-100 mt-2">Kembali ke halaman masuk</a>
        </form>
    </div>
@endsection
