@extends('layouts.app')

@section('judul', 'Riwayat Donasi')

@section('isi')
    @php
        $namaStatus = [
            'dijadwalkan' => 'Dijadwalkan',
            'check_in' => 'Sudah check-in',
            'menunggu_validasi' => 'Menunggu pemeriksaan staf',
            'disetujui' => 'Diterima',
            'ditolak' => 'Tidak diterima',
            'dibatalkan' => 'Dibatalkan',
        ];
        $nadaStatus = [
            'dijadwalkan' => 'status-info',
            'check_in' => 'status-accent',
            'menunggu_validasi' => 'status-warning',
            'disetujui' => 'status-success',
            'ditolak' => 'status-danger',
            'dibatalkan' => 'status-muted',
        ];
        $totalPotong = (int) $daftar->sum('received_items');
        $totalPoin = (int) $daftar->sum('points_awarded');
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
                                <li class="breadcrumb-item active" aria-current="page">Riwayat Donasi</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Riwayat Donasi</h1>
                        <p class="text-muted mb-0">Seluruh jadwal donasi pakaian Anda beserta poin yang diperoleh.</p>
                    </div>
                    <a href="{{ route('donasi.index') }}" class="btn btn-primary btn-sm">Jadwal baru</a>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <a href="{{ route('poin.index') }}" class="stat-tile">
                            <strong class="text-accent">{{ auth()->user()->points }}</strong>
                            <span>Saldo poin sekarang</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-tile">
                            <strong>{{ $totalPotong }}</strong>
                            <span>Potong pakaian yang diterima toko</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-tile">
                            <strong>{{ $totalPoin }}</strong>
                            <span>Poin dari donasi &middot; {{ $poinPerPotong }} poin per potong</span>
                        </div>
                    </div>
                </div>

                @if($daftar->count() > 0)
                    @foreach($daftar as $donasi)
                        <article class="order-card">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div class="small">
                                    <span class="code-pill">{{ $donasi->donation_code }}</span>
                                    @if($donasi->slot)
                                        <span class="ms-2">
                                            {{ tanggal_id($donasi->slot->date, true) }} &middot;
                                            {{ jam_singkat($donasi->slot->start_time) }} &ndash; {{ jam_singkat($donasi->slot->end_time) }}
                                        </span>
                                    @endif
                                </div>
                                <span class="status {{ $nadaStatus[$donasi->status] ?? 'status-muted' }}">
                                    {{ $namaStatus[$donasi->status] ?? ucfirst(str_replace('_', ' ', $donasi->status)) }}
                                </span>
                            </div>

                            <div class="small text-muted mt-1">
                                {{ $donasi->toko ? $donasi->toko->name : 'Toko tidak diketahui' }}
                                @if($donasi->toko) &middot; {{ $donasi->toko->city }} @endif
                            </div>

                            <div class="info-line mt-2">
                                <i class="bi bi-bag-heart" aria-hidden="true"></i>
                                <span>
                                    @if(in_array($donasi->status, ['disetujui', 'ditolak']))
                                        {{ $donasi->received_items }} potong diterima dari perkiraan {{ $donasi->estimated_items }} potong
                                    @else
                                        Perkiraan {{ $donasi->estimated_items }} potong
                                    @endif
                                </span>
                            </div>
                            <div class="info-line">
                                <i class="bi bi-coin" aria-hidden="true"></i>
                                <span>{{ $donasi->points_awarded > 0 ? '+'.$donasi->points_awarded.' poin' : 'Belum ada poin' }}</span>
                            </div>

                            <div class="mt-3">
                                <a href="{{ route('donasi.show', $donasi) }}" class="btn btn-outline-dark btn-sm">Lihat rincian</a>
                            </div>
                        </article>
                    @endforeach
                @else
                    <x-kosong ikon="recycle" judul="Belum ada donasi"
                              teks="Jadwalkan donasi pertama Anda di toko Colorbox terdekat dan tukarkan pakaian layak pakai menjadi poin.">
                        <a href="{{ route('donasi.index') }}" class="btn btn-primary">Jadwalkan donasi</a>
                    </x-kosong>
                @endif
            </div>
        </div>
    </div>
@endsection
