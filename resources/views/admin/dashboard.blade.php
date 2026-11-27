@extends('layouts.admin')

@section('judul', __('Dashboard'))

@section('isi')

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Ringkasan Hari Ini') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Angka di bawah dihitung langsung dari database, jadi selalu yang terbaru.') }}
    </p>

    <div class="ad-angka mt-3">
        <div>
            <p class="ad-angka__nilai mb-0">{{ $ringkasan['pesanan_hari_ini'] }}</p>
            <p class="ad-angka__label mb-0">{{ __('Pesanan hari ini') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $ringkasan['menunggu_pembayaran'] }}</p>
            <p class="ad-angka__label mb-0">{{ __('Nunggu pembayaran') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $ringkasan['perlu_diproses'] }}</p>
            <p class="ad-angka__label mb-0">{{ __('Perlu diproses') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ rupiah($ringkasan['penjualan_bulan']) }}</p>
            <p class="ad-angka__label mb-0">{{ __('Penjualan :p1 sampai sekarang', ['p1' => tanggal_id($bulan)]) }}</p>
        </div>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Barang dan Layanan') }}</h2>

    <div class="ad-angka mt-3">
        <div>
            <p class="ad-angka__nilai mb-0">{{ $ringkasan['produk_aktif'] }}</p>
            <p class="ad-angka__label mb-0">{{ __('Produk aktif') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $ringkasan['stok_menipis'] }}</p>
            <p class="ad-angka__label mb-0">{{ __('Varian yang stoknya tipis') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $ringkasan['retur_menunggu'] }}</p>
            <p class="ad-angka__label mb-0">{{ __('Retur yang nunggu dicek') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $ringkasan['donasi_menunggu'] }}</p>
            <p class="ad-angka__label mb-0">{{ __('Donasi yang nunggu dicek') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $ringkasan['ttc_hari_ini'] }}</p>
            <p class="ad-angka__label mb-0">{{ __('Booking Tap Try and Collect hari ini') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $ringkasan['keluhan_baru'] }}</p>
            <p class="ad-angka__label mb-0">{{ __('Keluhan baru') }}</p>
        </div>
    </div>
</div>

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 style="font-size: 1.05rem" class="mb-0">{{ __('10 Pesanan Terbaru') }}</h2>
        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.pesanan.index') }}">{{ __('Semua Pesanan') }}</a>
    </div>

    <div class="ad-gulir mt-3">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Kode') }}</th>
                <th>{{ __('Pelanggan') }}</th>
                <th>{{ __('Tanggal') }}</th>
                <th>{{ __('Metode') }}</th>
                <th>{{ __('Total') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($pesananTerbaru as $pesanan)
                <tr>
                    <td><a href="{{ route('admin.pesanan.show', $pesanan) }}">{{ $pesanan->order_code }}</a></td>
                    <td>{{ $pesanan->pengguna?->name ?? '-' }}</td>
                    <td>{{ tanggal_id($pesanan->created_at) }}</td>
                    <td>{{ $pesanan->method === 'ambil_toko' ? __('Ambil di toko') : __('Dikirim kurir') }}</td>
                    <td>{{ rupiah($pesanan->total) }}</td>
                    <td><span class="status status-accent">{{ $pesanan->labelStatus() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('Belum ada pesanan.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 style="font-size: 1.05rem" class="mb-0">{{ __('Stok Paling Tipis') }}</h2>
        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.produk.index') }}">{{ __('Kelola Produk') }}</a>
    </div>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Varian dengan stok kurang dari :p1 pcs.', ['p1' => $batasStok]) }}
    </p>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Produk') }}</th>
                <th>{{ __('Warna') }}</th>
                <th>{{ __('Ukuran') }}</th>
                <th>{{ __('Stok') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($varianMenipis as $varian)
                <tr>
                    <td>{{ $varian->produk?->name ?? '-' }}</td>
                    <td>{{ $varian->color }}</td>
                    <td>{{ $varian->size }}</td>
                    <td><strong>{{ $varian->stock }}</strong></td>
                    <td>
                        @if($varian->produk)
                            <a class="btn btn-outline-dark btn-sm"
                               href="{{ route('admin.produk.show', $varian->produk) }}">{{ __('Ubah Stok') }}</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('Semua stok masih aman.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
