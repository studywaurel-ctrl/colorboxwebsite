@extends('layouts.app')

@section('judul', 'Donasi '.$donasi->donation_code)

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
        $status = $donasi->status;
        $hariIni = $donasi->slot && $donasi->slot->date->toDateString() === now()->toDateString();
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
                                <li class="breadcrumb-item"><a href="{{ route('donasi.riwayat') }}">Riwayat Donasi</a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ $donasi->donation_code }}</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Donasi {{ $donasi->donation_code }}</h1>
                    </div>
                    <span class="status {{ $nadaStatus[$status] ?? 'status-muted' }}">
                        {{ $namaStatus[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-lg-7">
                        @if($status === 'dijadwalkan')
                            <div class="cb-card">
                                <h2 class="h6">Check-in di toko</h2>
                                @if($hariIni)
                                    <p class="small text-muted">
                                        Setelah tiba di toko dan menyerahkan kantong pakaian kepada staf,
                                        tekan tombol berikut supaya staf dapat memeriksa donasi Anda.
                                    </p>
                                    <form action="{{ route('donasi.check-in', $donasi) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-primary">Saya sudah di toko</button>
                                    </form>
                                @else
                                    <p class="small text-muted mb-0">
                                        Check-in tersedia pada tanggal jadwal Anda
                                        @if($donasi->slot), yaitu {{ tanggal_id($donasi->slot->date, true) }}@endif.
                                        Datanglah dengan membawa pakaian dalam kantong.
                                    </p>
                                @endif
                            </div>
                        @elseif($status === 'check_in' || $status === 'menunggu_validasi')
                            <div class="cb-card">
                                <h2 class="h6">Menunggu pemeriksaan staf</h2>
                                <p class="small text-muted">
                                    Tunjukkan QR berikut kepada staf toko. Staf menghitung pakaian yang diterima,
                                    lalu poinnya langsung masuk ke akun Anda.
                                </p>
                                {{-- Tautan verifikasi staf digambar menjadi QR oleh app.js --}}
                                <div class="qr-box" data-qr="{{ $alamatStaf }}" data-qr-label="{{ $donasi->donation_code }}"></div>
                                <div class="mt-2"><span class="code-pill">{{ $donasi->donation_code }}</span></div>
                                @if($donasi->checked_in_at)
                                    <div class="info-line mt-2">
                                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                                        <span>Check-in tercatat {{ tanggal_id($donasi->checked_in_at) }} pukul {{ $donasi->checked_in_at->format('H:i') }}</span>
                                    </div>
                                @endif
                            </div>
                        @elseif($status === 'disetujui' || $status === 'ditolak')
                            <div class="cb-card">
                                <h2 class="h6">Hasil pemeriksaan</h2>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="stat-tile">
                                            <strong>{{ $donasi->received_items }}</strong>
                                            <span>Potong diterima</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-tile">
                                            <strong>{{ max(0, $donasi->estimated_items - $donasi->received_items) }}</strong>
                                            <span>Selisih dari perkiraan</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-tile">
                                            <strong class="text-accent">+{{ $donasi->points_awarded }}</strong>
                                            <span>Poin masuk</span>
                                        </div>
                                    </div>
                                </div>
                                @if($donasi->verified_at)
                                    <div class="info-line mt-3">
                                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                                        <span>Diperiksa {{ tanggal_id($donasi->verified_at) }} pukul {{ $donasi->verified_at->format('H:i') }}
                                            @if($donasi->verified_by) oleh {{ $donasi->verified_by }}@endif</span>
                                    </div>
                                @endif
                                @if($donasi->reject_reason)
                                    <div class="info-line"><i class="bi bi-info-circle" aria-hidden="true"></i><span>{{ $donasi->reject_reason }}</span></div>
                                @endif
                                <a href="{{ route('poin.index') }}" class="btn btn-outline-dark btn-sm mt-3">Lihat poin saya</a>
                            </div>
                        @else
                            <div class="cb-card">
                                <h2 class="h6">Jadwal dibatalkan</h2>
                                <p class="small text-muted mb-0">
                                    Jadwal donasi ini sudah dibatalkan. Anda dapat membuat jadwal baru kapan saja.
                                </p>
                                <a href="{{ route('donasi.index') }}" class="btn btn-outline-dark btn-sm mt-3">Buat jadwal baru</a>
                            </div>
                        @endif

                        <div class="cb-card">
                            <h2 class="h6">Langkah donasi</h2>
                            <ol class="steps mb-0">
                                <li class="done">Buat jadwal</li>
                                <li class="{{ in_array($status, ['check_in', 'menunggu_validasi', 'disetujui', 'ditolak']) ? 'done' : ($status === 'dijadwalkan' ? 'current' : '') }}">Datang ke toko</li>
                                <li class="{{ in_array($status, ['disetujui', 'ditolak']) ? 'done' : (in_array($status, ['check_in', 'menunggu_validasi']) ? 'current' : '') }}">Diperiksa staf</li>
                                <li class="{{ $status === 'disetujui' ? 'done' : '' }}">Poin masuk</li>
                            </ol>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="cb-card">
                            <h2 class="h6">Jadwal</h2>
                            @if($donasi->toko)
                                <p class="mb-1"><strong>{{ $donasi->toko->name }}</strong></p>
                                <div class="small text-muted">{{ $donasi->toko->address }}, {{ $donasi->toko->city }}</div>
                                @if($donasi->toko->floor)
                                    <div class="info-line mt-2"><i class="bi bi-building" aria-hidden="true"></i><span>{{ $donasi->toko->floor }}</span></div>
                                @endif
                                <div class="info-line">
                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                    <span>Buka {{ jam_singkat($donasi->toko->open_time) }} &ndash; {{ jam_singkat($donasi->toko->close_time) }}</span>
                                </div>
                            @endif
                            @if($donasi->slot)
                                <div class="info-line">
                                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                    <span>{{ tanggal_id($donasi->slot->date, true) }} &middot;
                                        {{ jam_singkat($donasi->slot->start_time) }} &ndash; {{ jam_singkat($donasi->slot->end_time) }}</span>
                                </div>
                            @endif
                            <div class="info-line">
                                <i class="bi bi-bag-heart" aria-hidden="true"></i>
                                <span>Perkiraan {{ $donasi->estimated_items }} potong &middot; {{ $poinPerPotong }} poin per potong</span>
                            </div>
                            @if($donasi->condition_note)
                                <div class="info-line"><i class="bi bi-chat-left-text" aria-hidden="true"></i><span>{{ $donasi->condition_note }}</span></div>
                            @endif
                        </div>

                        @if(in_array($status, ['dijadwalkan', 'check_in']))
                            <form action="{{ route('donasi.batal', $donasi) }}" method="POST" class="mt-3"
                                  data-confirm="Batalkan jadwal donasi {{ $donasi->donation_code }}?">
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
