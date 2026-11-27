@extends('layouts.app')

@php
    $judul = $hanyaBaru ? 'New Arrival' : ($kategoriTerpilih ? $kategoriTerpilih->name : (request('cari') ? 'Hasil Pencarian' : 'Semua Produk'));
    $alamatDaftar = $hanyaBaru ? route('produk.baru') : route('produk.index');

    // Saringan yang sedang dipakai, dihitung untuk penanda di tombol Filter
    $saringanAktif = collect(['cari', 'kategori', 'koleksi', 'warna', 'ukuran', 'harga_min', 'harga_max'])
        ->filter(function ($kunci) { return request($kunci) !== null && request($kunci) !== ''; });
@endphp

@section('judul', $judul)

@section('isi')
<div class="container-xl section-sm">
    <x-remah :tautan="[$judul => null]" />

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div>
            <h1 class="page-title">{{ $judul }}</h1>
            <p class="text-muted mb-0" aria-live="polite">
                {{ $produk->total() }} produk
                @if(request('cari')) untuk pencarian "{{ request('cari') }}" @endif
                @if($hanyaBaru) yang baru masuk ke katalog @endif
            </p>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-light btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#panel-saringan" aria-controls="panel-saringan">
                <i class="bi bi-sliders me-1"></i>Filter
                @if($saringanAktif->count() > 0)<span class="status status-accent ms-1">{{ $saringanAktif->count() }}</span>@endif
            </button>

            {{-- Pengurutan dikirim bersama saringan yang sedang dipakai supaya tidak hilang --}}
            <form action="{{ $alamatDaftar }}" method="GET" class="d-flex align-items-center gap-2">
                @foreach(['cari', 'kategori', 'koleksi', 'warna', 'ukuran', 'harga_min', 'harga_max'] as $kunci)
                    @if(request($kunci) !== null && request($kunci) !== '')
                        <input type="hidden" name="{{ $kunci }}" value="{{ request($kunci) }}">
                    @endif
                @endforeach
                <label for="urut" class="small text-muted text-nowrap">Urutkan</label>
                <select id="urut" name="urut" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="terbaru" @if($urut === 'terbaru') selected @endif>Terbaru</option>
                    <option value="termurah" @if($urut === 'termurah') selected @endif>Harga terendah</option>
                    <option value="termahal" @if($urut === 'termahal') selected @endif>Harga tertinggi</option>
                    <option value="nama" @if($urut === 'nama') selected @endif>Nama A sampai Z</option>
                </select>
                <noscript><button class="btn btn-sm btn-dark" type="submit">Terapkan</button></noscript>
            </form>
        </div>
    </div>

    {{-- Penanda saringan yang sedang dipakai, ditekan untuk melepasnya --}}
    @if($saringanAktif->count() > 0)
        <div class="d-flex flex-wrap gap-2 mb-3">
            @if(request('cari'))
                <a class="filter-chip active" href="{{ request()->fullUrlWithoutQuery(['cari', 'page']) }}">
                    "{{ request('cari') }}" <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Lepas filter kata kunci</span>
                </a>
            @endif
            @if($kategoriTerpilih)
                <a class="filter-chip active" href="{{ request()->fullUrlWithoutQuery(['kategori', 'page']) }}">
                    {{ $kategoriTerpilih->name }} <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Lepas filter kategori</span>
                </a>
            @endif
            @if(request('koleksi'))
                <a class="filter-chip active" href="{{ request()->fullUrlWithoutQuery(['koleksi', 'page']) }}">
                    {{ request('koleksi') }} <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Lepas filter koleksi</span>
                </a>
            @endif
            @if(request('harga_min') !== null && request('harga_min') !== '')
                <a class="filter-chip active" href="{{ request()->fullUrlWithoutQuery(['harga_min', 'page']) }}">
                    Mulai {{ rupiah(request('harga_min')) }} <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Lepas harga terendah</span>
                </a>
            @endif
            @if(request('harga_max') !== null && request('harga_max') !== '')
                <a class="filter-chip active" href="{{ request()->fullUrlWithoutQuery(['harga_max', 'page']) }}">
                    Sampai {{ rupiah(request('harga_max')) }} <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Lepas harga tertinggi</span>
                </a>
            @endif
            <a class="filter-chip" href="{{ $alamatDaftar }}">Bersihkan semua</a>
        </div>
    @endif

    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="panel-saringan" aria-labelledby="judul-saringan">
                <div class="offcanvas-header">
                    <h2 class="offcanvas-title h5" id="judul-saringan">Filter</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#panel-saringan" aria-label="Tutup"></button>
                </div>
                <div class="offcanvas-body d-block">
                    <form action="{{ $alamatDaftar }}" method="GET" class="filter-panel w-100">
                        <input type="hidden" name="urut" value="{{ $urut }}">

                        <div class="filter-group">
                            <label for="f-cari" class="form-label">Kata kunci</label>
                            <input id="f-cari" type="search" name="cari" value="{{ request('cari') }}"
                                   class="form-control form-control-sm" maxlength="80" placeholder="Nama produk atau kode">
                        </div>

                        <div class="filter-group">
                            <h6>Kategori</h6>
                            <div class="d-flex flex-column gap-1">
                                <label class="form-check">
                                    <input class="form-check-input" type="radio" name="kategori" value="" @if(! request('kategori')) checked @endif>
                                    <span class="form-check-label">Semua kategori</span>
                                </label>
                                @foreach($kategoriInduk as $induk)
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio" name="kategori" value="{{ $induk->slug }}"
                                               @if(request('kategori') === $induk->slug) checked @endif>
                                        <span class="form-check-label">{{ $induk->name }}</span>
                                    </label>
                                    @foreach($induk->anak->sortBy('sort_order') as $anak)
                                        <label class="form-check ms-3">
                                            <input class="form-check-input" type="radio" name="kategori" value="{{ $anak->slug }}"
                                                   @if(request('kategori') === $anak->slug) checked @endif>
                                            <span class="form-check-label">{{ $anak->name }}</span>
                                        </label>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>

                        @if($daftarKoleksi->count() > 0)
                            <div class="filter-group">
                                <label for="f-koleksi" class="form-label">Koleksi</label>
                                <select id="f-koleksi" name="koleksi" class="form-select form-select-sm">
                                    <option value="">Semua koleksi</option>
                                    @foreach($daftarKoleksi as $koleksi)
                                        <option value="{{ $koleksi }}" @if(request('koleksi') === $koleksi) selected @endif>{{ $koleksi }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="filter-group">
                            <h6>Harga</h6>
                            <div class="d-flex gap-2 align-items-center">
                                <label class="visually-hidden" for="f-harga-min">Harga terendah</label>
                                <input id="f-harga-min" type="number" name="harga_min" min="0" step="10000"
                                       class="form-control form-control-sm" placeholder="Min" value="{{ request('harga_min') }}">
                                <span aria-hidden="true">&ndash;</span>
                                <label class="visually-hidden" for="f-harga-max">Harga tertinggi</label>
                                <input id="f-harga-max" type="number" name="harga_max" min="0" step="10000"
                                       class="form-control form-control-sm" placeholder="Maks" value="{{ request('harga_max') }}">
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-3">
                            <button type="submit" class="btn btn-dark btn-sm flex-fill">Terapkan</button>
                            <a href="{{ $alamatDaftar }}" class="btn btn-light btn-sm">Bersihkan</a>
                        </div>
                    </form>
                </div>
            </div>
        </aside>

        <div class="col-lg-9">
            @if($produk->count() > 0)
                <div class="row g-3 g-md-4">
                    @foreach($produk as $satu)
                        <div class="col-6 col-md-4"><x-kartu-produk :produk="$satu" /></div>
                    @endforeach
                </div>
                <div class="mt-4 d-flex justify-content-center">{{ $produk->links() }}</div>
            @else
                <x-kosong ikon="search" judul="Produk tidak ditemukan"
                          teks="Coba ubah kata kunci atau lepaskan saringan yang sedang dipakai.">
                    <a href="{{ route('produk.index') }}" class="btn btn-dark">Lihat semua produk</a>
                </x-kosong>
            @endif
        </div>
    </div>
</div>
@endsection
