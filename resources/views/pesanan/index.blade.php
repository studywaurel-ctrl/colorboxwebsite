@extends('layouts.app')

@section('judul', 'Pesanan Saya')

@section('isi')
    @php
        $labelStatus = [
            'menunggu_pembayaran' => 'Belum Dibayar',
            'dibayar' => 'Sudah Dibayar',
            'diproses' => 'Diproses',
            'dikemas' => 'Dikemas',
            'dikirim' => 'Dikirim',
            'siap_diambil' => 'Siap Diambil',
            'diterima' => 'Diterima',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ];
    @endphp

    <div class="container-xl section-sm">
        <div class="row g-4">
            <aside class="col-lg-3">
                @include('akun.menu')
            </aside>

            <div class="col-lg-9">
                <nav aria-label="Jejak halaman">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('beranda') }}">Beranda</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('akun.index') }}">Akun Saya</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Pesanan Saya</li>
                    </ol>
                </nav>
                <h1 class="page-title">Pesanan Saya</h1>
                <p class="text-muted">Seluruh pesanan Anda beserta status pembayaran dan pengirimannya.</p>

                <div class="tabs-scroll mb-3">
                    <a href="{{ route('pesanan.index') }}" class="{{ $status ? '' : 'active' }}">Semua</a>
                    @foreach($daftarStatus as $satu)
                        <a href="{{ route('pesanan.index', ['status' => $satu]) }}"
                           class="{{ $status === $satu ? 'active' : '' }}">{{ $labelStatus[$satu] ?? $satu }}</a>
                    @endforeach
                </div>

                @forelse($daftar as $pesanan)
                    @php $barangPertama = $pesanan->item->first(); @endphp
                    <div class="order-card">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                            <div class="small">
                                <span class="code-pill">{{ $pesanan->order_code }}</span>
                                <span class="text-muted ms-2">{{ tanggal_id($pesanan->created_at) }}</span>
                                <span class="chip ms-2">
                                    <i class="bi bi-{{ $pesanan->method === 'ambil_toko' ? 'shop' : 'truck' }}" aria-hidden="true"></i>
                                    {{ $pesanan->method === 'ambil_toko' ? 'Diambil di toko' : 'Dikirim kurir' }}
                                </span>
                            </div>
                            <x-status-pesanan :status="$pesanan->status" />
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            @foreach($pesanan->item->take(3) as $baris)
                                <img src="{{ gambar_produk($baris->image_path) }}" alt="{{ $baris->product_name }}" class="mini-thumb">
                            @endforeach
                            <div class="small ms-2 flex-fill">
                                <div class="fw-600">{{ $barangPertama ? $barangPertama->product_name : 'Tanpa barang' }}</div>
                                @if($pesanan->item->count() > 1)
                                    <div class="text-muted">dan {{ $pesanan->item->count() - 1 }} barang lainnya</div>
                                @endif
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Total</div>
                                <strong>{{ rupiah($pesanan->total) }}</strong>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                            @if($pesanan->status === 'menunggu_pembayaran' && $pesanan->pembayaran)
                                <a href="{{ route('pembayaran.show', $pesanan->pembayaran) }}" class="btn btn-primary btn-sm">
                                    Bayar Sekarang
                                </a>
                            @endif
                            @if(! in_array($pesanan->status, ['menunggu_pembayaran', 'dibatalkan']))
                                <a href="{{ route('lacak.index', ['kode' => $pesanan->order_code]) }}" class="btn btn-light btn-sm">
                                    <i class="bi bi-truck me-1" aria-hidden="true"></i>Lacak
                                </a>
                            @endif
                            @if(in_array($pesanan->status, ['diterima', 'selesai']))
                                <a href="{{ route('retur.create', $pesanan->order_code) }}" class="btn btn-light btn-sm">
                                    <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Retur
                                </a>
                            @endif
                            <a href="{{ route('pesanan.show', $pesanan) }}" class="btn btn-outline-dark btn-sm">Rincian</a>
                        </div>
                    </div>
                @empty
                    <x-kosong ikon="receipt" judul="Belum ada pesanan di sini"
                              teks="{{ $status ? 'Tidak ada pesanan dengan status tersebut.' : 'Pesanan yang Anda buat akan muncul di sini.' }}">
                        <a href="{{ route('produk.index') }}" class="btn btn-dark">Mulai belanja</a>
                    </x-kosong>
                @endforelse

                <div class="mt-3">{{ $daftar->links() }}</div>
            </div>
        </div>
    </div>
@endsection
