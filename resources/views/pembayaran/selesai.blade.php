@extends('layouts.app')

@section('judul', 'Status Pembayaran '.$pesanan->order_code)

@section('isi')
    @php
        $lunas = $pembayaran->payment_status === 'lunas';
        $gagal = in_array($pembayaran->payment_status, ['gagal', 'kedaluwarsa', 'dibatalkan']);
    @endphp

    <div class="container-xl section-sm" style="max-width: 820px">
        <x-langkah-belanja :aktif="$lunas ? 'selesai' : 'pembayaran'" />

        <div class="cb-card text-center py-5">
            @if($lunas)
                <span class="avatar lg mx-auto mb-3"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                <h1 class="h3">Pembayaran Anda sudah kami terima</h1>
                <p class="text-muted mb-1">
                    Nomor pesanan <span class="code-pill">{{ $pesanan->order_code }}</span>.
                    Simpan nomor ini untuk melacak pesanan maupun mengajukan retur bila diperlukan.
                </p>
            @elseif($gagal)
                <span class="avatar lg mx-auto mb-3"><i class="bi bi-x-lg" aria-hidden="true"></i></span>
                <h1 class="h3">Pembayaran belum berhasil</h1>
                <p class="text-muted mb-1">
                    Status pembayaran pesanan <span class="code-pill">{{ $pesanan->order_code }}</span> tercatat
                    {{ str_replace('_', ' ', $pembayaran->payment_status) }}. Stok yang sempat dikunci sudah
                    dikembalikan dan pesanan ini ditutup.
                </p>
            @else
                <span class="avatar lg mx-auto mb-3"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <h1 class="h3">Pembayaran sedang ditunggu</h1>
                <p class="text-muted mb-1">
                    Pembayaran pesanan <span class="code-pill">{{ $pesanan->order_code }}</span> belum tercatat lunas.
                    Bila Anda sudah membayar, status akan berubah sendiri begitu jawaban Midtrans kami terima.
                </p>
            @endif

            <p class="small text-muted">Total tagihan {{ rupiah($pembayaran->gross_amount) }}</p>

            <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                <a href="{{ route('pesanan.show', $pesanan) }}" class="btn btn-primary">Rincian Pesanan</a>
                @if($lunas)
                    <a href="{{ route('lacak.index', ['kode' => $pesanan->order_code]) }}" class="btn btn-outline-dark">
                        <i class="bi bi-truck me-1" aria-hidden="true"></i>Lacak Pesanan
                    </a>
                @elseif(! $gagal)
                    <a href="{{ route('pembayaran.show', $pembayaran) }}" class="btn btn-outline-dark">
                        Kembali ke Halaman Pembayaran
                    </a>
                @else
                    <form action="{{ route('pesanan.beli-lagi', $pesanan) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-dark">Beli Lagi</button>
                    </form>
                @endif
                <a href="{{ route('produk.index') }}" class="btn btn-light">Lanjut belanja</a>
            </div>
        </div>

        <div class="cb-card">
            <h2 class="h6 mb-3">Barang pada pesanan ini</h2>
            @foreach($pesanan->item as $baris)
                <div class="d-flex gap-3 mb-3">
                    <img src="{{ gambar_produk($baris->image_path) }}" alt="" class="mini-thumb">
                    <div class="flex-fill small">
                        <div class="fw-600">{{ $baris->product_name }}</div>
                        <div class="text-muted">{{ $baris->color }} · {{ $baris->size }} · {{ $baris->quantity }} potong</div>
                    </div>
                    <div class="fw-600 small">{{ rupiah($baris->subtotal) }}</div>
                </div>
            @endforeach
            <div class="summary-row total"><span>Total</span><span>{{ rupiah($pesanan->total) }}</span></div>
        </div>
    </div>
@endsection
