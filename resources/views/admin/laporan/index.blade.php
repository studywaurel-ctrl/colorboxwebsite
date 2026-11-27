@extends('layouts.admin')

@section('judul', __('Laporan Penjualan'))

@section('isi')

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Laporan Penjualan') }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                {{ __('Periode :p1 sampai :p2. Yang dihitung cuma pesanan yang uangnya udah masuk. Pesanan yang masih nunggu bayar atau udah dibatalkan nggak dihitung.', ['p1' => tanggal_id($dari), 'p2' => tanggal_id($sampai)]) }}
            </p>
        </div>
        <a class="btn btn-outline-dark btn-sm"
           href="{{ route('admin.laporan.index', ['dari' => $dari, 'sampai' => $sampai, 'format' => 'csv']) }}">
            <i class="bi bi-download"></i> {{ __('Download CSV') }}
        </a>
    </div>

    <form class="mt-3" action="{{ route('admin.laporan.index') }}" method="GET">
        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="dari">{{ __('Tanggal mulai') }}</label>
                <input class="form-control" type="date" id="dari" name="dari" value="{{ $dari }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="sampai">{{ __('Tanggal akhir') }}</label>
                <input class="form-control" type="date" id="sampai" name="sampai" value="{{ $sampai }}">
            </div>
        </div>
        <div class="d-flex gap-2 mt-2">
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Tampilkan') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.laporan.index') }}">{{ __('Bulan Ini') }}</a>
        </div>
    </form>

    <div class="ad-angka mt-3">
        <div>
            <p class="ad-angka__nilai mb-0">{{ $jumlahPesanan }}</p>
            <p class="ad-angka__label mb-0">{{ __('Jumlah pesanan') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $produkTerjual }}</p>
            <p class="ad-angka__label mb-0">{{ __('Produk terjual (pcs)') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ rupiah($totalPenjualan) }}</p>
            <p class="ad-angka__label mb-0">{{ __('Total penjualan') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ rupiah($rataRata) }}</p>
            <p class="ad-angka__label mb-0">{{ __('Rata-rata nilai pesanan') }}</p>
        </div>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Penjualan per Hari') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Tanggal') }}</th>
                <th>{{ __('Jumlah Pesanan') }}</th>
                <th>{{ __('Nilai Penjualan') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($perHari as $baris)
                <tr>
                    <td>{{ tanggal_id($baris->tanggal, true) }}</td>
                    <td>{{ $baris->jumlah }}</td>
                    <td>{{ rupiah($baris->nilai) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('Belum ada penjualan di periode ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('10 Produk Terlaris') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Produk') }}</th>
                <th>{{ __('Jumlah Terjual') }}</th>
                <th>{{ __('Nilai Penjualan') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($terlaris as $baris)
                <tr>
                    <td>{{ $baris->product_name }}</td>
                    <td>{{ $baris->jumlah }}</td>
                    <td>{{ rupiah($baris->nilai) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('Belum ada produk terjual di periode ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Cara Terima Barang') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Metode') }}</th>
                <th>{{ __('Jumlah Pesanan') }}</th>
                <th>{{ __('Nilai Penjualan') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($perMetode as $baris)
                <tr>
                    <td>{{ $baris->method === 'ambil_toko' ? __('Ambil di toko') : __('Dikirim kurir') }}</td>
                    <td>{{ $baris->jumlah }}</td>
                    <td>{{ rupiah($baris->nilai) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('Belum ada data di periode ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
