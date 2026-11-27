@extends('layouts.app')

@section('judul', 'Checkout')

@section('isi')
    @php
        // Ongkir termurah dipakai sebagai perkiraan pada ringkasan biaya.
        // Angka pastinya ikut jasa kirim yang dipilih dan dihitung ulang di controller.
        $ongkirTermurah = $jasaKirim->count() > 0 ? (int) $jasaKirim->min('cost') : 0;
        $ongkirPerkiraan = $gratisOngkir ? 0 : $ongkirTermurah;
        $kurangGratis = max(0, $batasGratisOngkir - $subtotal);
        $alamatUtama = $alamat->firstWhere('is_primary', true) ?: $alamat->first();
        $totalPerkiraan = max(0, $subtotal + $ongkirPerkiraan - $diskon);
    @endphp

    <div class="container-xl section-sm">
        <x-langkah-belanja aktif="checkout" />

        <nav aria-label="Jejak halaman">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('beranda') }}">Beranda</a></li>
                <li class="breadcrumb-item"><a href="{{ route('keranjang.index') }}">Keranjang</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </nav>
        <h1 class="page-title">Checkout</h1>
        <p class="text-muted">Pilih cara menerima barang, potongan yang dipakai, lalu buat pesanan Anda.</p>

        <form action="{{ route('checkout.store') }}" method="POST">
            @csrf
            <div class="row g-4">
                <div class="col-lg-7">

                    {{-- 1. Cara menerima barang --}}
                    <div class="cb-card">
                        <h2 class="h6 mb-3">1. Cara menerima barang</h2>
                        <div class="row g-2" role="radiogroup" aria-label="Cara menerima barang">
                            <div class="col-md-6">
                                <label class="option-card position-relative">
                                    <input type="radio" name="method" value="kirim" required
                                           @checked(old('method', 'kirim') === 'kirim')>
                                    <span class="d-flex gap-2">
                                        <span class="check mt-1" aria-hidden="true"></span>
                                        <span>
                                            <strong>Dikirim kurir</strong>
                                            <span class="small text-muted d-block">Paket diantar ke alamat pengiriman Anda.</span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="option-card position-relative">
                                    <input type="radio" name="method" value="ambil_toko"
                                           @checked(old('method') === 'ambil_toko')>
                                    <span class="d-flex gap-2">
                                        <span class="check mt-1" aria-hidden="true"></span>
                                        <span>
                                            <strong>Diambil di toko</strong>
                                            <span class="small text-muted d-block">Barang disiapkan toko, tanpa ongkos kirim.</span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Alamat pengiriman --}}
                    <div class="cb-card">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h2 class="h6 mb-0">2. Alamat pengiriman</h2>
                            <a href="{{ route('alamat.create') }}" class="btn btn-light btn-sm">
                                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah alamat
                            </a>
                        </div>

                        @if($alamat->isEmpty())
                            <div class="alert alert-warning small mb-0">
                                Anda belum menyimpan alamat pengiriman. Tambahkan alamat lebih dahulu bila barang
                                ingin dikirim kurir.
                            </div>
                        @else
                            <div class="row g-2" role="radiogroup" aria-label="Pilih alamat pengiriman">
                                @foreach($alamat as $satu)
                                    <div class="col-12">
                                        <label class="option-card position-relative">
                                            <input type="radio" name="address_id" value="{{ $satu->id }}"
                                                   @checked((int) old('address_id', $alamatUtama ? $alamatUtama->id : 0) === $satu->id)>
                                            <span class="d-flex gap-3">
                                                <span class="check mt-1" aria-hidden="true"></span>
                                                <span class="flex-fill">
                                                    <span class="d-flex flex-wrap gap-2 align-items-center">
                                                        <strong>{{ $satu->label }}</strong>
                                                        @if($satu->is_primary)<span class="status status-accent">Utama</span>@endif
                                                    </span>
                                                    <span class="d-block">{{ $satu->recipient }} · {{ $satu->phone }}</span>
                                                    <span class="small text-muted d-block">
                                                        {{ $satu->detail }}, {{ $satu->district ? $satu->district.', ' : '' }}{{ $satu->city }},
                                                        {{ $satu->province }} {{ $satu->postal_code }}
                                                    </span>
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- 3. Jasa kirim --}}
                    <div class="cb-card">
                        <h2 class="h6 mb-2">3. Jasa kirim</h2>
                        @if($gratisOngkir)
                            <div class="alert alert-success small">
                                <i class="bi bi-truck me-1" aria-hidden="true"></i>
                                Belanja Anda sudah mencapai {{ rupiah($batasGratisOngkir) }}, ongkos kirim gratis
                                untuk semua jasa kirim di bawah ini.
                            </div>
                        @else
                            <div class="alert alert-light small">
                                <i class="bi bi-truck me-1" aria-hidden="true"></i>
                                Belanja {{ rupiah($kurangGratis) }} lagi untuk gratis ongkir
                                (minimal {{ rupiah($batasGratisOngkir) }}).
                            </div>
                        @endif

                        <div class="row g-2" role="radiogroup" aria-label="Pilih jasa kirim">
                            @forelse($jasaKirim as $jasa)
                                <div class="col-12">
                                    <label class="option-card position-relative">
                                        <input type="radio" name="shipping_rate_id" value="{{ $jasa->id }}"
                                               @checked((int) old('shipping_rate_id') === $jasa->id)>
                                        <span class="d-flex gap-3 align-items-center">
                                            <span class="check" aria-hidden="true"></span>
                                            <span class="flex-fill">
                                                <strong>{{ $jasa->courier }}</strong>
                                                <span class="text-muted">{{ $jasa->service }}</span>
                                                <span class="small text-muted d-block">
                                                    Estimasi tiba {{ $jasa->etd_min }} sampai {{ $jasa->etd_max }} hari kerja
                                                </span>
                                            </span>
                                            <span class="text-end">
                                                @if($gratisOngkir)
                                                    <span class="was d-block small">{{ rupiah($jasa->cost) }}</span>
                                                    <strong class="text-success">Gratis</strong>
                                                @else
                                                    <strong>{{ rupiah($jasa->cost) }}</strong>
                                                @endif
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-warning small mb-0">
                                        Jasa kirim sedang tidak tersedia. Silakan pilih pengambilan di toko.
                                    </div>
                                </div>
                            @endforelse
                        </div>
                        <p class="small text-muted mt-2 mb-0">Nomor resi muncul setelah pembayaran diterima.</p>
                    </div>

                    {{-- 4. Toko pengambilan --}}
                    <div class="cb-card">
                        <h2 class="h6 mb-2">4. Toko pengambilan</h2>
                        <p class="small text-muted">
                            Diisi bila Anda memilih mengambil barang di toko. Barang disiapkan staf dan dapat
                            diambil setelah pembayaran diterima.
                        </p>
                        <label class="form-label" for="store_id">Pilih toko</label>
                        <select class="form-select" id="store_id" name="store_id">
                            <option value="">Belum memilih toko</option>
                            @foreach($daftarToko as $toko)
                                <option value="{{ $toko->id }}" @selected((int) old('store_id') === $toko->id)>
                                    {{ $toko->name }} — {{ $toko->city }}{{ $toko->floor ? ', '.$toko->floor : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 5. Catatan pesanan --}}
                    <div class="cb-card">
                        <h2 class="h6 mb-2">5. Catatan untuk toko</h2>
                        <label class="form-label" for="note">Catatan (opsional)</label>
                        <input type="text" class="form-control" id="note" name="note" maxlength="191"
                               value="{{ old('note') }}" placeholder="Contoh: titip ke satpam bila rumah kosong">
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="sticky-summary">
                        @include('checkout.partials.barang', ['item' => $item])

                        {{-- Voucher: isian memakai form terpisah di bawah halaman --}}
                        <div class="cb-card">
                            <h2 class="h6 mb-2">Voucher</h2>
                            @if($voucher)
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span>
                                        <span class="code-pill">{{ $voucher->code }}</span>
                                        <span class="small text-success ms-2">&minus; {{ rupiah($diskon) }}</span>
                                    </span>
                                    <button type="submit" form="formHapusVoucher" class="btn btn-link btn-sm">Lepas</button>
                                </div>
                                <div class="small text-muted mt-1">{{ $voucher->name }}</div>
                            @else
                                <label class="form-label" for="kode">Kode voucher</label>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control" id="kode" name="kode" form="formVoucher"
                                           maxlength="30" autocomplete="off" style="text-transform: uppercase"
                                           placeholder="Contoh: COLORBOX50">
                                    <button type="submit" form="formVoucher" class="btn btn-dark">Pakai</button>
                                </div>
                            @endif
                        </div>

                        {{-- Poin donasi --}}
                        <div class="cb-card">
                            <h2 class="h6 mb-2">Poin donasi</h2>
                            @if($poinMaks > 0)
                                <p class="small text-muted mb-2">
                                    Saldo Anda {{ $poinDimiliki }} poin. Satu poin bernilai {{ rupiah($nilaiPoin) }}
                                    dan paling banyak {{ $persenPoin }} persen dari subtotal, jadi pesanan ini boleh
                                    memakai sampai {{ $poinMaks }} poin.
                                </p>
                                <label class="form-label" for="poin">Jumlah poin yang dipakai</label>
                                <input type="number" class="form-control" id="poin" name="poin" min="0"
                                       max="{{ $poinMaks }}" value="{{ old('poin', 0) }}" inputmode="numeric">
                                <div class="form-text">Potongan poin dihitung ulang saat pesanan dibuat.</div>
                            @else
                                <p class="small text-muted mb-0">
                                    Poin Anda belum mencukupi untuk pesanan ini. Poin diperoleh dari donasi pakaian
                                    layak pakai di toko Colorbox.
                                </p>
                                <input type="hidden" name="poin" value="0">
                            @endif
                        </div>

                        {{-- Ringkasan biaya --}}
                        <div class="cb-card">
                            <h2 class="h6 mb-3">Ringkasan Biaya</h2>
                            <div class="summary-row">
                                <span>Subtotal ({{ $item->sum('quantity') }} barang)</span>
                                <span>{{ rupiah($subtotal) }}</span>
                            </div>
                            @if($diskon > 0)
                                <div class="summary-row">
                                    <span>Potongan voucher</span>
                                    <span class="text-success">&minus; {{ rupiah($diskon) }}</span>
                                </div>
                            @endif
                            <div class="summary-row">
                                <span>Ongkos kirim</span>
                                <span>
                                    @if($gratisOngkir)
                                        Gratis
                                    @else
                                        mulai {{ rupiah($ongkirTermurah) }}
                                    @endif
                                </span>
                            </div>
                            <div class="summary-row total">
                                <span>Perkiraan total</span>
                                <span>{{ rupiah($totalPerkiraan) }}</span>
                            </div>
                            <p class="small text-muted mt-2">
                                Total akhir dihitung ulang mengikuti jasa kirim dan poin yang Anda pilih, lalu
                                ditampilkan di halaman pembayaran.
                            </p>

                            <button type="submit" class="btn btn-primary w-100">Buat Pesanan</button>
                            <a href="{{ route('keranjang.index') }}" class="btn btn-link btn-sm w-100 mt-1">Kembali ke keranjang</a>
                            <p class="small text-muted mb-0 mt-2">
                                Cara pembayaran QRIS, Virtual Account, dan dompet digital dipilih pada jendela
                                pembayaran Midtrans setelah pesanan dibuat.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        {{-- Form voucher ditulis terpisah supaya tidak berada di dalam form pesanan --}}
        <form action="{{ route('checkout.voucher') }}" method="POST" id="formVoucher">@csrf</form>
        <form action="{{ route('checkout.voucher.hapus') }}" method="POST" id="formHapusVoucher">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endsection
