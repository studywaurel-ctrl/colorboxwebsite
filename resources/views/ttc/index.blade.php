@extends('layouts.app')

@section('judul', 'Tap Try and Collect')

@section('isi')
<div class="container-xl section-sm">
    <x-remah :tautan="['Tap Try and Collect' => null]" />

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h1 class="page-title">Tap Try and Collect</h1>
            <p class="text-muted mb-0" style="max-width: 62ch">
                Pilih produk dari katalog, kami siapkan di ruang ganti toko pada jam pilihan Anda.
                Coba dulu, bayar di kasir hanya produk yang cocok.
            </p>
        </div>
        @auth
            <a href="{{ route('ttc.riwayat') }}" class="btn btn-light btn-sm">
                <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Riwayat jadwal
            </a>
        @endauth
    </div>

    @if(! $varian)
        {{-- Tanpa produk: halaman ringkas yang menjelaskan layanan dan mengarah ke katalog --}}
        <ol class="steps">
            <li class="current">Pilih produk di katalog</li>
            <li>Tentukan toko</li>
            <li>Pilih tanggal</li>
            <li>Pilih jam</li>
            <li>Coba di toko</li>
        </ol>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="cb-card h-100">
                    <h2 class="h6">Dipilih dari katalog</h2>
                    <p class="small text-muted mb-0">
                        Buka halaman produk, pilih warna dan ukurannya, lalu tekan tombol
                        Tap Try and Collect pada produk tersebut.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cb-card h-100">
                    <h2 class="h6">Disiapkan staf toko</h2>
                    <p class="small text-muted mb-0">
                        Produk disiapkan {{ $menitSiap }} menit sebelum jadwal, jadi Anda tidak
                        perlu menunggu saat tiba di toko.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cb-card h-100">
                    <h2 class="h6">Bayar yang cocok saja</h2>
                    <p class="small text-muted mb-0">
                        Coba produk di ruang ganti. Pembayaran dilakukan di kasir hanya untuk
                        produk yang Anda ambil.
                    </p>
                </div>
            </div>
        </div>

        <x-kosong ikon="hand-index" judul="Belum ada produk yang dipilih"
                  teks="Produk untuk Tap Try and Collect dipilih dari katalog, bukan dari halaman ini.">
            <a href="{{ route('produk.index') }}" class="btn btn-primary">Lihat Katalog</a>
        </x-kosong>
    @else
        @php
            // Gambar mengikuti warna varian bila ada, kalau tidak dipakai gambar utama
            $gambarWarna = $varian->produk->gambar->firstWhere('color', $varian->color)
                ?: $varian->produk->gambarUtama();
        @endphp

        <ol class="steps">
            <li class="done">Pilih produk di katalog</li>
            <li class="current">Tentukan toko</li>
            <li>Pilih tanggal</li>
            <li>Pilih jam</li>
            <li>Coba di toko</li>
        </ol>

        @if($toko->count() < 1)
            <x-kosong ikon="shop" judul="Belum ada toko yang melayani"
                      teks="Layanan Tap Try and Collect belum tersedia di toko mana pun. Silakan periksa kembali nanti.">
                <a href="{{ route('toko.index') }}" class="btn btn-primary">Lihat daftar toko</a>
            </x-kosong>
        @else
            <form action="{{ route('ttc.store') }}" method="POST">
                @csrf
                {{-- Produk, toko, dan tanggal sudah ditentukan lewat tautan di atas,
                     jadi cukup dibawa formulir ini sebagai isian tersembunyi --}}
                <input type="hidden" name="product_variant_id" value="{{ $varian->id }}">
                <input type="hidden" name="store_id" value="{{ $tokoTerpilih }}">
                <input type="hidden" name="tanggal" value="{{ $tanggalTerpilih }}">

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="cb-card">
                            <h2 class="h6">1. Produk yang disiapkan</h2>
                            <div class="d-flex gap-3 align-items-center">
                                <img src="{{ gambar_produk($gambarWarna ? $gambarWarna->path : null) }}"
                                     alt="{{ $varian->produk->name }}" class="mini-thumb" style="width: 72px">
                                <div class="flex-fill">
                                    <div class="fw-bold">
                                        <a href="{{ route('produk.show', $varian->produk) }}">{{ $varian->produk->name }}</a>
                                    </div>
                                    <div class="small text-muted">
                                        Warna {{ $varian->color }} &middot; Ukuran {{ $varian->size }}
                                    </div>
                                    <div class="price mt-1">{{ rupiah($varian->produk->hargaJual()) }}</div>
                                </div>
                                <a href="{{ route('produk.index') }}" class="btn btn-light btn-sm">Ganti produk</a>
                            </div>
                            <p class="form-text mb-0 mt-2">
                                Satu jadwal untuk satu produk. Buat jadwal lagi bila ingin mencoba produk lainnya.
                            </p>
                        </div>

                        <div class="cb-card">
                            <h2 class="h6">2. Pilih toko</h2>
                            <p class="small text-muted">
                                Hanya toko yang stoknya masih ada yang ditampilkan di sini. Angka
                                stoknya adalah stok nyata di toko tersebut, sudah dikurangi barang
                                yang sedang ditahan untuk pelanggan lain.
                            </p>

                            @include('partials.urut-lokasi', [
                                'catatan' => 'Toko terdekat bisa ditampilkan paling atas.',
                            ])

                            @if($toko->count() < 1)
                                <div class="alert alert-warning small py-2 mb-0">
                                    Varian ini sedang habis di seluruh toko, jadi belum bisa dipesan
                                    lewat Tap Try and Collect. Silakan pilih warna atau ukuran lain,
                                    atau pesan dengan pengiriman kurir.
                                </div>
                            @else
                            <div class="row g-2">
                                @foreach($toko as $nomor => $satu)
                                    <div class="col-md-6">
                                        <a class="option-card {{ (int) $tokoTerpilih === (int) $satu->id ? 'terpilih' : '' }}"
                                           href="{{ route('ttc.index', array_filter([
                                                    'varian' => $varian->id,
                                                    'toko' => $satu->id,
                                                    'tanggal' => $tanggalTerpilih,
                                                ])) }}">
                                            <span class="d-flex gap-2 align-items-start">
                                                <span class="check mt-1" aria-hidden="true"></span>
                                                <span class="flex-fill">
                                                    <strong class="d-block">{{ $satu->name }}</strong>
                                                    <span class="small text-muted d-block">{{ $satu->address }}, {{ $satu->city }}</span>
                                                    @if($satu->floor)
                                                        <span class="info-line"><i class="bi bi-building" aria-hidden="true"></i><span>{{ $satu->floor }}</span></span>
                                                    @endif
                                                    <span class="info-line">
                                                        <i class="bi bi-clock" aria-hidden="true"></i>
                                                        <span>Buka {{ jam_singkat($satu->open_time) }} &ndash; {{ jam_singkat($satu->close_time) }}</span>
                                                    </span>
                                                    @if(isset($satu->jarak))
                                                        <span class="info-line">
                                                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                                            <span>Sekitar {{ jarak_km($satu->jarak) }} dari lokasi Anda</span>
                                                        </span>
                                                    @endif
                                                    <span class="small d-block mt-1">
                                                        <span class="status status-success">Tersedia {{ $stokToko[$satu->id] ?? 0 }} potong</span>
                                                    </span>
                                                </span>
                                            </span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <div class="cb-card">
                            <h2 class="h6">3. Tanggal dan jam kunjungan</h2>
                            @if($lewatBatasHariIni)
                                <div class="alert alert-warning small py-2">
                                    Pemesanan untuk hari ini sudah ditutup pukul {{ $batasHariIni }} WIB,
                                    jadi kalender dimulai dari besok.
                                </div>
                            @else
                                <p class="small text-muted mb-2">
                                    Pemesanan untuk hari ini ditutup pukul {{ $batasHariIni }} WIB, dan
                                    jadwal paling cepat {{ $menitSiap }} menit dari sekarang.
                                </p>
                            @endif
                            @if(! $tokoTerpilih)
                                <p class="text-muted small mb-0">Pilih toko terlebih dahulu.</p>
                            @else
                                <span class="form-label">Tanggal kunjungan</span>
                                @include('partials.pilih-tanggal', [
                                    'terpilih' => $tanggalTerpilih,
                                    'rute' => 'ttc.index',
                                    'dasar' => ['varian' => $varian->id, 'toko' => $tokoTerpilih],
                                ])

                                <span class="form-label mt-3">Jam kunjungan</span>
                                <p class="slot-jam-sekarang mb-2">
                                    <x-jam-wib /> &mdash; daftar jam di bawah ini mengikuti waktu ini.
                                </p>
                                @include('partials.pilih-jam', [
                                    'nama' => 'tap_try_collect_slot_id',
                                    'terpilih' => old('tap_try_collect_slot_id'),
                                ])
                                <p class="form-text mt-2 mb-0">
                                    Jam yang penuh, sudah lewat, atau kurang waktu penyiapan tidak dapat
                                    dipilih beserta alasannya.
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="cb-card sticky-summary">
                            <h2 class="h6">4. Konfirmasi</h2>
                            <div class="info-line">
                                <i class="bi bi-box-seam" aria-hidden="true"></i>
                                <span>{{ $varian->produk->name }} &middot; {{ $varian->color }} / {{ $varian->size }}</span>
                            </div>
                            <div class="info-line">
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                <span>Produk disiapkan {{ $menitSiap }} menit sebelum jadwal Anda.</span>
                            </div>
                            <div class="info-line">
                                <i class="bi bi-calendar-x" aria-hidden="true"></i>
                                <span>Pemesanan hari yang sama ditutup pukul {{ $batasHariIni }} WIB.</span>
                            </div>
                            <div class="info-line">
                                <i class="bi bi-door-closed" aria-hidden="true"></i>
                                <span>Pengambilan dilayani sampai pukul {{ jam_singkat($batasAmbil) }} WIB.</span>
                            </div>
                            <ul class="small text-muted ps-3 mt-3">
                                <li>Produk ditahan khusus untuk Anda sejak jadwal dibuat.</li>
                                <li>Pembayaran dilakukan di kasir toko untuk produk yang Anda ambil.</li>
                                <li>Jam kunjungan paling malam pukul {{ jam_singkat($jamTerakhir) }}, sedangkan
                                    pengambilannya masih dilayani sampai pukul {{ jam_singkat($batasAmbil) }}.</li>
                                <li>Lewat dari jam kunjungan, barangnya masih bisa diambil sampai pukul
                                    {{ jam_singkat($batasAmbil) }} keesokan harinya.</li>
                                <li>Lewat dari itu jadwal ditutup sendiri oleh sistem dan produk kembali
                                    dijual. Anda tidak perlu membuka situs ini untuk membatalkannya.</li>
                                <li>Jadwal juga dapat dibatalkan sendiri sebelum jam kunjungan dimulai.</li>
                            </ul>
                            @auth
                                <button type="submit" class="btn btn-primary w-100">Konfirmasi Jadwal</button>
                            @else
                                <p class="small text-muted">Anda perlu masuk ke akun untuk membuat jadwal.</p>
                                <a href="{{ route('login') }}" class="btn btn-primary w-100">Masuk untuk membuat jadwal</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </form>
        @endif
    @endif
</div>
@endsection
