<!doctype html>
<html lang="id" data-theme="{{ session('tema', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Dashboard') — Admin Colorbox</title>
    <link rel="icon" href="{{ asset('images/logo/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<header class="ad-atas">
    <button class="ad-burger" type="button" data-bs-toggle="offcanvas" data-bs-target="#menu-admin" aria-label="Buka menu">
        <i class="bi bi-list"></i>
    </button>
    <img src="{{ asset('images/logo/colorbox-mark.png') }}" alt="" class="brand-mark-img" aria-hidden="true">
    <img src="{{ asset('images/logo/colorbox-wordmark.png') }}" alt="Colorbox" class="brand-word">
    <h1 class="ad-atas__judul">@yield('judul', 'Dashboard')</h1>
    <div class="ad-atas__kanan">
        <span class="d-none d-md-inline" style="font-size: .875rem; color: var(--cb-ink-3)">
            {{ auth()->user()->name }}
        </span>
        <a class="btn btn-outline-dark btn-sm" href="{{ route('beranda') }}">Lihat Situs</a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="btn btn-outline-dark btn-sm" type="submit">Keluar</button>
        </form>
    </div>
</header>

{{-- Menu utama dashboard: muncul sebagai panel geser, baik di desktop maupun ponsel --}}
<div class="offcanvas offcanvas-start" tabindex="-1" id="menu-admin" aria-labelledby="judul-menu-admin">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title" id="judul-menu-admin" style="font-size: 1.05rem">Menu Admin</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="ad-menu">
            <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="{{ route('admin.produk.index') }}" @if(request()->routeIs('admin.produk.*')) aria-current="page" @endif><i class="bi bi-bag"></i> Produk dan Stok</a>
            <a href="{{ route('admin.kategori.index') }}" @if(request()->routeIs('admin.kategori.*')) aria-current="page" @endif><i class="bi bi-tags"></i> Kategori</a>
            <a href="{{ route('admin.pesanan.index') }}" @if(request()->routeIs('admin.pesanan.*')) aria-current="page" @endif><i class="bi bi-receipt"></i> Pesanan</a>
            <a href="{{ route('admin.pembayaran.index') }}" @if(request()->routeIs('admin.pembayaran.*')) aria-current="page" @endif><i class="bi bi-credit-card"></i> Pembayaran</a>
            <a href="{{ route('admin.retur.index') }}" @if(request()->routeIs('admin.retur.*')) aria-current="page" @endif><i class="bi bi-arrow-counterclockwise"></i> Retur</a>
            <a href="{{ route('admin.donasi.index') }}" @if(request()->routeIs('admin.donasi.*')) aria-current="page" @endif><i class="bi bi-box-seam"></i> Donasi</a>
            <a href="{{ route('admin.slot-donasi.index') }}" @if(request()->routeIs('admin.slot-donasi.*')) aria-current="page" @endif><i class="bi bi-calendar-heart"></i> Slot Donasi</a>
            <a href="{{ route('admin.ttc.index') }}" @if(request()->routeIs('admin.ttc.*')) aria-current="page" @endif><i class="bi bi-shop-window"></i> Tap Try &amp; Collect</a>
            <a href="{{ route('admin.slot-ttc.index') }}" @if(request()->routeIs('admin.slot-ttc.*')) aria-current="page" @endif><i class="bi bi-calendar-check"></i> Slot Tap Try &amp; Collect</a>
            <a href="{{ route('admin.toko.index') }}" @if(request()->routeIs('admin.toko.*')) aria-current="page" @endif><i class="bi bi-geo-alt"></i> Toko</a>
            <a href="{{ route('admin.pelanggan.index') }}" @if(request()->routeIs('admin.pelanggan.*')) aria-current="page" @endif><i class="bi bi-people"></i> Pelanggan</a>
            <a href="{{ route('admin.keluhan.index') }}" @if(request()->routeIs('admin.keluhan.*')) aria-current="page" @endif><i class="bi bi-chat-left-text"></i> Keluhan</a>
            <a href="{{ route('admin.voucher.index') }}" @if(request()->routeIs('admin.voucher.*')) aria-current="page" @endif><i class="bi bi-ticket-perforated"></i> Voucher</a>
            <a href="{{ route('admin.laporan.index') }}" @if(request()->routeIs('admin.laporan.*')) aria-current="page" @endif><i class="bi bi-graph-up"></i> Laporan Penjualan</a>
            <a href="{{ route('admin.pengaturan.index') }}" @if(request()->routeIs('admin.pengaturan.*')) aria-current="page" @endif><i class="bi bi-gear"></i> Pengaturan</a>
        </nav>
    </div>
</div>

<main class="ad-isi">
    @include('partials.pesan')
    @yield('isi')
</main>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('skrip')
</body>
</html>
