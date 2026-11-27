@extends('layouts.app')

@section('judul', 'Jadwal '.$booking->booking_code)

@section('isi')
    @php
        // Nama status ditulis manusiawi, lengkap dengan nada warnanya
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
        $status = $booking->status;
        $bisaDatang = in_array($status, ['SCHEDULED', 'READY']);
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
                                <li class="breadcrumb-item"><a href="{{ route('ttc.riwayat') }}">Tap Try and Collect</a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ $booking->booking_code }}</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Jadwal {{ $booking->booking_code }}</h1>
                    </div>
                    <span class="status {{ $nadaStatus[$status] ?? 'status-muted' }}">
                        {{ $namaStatus[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="cb-card">
                            <h2 class="h6">Kunjungan</h2>
                            @if($booking->toko)
                                <p class="mb-1"><strong>{{ $booking->toko->name }}</strong></p>
                                <div class="small text-muted">{{ $booking->toko->address }}, {{ $booking->toko->city }}</div>
                                @if($booking->toko->floor)
                                    <div class="info-line mt-2"><i class="bi bi-building" aria-hidden="true"></i><span>{{ $booking->toko->floor }}</span></div>
                                @endif
                                <div class="info-line">
                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                    <span>Toko buka {{ jam_singkat($booking->toko->open_time) }} &ndash; {{ jam_singkat($booking->toko->close_time) }}</span>
                                </div>
                            @else
                                <p class="small text-muted mb-0">Data toko tidak ditemukan.</p>
                            @endif

                            @if($booking->slot)
                                <div class="info-line">
                                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                    <span>{{ tanggal_id($booking->slot->date, true) }} &middot;
                                        {{ jam_singkat($booking->slot->start_time) }} &ndash; {{ jam_singkat($booking->slot->end_time) }}</span>
                                </div>
                            @endif
                            <div class="info-line">
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                <span>Produk disiapkan {{ $menitSiap }} menit sebelum jadwal.</span>
                            </div>
                        </div>

                        @if($bisaDatang)
                            <div class="cb-card">
                                <h2 class="h6">Di toko</h2>
                                <div class="d-flex gap-3 align-items-center flex-wrap">
                                    {{-- Tautan verifikasi staf digambar menjadi QR oleh app.js --}}
                                    <div class="qr-box" data-qr="{{ $alamatStaf }}" data-qr-label="{{ $booking->booking_code }}"></div>
                                    <ol class="small text-muted ps-3 mb-0" style="max-width: 320px">
                                        <li>Datang pada jam yang dipilih dan sampaikan kode {{ $booking->booking_code }}.</li>
                                        <li>Staf toko memindai QR ini lalu mengantar Anda ke ruang ganti.</li>
                                        <li>Bayar di kasir hanya produk yang Anda ambil.</li>
                                    </ol>
                                </div>
                                <div class="mt-2"><span class="code-pill">{{ $booking->booking_code }}</span></div>
                            </div>
                        @endif

                        @if($booking->purchase_code)
                            <div class="cb-card">
                                <h2 class="h6">Pembelian di kasir</h2>
                                <div class="info-line"><i class="bi bi-receipt" aria-hidden="true"></i><span>Kode pembelian <span class="code-pill">{{ $booking->purchase_code }}</span></span></div>
                                <div class="info-line"><i class="bi bi-cash-coin" aria-hidden="true"></i><span>Total dibayar {{ rupiah($booking->total_paid) }}</span></div>
                                @if($booking->verified_by)
                                    <div class="info-line"><i class="bi bi-person-badge" aria-hidden="true"></i><span>Diperiksa staf {{ $booking->verified_by }}</span></div>
                                @endif
                                <p class="form-text mb-0 mt-2">Kode pembelian ini dipakai bila nanti ingin mengajukan retur atau penukaran.</p>
                            </div>
                        @endif
                    </div>

                    <div class="col-lg-5">
                        <div class="cb-card">
                            <h2 class="h6">Produk</h2>
                            @forelse($booking->item as $item)
                                @php
                                    $produkItem = $item->varian ? $item->varian->produk : null;
                                    $gambarItem = $produkItem
                                        ? ($produkItem->gambar->firstWhere('color', $item->varian->color) ?: $produkItem->gambarUtama())
                                        : null;
                                @endphp
                                <div class="d-flex gap-2 small mb-2">
                                    <img src="{{ gambar_produk($gambarItem ? $gambarItem->path : null) }}"
                                         alt="{{ $produkItem ? $produkItem->name : 'Produk' }}" class="mini-thumb" style="width: 44px">
                                    <div>
                                        @if($produkItem)
                                            <a href="{{ route('produk.show', $produkItem) }}" class="fw-bold">{{ $produkItem->name }}</a>
                                            <div class="text-muted">
                                                {{ $item->varian->label() }} &middot; {{ rupiah($produkItem->hargaJual()) }}
                                            </div>
                                        @else
                                            <span class="fw-bold">Produk sudah tidak tersedia</span>
                                        @endif
                                        @if($item->purchased)
                                            <span class="status status-success mt-1">Jadi dibeli</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="small text-muted mb-0">Tidak ada produk pada jadwal ini.</p>
                            @endforelse
                        </div>

                        @if($booking->bisaDibatalkan())
                            <form action="{{ route('ttc.batal', $booking) }}" method="POST" class="mt-3"
                                  data-confirm="Batalkan jadwal {{ $booking->booking_code }}? Produk akan dikembalikan ke rak toko.">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-outline-dark w-100">Batalkan Jadwal</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
