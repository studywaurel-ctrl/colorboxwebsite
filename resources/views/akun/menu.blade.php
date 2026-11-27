{{-- Menu samping halaman akun. Dipakai ulang oleh seluruh halaman akun. --}}
@php
    // Menu ditulis sebagai array supaya tidak berulang dan mudah ditambah.
    // Baris berisi "bagian" dipakai sebagai judul kelompok, bukan tautan.
    $menuAkun = [
        ['bagian' => 'Akun'],
        ['rute' => 'akun.index', 'ikon' => 'grid', 'label' => 'Akun Saya', 'aktif' => request()->routeIs('akun.index')],
        ['rute' => 'profil.edit', 'ikon' => 'person', 'label' => 'Profil', 'aktif' => request()->routeIs('profil.edit')],
        ['rute' => 'alamat.index', 'ikon' => 'geo-alt', 'label' => 'Alamat Pengiriman', 'aktif' => request()->routeIs('alamat.*')],
        ['bagian' => 'Belanja'],
        ['rute' => 'pesanan.index', 'ikon' => 'receipt', 'label' => 'Pesanan Saya', 'aktif' => request()->routeIs('pesanan.*')],
        ['rute' => 'retur.index', 'ikon' => 'arrow-left-right', 'label' => 'Retur & Penukaran', 'aktif' => request()->routeIs('retur.*')],
        ['bagian' => 'Layanan'],
        ['rute' => 'poin.index', 'ikon' => 'coin', 'label' => 'Poin Saya', 'aktif' => request()->routeIs('poin.*')],
        ['rute' => 'donasi.riwayat', 'ikon' => 'recycle', 'label' => 'Riwayat Donasi', 'aktif' => request()->routeIs('donasi.riwayat', 'donasi.show')],
        ['rute' => 'ttc.riwayat', 'ikon' => 'hand-index', 'label' => 'Riwayat Tap Try and Collect', 'aktif' => request()->routeIs('ttc.riwayat', 'ttc.show')],
        ['bagian' => 'Bantuan'],
        ['rute' => 'notifikasi.index', 'ikon' => 'bell', 'label' => 'Notifikasi', 'aktif' => request()->routeIs('notifikasi.*')],
        ['rute' => 'keluhan.index', 'ikon' => 'chat-dots', 'label' => 'Keluhan', 'aktif' => request()->routeIs('keluhan.*')],
    ];
@endphp

<div class="d-lg-none mb-2">
    <button class="btn btn-outline-dark btn-sm w-100 d-flex justify-content-between align-items-center" type="button"
            data-bs-toggle="collapse" data-bs-target="#menuAkun" aria-expanded="false" aria-controls="menuAkun">
        <span><i class="bi bi-person-circle me-2" aria-hidden="true"></i>Menu akun</span>
        <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </button>
</div>

<nav class="account-nav collapse d-lg-block" id="menuAkun" aria-label="Menu akun">
    @foreach($menuAkun as $butir)
        @if(isset($butir['bagian']))
            <div class="nav-label">{{ $butir['bagian'] }}</div>
        @else
            <a href="{{ route($butir['rute']) }}" class="{{ $butir['aktif'] ? 'active' : '' }}"
               @if($butir['aktif']) aria-current="page" @endif>
                <i class="bi bi-{{ $butir['ikon'] }}" aria-hidden="true"></i>{{ $butir['label'] }}
            </a>
        @endif
    @endforeach

    <form method="post" action="{{ route('logout') }}" class="mt-1">
        @csrf
        <button type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Keluar</button>
    </form>
</nav>
