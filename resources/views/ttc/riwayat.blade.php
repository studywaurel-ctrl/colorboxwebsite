@extends('layouts.app')

@section('judul', 'Riwayat Tap Try and Collect')

@section('isi')
    @php
        $namaStatus = [
            'SCHEDULED' => 'Dijadwalkan',
            'READY' => 'Produk sudah disiapkan',
            'COLLECTED' => 'Selesai',
            'CANCELLED' => 'Dibatalkan',
            'NOT_PICKED_UP' => 'Kedaluwarsa',
        ];
        $nadaStatus = [
            'SCHEDULED' => 'status-info',
            'READY' => 'status-accent',
            'COLLECTED' => 'status-success',
            'CANCELLED' => 'status-muted',
            'NOT_PICKED_UP' => 'status-muted',
        ];
        $jumlahDatang = $daftar->whereIn('status', ['SCHEDULED', 'READY'])->count();
        $jumlahSelesai = $daftar->where('status', 'COLLECTED')->count();
    @endphp

    <div class="container-xl section-sm">
        <div class="row g-4">
            <aside class="col-lg-3">
                @include('akun.menu')
            </aside>

            <div class="col-lg-9">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <div>
                        <nav aria-label="Jejak halaman">
                            <ol class="breadcrumb mb-1">
                                <li class="breadcrumb-item"><a href="{{ route('beranda') }}">Beranda</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('akun.index') }}">Akun Saya</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Tap Try and Collect</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Riwayat Tap Try and Collect</h1>
                        <p class="text-muted mb-0">Seluruh jadwal mencoba produk di toko yang pernah Anda buat.</p>
                    </div>
                    <a href="{{ route('produk.index') }}" class="btn btn-primary btn-sm">Pilih produk baru</a>
                </div>

                @if($daftar->count() > 0)
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="stat-tile">
                                <strong>{{ $jumlahDatang }}</strong>
                                <span>Jadwal yang menunggu kunjungan Anda</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-tile">
                                <strong>{{ $jumlahSelesai }}</strong>
                                <span>Kunjungan yang sudah selesai</span>
                            </div>
                        </div>
                    </div>

                    @foreach($daftar as $booking)
                        <article class="order-card">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div class="small">
                                    <span class="code-pill">{{ $booking->booking_code }}</span>
                                    @if($booking->slot)
                                        <span class="ms-2">
                                            {{ tanggal_id($booking->slot->date, true) }} &middot;
                                            {{ jam_singkat($booking->slot->start_time) }} &ndash; {{ jam_singkat($booking->slot->end_time) }}
                                        </span>
                                    @endif
                                </div>
                                <span class="status {{ $nadaStatus[$booking->status] ?? 'status-muted' }}">
                                    {{ $namaStatus[$booking->status] ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                                </span>
                            </div>

                            <div class="small text-muted mt-1">
                                {{ $booking->toko ? $booking->toko->name : 'Toko tidak diketahui' }}
                                @if($booking->toko && $booking->toko->floor) &middot; {{ $booking->toko->floor }} @endif
                            </div>

                            <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                                @foreach($booking->item as $item)
                                    @php
                                        $produkItem = $item->varian ? $item->varian->produk : null;
                                    @endphp
                                    <img src="{{ gambar_produk($produkItem && $produkItem->gambarUtama() ? $produkItem->gambarUtama()->path : null) }}"
                                         alt="{{ $produkItem ? $produkItem->name : 'Produk' }}" class="mini-thumb" style="width: 40px">
                                    <span class="small text-muted">
                                        {{ $produkItem ? $produkItem->name : 'Produk sudah tidak tersedia' }}
                                        @if($item->varian) &middot; {{ $item->varian->label() }} @endif
                                    </span>
                                @endforeach
                            </div>

                            @if($booking->purchase_code)
                                <div class="info-line mt-2">
                                    <i class="bi bi-receipt" aria-hidden="true"></i>
                                    <span>Pembelian {{ rupiah($booking->total_paid) }} dengan kode {{ $booking->purchase_code }}</span>
                                </div>
                            @endif

                            <div class="mt-3">
                                <a href="{{ route('ttc.show', $booking) }}" class="btn btn-outline-dark btn-sm">Lihat rincian</a>
                            </div>
                        </article>
                    @endforeach
                @else
                    <x-kosong ikon="calendar2-x" judul="Tidak ada jadwal"
                              teks="Jadwal Tap Try and Collect Anda akan muncul di sini. Pilih produk dari katalog, lalu coba langsung di toko.">
                        <a href="{{ route('produk.index') }}" class="btn btn-primary">Lihat Katalog</a>
                    </x-kosong>
                @endif
            </div>
        </div>
    </div>
@endsection
