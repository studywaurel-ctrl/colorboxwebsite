{{-- Daftar barang yang dibawa ke checkout. Dipakai ulang di kolom ringkasan. --}}
<div class="cb-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0">{{ $item->sum('quantity') }} barang dipilih</h2>
        <a href="{{ route('keranjang.index') }}" class="small">Ubah keranjang</a>
    </div>

    @foreach($item as $baris)
        @php
            $varian = $baris->varian;
            $produk = $varian ? $varian->produk : null;
            $gambar = $produk ? ($produk->gambar->firstWhere('color', $varian->color) ?: $produk->gambar->first()) : null;
            $harga = $produk ? $produk->hargaJual() : 0;
        @endphp
        <div class="d-flex gap-3 mb-3">
            <img src="{{ gambar_produk($gambar ? $gambar->path : null) }}" alt="" class="mini-thumb">
            <div class="small flex-fill">
                <div class="fw-600">{{ $produk ? $produk->name : 'Produk tidak tersedia' }}</div>
                <div class="text-muted">{{ $varian ? $varian->label() : '-' }} · {{ $baris->quantity }} potong</div>
            </div>
            <div class="small fw-600 text-nowrap">{{ rupiah($harga * $baris->quantity) }}</div>
        </div>
    @endforeach
</div>
