@extends('layouts.app')

@section('judul', 'Beranda')

@section('isi')

@php
    // Dua gambar produk dipakai sebagai hiasan pada bagian sorotan
    $gambarSorot = $produkBaru->take(2);
    if ($gambarSorot->isEmpty()) {
        $gambarSorot = $produkKolaborasi->take(2);
    }
@endphp

<div class="container-xl pt-4">
    {{-- Bagian sorotan di paling atas halaman --}}
    <section class="hero" aria-labelledby="judul-sorot">
        <div class="row g-0 align-items-stretch">
            <div class="col-lg-6 d-flex">
                <div class="hero-body align-self-center">
                    <p class="eyebrow mb-2">Koleksi Colorbox</p>
                    <h1 id="judul-sorot" class="display-title mb-3">Temukan Gaya Favoritmu di Colorbox</h1>
                    <p class="text-muted mb-4" style="max-width: 440px">
                        Eksplor koleksi fashion terbaru yang siap melengkapi gaya sehari-harimu.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('produk.index') }}" class="btn btn-primary btn-lg">Belanja Sekarang</a>
                        <a href="{{ route('produk.baru') }}" class="btn btn-outline-dark btn-lg">Lihat Koleksi</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-art h-100" aria-hidden="true">
                    @foreach($gambarSorot as $satu)
                        @php $gambarKartu = $satu->gambarUtama(); @endphp
                        <img src="{{ gambar_produk($gambarKartu ? $gambarKartu->path : null) }}" alt="" width="230" height="288">
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <div class="promo-strip mt-3" role="note">
        <i class="bi bi-truck" aria-hidden="true"></i>
        <div>
            <strong>Gratis ongkir untuk belanja minimal {{ rupiah(setting('free_shipping_threshold', 350000)) }}</strong>
            <span class="text-muted small d-block d-sm-inline ms-sm-1">Berlaku untuk pesanan yang dikirim ke alamat Anda.</span>
        </div>
    </div>
</div>

{{-- Produk yang baru masuk --}}
<section class="section" aria-labelledby="judul-baru">
    <div class="container-xl">
        <div class="section-head">
            <div>
                <p class="eyebrow mb-1">Baru datang</p>
                <h2 id="judul-baru">New Arrival</h2>
            </div>
            <a href="{{ route('produk.baru') }}" class="btn btn-link">Lihat semua</a>
        </div>

        @if($produkBaru->count() > 0)
            <div class="row g-3 g-md-4">
                @foreach($produkBaru as $satu)
                    <div class="col-6 col-md-3"><x-kartu-produk :produk="$satu" /></div>
                @endforeach
            </div>
        @else
            <x-kosong ikon="stars" judul="Belum ada produk baru"
                      teks="Koleksi yang baru masuk akan muncul di bagian ini." />
        @endif
    </div>
</section>

{{-- Kategori pilihan --}}
@if($kategoriPilihan->count() > 0)
    <section class="section pt-0" aria-labelledby="judul-kategori">
        <div class="container-xl">
            <div class="section-head">
                <div>
                    <p class="eyebrow mb-1">Mulai dari sini</p>
                    <h2 id="judul-kategori">Kategori Pilihan</h2>
                </div>
                <a href="{{ route('produk.index') }}" class="btn btn-link">Semua produk</a>
            </div>
            <div class="tabs-scroll">
                @foreach($kategoriPilihan as $kategori)
                    <a href="{{ route('produk.index', ['kategori' => $kategori->slug]) }}">{{ $kategori->name }}</a>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Layanan Colorbox --}}
<section class="section pt-0" aria-labelledby="judul-layanan">
    <div class="container-xl">
        <div class="section-head">
            <div>
                <p class="eyebrow mb-1">Layanan</p>
                <h2 id="judul-layanan">Layanan Colorbox</h2>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="service-tile">
                    <div class="tile-icon"><i class="bi bi-person-bounding-box" aria-hidden="true"></i></div>
                    <h3 class="h5">Virtual Try On</h3>
                    <p>Lihat gambaran pakaian pada foto Anda sebelum memutuskan untuk membelinya.</p>
                    <a href="{{ route('vto.index') }}" class="btn btn-outline-dark btn-sm align-self-start">Mulai mencoba</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="service-tile">
                    <div class="tile-icon"><i class="bi bi-hand-index" aria-hidden="true"></i></div>
                    <h3 class="h5">Tap Try and Collect</h3>
                    <p>Pesan lewat situs, lalu coba dan ambil barangnya di toko pilihan Anda tanpa ongkos kirim.</p>
                    <a href="{{ route('ttc.index') }}" class="btn btn-outline-dark btn-sm align-self-start">Buat jadwal</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="service-tile">
                    <div class="tile-icon"><i class="bi bi-recycle" aria-hidden="true"></i></div>
                    <h3 class="h5">Donasi Poin</h3>
                    <p>Serahkan pakaian layak pakai di toko dan tukarkan poinnya menjadi potongan belanja.</p>
                    <a href="{{ route('donasi.index') }}" class="btn btn-outline-dark btn-sm align-self-start">Jadwalkan donasi</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="service-tile">
                    <div class="tile-icon"><i class="bi bi-shop" aria-hidden="true"></i></div>
                    <h3 class="h5">Daftar Toko</h3>
                    <p>Cari alamat, jam buka, dan layanan yang tersedia di setiap toko Colorbox.</p>
                    <a href="{{ route('toko.index') }}" class="btn btn-outline-dark btn-sm align-self-start">Lihat daftar toko</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Koleksi kolaborasi --}}
@if($produkKolaborasi->count() > 0)
    <section class="section pt-0" aria-labelledby="judul-kolaborasi">
        <div class="container-xl">
            <div class="section-head">
                <div>
                    <p class="eyebrow mb-1">Kolaborasi</p>
                    <h2 id="judul-kolaborasi">Koleksi Kolaborasi</h2>
                </div>
                @if($namaKoleksi->count() > 0)
                    <a href="{{ route('produk.index', ['koleksi' => $namaKoleksi->first()]) }}" class="btn btn-link">Lihat koleksinya</a>
                @endif
            </div>

            @if($namaKoleksi->count() > 0)
                <div class="row g-3 mb-4">
                    @foreach($namaKoleksi->take(3) as $koleksi)
                        @php
                            $contoh = $produkKolaborasi->firstWhere('collection', $koleksi);
                            $gambarKoleksi = $contoh ? $contoh->gambarUtama() : null;
                        @endphp
                        <div class="col-md-4">
                            <a href="{{ route('produk.index', ['koleksi' => $koleksi]) }}" class="collab-card">
                                <div class="collab-banner">
                                    @if($gambarKoleksi)
                                        <img src="{{ gambar_produk($gambarKoleksi->path) }}" alt="{{ $koleksi }}" loading="lazy">
                                    @else
                                        {{ $koleksi }}
                                    @endif
                                </div>
                                <div class="p-3">
                                    <strong class="d-block">{{ $koleksi }}</strong>
                                    <span class="small text-muted">Koleksi khusus hasil kerja sama Colorbox</span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="row g-3 g-md-4">
                @foreach($produkKolaborasi as $satu)
                    <div class="col-6 col-md-3"><x-kartu-produk :produk="$satu" /></div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
