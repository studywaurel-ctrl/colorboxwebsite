@extends('layouts.app')

@section('judul', 'Poin Saya')

@section('isi')
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
                                <li class="breadcrumb-item active" aria-current="page">Poin Saya</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Poin Saya</h1>
                        <p class="text-muted mb-0">Poin donasi beserta riwayat masuk dan keluarnya.</p>
                    </div>
                    <a href="{{ route('donasi.index') }}" class="btn btn-primary btn-sm">Jadwalkan Donasi</a>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="stat-tile">
                            <strong class="text-accent">{{ $pengguna->points }}</strong>
                            <span>Saldo poin · setara {{ rupiah($pengguna->points * $nilaiPoin) }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-tile">
                            <strong>{{ rupiah($nilaiPoin) }}</strong>
                            <span>Nilai satu poin saat ditukar menjadi potongan belanja</span>
                        </div>
                    </div>
                </div>

                {{-- Aturan singkat cara memperoleh dan memakai poin --}}
                <div class="cb-card mb-3">
                    <h2 class="h6 mb-2">Cara Memperoleh dan Memakai Poin</h2>
                    <ul class="small text-muted mb-0 ps-3">
                        <li>
                            Poin diperoleh dari donasi pakaian layak pakai di toko Colorbox. Setiap satu potong
                            pakaian yang diterima petugas bernilai {{ $poinPerPotong }} poin.
                        </li>
                        <li>
                            Poin baru masuk setelah petugas toko memeriksa dan menyetujui donasi Anda,
                            bukan saat jadwal donasi dibuat.
                        </li>
                        <li>
                            Satu poin bernilai {{ rupiah($nilaiPoin) }} ketika ditukar menjadi potongan belanja
                            pada halaman checkout.
                        </li>
                        <li>
                            Poin yang dipakai paling banyak {{ $batasPersen }} persen dari subtotal belanja,
                            jadi tidak seluruh tagihan dapat dibayar memakai poin.
                        </li>
                    </ul>
                </div>

                <div class="section-head">
                    <h2 class="h5 mb-0">Riwayat Poin</h2>
                </div>

                @if($riwayatPoin->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-coin" aria-hidden="true"></i>
                        <h3>Belum ada riwayat poin</h3>
                        <p>Poin pertama Anda muncul setelah donasi pakaian disetujui petugas toko.</p>
                        <a href="{{ route('donasi.index') }}" class="btn btn-primary">Jadwalkan donasi</a>
                    </div>
                @else
                    <div class="cb-card p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead>
                                    <tr class="small text-muted">
                                        <th class="ps-3" scope="col">Tanggal</th>
                                        <th scope="col">Sumber</th>
                                        <th scope="col">Keterangan</th>
                                        <th class="pe-3 text-end" scope="col">Poin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($riwayatPoin as $baris)
                                        <tr>
                                            <td class="ps-3 small">{{ tanggal_id($baris->created_at) }}</td>
                                            <td class="small">{{ ucfirst(str_replace('_', ' ', $baris->source)) }}</td>
                                            <td class="small text-muted">{{ $baris->note ?: '-' }}</td>
                                            <td class="pe-3 text-end">
                                                @if($baris->type === 'masuk')
                                                    <span class="status status-success">+{{ $baris->amount }}</span>
                                                @else
                                                    <span class="status status-warning">&minus;{{ $baris->amount }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($riwayatPoin->hasPages())
                        <div class="mt-3">{{ $riwayatPoin->links() }}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
