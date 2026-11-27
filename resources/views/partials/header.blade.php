@php
    // Menu utama situs. Ditulis sebagai array supaya tidak berulang antara
    // menu layar lebar dan menu ponsel.
    $menu = [
        ['rute' => 'beranda', 'label' => 'Beranda', 'ikon' => 'house', 'aktif' => request()->routeIs('beranda')],
        ['rute' => 'produk.baru', 'label' => 'New Arrival', 'ikon' => 'stars', 'aktif' => request()->routeIs('produk.baru')],
        ['rute' => 'produk.index', 'label' => 'Produk', 'ikon' => 'bag', 'aktif' => request()->routeIs('produk.index', 'produk.show')],
        ['rute' => 'vto.index', 'label' => 'Virtual Try On', 'ikon' => 'person-bounding-box', 'aktif' => request()->routeIs('vto.*')],
        ['rute' => 'ttc.index', 'label' => 'Tap Try and Collect', 'ikon' => 'hand-index', 'aktif' => request()->routeIs('ttc.*')],
        ['rute' => 'donasi.index', 'label' => 'Donasi', 'ikon' => 'recycle', 'aktif' => request()->routeIs('donasi.*')],
        ['rute' => 'toko.index', 'label' => 'Daftar Toko', 'ikon' => 'shop', 'aktif' => request()->routeIs('toko.*')],
    ];

    $jumlahKeranjang = 0;
    $jumlahWishlist = 0;
    $jumlahNotifikasi = 0;
    if (auth()->check()) {
        $keranjangSaya = \App\Models\Cart::where('user_id', auth()->id())->first();
        $jumlahKeranjang = $keranjangSaya ? (int) $keranjangSaya->item()->sum('quantity') : 0;
        $jumlahWishlist = \App\Models\Wishlist::where('user_id', auth()->id())->count();
        $jumlahNotifikasi = \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->count();
    }
@endphp

<header class="site-header">
    <div class="container-xl">
        <div class="header-top">
            <button class="btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuPonsel"
                    aria-controls="menuPonsel" aria-label="Buka menu">
                <i class="bi bi-list"></i>
            </button>

            <a href="{{ route('beranda') }}" class="brand" aria-label="Colorbox, Beranda">
                <img src="{{ asset('images/logo/colorbox-mark.png') }}" alt="" class="brand-mark-img" aria-hidden="true">
                <img src="{{ asset('images/logo/colorbox-wordmark.png') }}" alt="Colorbox" class="brand-word">
            </a>

            <form action="{{ route('produk.index') }}" method="get" class="header-search d-none d-md-block" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="cari-situs" class="visually-hidden">Cari produk</label>
                <input type="search" id="cari-situs" name="cari" class="form-control"
                       value="{{ request()->routeIs('produk.*') ? request('cari') : '' }}"
                       placeholder="Cari produk, kategori, koleksi" autocomplete="off" maxlength="80">
            </form>

            <div class="header-actions ms-auto ms-md-0">
                <button class="btn-icon d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#cariPonsel"
                        aria-controls="cariPonsel" aria-expanded="false" aria-label="Cari">
                    <i class="bi bi-search"></i>
                </button>

                <a href="{{ route('wishlist.index') }}" class="btn-icon d-none d-sm-inline-flex" aria-label="Wishlist" title="Wishlist">
                    <i class="bi bi-heart"></i><span class="count-badge">{{ $jumlahWishlist ?: '' }}</span>
                </a>

                <a href="{{ route('keranjang.index') }}" class="btn-icon" aria-label="Keranjang" title="Keranjang">
                    <i class="bi bi-bag"></i><span class="count-badge">{{ $jumlahKeranjang ?: '' }}</span>
                </a>

                @auth
                    <div class="dropdown">
                        <button class="btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Akun">
                            <i class="bi bi-person"></i>
                            @if($jumlahNotifikasi > 0)<span class="count-badge">{{ $jumlahNotifikasi }}</span>@endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li class="px-3 py-2 small text-muted">{{ auth()->user()->name }}</li>
                            @if(auth()->user()->adalahAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('akun.index') }}"><i class="bi bi-person me-2"></i>Akun Saya</a></li>
                            <li><a class="dropdown-item" href="{{ route('pesanan.index') }}"><i class="bi bi-receipt me-2"></i>Pesanan Saya</a></li>
                            <li><a class="dropdown-item" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell me-2"></i>Notifikasi
                                    @if($jumlahNotifikasi > 0)<span class="status status-accent ms-1">{{ $jumlahNotifikasi }}</span>@endif
                                </a></li>
                            <li><a class="dropdown-item" href="{{ route('retur.index') }}"><i class="bi bi-arrow-left-right me-2"></i>Retur &amp; Penukaran</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="{{ route('logout') }}">@csrf
                                    <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn-icon" aria-label="Masuk" title="Masuk"><i class="bi bi-person"></i></a>
                @endauth

                <button class="btn-icon theme-toggle d-none d-sm-inline-flex" type="button" data-theme-toggle
                        aria-label="Ganti mode terang atau gelap" title="Ganti mode terang atau gelap">
                    <i class="bi bi-moon-stars"></i><i class="bi bi-sun"></i>
                </button>
            </div>
        </div>

        <div class="collapse d-md-none pb-3" id="cariPonsel">
            <form action="{{ route('produk.index') }}" method="get" class="header-search mw-100" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="cari-situs-ponsel" class="visually-hidden">Cari produk</label>
                <input type="search" id="cari-situs-ponsel" name="cari" class="form-control"
                       placeholder="Cari produk, kategori, koleksi" autocomplete="off" maxlength="80">
            </form>
        </div>

        <nav class="main-nav d-none d-lg-flex" aria-label="Menu utama">
            @foreach ($menu as $butir)
                <a href="{{ route($butir['rute']) }}" class="{{ $butir['aktif'] ? 'active' : '' }}"
                   @if($butir['aktif']) aria-current="page" @endif>{{ $butir['label'] }}</a>
            @endforeach
        </nav>
    </div>
</header>

<div class="offcanvas offcanvas-start" tabindex="-1" id="menuPonsel" aria-labelledby="judulMenuPonsel">
    <div class="offcanvas-header">
        <span class="brand" id="judulMenuPonsel">
            <img src="{{ asset('images/logo/colorbox-mark.png') }}" alt="" class="brand-mark-img" aria-hidden="true">
            <img src="{{ asset('images/logo/colorbox-wordmark.png') }}" alt="Colorbox" class="brand-word">
        </span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <nav class="mobile-nav" aria-label="Menu utama">
            @foreach ($menu as $butir)
                <a href="{{ route($butir['rute']) }}" @if($butir['aktif']) aria-current="page" class="fw-600" @endif>
                    <i class="bi bi-{{ $butir['ikon'] }}"></i>{{ $butir['label'] }}
                </a>
            @endforeach
            <a href="{{ route('wishlist.index') }}"><i class="bi bi-heart"></i>Wishlist</a>
            <a href="{{ route('lacak.index') }}"><i class="bi bi-truck"></i>Lacak Pesanan</a>
            @auth
                <a href="{{ route('akun.index') }}"><i class="bi bi-person"></i>Akun Saya</a>
            @else
                <a href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i>Masuk / Daftar</a>
            @endauth
        </nav>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-sm btn-outline-dark theme-toggle flex-fill" type="button" data-theme-toggle>
                <i class="bi bi-moon-stars"></i><i class="bi bi-sun"></i> <span data-theme-label>Tema</span>
            </button>
        </div>
    </div>
</div>
