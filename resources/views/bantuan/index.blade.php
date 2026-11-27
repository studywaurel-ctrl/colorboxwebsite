@extends('layouts.app')

@section('judul', 'Pusat Bantuan')

@section('isi')
<div class="container-xl section-sm">
    <x-remah :tautan="['Bantuan' => null]" />

    <div class="row g-4">
        <aside class="col-lg-3">
            <nav class="account-nav" aria-label="Topik bantuan">
                <div class="nav-label">Topik</div>
                @foreach($topik as $alamat => $keterangan)
                    <a href="{{ route('bantuan.show', $alamat) }}">{{ $keterangan[0] }}</a>
                @endforeach
            </nav>
        </aside>

        <div class="col-lg-9 prose">
            <h1 class="page-title mb-2">Pusat Bantuan</h1>
            <p>
                Kumpulan keterangan mengenai belanja, pembayaran, pengiriman, retur, dan layanan
                Colorbox lainnya. Pilih topik di bawah untuk membaca keterangan lengkapnya.
            </p>

            <div class="row g-3 mt-1">
                @foreach($topik as $alamat => $keterangan)
                    <div class="col-md-6">
                        <div class="service-tile">
                            <h2 class="h6 mt-0">{{ $keterangan[0] }}</h2>
                            <p>{{ $keterangan[1] }}</p>
                            <a href="{{ route('bantuan.show', $alamat) }}" class="btn btn-outline-dark btn-sm align-self-start">
                                Baca selengkapnya
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="cb-card mt-4">
                <h2 class="h6 mt-0">Masih perlu bantuan?</h2>
                <p class="mb-3">
                    Lacak pesanan Anda memakai kode pesanan, atau kirim keluhan lewat halaman akun
                    supaya ditindaklanjuti petugas Colorbox.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('lacak.index') }}" class="btn btn-dark btn-sm">Lacak Pesanan</a>
                    <a href="{{ route('keluhan.index') }}" class="btn btn-outline-dark btn-sm">Kirim Keluhan</a>
                    <a href="{{ route('toko.index') }}" class="btn btn-light btn-sm">Daftar Toko</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
