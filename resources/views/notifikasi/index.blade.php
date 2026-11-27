@extends('layouts.app')

@section('judul', 'Notifikasi')

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
                                <li class="breadcrumb-item active" aria-current="page">Notifikasi</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Notifikasi</h1>
                        <p class="text-muted mb-0">
                            Kabar terbaru tentang pesanan, retur, donasi, dan kunjungan toko.
                            @if($jumlahBelumDibaca > 0)
                                <span class="status status-accent ms-1">{{ $jumlahBelumDibaca }} belum dibaca</span>
                            @endif
                        </p>
                    </div>

                    @if($jumlahBelumDibaca > 0)
                        <form method="post" action="{{ route('notifikasi.baca-semua') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-dark btn-sm">Tandai semua dibaca</button>
                        </form>
                    @endif
                </div>

                @forelse($daftarNotifikasi as $notifikasi)
                    <a href="{{ route('notifikasi.buka', $notifikasi->id) }}"
                       class="order-card d-flex gap-3 {{ $notifikasi->read_at ? '' : 'border-start border-3' }}"
                       @if(! $notifikasi->read_at) style="border-left-color: var(--cb-accent) !important" @endif>
                        <i class="bi bi-bell fs-5 text-soft" aria-hidden="true"></i>
                        <div class="flex-fill">
                            <div class="{{ $notifikasi->read_at ? '' : 'fw-600' }}">{{ $notifikasi->title }}</div>
                            @if($notifikasi->body)
                                <div class="small text-muted">{{ $notifikasi->body }}</div>
                            @endif
                            <div class="small text-soft">{{ tanggal_id($notifikasi->created_at, true) }}</div>
                        </div>
                        @if(! $notifikasi->read_at)
                            <span class="status status-accent align-self-start">Baru</span>
                        @endif
                    </a>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <h3>Belum ada notifikasi</h3>
                        <p>Pemberitahuan tentang pesanan dan layanan Anda akan muncul di halaman ini.</p>
                        <a href="{{ route('produk.index') }}" class="btn btn-primary">Lihat produk</a>
                    </div>
                @endforelse

                @if($daftarNotifikasi->hasPages())
                    <div class="mt-3">{{ $daftarNotifikasi->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
