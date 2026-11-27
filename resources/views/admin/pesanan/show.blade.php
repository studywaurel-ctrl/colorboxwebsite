@extends('layouts.admin')

@section('judul', __('Pesanan').' '.$pesanan->order_code)

@section('isi')

<div class="ad-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Pesanan :p1', ['p1' => $pesanan->order_code]) }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                {{ __('Dibuat :p1.', ['p1' => tanggal_id($pesanan->created_at, true)]) }}
            </p>
        </div>
        <a class="btn btn-link btn-sm p-0" href="{{ route('admin.pesanan.index') }}">{{ __('Kembali ke Daftar') }}</a>
    </div>

    <div class="ad-angka mt-3">
        <div>
            <p class="ad-angka__nilai mb-0">{{ $pesanan->labelStatus() }}</p>
            <p class="ad-angka__label mb-0">{{ __('Status pesanan') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ rupiah($pesanan->total) }}</p>
            <p class="ad-angka__label mb-0">{{ __('Total pesanan') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $pesanan->method === 'ambil_toko' ? __('Ambil di toko') : __('Dikirim kurir') }}</p>
            <p class="ad-angka__label mb-0">{{ __('Cara terima barang') }}</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $pesanan->item->count() }}</p>
            <p class="ad-angka__label mb-0">{{ __('Jenis barang') }}</p>
        </div>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Pelanggan dan Pengiriman') }}</h2>

    <div class="row g-3 mt-2">
        <div>
            <h3 style="font-size: var(--cb-h5)">{{ __('Pelanggan') }}</h3>
            <p class="mb-1">{{ $pesanan->pengguna?->name ?? '-' }}</p>
            <p class="mb-1" style="color: var(--cb-ink-2); font-size: .875rem">
                {{ $pesanan->pengguna?->email ?? '-' }}<br>
                {{ $pesanan->pengguna?->phone ?? __('Tanpa nomor HP') }}
            </p>
            @if($pesanan->pengguna)
                <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.pelanggan.show', $pesanan->pengguna) }}">{{ __('Lihat Pelanggan') }}</a>
            @endif
        </div>
        <div>
            @if($pesanan->method === 'ambil_toko')
                <h3 style="font-size: var(--cb-h5)">{{ __('Diambil di Toko') }}</h3>
                <p class="mb-1">{{ $pesanan->toko?->name ?? __('Toko belum dipilih') }}</p>
                <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                    {{ $pesanan->toko?->address ?? '-' }}
                </p>
            @else
                <h3 style="font-size: var(--cb-h5)">{{ __('Alamat Pengiriman') }}</h3>
                @if($pesanan->alamat)
                    <p class="mb-1">{{ $pesanan->alamat->recipient }} ({{ $pesanan->alamat->phone }})</p>
                    <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                        {{ $pesanan->alamat->detail }},
                        {{ $pesanan->alamat->district }}, {{ $pesanan->alamat->city }},
                        {{ $pesanan->alamat->province }} {{ $pesanan->alamat->postal_code }}
                    </p>
                @else
                    <p class="mb-0">{{ __('Alamat pengiriman nggak tercatat.') }}</p>
                @endif
            @endif
        </div>
    </div>

    @if($pesanan->note)
        <p class="alert alert-warning mt-3 mb-0">{{ __('Catatan dari pelanggan: :p1', ['p1' => $pesanan->note]) }}</p>
    @endif
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Barang yang Dipesan') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Produk') }}</th>
                <th>{{ __('Warna') }}</th>
                <th>{{ __('Ukuran') }}</th>
                <th>{{ __('Harga') }}</th>
                <th>{{ __('Jumlah') }}</th>
                <th>{{ __('Subtotal') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($pesanan->item as $baris)
                <tr>
                    <td>{{ $baris->product_name }}</td>
                    <td>{{ $baris->color ?: '-' }}</td>
                    <td>{{ $baris->size ?: '-' }}</td>
                    <td>{{ rupiah($baris->price) }}</td>
                    <td>{{ $baris->quantity }}</td>
                    <td>{{ rupiah($baris->subtotal) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('Belum ada barang di pesanan ini.') }}</td></tr>
            @endforelse
            </tbody>
            <tfoot>
            <tr>
                <th colspan="5">{{ __('Subtotal') }}</th>
                <th>{{ rupiah($pesanan->subtotal) }}</th>
            </tr>
            <tr>
                <th colspan="5">{{ __('Ongkos kirim') }}</th>
                <th>{{ rupiah($pesanan->shipping_cost) }}</th>
            </tr>
            <tr>
                <th colspan="5">{{ __('Potongan voucher') }}</th>
                <th>{{ rupiah($pesanan->discount) }}</th>
            </tr>
            <tr>
                <th colspan="5">{{ __('Poin dipakai') }}</th>
                <th>{{ __(':p1 poin', ['p1' => $pesanan->points_used]) }}</th>
            </tr>
            <tr>
                <th colspan="5">{{ __('Total dibayar') }}</th>
                <th>{{ rupiah($pesanan->total) }}</th>
            </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Pembayaran') }}</h2>

    @if($pesanan->pembayaran)
        <div class="ad-gulir mt-2">
            <table class="ad-tabel">
                <tbody>
                <tr>
                    <th>{{ __('Status pembayaran') }}</th>
                    <td>{{ __(ucfirst($pesanan->pembayaran->payment_status)) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Status Midtrans') }}</th>
                    <td>{{ $pesanan->pembayaran->transaction_status ?: '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Cara bayar') }}</th>
                    <td>{{ $pesanan->pembayaran->payment_method ?: '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Nomor transaksi') }}</th>
                    <td>{{ $pesanan->pembayaran->transaction_id ?: '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Nilai tagihan') }}</th>
                    <td>{{ rupiah($pesanan->pembayaran->gross_amount) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Dibayar pada') }}</th>
                    <td>{{ $pesanan->pembayaran->paid_at ? tanggal_id($pesanan->pembayaran->paid_at, true) : __('Belum dibayar') }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <p class="mt-2 mb-2" style="color: var(--cb-ink-2); font-size: .875rem">
            {{ __('Status pembayaran cuma berubah sesuai jawaban Midtrans, nggak pernah diubah manual.') }}
        </p>
        <form action="{{ route('admin.pembayaran.periksa', $pesanan->pembayaran) }}" method="POST">
            @csrf
            @method('PUT')
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Cek Status ke Midtrans') }}</button>
        </form>
    @else
        <p class="mb-0">{{ __('Pesanan ini belum punya data pembayaran.') }}</p>
    @endif
</div>

@if($pesanan->pengiriman)
    <div class="ad-kartu">
        <h2 style="font-size: 1.05rem">{{ __('Pengiriman') }}</h2>
        <div class="ad-gulir mt-2">
            <table class="ad-tabel">
                <tbody>
                <tr><th>{{ __('Kurir') }}</th><td>{{ $pesanan->pengiriman->courier ?: '-' }}</td></tr>
                <tr><th>{{ __('Layanan') }}</th><td>{{ $pesanan->pengiriman->service ?: '-' }}</td></tr>
                <tr><th>{{ __('Nomor resi') }}</th><td>{{ $pesanan->pengiriman->tracking_number ?: __('Belum ada') }}</td></tr>
                <tr><th>{{ __('Dikirim pada') }}</th><td>{{ $pesanan->pengiriman->shipped_at ? tanggal_id($pesanan->pengiriman->shipped_at, true) : '-' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Aksi Pesanan') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Status sekarang:') }} <strong>{{ $pesanan->labelStatus() }}</strong>{{ __('. Setiap aksi tercatat di riwayat dan pelanggan langsung dapat notifikasi.') }}
    </p>

    @if(empty($aksiBerikutnya))
        <p class="alert alert-warning mb-0">
            {{ __('Nggak ada aksi manual untuk status ini. Perubahan berikutnya otomatis dari sistem (pembayaran dari Midtrans, perjalanan paket dari pelacakan kurir).') }}
        </p>
    @else
        @foreach($aksiBerikutnya as $aksi)
            @php
                $butuhResi = $aksi['butuh_resi'] ?? false;
                $butuhAlasan = $aksi['butuh_alasan'] ?? false;
                $bahaya = $aksi['bahaya'] ?? false;
                $kurirTercatat = $pesanan->pengiriman?->courier;
            @endphp

            <form class="mt-3 pt-3" style="border-top: 1px solid var(--cb-line, #e5e5e5)"
                  action="{{ route('admin.pesanan.status', $pesanan) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="{{ $aksi['status'] }}">

                @if($butuhResi)
                    @if($kurirTercatat)
                        <p class="mb-2">
                            {{ __('Kurir pilihan pelanggan:') }}
                            <strong>{{ $pesanan->pengiriman->courier }} {{ $pesanan->pengiriman->service }}</strong>
                        </p>
                    @else
                        <div class="row g-3">
                            <div class="mb-3">
                                <label class="form-label" for="courier">{{ __('Kurir') }}</label>
                                <input class="form-control" type="text" id="courier" name="courier" maxlength="40"
                                       value="{{ old('courier') }}" placeholder="JNE" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="service">{{ __('Layanan kurir') }}</label>
                                <input class="form-control" type="text" id="service" name="service" maxlength="60"
                                       value="{{ old('service') }}" placeholder="REG" required>
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label" for="tracking_number">{{ __('Nomor resi') }}</label>
                        <input class="form-control" type="text" id="tracking_number" name="tracking_number" maxlength="60"
                               value="{{ old('tracking_number') }}" required>
                        <p class="form-text">{{ __('Wajib diisi sesuai bukti serah terima dari kurir. Resinya akan dicek ke pelacakan kurir.') }}</p>
                    </div>
                @endif

                @if($butuhAlasan)
                    <div class="mb-3">
                        <label class="form-label" for="alasan">{{ __('Alasan') }}</label>
                        <input class="form-control" type="text" id="alasan" name="alasan" maxlength="191"
                               value="{{ old('alasan') }}" placeholder="{{ __('Wajib diisi') }}" required>
                        <p class="form-text">{{ __('Alasan ini tersimpan permanen di riwayat dan bisa dibaca pelanggan.') }}</p>
                    </div>
                @endif

                <button class="btn {{ $bahaya ? 'btn-outline-danger' : 'btn-primary' }} btn-sm" type="submit"
                        data-confirm="{{ __(':label untuk pesanan :kode? Pelanggan langsung dapat notifikasi dan aksi ini nggak bisa dibatalin sendiri.', ['label' => $aksi['label'], 'kode' => $pesanan->order_code]) }}">
                    {{ $aksi['label'] }}
                </button>
            </form>
        @endforeach
    @endif
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Riwayat Status') }}</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Waktu') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Keterangan') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($riwayat as $langkah)
                <tr>
                    <td>{{ tanggal_id($langkah->happened_at, true) }}</td>
                    <td>{{ __(ucfirst(str_replace('_', ' ', $langkah->status))) }}</td>
                    <td>{{ $langkah->description }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('Belum ada riwayat status.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
