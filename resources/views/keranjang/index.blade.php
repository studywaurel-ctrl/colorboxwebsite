@extends('layouts.app')

@section('judul', 'Keranjang Belanja')

@section('isi')
    @php
        // Progres gratis ongkir dihitung dari subtotal barang yang dicentang.
        $kurang = max(0, $batasGratisOngkir - $subtotal);
        $persen = $batasGratisOngkir > 0 ? min(100, (int) round($subtotal / $batasGratisOngkir * 100)) : 100;
    @endphp

    <div class="container-xl section-sm">
        <x-langkah-belanja aktif="keranjang" />

        <nav aria-label="Jejak halaman">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('beranda') }}">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Keranjang Belanja</li>
            </ol>
        </nav>
        <h1 class="page-title">Keranjang Belanja</h1>
        <p class="text-muted">Centang barang yang ingin dibayar sekarang. Barang yang tidak dicentang tetap tersimpan.</p>

        @if($daftar->isEmpty())
            <x-kosong ikon="bag" judul="Keranjang Anda masih kosong"
                      teks="Barang yang Anda masukkan dari halaman produk akan muncul di sini.">
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="{{ route('produk.baru') }}" class="btn btn-dark">Lihat Koleksi Terbaru</a>
                    <a href="{{ route('wishlist.index') }}" class="btn btn-light">Buka Wishlist</a>
                </div>
            </x-kosong>
        @else
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="cb-card">
                        {{-- Pencentangan memakai form biasa, tanpa AJAX --}}
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pb-2 border-bottom">
                            <div class="d-flex gap-2">
                                <form action="{{ route('keranjang.pilih-semua') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="pilih" value="1">
                                    <button type="submit" class="btn btn-light btn-sm">
                                        <i class="bi bi-check2-square me-1" aria-hidden="true"></i>Centang semua
                                    </button>
                                </form>
                                <form action="{{ route('keranjang.pilih-semua') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="pilih" value="0">
                                    <button type="submit" class="btn btn-light btn-sm">
                                        <i class="bi bi-square me-1" aria-hidden="true"></i>Lepas semua
                                    </button>
                                </form>
                            </div>
                            <span class="small text-muted">{{ $daftar->count() }} produk di keranjang</span>
                        </div>

                        @foreach($daftar as $baris)
                            @php
                                $varian = $baris->varian;
                                $produk = $varian ? $varian->produk : null;
                                $harga = $produk ? $produk->hargaJual() : 0;
                                $gambar = $produk ? ($produk->gambar->firstWhere('color', $varian->color) ?: $produk->gambar->first()) : null;
                                $stok = $varian ? (int) $varian->stock : 0;
                                $maksimal = max(1, min(20, $stok));
                                $masalah = null;
                                if (! $produk) {
                                    $masalah = 'Produk ini sudah tidak dijual.';
                                } elseif ($stok < 1) {
                                    $masalah = 'Stok warna dan ukuran ini sedang habis.';
                                } elseif ($stok < $baris->quantity) {
                                    $masalah = 'Stok tinggal '.$stok.' potong.';
                                }
                            @endphp

                            <div class="cart-line">
                                {{-- Centang satu baris: tombol kirim, bukan kotak centang JavaScript --}}
                                <form action="{{ route('keranjang.pilih', $baris) }}" method="POST" class="pt-1">
                                    @csrf
                                    <button type="submit" class="btn btn-icon btn-sm"
                                            aria-label="{{ $baris->selected ? 'Lepas centang' : 'Centang' }} {{ $produk ? $produk->name : 'barang ini' }}"
                                            title="{{ $baris->selected ? 'Lepas centang barang ini' : 'Centang barang ini' }}">
                                        <i class="bi bi-{{ $baris->selected ? 'check-square-fill text-accent' : 'square' }}" aria-hidden="true"></i>
                                    </button>
                                </form>

                                @if($produk)
                                    <a href="{{ route('produk.show', $produk) }}">
                                        <img src="{{ gambar_produk($gambar ? $gambar->path : null) }}" alt="{{ $produk->name }}" loading="lazy">
                                    </a>
                                @else
                                    <img src="{{ gambar_produk(null) }}" alt="" loading="lazy">
                                @endif

                                <div>
                                    @if($produk)
                                        <a href="{{ route('produk.show', $produk) }}" class="fw-600 d-block">{{ $produk->name }}</a>
                                    @else
                                        <span class="fw-600 d-block">Produk tidak tersedia</span>
                                    @endif
                                    <div class="small text-muted">
                                        {{ $varian ? $varian->label() : 'Varian dihapus' }}
                                    </div>
                                    <div class="small mt-1">
                                        {{ rupiah($harga) }}
                                        @if($produk && $produk->discount_price && $produk->discount_price < $produk->price)
                                            <span class="was ms-1">{{ rupiah($produk->price) }}</span>
                                        @endif
                                    </div>
                                    @if($masalah)
                                        <div class="small text-danger mt-1">
                                            <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ $masalah }}
                                        </div>
                                    @endif

                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                        {{-- Jumlah barang: form PUT biasa, dikirim saat tombol ditekan --}}
                                        <form action="{{ route('keranjang.update', $baris) }}" method="POST"
                                              class="d-flex align-items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <div class="qty-stepper sm" data-qty>
                                                <button type="button" data-step="-1" aria-label="Kurangi jumlah">
                                                    <i class="bi bi-dash" aria-hidden="true"></i>
                                                </button>
                                                <label class="visually-hidden" for="jumlah-{{ $baris->id }}">Jumlah</label>
                                                <input type="number" id="jumlah-{{ $baris->id }}" name="quantity"
                                                       value="{{ min($baris->quantity, $maksimal) }}" min="1" max="{{ $maksimal }}"
                                                       inputmode="numeric">
                                                <button type="button" data-step="1" aria-label="Tambah jumlah">
                                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <button type="submit" class="btn btn-light btn-sm">Perbarui</button>
                                        </form>

                                        <form action="{{ route('keranjang.destroy', $baris) }}" method="POST"
                                              data-confirm="Keluarkan barang ini dari keranjang?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link btn-sm text-danger">
                                                <i class="bi bi-trash me-1" aria-hidden="true"></i>Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="fw-600 text-end line-total">{{ rupiah($harga * $baris->quantity) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <p class="small text-muted mt-2">
                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                        Stok baru dikunci setelah pesanan dibuat, jadi selesaikan pembayaran tepat waktu.
                    </p>
                </div>

                <div class="col-lg-4">
                    <div class="sticky-summary">
                        <div class="cb-card">
                            <h2 class="h5 mb-3">Ringkasan Belanja</h2>

                            <div class="small mb-3">
                                @if($kurang > 0)
                                    <div>Belanja {{ rupiah($kurang) }} lagi untuk mendapat gratis ongkir.</div>
                                @else
                                    <div>Belanja Anda sudah mencapai batas gratis ongkir.</div>
                                @endif
                                <div class="progress mt-2" role="progressbar" aria-label="Progres gratis ongkir"
                                     aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: {{ $persen }}%"></div>
                                </div>
                                <div class="text-soft mt-1">Batas gratis ongkir {{ rupiah($batasGratisOngkir) }}</div>
                            </div>

                            <div class="summary-row">
                                <span>Subtotal ({{ $jumlahDipilih }} barang)</span>
                                <span>{{ rupiah($subtotal) }}</span>
                            </div>
                            <div class="summary-row">
                                <span>Estimasi ongkir</span>
                                <span>{{ $jumlahDipilih === 0 ? '-' : ($kurang > 0 ? 'Dihitung di checkout' : 'Gratis') }}</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total sementara</span>
                                <span>{{ rupiah($subtotal) }}</span>
                            </div>

                            <p class="small text-muted mt-2 mb-3">
                                Voucher, poin, dan ongkos kirim akhir dipilih pada halaman checkout.
                            </p>

                            @if($jumlahDipilih > 0)
                                <a href="{{ route('checkout.index') }}" class="btn btn-primary w-100">Lanjut ke Checkout</a>
                            @else
                                <button type="button" class="btn btn-primary w-100" disabled>Lanjut ke Checkout</button>
                                <p class="small text-muted text-center mt-2 mb-0">Centang dulu barang yang ingin dibayar.</p>
                            @endif

                            @guest
                                <p class="small text-muted text-center mt-2 mb-0">
                                    Anda akan diminta masuk terlebih dahulu sebelum checkout.
                                </p>
                            @endguest

                            <a href="{{ route('produk.index') }}" class="btn btn-link btn-sm w-100 mt-1">Lanjut belanja</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
