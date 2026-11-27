@extends('layouts.admin')

@section('judul', __('Pembayaran'))

@section('isi')

<div class="ad-kartu">
    <div>
        <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Pembayaran Pesanan') }}</h2>
        <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
            {{ __('Status pembayaran cuma berubah sesuai jawaban Midtrans. Admin nggak bisa ubah manual, cuma bisa cek ulang status terbarunya.') }}
        </p>
    </div>

    <form class="mt-3" action="{{ route('admin.pembayaran.index') }}" method="GET">
        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="cari">{{ __('Cari kode pesanan') }}</label>
                <input class="form-control" type="search" id="cari" name="cari" value="{{ $cari }}" placeholder="{{ __('Contoh: CBX-20260101-0001') }}">
            </div>
            <div class="mb-3">
                <span class="form-label d-block">{{ __('Status pembayaran') }}</span>
                <div class="d-flex flex-wrap gap-1" role="group" aria-label="{{ __('Status pembayaran') }}">
                    <input class="btn-check" type="radio" name="status" id="status-semua" value="" autocomplete="off" @checked(blank($status))>
                    <label class="btn btn-outline-dark btn-sm" for="status-semua">{{ __('Semua') }}</label>
                    @foreach($daftarStatus as $pilihan)
                        <input class="btn-check" type="radio" name="status" id="status-{{ $pilihan }}" value="{{ $pilihan }}" autocomplete="off" @checked($status === $pilihan)>
                        <label class="btn btn-outline-dark btn-sm" for="status-{{ $pilihan }}">{{ __(ucfirst($pilihan)) }}</label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-2">
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Filter') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.pembayaran.index') }}">{{ __('Reset') }}</a>
        </div>
    </form>
</div>

<div class="ad-kartu">
    <div class="ad-gulir">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Pesanan') }}</th>
                <th>{{ __('Pelanggan') }}</th>
                <th>{{ __('Cara Bayar') }}</th>
                <th>{{ __('Nominal') }}</th>
                <th>{{ __('Status Midtrans') }}</th>
                <th>{{ __('Status Colorbox') }}</th>
                <th>{{ __('Dibayar') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $bayar)
                <tr>
                    <td>
                        @if($bayar->pesanan)
                            <a href="{{ route('admin.pesanan.show', $bayar->pesanan) }}">{{ $bayar->pesanan->order_code }}</a>
                        @else
                            <span style="color: var(--cb-ink-3)">{{ __('Pesanannya udah nggak ada') }}</span>
                        @endif
                    </td>
                    <td>{{ $bayar->pesanan?->pengguna?->name ?? '-' }}</td>
                    <td>{{ $bayar->payment_method ?: '-' }}</td>
                    <td>{{ rupiah($bayar->gross_amount) }}</td>
                    <td>{{ $bayar->transaction_status ?: '-' }}</td>
                    <td>
                        <span class="status {{ $bayar->payment_status === 'lunas' ? 'status-success' : 'status-warning' }}">
                            {{ __(ucfirst($bayar->payment_status)) }}
                        </span>
                    </td>
                    <td>{{ $bayar->paid_at ? tanggal_id($bayar->paid_at) : __('Belum') }}</td>
                    <td>
                        <form action="{{ route('admin.pembayaran.periksa', $bayar) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Cek Status ke Midtrans') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">{{ __('Belum ada pembayaran di filter ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
