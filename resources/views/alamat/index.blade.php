@extends('layouts.app')

@section('judul', 'Alamat Pengiriman')

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
                                <li class="breadcrumb-item active" aria-current="page">Alamat Pengiriman</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Alamat Pengiriman</h1>
                        <p class="text-muted mb-0">Simpan beberapa alamat supaya checkout lebih cepat.</p>
                    </div>
                    <a href="{{ route('alamat.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Alamat
                    </a>
                </div>

                @forelse($daftarAlamat as $alamat)
                    <div class="order-card">
                        <div class="d-flex flex-wrap justify-content-between gap-3">
                            <div>
                                <strong>{{ $alamat->label ?: 'Alamat' }}</strong>
                                @if($alamat->is_primary)
                                    <span class="status status-accent ms-1">Alamat utama</span>
                                @endif
                                <div>{{ $alamat->recipient }} · {{ $alamat->phone }}</div>
                                <div class="small text-muted">
                                    {{ $alamat->detail }},
                                    @if($alamat->district){{ $alamat->district }}, @endif
                                    {{ $alamat->city }}, {{ $alamat->province }}
                                    @if($alamat->postal_code) {{ $alamat->postal_code }}@endif
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-1 align-items-start">
                                @if(! $alamat->is_primary)
                                    <form method="post" action="{{ route('alamat.utama', $alamat->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-outline-dark btn-sm">Jadikan utama</button>
                                    </form>
                                @endif

                                <a href="{{ route('alamat.edit', $alamat->id) }}" class="btn btn-outline-dark btn-sm">Ubah</a>

                                <form method="post" action="{{ route('alamat.destroy', $alamat->id) }}"
                                      data-confirm="Hapus alamat {{ $alamat->label ?: 'ini' }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-dark btn-sm text-danger"
                                            aria-label="Hapus alamat {{ $alamat->label ?: 'ini' }}">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                        <h3>Belum ada alamat</h3>
                        <p>Tambahkan alamat pengiriman supaya tidak perlu mengetik ulang setiap checkout.</p>
                        <a href="{{ route('alamat.create') }}" class="btn btn-primary">Tambah Alamat</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
