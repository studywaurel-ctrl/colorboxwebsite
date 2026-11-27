@extends('layouts.app')

@section('judul', 'Masuk ke Akun')

@section('isi')
    <div class="container-xl section-sm" style="max-width: 980px">
        <div class="row g-4">
            <div class="col-md-6">
                <form method="post" action="{{ route('login.proses') }}" class="cb-card" novalidate>
                    @csrf

                    <h1 class="h4">Masuk ke Akun</h1>
                    <p class="small text-muted">
                        Masuk untuk menyelesaikan pembayaran, melihat riwayat pesanan, menjadwalkan
                        donasi, dan mengajukan retur.
                    </p>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-accent" aria-hidden="true">*</span></label>
                        <input type="email" id="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" autocomplete="email" required autofocus>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Kata sandi <span class="text-accent" aria-hidden="true">*</span></label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="current-password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <label class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="ingat" value="1" @if(old('ingat')) checked @endif>
                            <span class="form-check-label small">Ingat saya di perangkat ini</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="small">Lupa kata sandi?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Masuk</button>
                </form>
            </div>

            <div class="col-md-6">
                <div class="cb-card h-100">
                    <h2 class="h5">Belum punya akun?</h2>
                    <ul class="small text-muted ps-3">
                        <li>Checkout lebih cepat dengan alamat pengiriman tersimpan</li>
                        <li>Lacak pesanan dan ajukan retur dari satu halaman</li>
                        <li>Kumpulkan poin dari donasi pakaian layak pakai</li>
                        <li>Jadwalkan kunjungan Tap Try and Collect di toko</li>
                    </ul>
                    <a href="{{ route('register') }}" class="btn btn-outline-dark w-100">Daftar Akun Baru</a>

                    <hr>

                    <p class="small text-muted mb-0">
                        Ingin melihat status pesanan tanpa masuk?
                        <a href="{{ route('lacak.index') }}">Lacak pesanan memakai kode pesanan</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
