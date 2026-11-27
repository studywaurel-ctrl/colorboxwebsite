@extends('layouts.app')

@php
    $disukai = auth()->check()
        ? \App\Models\Wishlist::where('user_id', auth()->id())->where('product_id', $produk->id)->exists()
        : false;

    // Gambar yang ditampilkan lebih dulu adalah gambar warna awal.
    // Kalau warna itu tidak punya gambar sendiri, semua gambar dipakai.
    $gambarWarna = $gambar->where('color', $warnaAwal);
    if ($gambarWarna->isEmpty()) {
        $gambarWarna = $gambar;
    }
    $gambarAwal = $gambarWarna->first();

    // Varian pertama yang stoknya masih ada pada warna awal dipilih lebih dulu
    $varianAwal = $produk->varian->first(function ($v) use ($warnaAwal) {
        return $v->color === $warnaAwal && $v->stock > 0;
    });
    if (! $varianAwal) {
        $varianAwal = $produk->varian->first(function ($v) use ($warnaAwal) {
            return $v->color === $warnaAwal;
        });
    }

    $stokAwal = $varianAwal ? (int) $varianAwal->stock : 0;
    $adaDiskon = $produk->discount_price && $produk->discount_price < $produk->price;
    $hariRetur = (int) setting('return_window_days', 7);
@endphp

@section('judul', $produk->name)
@section('deskripsi', \Illuminate\Support\Str::limit(strip_tags((string) $produk->description), 150))
@section('kelas_body', 'has-bottom-bar')

@section('isi')
<div class="container-xl section-sm">
    <x-remah :tautan="array_filter([
        'Produk' => route('produk.index'),
        ($produk->kategori ? $produk->kategori->name : '') => $produk->kategori ? route('produk.index', ['kategori' => $produk->kategori->slug]) : null,
        $produk->name => null,
    ], function ($nilai, $label) { return $label !== ''; }, ARRAY_FILTER_USE_BOTH)" />

    <div class="row g-4 g-lg-5" id="rincian-produk">
        {{-- Galeri gambar. Foto disaring mengikuti warna yang dipilih. --}}
        <div class="col-lg-6">
            <div class="gallery-main">
                <img id="gambar-utama" src="{{ gambar_produk($gambarAwal ? $gambarAwal->path : null) }}"
                     alt="{{ $produk->name }}" width="600" height="750">
            </div>
            @if($gambar->count() > 0)
                <div class="gallery-thumbs" id="daftar-gambar" aria-label="Foto produk">
                    @foreach($gambar as $satuGambar)
                        <button type="button" class="{{ $gambarAwal && $satuGambar->id === $gambarAwal->id ? 'active' : '' }}"
                                data-warna-gambar="{{ $satuGambar->color }}"
                                data-sumber="{{ gambar_produk($satuGambar->path) }}"
                                @if(! $gambarWarna->contains('id', $satuGambar->id)) hidden @endif
                                aria-label="Lihat foto {{ $loop->iteration }}">
                            <img src="{{ gambar_produk($satuGambar->path) }}" alt="" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="col-lg-6">
            <div class="d-flex flex-wrap gap-2 mb-2">
                @if($produk->collection)
                    <a href="{{ route('produk.index', ['koleksi' => $produk->collection]) }}" class="chip">
                        <i class="bi bi-people" aria-hidden="true"></i>{{ $produk->collection }}
                    </a>
                @endif
                @if($produk->is_new)<span class="tag">Baru</span>@endif
            </div>

            <h1 class="h2 mb-1">{{ $produk->name }}</h1>
            <p class="small text-muted mb-2">Kode produk {{ $produk->sku }}</p>

            <p class="small text-muted mb-3">
                @if($produk->ulasan->count() > 0)
                    <i class="bi bi-star-fill text-warning" aria-hidden="true"></i>
                    {{ number_format($rataRating, 1, ',', '.') }} dari 5
                    <a href="#ulasan-produk">({{ $produk->ulasan->count() }} ulasan)</a>
                @else
                    Belum ada ulasan untuk produk ini.
                @endif
            </p>

            <div class="price fs-4 mb-3">
                {{ rupiah($produk->hargaJual()) }}
                @if($adaDiskon)
                    <span class="was">{{ rupiah($produk->price) }}</span>
                    <span class="off">&minus;{{ (int) round(($produk->price - $produk->discount_price) / $produk->price * 100) }}%</span>
                @endif
            </div>

            {{-- Pilihan warna, ukuran, dan jumlah sebelum barang masuk keranjang --}}
            <form action="{{ route('keranjang.store') }}" method="POST" id="borang-beli">
                @csrf

                @if($warna->count() > 0)
                    <fieldset class="mb-3">
                        <legend class="form-label mb-2">Warna: <span class="fw-normal" id="nama-warna">{{ $warnaAwal }}</span></legend>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($warna as $satuWarna)
                                <label class="color-option" title="{{ $satuWarna->color }}">
                                    <input type="radio" name="warna" value="{{ $satuWarna->color }}"
                                           @if($satuWarna->color === $warnaAwal) checked @endif>
                                    <span style="background: {{ $satuWarna->color_hex ?: '#e8e4de' }}"></span>
                                    <span class="visually-hidden">{{ $satuWarna->color }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <fieldset class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <legend class="form-label mb-0 w-auto">Ukuran</legend>
                        <button type="button" class="btn btn-link btn-sm" data-bs-toggle="modal" data-bs-target="#panduan-ukuran">
                            Panduan ukuran
                        </button>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($produk->varian as $varian)
                            <label class="size-option" data-warna-ukuran="{{ $varian->color }}"
                                   @if($varian->color !== $warnaAwal) hidden @endif
                                   title="{{ $varian->stock > 0 ? $varian->stock.' tersedia' : 'Stok online habis' }}">
                                <input type="radio" name="product_variant_id" value="{{ $varian->id }}"
                                       data-stok="{{ $varian->stock }}"
                                       @if($varian->stock < 1) disabled @endif
                                       @if($varianAwal && $varianAwal->id === $varian->id) checked @endif>
                                <span>{{ $varian->size }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <p class="stock-note mb-3 {{ ! $varianAwal ? 'text-muted' : ($stokAwal < 1 ? 'out' : ($stokAwal <= 3 ? 'low' : 'text-success')) }}"
                   id="catatan-stok" aria-live="polite">
                    @if(! $varianAwal)
                        Pilih ukuran untuk melihat stoknya.
                    @elseif($stokAwal < 1)
                        Stok online habis untuk ukuran ini. Cek stoknya di toko.
                    @elseif($stokAwal <= 3)
                        Sisa {{ $stokAwal }} potong, segera selesaikan pembelian.
                    @else
                        Stok online tersedia.
                    @endif
                </p>

                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <div class="qty-stepper" data-qty>
                        <button type="button" data-step="-1" aria-label="Kurangi jumlah"><i class="bi bi-dash"></i></button>
                        <label class="visually-hidden" for="jumlah-produk">Jumlah pembelian</label>
                        <input type="number" id="jumlah-produk" name="quantity" value="1" min="1"
                               max="{{ $stokAwal > 0 ? min(10, $stokAwal) : 10 }}" inputmode="numeric">
                        <button type="button" data-step="1" aria-label="Tambah jumlah"><i class="bi bi-plus"></i></button>
                    </div>
                    <button type="submit" class="btn btn-primary flex-fill" data-tombol-beli
                            @if($stokAwal < 1) disabled @endif>Tambah ke Keranjang</button>
                </div>
            </form>

            <div class="d-flex gap-2 mb-3">
                @auth
                    <form action="{{ route('wishlist.store') }}" method="POST" class="flex-fill">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $produk->id }}">
                        <button type="submit" class="btn btn-light w-100 wish-btn {{ $disukai ? 'is-active' : '' }}"
                                aria-pressed="{{ $disukai ? 'true' : 'false' }}">
                            <i class="bi bi-heart"></i><i class="bi bi-heart-fill"></i>
                            {{ $disukai ? 'Tersimpan di Wishlist' : 'Simpan ke Wishlist' }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-light w-100 wish-btn">
                        <i class="bi bi-heart"></i><i class="bi bi-heart-fill"></i> Simpan ke Wishlist
                    </a>
                @endauth
            </div>

            {{-- Layanan toko untuk produk ini --}}
            <div class="feature-links mb-2">
                <button type="button" class="btn btn-light" id="cek-stok" data-alamat="{{ route('produk.stok', $produk) }}">
                    <i class="bi bi-shop" aria-hidden="true"></i>Cek Stok Toko
                </button>
                <a href="{{ $varianAwal ? route('ttc.index', ['varian' => $varianAwal->id]) : route('ttc.index') }}"
                   class="btn btn-light" id="tautan-ttc" data-dasar="{{ route('ttc.index') }}">
                    <i class="bi bi-hand-index" aria-hidden="true"></i>Tap Try and Collect
                </a>
                <a href="{{ route('vto.index', ['produk' => $produk->slug]) }}" class="btn btn-light">
                    <i class="bi bi-person-bounding-box" aria-hidden="true"></i>Coba Virtual Try On
                </a>
            </div>
            <p class="small text-muted">
                Pilih warna dan ukuran terlebih dahulu supaya stok toko dan jadwal Tap Try and Collect
                mengikuti varian yang Anda inginkan.
            </p>
            <div id="hasil-stok" class="mb-4" aria-live="polite"></div>

            {{-- Keterangan produk --}}
            <div class="accordion" id="keterangan-produk">
                @php
                    $keterangan = [
                        'deskripsi' => ['Deskripsi', $produk->description ?: 'Keterangan produk belum tersedia.'],
                        'bahan' => ['Bahan dan Perawatan', 'Bahan: '.($produk->material ?: '-').'. Perawatan: '.($produk->care ?: '-').'.'],
                        'pengiriman' => ['Pengiriman', 'Dikirim dari gudang Colorbox memakai jasa kirim yang dipilih pada halaman checkout. Gratis ongkos kirim untuk belanja mulai '.rupiah(setting('free_shipping_threshold', 350000)).'.'],
                        'retur' => ['Retur dan Penukaran', 'Pengajuan retur atau penukaran dibuka paling lama '.$hariRetur.' hari setelah pesanan diterima, selama label masih terpasang dan barang belum dipakai.'],
                    ];
                @endphp
                @foreach($keterangan as $kunci => $bagian)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#bagian-{{ $kunci }}"
                                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="bagian-{{ $kunci }}">
                                {{ $bagian[0] }}
                            </button>
                        </h2>
                        <div id="bagian-{{ $kunci }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}">
                            <div class="accordion-body">{{ $bagian[1] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Ulasan pelanggan --}}
    <section class="section" aria-labelledby="judul-ulasan" id="ulasan-produk">
        <div class="section-head">
            <div>
                <p class="eyebrow mb-1">Kata pelanggan</p>
                <h2 id="judul-ulasan">Ulasan Pelanggan</h2>
            </div>
            @if($produk->ulasan->count() > 0)
                <span class="status status-accent">{{ number_format($rataRating, 1, ',', '.') }} dari 5</span>
            @endif
        </div>

        @if($produk->ulasan->count() > 0)
            <ul class="event-log">
                @foreach($produk->ulasan->sortByDesc('id') as $ulasan)
                    <li>
                        <div class="fw-600">
                            {{ $ulasan->pengguna ? $ulasan->pengguna->name : 'Pelanggan Colorbox' }}
                            <span class="ms-1 text-warning" aria-label="Nilai {{ $ulasan->rating }} dari 5">
                                @for($bintang = 1; $bintang <= 5; $bintang++)
                                    <i class="bi {{ $bintang <= $ulasan->rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                                @endfor
                            </span>
                        </div>
                        <div class="small text-muted">{{ $ulasan->comment ?: 'Pelanggan tidak menuliskan komentar.' }}</div>
                        <div class="small text-soft">{{ tanggal_id($ulasan->created_at, true) }}</div>
                    </li>
                @endforeach
            </ul>
        @else
            <x-kosong ikon="chat-quote" judul="Belum ada ulasan"
                      teks="Ulasan ditulis pelanggan setelah pesanannya selesai." />
        @endif
    </section>

    {{-- Produk lain dari kategori yang sama --}}
    @if($terkait->count() > 0)
        <section class="section pt-0" aria-labelledby="judul-terkait">
            <div class="section-head">
                <div>
                    <p class="eyebrow mb-1">Pilihan lain</p>
                    <h2 id="judul-terkait">Produk Terkait</h2>
                </div>
                @if($produk->kategori)
                    <a href="{{ route('produk.index', ['kategori' => $produk->kategori->slug]) }}" class="btn btn-link">
                        Lihat kategori {{ $produk->kategori->name }}
                    </a>
                @endif
            </div>
            <div class="row g-3 g-md-4">
                @foreach($terkait as $lain)
                    <div class="col-6 col-md-3"><x-kartu-produk :produk="$lain" /></div>
                @endforeach
            </div>
        </section>
    @endif
</div>

{{-- Baris tetap di bawah layar ponsel --}}
<div class="bottom-bar d-lg-none">
    <div class="flex-fill">
        <div class="small text-muted">{{ $produk->name }}</div>
        <strong>{{ rupiah($produk->hargaJual()) }}</strong>
    </div>
    <button type="submit" form="borang-beli" class="btn btn-primary" data-tombol-beli
            @if($stokAwal < 1) disabled @endif>Tambah ke Keranjang</button>
</div>

{{-- Panduan ukuran --}}
<div class="modal fade" id="panduan-ukuran" tabindex="-1" aria-labelledby="judul-panduan" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="judul-panduan">Panduan Ukuran</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    Angka di bawah adalah ukuran badan dalam sentimeter, bukan ukuran pakaiannya.
                    Bila hasil pengukuran berada di antara dua ukuran, pilih ukuran yang lebih besar.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Ukuran</th>
                                <th scope="col">Lingkar dada (cm)</th>
                                <th scope="col">Lingkar pinggang (cm)</th>
                                <th scope="col">Lingkar pinggul (cm)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><th scope="row">XS</th><td>78 &ndash; 82</td><td>60 &ndash; 64</td><td>84 &ndash; 88</td></tr>
                            <tr><th scope="row">S</th><td>83 &ndash; 87</td><td>65 &ndash; 69</td><td>89 &ndash; 93</td></tr>
                            <tr><th scope="row">M</th><td>88 &ndash; 92</td><td>70 &ndash; 74</td><td>94 &ndash; 98</td></tr>
                            <tr><th scope="row">L</th><td>93 &ndash; 98</td><td>75 &ndash; 80</td><td>99 &ndash; 104</td></tr>
                            <tr><th scope="row">XL</th><td>99 &ndash; 104</td><td>81 &ndash; 86</td><td>105 &ndash; 110</td></tr>
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('bantuan.show', 'panduan-ukuran') }}" class="btn btn-outline-dark btn-sm">
                    Cara mengukur badan sendiri
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('skrip')
<script>
// Halaman produk: mengganti foto dan daftar ukuran mengikuti warna yang dipilih,
// memperbarui catatan stok, alamat Tap Try and Collect, lalu mengambil stok toko.
document.addEventListener('DOMContentLoaded', function () {
    var borang = document.getElementById('borang-beli');
    if (!borang) { return; }

    var gambarUtama = document.getElementById('gambar-utama');
    var daftarGambar = document.getElementById('daftar-gambar');
    var namaWarna = document.getElementById('nama-warna');
    var catatan = document.getElementById('catatan-stok');
    var tautanTtc = document.getElementById('tautan-ttc');
    var isianJumlah = document.getElementById('jumlah-produk');
    var tombolBeli = document.querySelectorAll('[data-tombol-beli]');

    function warnaTerpilih() {
        var pilih = borang.querySelector('input[name="warna"]:checked');
        return pilih ? pilih.value : '';
    }

    function varianTerpilih() {
        return borang.querySelector('input[name="product_variant_id"]:checked');
    }

    function tandaiThumb(tombol) {
        Array.prototype.forEach.call(daftarGambar.querySelectorAll('button'), function (lain) {
            lain.classList.toggle('active', lain === tombol);
        });
    }

    function gantiGambar(warna) {
        if (!daftarGambar) { return; }
        var semua = Array.prototype.slice.call(daftarGambar.querySelectorAll('button'));
        var cocok = semua.filter(function (t) { return t.dataset.warnaGambar === warna; });
        var tampil = cocok.length > 0 ? cocok : semua;

        semua.forEach(function (t) { t.hidden = tampil.indexOf(t) === -1; });
        if (tampil.length > 0) {
            gambarUtama.src = tampil[0].dataset.sumber;
            tandaiThumb(tampil[0]);
        }
    }

    if (daftarGambar) {
        daftarGambar.addEventListener('click', function (e) {
            var tombol = e.target.closest('button');
            if (!tombol) { return; }
            gambarUtama.src = tombol.dataset.sumber;
            tandaiThumb(tombol);
        });
    }

    function gantiWarna(warna) {
        var pertama = null;
        Array.prototype.forEach.call(borang.querySelectorAll('[data-warna-ukuran]'), function (label) {
            var sama = label.dataset.warnaUkuran === warna;
            var isian = label.querySelector('input');
            label.hidden = !sama;
            if (!sama && isian.checked) { isian.checked = false; }
            if (sama && !isian.disabled && !pertama) { pertama = isian; }
        });

        if (!varianTerpilih() && pertama) { pertama.checked = true; }
        if (namaWarna) { namaWarna.textContent = warna; }
        gantiGambar(warna);
    }

    function perbarui() {
        var pilih = varianTerpilih();
        var stok = pilih ? parseInt(pilih.dataset.stok, 10) : 0;

        if (!pilih) {
            catatan.className = 'stock-note mb-3 text-muted';
            catatan.textContent = 'Pilih ukuran untuk melihat stoknya.';
        } else if (stok < 1) {
            catatan.className = 'stock-note mb-3 out';
            catatan.textContent = 'Stok online habis untuk ukuran ini. Cek stoknya di toko.';
        } else if (stok <= 3) {
            catatan.className = 'stock-note mb-3 low';
            catatan.textContent = 'Sisa ' + stok + ' potong, segera selesaikan pembelian.';
        } else {
            catatan.className = 'stock-note mb-3 text-success';
            catatan.textContent = 'Stok online tersedia.';
        }

        Array.prototype.forEach.call(tombolBeli, function (t) { t.disabled = stok < 1; });

        if (isianJumlah) {
            isianJumlah.max = stok > 0 ? Math.min(10, stok) : 10;
            if (parseInt(isianJumlah.value, 10) > parseInt(isianJumlah.max, 10)) {
                isianJumlah.value = isianJumlah.max;
            }
        }

        if (tautanTtc) {
            tautanTtc.href = pilih
                ? tautanTtc.dataset.dasar + '?varian=' + encodeURIComponent(pilih.value)
                : tautanTtc.dataset.dasar;
        }
    }

    borang.addEventListener('change', function (e) {
        if (e.target.name === 'warna') { gantiWarna(e.target.value); }
        perbarui();
    });

    perbarui();

    /* ------------------------------------------------- stok tiap toko ---- */
    var tombolStok = document.getElementById('cek-stok');
    var hasil = document.getElementById('hasil-stok');
    if (!tombolStok || !hasil) { return; }

    tombolStok.addEventListener('click', function () {
        var pilih = varianTerpilih();
        if (!pilih) {
            hasil.innerHTML = '<p class="small text-muted mb-0">Pilih warna dan ukuran terlebih dahulu.</p>';
            return;
        }

        hasil.innerHTML = '<div class="skeleton" style="height:72px"></div>';

        fetch(tombolStok.dataset.alamat + '?product_variant_id=' + encodeURIComponent(pilih.value), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (jawab) { return jawab.json(); })
            .then(function (data) {
                if (data.pesan) {
                    hasil.innerHTML = '<p class="small text-muted mb-0">' + data.pesan + '</p>';
                    return;
                }

                hasil.innerHTML = '';

                var judul = document.createElement('p');
                judul.className = 'small text-muted';
                judul.textContent = 'Stok untuk varian ' + data.varian + '.';
                hasil.appendChild(judul);

                data.toko.forEach(function (toko) {
                    var baris = document.createElement('div');
                    baris.className = 'd-flex flex-wrap justify-content-between gap-2 py-3 border-bottom';

                    var kiri = document.createElement('div');

                    var nama = document.createElement('strong');
                    nama.textContent = toko.nama;
                    kiri.appendChild(nama);

                    var alamat = document.createElement('div');
                    alamat.className = 'small text-muted';
                    alamat.textContent = toko.alamat + ', ' + toko.kota;
                    kiri.appendChild(alamat);

                    var jam = document.createElement('div');
                    jam.className = 'info-line';
                    jam.textContent = 'Buka ' + toko.jam + (toko.lantai ? ' · ' + toko.lantai : '');
                    kiri.appendChild(jam);

                    var kanan = document.createElement('div');
                    kanan.className = 'text-end';

                    var tanda = document.createElement('span');
                    tanda.className = toko.stok > 0 ? 'status status-success' : 'status status-muted';
                    tanda.textContent = toko.keterangan;
                    kanan.appendChild(tanda);

                    var tautan = document.createElement('a');
                    tautan.className = 'btn btn-light btn-sm d-block mt-2';
                    tautan.href = toko.tautan;
                    tautan.textContent = 'Detail toko';
                    kanan.appendChild(tautan);

                    baris.appendChild(kiri);
                    baris.appendChild(kanan);
                    hasil.appendChild(baris);
                });
            })
            .catch(function () {
                hasil.innerHTML = '<div class="alert alert-danger small mb-0">Stok toko gagal diambil. Silakan coba lagi.</div>';
            });
    });
});
</script>
@endpush
