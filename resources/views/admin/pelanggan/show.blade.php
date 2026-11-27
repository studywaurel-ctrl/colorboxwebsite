@extends('layouts.admin')

@section('judul', __('Pelanggan').' '.$pelanggan->name)

@section('isi')

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">{{ $pelanggan->name }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                {{ __(':p1 · :p2 · gabung :p3', ['p1' => $pelanggan->email, 'p2' => $pelanggan->phone ?: __('tanpa nomor HP'), 'p3' => tanggal_id($pelanggan->created_at)]) }}
            </p>
        </div>
        <a class="btn btn-link btn-sm p-0" href="{{ route('admin.pelanggan.index') }}">{{ __('Kembali ke Daftar') }}</a>
    </div>

    <div class="ad-angka mt-3">
        <div>
            <p class="ad-angka__nilai mb-0">{{ $pelanggan->points }}</p>
            <p class="ad-angka__label mb-0">{{ __('Poin sekarang') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $pelanggan->member_level ?: '-' }}</p>
            <p class="ad-angka__label mb-0">{{ __('Level member') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $pesanan->count() }}</p>
            <p class="ad-angka__label mb-0">{{ __('Pesanan terakhir') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $alamat->count() }}</p>
            <p class="ad-angka__label mb-0">{{ __('Alamat tersimpan') }}</p>
        </div>
    </div>

    <div class="ad-gulir mt-3">
        <table class="ad-tabel">
            <tbody>
            <tr><th>{{ __('Tanggal lahir') }}</th><td>{{ $pelanggan->birth_date ? tanggal_id($pelanggan->birth_date) : '-' }}</td></tr>
            <tr><th>{{ __('Jenis kelamin') }}</th><td>{{ $pelanggan->gender ?: '-' }}</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Alamat') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Label') }}</th>
                <th>{{ __('Penerima') }}</th>
                <th>{{ __('No. HP') }}</th>
                <th>{{ __('Alamat') }}</th>
                <th>{{ __('Utama') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($alamat as $baris)
                <tr>
                    <td>{{ $baris->label }}</td>
                    <td>{{ $baris->recipient }}</td>
                    <td>{{ $baris->phone }}</td>
                    <td>{{ $baris->detail }}, {{ $baris->district }}, {{ $baris->city }}, {{ $baris->province }} {{ $baris->postal_code }}</td>
                    <td>{{ $baris->is_primary ? __('Ya') : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('Belum ada alamat tersimpan.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Pesanan Terakhir') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Kode') }}</th>
                <th>{{ __('Tanggal') }}</th>
                <th>{{ __('Metode') }}</th>
                <th>{{ __('Total') }}</th>
                <th>{{ __('Status') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($pesanan as $baris)
                <tr>
                    <td>{{ $baris->order_code }}</td>
                    <td>{{ tanggal_id($baris->created_at) }}</td>
                    <td>{{ $baris->method === 'ambil_toko' ? __('Ambil di toko') : __('Dikirim kurir') }}</td>
                    <td>{{ rupiah($baris->total) }}</td>
                    <td>{{ $baris->labelStatus() }}</td>
                    <td>
                        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.pesanan.show', $baris) }}">{{ __('Detail') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('Pelanggan ini belum pernah pesan.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Donasi') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Kode') }}</th>
                <th>{{ __('Toko') }}</th>
                <th>{{ __('Diterima') }}</th>
                <th>{{ __('Poin') }}</th>
                <th>{{ __('Status') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($donasi as $baris)
                <tr>
                    <td>{{ $baris->donation_code }}</td>
                    <td>{{ $baris->toko?->name ?? '-' }}</td>
                    <td>{{ $baris->received_items !== null ? $baris->received_items.' '.__('pcs') : '-' }}</td>
                    <td>{{ $baris->points_awarded ?: 0 }}</td>
                    <td>{{ __(ucfirst(str_replace('_', ' ', $baris->status))) }}</td>
                    <td>
                        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.donasi.show', $baris) }}">{{ __('Detail') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('Belum ada donasi dari pelanggan ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Retur') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Kode') }}</th>
                <th>{{ __('Jenis') }}</th>
                <th>{{ __('Tanggal') }}</th>
                <th>{{ __('Status') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($retur as $baris)
                <tr>
                    <td>{{ $baris->return_code }}</td>
                    <td>{{ $baris->type === 'tukar' ? __('Tukar barang') : __('Refund') }}</td>
                    <td>{{ tanggal_id($baris->created_at) }}</td>
                    <td>{{ __(ucfirst(str_replace('_', ' ', $baris->status))) }}</td>
                    <td>
                        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.retur.show', $baris) }}">{{ __('Detail') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('Belum ada retur dari pelanggan ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Riwayat Poin') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Tanggal') }}</th>
                <th>{{ __('Jenis') }}</th>
                <th>{{ __('Sumber') }}</th>
                <th>{{ __('Jumlah') }}</th>
                <th>{{ __('Catatan') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($riwayatPoin as $baris)
                <tr>
                    <td>{{ tanggal_id($baris->created_at) }}</td>
                    <td>{{ $baris->type === 'masuk' ? __('Poin masuk') : __('Poin dipakai') }}</td>
                    <td>{{ $baris->source }}</td>
                    <td>{{ $baris->amount }}</td>
                    <td>{{ $baris->note ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('Belum ada riwayat poin.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
