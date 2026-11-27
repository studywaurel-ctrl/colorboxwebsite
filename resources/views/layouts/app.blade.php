<!DOCTYPE html>
<html lang="id" data-theme="{{ session('tema', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('deskripsi', 'Colorbox: fashion wanita, koleksi terbaru, kolaborasi, dan layanan toko.')">
    @php $judulHalaman = trim($__env->yieldContent('judul')); @endphp
    <title>{{ $judulHalaman !== '' ? $judulHalaman.' · Colorbox' : 'Colorbox' }}</title>
    <link rel="icon" href="{{ asset('images/logo/favicon.png') }}" type="image/png">
    <link rel="preload" href="{{ asset('fonts/jakarta-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>
{{-- Alamat endpoint dikirim lewat atribut data supaya Blade tidak perlu menanam data JSON --}}
<body class="@yield('kelas_body')"
      data-masuk="{{ auth()->check() ? '1' : '0' }}"
      data-rute-masuk="{{ route('login') }}"
      data-rute-tema="{{ route('tema.ubah') }}"
      data-rute-slot-ttc="{{ route('ttc.slot') }}"
      data-rute-slot-donasi="{{ route('donasi.slot') }}">
<a href="#konten" class="skip-link">Lewati ke konten</a>

<div class="announcement">
    <i class="bi bi-truck me-1" aria-hidden="true"></i>
    Gratis ongkir untuk belanja minimal {{ rupiah(setting('free_shipping_threshold', 350000)) }}
    <span class="d-none d-sm-inline">· <a href="{{ route('bantuan.show', 'pengiriman') }}">Info pengiriman</a></span>
</div>

@include('partials.header')

<main id="konten" tabindex="-1">
    @include('partials.pesan')
    @yield('isi')
</main>

@include('partials.footer')

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/qrcode/qrcode.js') }}" defer></script>
<script src="{{ asset('js/app.js') }}" defer></script>
@stack('skrip')
</body>
</html>
