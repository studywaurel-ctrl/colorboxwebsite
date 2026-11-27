@extends('layouts.app')

@section('judul', 'Retur dan Penukaran')

@section('isi')
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
                        <li class="breadcrumb-item active" aria-current="page">Retur dan Penukaran</li>
                    </ol>
                </nav>
                <h1 class="page-title">Retur dan Penukaran</h1>
                <p class="text-muted">Pengajuan dibuka sampai {{ $hariRetur }} hari setelah barang Anda terima.</p>

                <div class="row g-3 mb-4">
                    <div class="col-lg-7">
                        <div class="cb-card h-100">
                            <h2 class="h6">Pesanan yang masih bisa diretur</h2>
                            @forelse($pesananBisaRetur as $pesanan)
                                @php
                                    $barangPertama = $pesanan->item->first();
                                    $batas = ($pesanan->completed_at ?: $pesanan->updated_at)->copy()->addDays($hariRetur);
                                @endphp
                                <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                                    <img src="{{ gambar_produk($barangPertama ? $barangPertama->image_path : null) }}"
                                         alt="" class="mini-thumb" style="width: 44px">
                                    <div class="small flex-fill">
                                        <span class="code-pill">{{ $pesanan->order_code }}</span>
                                        <div class="text-muted mt-1">
                                            {{ $pesanan->item->count() }} barang · batas pengajuan {{ tanggal_id($batas) }}
                                        </div>
                                    </div>
                                    <a href="{{ route('retur.create', $pesanan->order_code) }}" class="btn btn-dark btn-sm">Pilih</a>
                                </div>
                            @empty
                                <p class="small text-muted mb-0">
                                    Tidak ada pesanan yang masih berada dalam masa retur.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <form action="{{ route('retur.cari-struk') }}" method="POST" class="cb-card h-100">
                            @csrf
                            <h2 class="h6">Cari dengan kode pembelian</h2>
                            <p class="small text-muted">
                                Untuk pembelian di toko lewat Tap Try and Collect, masukkan kode pembelian yang
                                tertera pada struk Anda.
                            </p>
                            <label class="form-label" for="purchase_code">Kode pembelian</label>
                            <input type="text" class="form-control mb-2" id="purchase_code" name="purchase_code"
                                   maxlength="40" required value="{{ old('purchase_code') }}"
                                   style="text-transform: uppercase" placeholder="Contoh: TTC-20260921-0001">
                            <button type="submit" class="btn btn-outline-dark w-100">Cari pembelian</button>
                        </form>
                    </div>
                </div>

                <h2 class="h5">Riwayat pengajuan</h2>
                @forelse($daftar as $retur)
                    <div class="order-card">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div class="small">
                                <span class="code-pill">{{ $retur->return_code }}</span>
                                <span class="ms-2">
                                    {{ $retur->type === 'tukar' ? 'Penukaran barang' : 'Pengembalian dana' }} ·
                                    {{ $retur->method === 'toko' ? 'Diantar ke toko' : 'Dikirim lewat jasa kirim' }}
                                </span>
                            </div>
                            <x-status-retur :status="$retur->status" />
                        </div>
                        <div class="small text-muted mt-1">
                            @if($retur->pesanan)
                                Pesanan {{ $retur->pesanan->order_code }}
                            @elseif($retur->purchase_code)
                                Pembelian di toko {{ $retur->purchase_code }}
                            @endif
                            · {{ $retur->item->count() }} barang · diajukan {{ tanggal_id($retur->created_at) }}
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <a href="{{ route('retur.show', $retur) }}" class="btn btn-outline-dark btn-sm">Rincian</a>
                        </div>
                    </div>
                @empty
                    <x-kosong ikon="arrow-left-right" judul="Belum ada pengajuan"
                              teks="Pengajuan retur dan penukaran Anda akan muncul di sini beserta statusnya." />
                @endforelse
            </div>
        </div>
    </div>
@endsection
