@extends('layouts.app')

@section('judul', 'Wishlist')

@section('isi')
<div class="container-xl section-sm">
    <x-remah :tautan="['Wishlist' => null]" />

    <h1 class="page-title">Wishlist</h1>
    <p class="text-muted">
        {{ $daftar->count() }} produk tersimpan. Wishlist membantu Anda menandai produk yang ingin dibeli nanti.
    </p>

    @if($daftar->count() > 0)
        <div class="row g-3 g-md-4">
            @foreach($daftar as $baris)
                @if($baris->produk)
                    <div class="col-6 col-md-3">
                        <x-kartu-produk :produk="$baris->produk" :disukai="true" />
                        <form action="{{ route('wishlist.destroy', $baris->produk) }}" method="POST" class="mt-2"
                              data-confirm="Keluarkan produk ini dari wishlist?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-sm w-100">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i>Keluarkan
                            </button>
                        </form>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <x-kosong ikon="heart" judul="Wishlist masih kosong"
                  teks="Tekan ikon hati pada produk yang Anda sukai, lalu produknya akan muncul di halaman ini.">
            <a href="{{ route('produk.index') }}" class="btn btn-dark">Mulai belanja</a>
        </x-kosong>
    @endif
</div>
@endsection
