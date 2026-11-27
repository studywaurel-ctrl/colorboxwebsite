@extends('layouts.app')

@section('judul', 'Akun Saya')

@section('isi')
    @php
        // Huruf awal nama dipakai bila pelanggan belum mengunggah foto profil
        $potongNama = preg_split('/\s+/', trim($pengguna->name));
        $inisial = mb_strtoupper(mb_substr($potongNama[0] ?? 'C', 0, 1));
        if (count($potongNama) > 1) {
            $inisial .= mb_strtoupper(mb_substr(end($potongNama), 0, 1));
        }

        // Warna lencana status pesanan
        $nadaStatus = [
            'menunggu_pembayaran' => 'warning',
            'dibayar' => 'info',
            'diproses' => 'info',
            'dikemas' => 'info',
            'dikirim' => 'info',
            'siap_diambil' => 'accent',
            'diterima' => 'success',
            'selesai' => 'success',
            'dibatalkan' => 'danger',
        ];
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
                                <li class="breadcrumb-item active" aria-current="page">Akun Saya</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Akun Saya</h1>
                        <p class="text-muted mb-0">Ringkasan pesanan, poin, dan jadwal layanan Anda.</p>
                    </div>
                </div>

                {{-- Identitas pelanggan --}}
                <div class="cb-card d-flex flex-wrap gap-3 align-items-center">
                    <span class="avatar lg">
                        @if($pengguna->photo)
                            <img src="{{ asset($pengguna->photo) }}" alt="Foto profil {{ $pengguna->name }}">
                        @else
                            {{ $inisial }}
                        @endif
                    </span>
                    <div class="flex-fill">
                        <h2 class="h5 mb-0">{{ $pengguna->name }}</h2>
                        <div class="small text-muted">
                            {{ $pengguna->email }} · {{ $pengguna->phone ?: 'Nomor telepon belum diisi' }}
                        </div>
                        <div class="small text-soft">
                            Bergabung {{ tanggal_id($pengguna->created_at) }} ·
                            <span class="status status-accent">{{ $pengguna->member_level }}</span>
                        </div>
                    </div>
                    <a href="{{ route('profil.edit') }}" class="btn btn-outline-dark btn-sm">Ubah Profil</a>
                </div>

                {{-- Ringkasan angka pesanan --}}
                <div class="row g-3 mt-1">
                    <div class="col-6 col-md-3">
                        <a href="{{ route('pesanan.index', ['status' => 'menunggu_pembayaran']) }}" class="stat-tile">
                            <strong>{{ $jumlahBelumDibayar }}</strong><span>Belum Dibayar</span>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="{{ route('pesanan.index', ['status' => 'diproses']) }}" class="stat-tile">
                            <strong>{{ $jumlahDiproses }}</strong><span>Diproses</span>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="{{ route('pesanan.index', ['status' => 'dikirim']) }}" class="stat-tile">
                            <strong>{{ $jumlahDikirim }}</strong><span>Dikirim</span>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="{{ route('retur.index') }}" class="stat-tile">
                            <strong>{{ $jumlahReturBerjalan }}</strong><span>Retur Berjalan</span>
                        </a>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <a href="{{ route('poin.index') }}" class="stat-tile">
                            <strong class="text-accent">{{ $pengguna->points }}</strong>
                            <span>Poin donasi · setara {{ rupiah($pengguna->points * (int) setting('point_value', 100)) }}</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ $ttcTerdekat ? route('ttc.show', $ttcTerdekat->id) : route('ttc.index') }}" class="stat-tile">
                            <span class="d-block small text-muted">Tap Try and Collect</span>
                            @if($ttcTerdekat)
                                <strong class="fs-6">
                                    {{ tanggal_id($ttcTerdekat->slot->date) }} ·
                                    {{ $ttcTerdekat->toko ? $ttcTerdekat->toko->name : 'Toko belum dipilih' }}
                                </strong>
                                <span>Pukul {{ jam_singkat($ttcTerdekat->slot->start_time) }} &ndash; {{ jam_singkat($ttcTerdekat->slot->end_time) }}</span>
                            @else
                                <strong class="fs-6">Belum ada jadwal</strong>
                                <span>Pesan kunjungan mencoba dan mengambil pesanan di toko.</span>
                            @endif
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ $donasiTerdekat ? route('donasi.show', $donasiTerdekat->id) : route('donasi.index') }}" class="stat-tile">
                            <span class="d-block small text-muted">Donasi Pakaian</span>
                            @if($donasiTerdekat)
                                <strong class="fs-6">
                                    {{ tanggal_id($donasiTerdekat->slot->date) }} ·
                                    {{ $donasiTerdekat->toko ? $donasiTerdekat->toko->name : 'Toko belum dipilih' }}
                                </strong>
                                <span>Pukul {{ jam_singkat($donasiTerdekat->slot->start_time) }} &ndash; {{ jam_singkat($donasiTerdekat->slot->end_time) }}</span>
                            @else
                                <strong class="fs-6">Belum ada jadwal</strong>
                                <span>Jadwalkan donasi pakaian layak pakai dan kumpulkan poin.</span>
                            @endif
                        </a>
                    </div>
                </div>

                {{-- Pesanan terbaru --}}
                <div class="section-head mt-4">
                    <h2 class="h5 mb-0">Pesanan Terbaru</h2>
                    <a href="{{ route('pesanan.index') }}" class="small">Lihat semua pesanan</a>
                </div>

                @forelse($pesananTerbaru as $pesanan)
                    @php $barangPertama = $pesanan->item->first(); @endphp
                    <a href="{{ route('pesanan.show', $pesanan) }}" class="order-card d-flex gap-3 align-items-center">
                        <img src="{{ gambar_produk($barangPertama ? $barangPertama->image_path : null) }}" alt="" class="mini-thumb">
                        <div class="flex-fill small">
                            <span class="code-pill">{{ $pesanan->order_code }}</span>
                            <div class="text-muted mt-1">
                                {{ tanggal_id($pesanan->created_at) }} · {{ rupiah($pesanan->total) }} ·
                                {{ $pesanan->item->count() }} barang
                            </div>
                        </div>
                        <span class="status status-{{ $nadaStatus[$pesanan->status] ?? 'muted' }}">{{ $pesanan->labelStatus() }}</span>
                    </a>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-receipt" aria-hidden="true"></i>
                        <h3>Belum ada pesanan</h3>
                        <p>Pesanan yang Anda buat akan muncul di sini beserta status pengirimannya.</p>
                        <a href="{{ route('produk.index') }}" class="btn btn-primary">Mulai belanja</a>
                    </div>
                @endforelse

                {{-- Notifikasi belum dibaca --}}
                @if($notifikasiBelumDibaca > 0)
                    <div class="cb-card mt-4 d-flex flex-wrap gap-3 align-items-center">
                        <i class="bi bi-bell fs-4 text-accent" aria-hidden="true"></i>
                        <div class="flex-fill">
                            <strong>{{ $notifikasiBelumDibaca }} notifikasi belum dibaca</strong>
                            <div class="small text-muted">Kabar terbaru tentang pesanan, retur, donasi, dan kunjungan toko.</div>
                        </div>
                        <a href="{{ route('notifikasi.index') }}" class="btn btn-outline-dark btn-sm">Buka notifikasi</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
