@props(['produk', 'disukai' => null])

@php
    // Kartu produk dipakai di beranda, katalog, wishlist, dan halaman toko.
    // Penanda wishlist dihitung di sini bila tidak dikirim dari luar.
    if ($disukai === null) {
        $disukai = auth()->check()
            ? \App\Models\Wishlist::where('user_id', auth()->id())->where('product_id', $produk->id)->exists()
            : false;
    }

    $gambar = $produk->gambar->sortBy('sort_order');
    $gambarUtama = $gambar->first();
    $gambarKedua = $gambar->skip(1)->first();

    $adaDiskon = $produk->discount_price && $produk->discount_price < $produk->price;
    $hemat = $adaDiskon ? (int) round(($produk->price - $produk->discount_price) / $produk->price * 100) : 0;

    $stok = $produk->totalStok();
    $warna = $produk->varian->unique('color');
@endphp

<article class="product-card">
    <div class="tags">
        @if($adaDiskon)<span class="tag tag-accent">&minus;{{ $hemat }}%</span>@endif
        @if($produk->is_new)<span class="tag">Baru</span>@endif
        @if($stok < 1)<span class="tag tag-dark">Stok Habis</span>@endif
    </div>

    @auth
        <form action="{{ route('wishlist.store') }}" method="POST">
            @csrf
            <input type="hidden" name="product_id" value="{{ $produk->id }}">
            <button type="submit" class="btn-icon wish-btn {{ $disukai ? 'is-active' : '' }}"
                    aria-pressed="{{ $disukai ? 'true' : 'false' }}"
                    aria-label="{{ $disukai ? 'Keluarkan '.$produk->name.' dari wishlist' : 'Simpan '.$produk->name.' ke wishlist' }}">
                <i class="bi bi-heart"></i><i class="bi bi-heart-fill"></i>
            </button>
        </form>
    @else
        <a href="{{ route('login') }}" class="btn-icon wish-btn" aria-label="Masuk untuk menyimpan {{ $produk->name }} ke wishlist">
            <i class="bi bi-heart"></i><i class="bi bi-heart-fill"></i>
        </a>
    @endauth

    <a href="{{ route('produk.show', $produk) }}" class="thumb">
        <img src="{{ gambar_produk($gambarUtama ? $gambarUtama->path : null) }}" alt="{{ $produk->name }}"
             loading="lazy" width="400" height="500">
        @if($gambarKedua)
            <img src="{{ gambar_produk($gambarKedua->path) }}" alt="" class="alt" loading="lazy" width="400" height="500">
        @endif
    </a>

    <div class="meta mt-2">{{ $produk->kategori ? $produk->kategori->name : 'Colorbox' }}</div>
    <h3 class="name"><a href="{{ route('produk.show', $produk) }}">{{ $produk->name }}</a></h3>

    <div class="price">
        {{ rupiah($produk->hargaJual()) }}
        @if($adaDiskon)<span class="was">{{ rupiah($produk->price) }}</span>@endif
    </div>

    @if($warna->count() > 1)
        <div class="swatches" aria-label="{{ $warna->count() }} pilihan warna">
            @foreach($warna->take(5) as $varian)
                <span class="swatch-dot" style="background: {{ $varian->color_hex ?: '#e8e4de' }}" title="{{ $varian->color }}"></span>
            @endforeach
        </div>
    @endif
</article>
