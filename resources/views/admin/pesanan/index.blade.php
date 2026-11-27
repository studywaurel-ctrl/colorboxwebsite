@extends('layouts.admin')

@section('judul', __('Pesanan'))

@section('isi')

<div class="ad-kartu">
    <div>
        <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Pesanan Pelanggan') }}</h2>
        <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
            {{ __('Ada :p1 pesanan di filter ini.', ['p1' => $daftar->total()]) }}
        </p>
    </div>

    <form class="mt-3" action="{{ route('admin.pesanan.index') }}" method="GET">
        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="cari">{{ __('Cari kode pesanan, nama, atau email') }}</label>
                <input class="form-control" type="search" id="cari" name="cari" value="{{ $cari }}" placeholder="{{ __('Contoh: CBX-20260101-0001') }}">
            </div>
            <div class="mb-3">
                <span class="form-label d-block">{{ __('Status') }}</span>
                <div class="d-flex flex-wrap gap-1" role="group" aria-label="{{ __('Status') }}">
                    <input class="btn-check" type="radio" name="status" id="status-semua" value="" autocomplete="off" @checked(blank($status))>
                    <label class="btn btn-outline-dark btn-sm" for="status-semua">{{ __('Semua') }}</label>
                    @foreach($daftarStatus as $pilihan)
                        <input class="btn-check" type="radio" name="status" id="status-{{ $pilihan }}" value="{{ $pilihan }}" autocomplete="off" @checked($status === $pilihan)>
                        <label class="btn btn-outline-dark btn-sm" for="status-{{ $pilihan }}">{{ __(ucfirst(str_replace('_', ' ', $pilihan))) }}</label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-2">
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Filter') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.pesanan.index') }}">{{ __('Reset') }}</a>
        </div>
    </form>
</div>

<div class="ad-kartu">
    <div class="ad-gulir">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Kode') }}</th>
                <th>{{ __('Pelanggan') }}</th>
                <th>{{ __('Tanggal') }}</th>
                <th>{{ __('Metode') }}</th>
                <th>{{ __('Total') }}</th>
                <th>{{ __('Pembayaran') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $pesanan)
                <tr>
                    <td>{{ $pesanan->order_code }}</td>
                    <td>
                        {{ $pesanan->pengguna?->name ?? '-' }}<br>
                        <span style="color: var(--cb-ink-3); font-size: .78rem">{{ $pesanan->pengguna?->email ?? '-' }}</span>
                    </td>
                    <td>{{ tanggal_id($pesanan->created_at) }}</td>
                    <td>{{ $pesanan->method === 'ambil_toko' ? __('Ambil di toko') : __('Dikirim kurir') }}</td>
                    <td>{{ rupiah($pesanan->total) }}</td>
                    <td>
                        @if($pesanan->pembayaran)
                            <span class="status {{ $pesanan->pembayaran->payment_status === 'lunas' ? 'status-success' : 'status-warning' }}">
                                {{ __(ucfirst($pesanan->pembayaran->payment_status)) }}
                            </span>
                        @else
                            <span style="color: var(--cb-ink-3); font-size: .78rem">{{ __('Belum ada') }}</span>
                        @endif
                    </td>
                    <td><span class="status status-accent">{{ $pesanan->labelStatus() }}</span></td>
                    <td>
                        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.pesanan.show', $pesanan) }}">{{ __('Detail') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">{{ __('Belum ada pesanan di filter ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
