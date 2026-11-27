@extends('layouts.app')

@php
    // Warna yang sedang dicoba, dipakai untuk menyiapkan gambar dan daftar ukuran
    $warnaAktif = $sesi->color ?: ($warna->count() > 0 ? $warna->first()->color : null);

    $gambarAktif = $produk->gambar->firstWhere('color', $warnaAktif) ?: $produk->gambarUtama();

    $varianAktif = $produk->varian->first(function ($v) use ($warnaAktif, $sesi) {
        return $v->color === $warnaAktif && (! $sesi->size || $v->size === $sesi->size);
    });
    if (! $varianAktif) {
        $varianAktif = $produk->varian->firstWhere('color', $warnaAktif) ?: $produk->varian->first();
    }
@endphp

@section('judul', 'Virtual Try On '.$produk->name)

@section('isi')
<div class="container-xl section-sm">
    <x-remah :tautan="['Virtual Try On' => route('vto.index'), $produk->name => null]" />

    <h1 class="page-title">Virtual Try On</h1>

    <div class="row g-4 g-lg-5">

        {{-- Kolom kiri: produk, warna, ukuran, dan riwayat --}}
        <div class="col-lg-5">
            <div class="cb-card">
                <div class="vto-product mb-3">
                    <img class="mini-thumb" style="width: 84px" data-vto-gambar-produk
                         src="{{ gambar_produk($gambarAktif ? $gambarAktif->path : null) }}"
                         alt="{{ $produk->name }}">
                    <div>
                        <strong>{{ $produk->name }}</strong>
                        <div class="price">{{ rupiah($produk->hargaJual()) }}</div>
                        <a href="{{ route('produk.show', $produk) }}" class="btn btn-link btn-sm px-0">Buka halaman produk</a>
                    </div>
                </div>

                <form action="{{ route('keranjang.store') }}" method="POST" data-vto-pilihan>
                    @csrf
                    <input type="hidden" name="quantity" value="1">

                    @if($warna->count() > 0)
                        <fieldset class="mb-3">
                            <legend class="form-label mb-2">
                                Warna: <span class="fw-normal" data-vto-nama-warna>{{ $warnaAktif }}</span>
                            </legend>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($warna as $satuWarna)
                                    @php
                                        $gambarWarna = $produk->gambar->firstWhere('color', $satuWarna->color) ?: $produk->gambarUtama();
                                    @endphp
                                    <label class="color-option" title="{{ $satuWarna->color }}">
                                        <input type="radio" name="warna" value="{{ $satuWarna->color }}"
                                               data-gambar="{{ gambar_produk($gambarWarna ? $gambarWarna->path : null) }}"
                                               @if($satuWarna->color === $warnaAktif) checked @endif>
                                        <span style="background: {{ $satuWarna->color_hex ?: '#e8e4de' }}"></span>
                                        <span class="visually-hidden">{{ $satuWarna->color }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    <fieldset class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <legend class="form-label mb-0 w-auto">Ukuran</legend>
                            <a href="{{ route('bantuan.show', 'panduan-ukuran') }}" class="btn btn-link btn-sm">Panduan ukuran</a>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($produk->varian as $varian)
                                <label class="size-option" data-warna-ukuran="{{ $varian->color }}"
                                       @if($varian->color !== $warnaAktif) hidden @endif
                                       title="{{ $varian->stock > 0 ? $varian->stock.' tersedia' : 'Stok online habis' }}">
                                    <input type="radio" name="product_variant_id" value="{{ $varian->id }}"
                                           data-ukuran="{{ $varian->size }}"
                                           @if($varian->stock < 1) disabled @endif
                                           @if($varianAktif && $varianAktif->id === $varian->id) checked @endif>
                                    <span>{{ $varian->size }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-bag" aria-hidden="true"></i> Tambah ke Keranjang
                    </button>
                </form>

                @auth
                    <form action="{{ route('wishlist.store') }}" method="POST" class="mt-2">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $produk->id }}">
                        <button type="submit" class="btn btn-outline-dark w-100">
                            <i class="bi bi-heart" aria-hidden="true"></i> Simpan ke Wishlist
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-dark w-100 mt-2">
                        <i class="bi bi-heart" aria-hidden="true"></i> Masuk untuk menyimpan ke wishlist
                    </a>
                @endauth
            </div>

            @if($riwayat->count() > 0)
                <div class="cb-card">
                    <h2 class="h6">Hasil terakhir</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($riwayat as $satuHasil)
                            <a href="{{ route('vto.show', $satuHasil) }}" title="{{ $satuHasil->produk->name }}">
                                <img class="mini-thumb" style="width: 64px" loading="lazy"
                                     src="{{ asset($satuHasil->result_path) }}"
                                     alt="Hasil Virtual Try On {{ $satuHasil->produk->name }}">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <form action="{{ route('vto.destroy', $sesi) }}" method="POST"
                  data-confirm="Hapus sesi Virtual Try On ini beserta gambarnya?">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-link btn-sm px-0">
                    <i class="bi bi-trash" aria-hidden="true"></i> Hapus sesi ini
                </button>
            </form>
        </div>

        {{-- Kolom kanan: panggung kamera dan hasilnya --}}
        <div class="col-lg-7">
            <div class="vto-stage" data-vto-panggung
                 data-mode="{{ $mode }}"
                 data-gambar="{{ gambar_produk($gambarAktif ? $gambarAktif->path : null) }}"
                 @if($sesi->result_path) hidden @endif>
                <video data-vto-video playsinline muted autoplay></video>
                <div class="vto-frame" data-vto-bingkai aria-hidden="true"></div>
                <div class="vto-hint" data-vto-ket role="status">Posisikan tubuh di dalam bingkai</div>
            </div>

            <div class="d-grid gap-2 mt-3" data-vto-aksi @if($sesi->result_path) hidden @endif>
                <button type="button" class="btn btn-primary btn-lg" data-vto-generate>Generate</button>
            </div>

            <div class="text-center mt-3" data-vto-memuat hidden>
                <div class="spinner-border" role="status" aria-hidden="true"></div>
                <p class="small text-muted mt-2 mb-0">Sedang memproses</p>
            </div>

            @if($sesi->result_path)
                <div data-vto-hasil>
                    <div class="vto-result">
                        <img src="{{ asset($sesi->result_path) }}"
                             alt="Hasil Virtual Try On {{ $produk->name }} warna {{ $sesi->color }}">
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
                        <a href="{{ asset($sesi->result_path) }}" download="colorbox-virtual-try-on.png" class="btn btn-dark">
                            <i class="bi bi-download" aria-hidden="true"></i> Unduh Hasil
                        </a>
                        <button type="button" class="btn btn-light" data-vto-ulang>Coba Lagi</button>
                    </div>
                </div>
            @elseif($sesi->status === 'gagal')
                <p class="small text-muted text-center mt-3">Hasil belum tersedia. Silakan tekan Generate sekali lagi.</p>
            @endif

            <p class="small text-muted text-center mt-3" data-vto-pesan role="status"></p>

            {{-- Gambar hasil kamera dikirim lewat formulir ini --}}
            <form action="{{ route('vto.proses', $sesi) }}" method="POST" data-vto-borang hidden>
                @csrf
                <input type="hidden" name="gambar" data-vto-data-gambar value="">
                <input type="hidden" name="foto" data-vto-data-foto value="">
                <input type="hidden" name="warna" data-vto-data-warna value="{{ $warnaAktif }}">
                <input type="hidden" name="ukuran" data-vto-data-ukuran value="{{ $varianAktif ? $varianAktif->size : '' }}">
            </form>

            <canvas data-vto-kanvas hidden></canvas>
        </div>
    </div>
</div>
@endsection

@push('skrip')
<script src="{{ asset('js/vto.js') }}" defer></script>
@endpush
