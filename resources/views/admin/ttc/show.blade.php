@extends('layouts.admin')

@section('judul', __('Booking').' '.$booking->booking_code)

@section('isi')

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Booking :p1', ['p1' => $booking->booking_code]) }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                {{ __('Tap Try and Collect, dibuat :p1.', ['p1' => tanggal_id($booking->created_at, true)]) }}
            </p>
        </div>
        <a class="btn btn-link btn-sm p-0" href="{{ route('admin.ttc.index') }}">{{ __('Kembali ke Daftar') }}</a>
    </div>

    <div class="ad-angka mt-3">
        <div>
            <p class="ad-angka__nilai mb-0">{{ __(ucfirst($booking->status)) }}</p>
            <p class="ad-angka__label mb-0">{{ __('Status booking') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $booking->item->count() }}</p>
            <p class="ad-angka__label mb-0">{{ __('Barang disiapkan') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $booking->purchase_code ?: '-' }}</p>
            <p class="ad-angka__label mb-0">{{ __('Kode pembelian kasir') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $booking->total_paid ? rupiah($booking->total_paid) : '-' }}</p>
            <p class="ad-angka__label mb-0">{{ __('Total dibayar di toko') }}</p>
        </div>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Info Booking') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <tbody>
            <tr>
                <th>{{ __('Pelanggan') }}</th>
                <td>
                    {{ $booking->pengguna?->name ?? '-' }}
                    @if($booking->pengguna)
                        <br><span style="color: var(--cb-ink-3); font-size: .78rem">{{ $booking->pengguna->email }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ __('Toko') }}</th>
                <td>{{ $booking->toko?->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ __('Jadwal nyoba') }}</th>
                <td>
                    @if($booking->slot)
                        {{ __(':p1, jam :p2–:p3', ['p1' => tanggal_id($booking->slot->date, true), 'p2' => jam_singkat($booking->slot->start_time), 'p3' => jam_singkat($booking->slot->end_time)]) }}
                    @else
                        {{ __('Jadwalnya udah nggak ada.') }}
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ __('Dicek oleh') }}</th>
                <td>{{ $booking->verified_by ?: '-' }}</td>
            </tr>
            <tr>
                <th>{{ __('Selesai pada') }}</th>
                <td>{{ $booking->completed_at ? tanggal_id($booking->completed_at, true) : '-' }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Barang yang Mau Dicoba') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Produk') }}</th>
                <th>{{ __('Varian') }}</th>
                <th>{{ __('Jumlah') }}</th>
                <th>{{ __('Jadi Dibeli') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($booking->item as $baris)
                <tr>
                    <td>{{ $baris->varian?->produk?->name ?? __('Produknya udah nggak ada') }}</td>
                    <td>{{ $baris->varian?->label() ?? '-' }}</td>
                    <td>{{ $baris->quantity }}</td>
                    <td>{{ $baris->purchased ? __('Ya') : __('Belum') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('Belum ada barang di booking ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Aksi Booking') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Status sekarang:') }} <strong>{{ __(ucfirst($booking->status)) }}</strong>{{ __('. Kalau booking dibatalkan, kuota slotnya balik lagi biar bisa dipakai pelanggan lain. Booking yang jadwalnya udah lewat otomatis jadi kedaluwarsa.') }}
    </p>

    @if(empty($aksiBerikutnya))
        <p class="alert alert-warning mb-0">
            @if(in_array($booking->status, ['selesai', 'dibatalkan', 'kedaluwarsa']))
                {{ __('Booking ini udah ditutup, statusnya nggak bisa diubah lagi.') }}
            @else
                {{ __('Nggak ada aksi manual untuk status ini.') }}
            @endif
        </p>
    @else
        @foreach($aksiBerikutnya as $aksi)
            @php
                $butuhKasir = $aksi['butuh_kasir'] ?? false;
                $bahaya = $aksi['bahaya'] ?? false;
            @endphp

            <form class="mt-3 pt-3" style="border-top: 1px solid var(--cb-line, #e5e5e5)"
                  action="{{ route('admin.ttc.status', $booking) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="{{ $aksi['status'] }}">

                @if($butuhKasir)
                    <div class="row g-3">
                        <div class="mb-3">
                            <label class="form-label" for="purchase_code">{{ __('Kode pembelian kasir') }}</label>
                            <input class="form-control" type="text" id="purchase_code" name="purchase_code" maxlength="40"
                                   value="{{ old('purchase_code') }}" required>
                            <p class="form-text">{{ __('Wajib diisi sesuai struk kasir. Satu kode cuma bisa dipakai untuk satu booking.') }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="total_paid">{{ __('Total dibayar di toko (Rp)') }}</label>
                            <input class="form-control" type="number" id="total_paid" name="total_paid" min="1" max="1000000000"
                                   value="{{ old('total_paid') }}" required>
                        </div>
                    </div>
                @endif

                <button class="btn {{ $bahaya ? 'btn-outline-danger' : 'btn-primary' }} btn-sm" type="submit"
                        data-confirm="{{ __(':label untuk booking :kode? Pelanggan langsung dapat notifikasi dan aksi ini nggak bisa dibatalin sendiri.', ['label' => $aksi['label'], 'kode' => $booking->booking_code]) }}">
                    {{ $aksi['label'] }}
                </button>
            </form>
        @endforeach
    @endif
</div>

@endsection
