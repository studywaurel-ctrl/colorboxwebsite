@props(['aktif'])

{{-- Urutan langkah belanja. Project ini memakai satu halaman checkout, jadi
     langkahnya lebih ringkas daripada alur lama yang terpisah-pisah. --}}
@php
    $langkah = [
        'keranjang' => 'Keranjang',
        'checkout' => 'Checkout',
        'pembayaran' => 'Pembayaran',
        'selesai' => 'Pesanan Dibuat',
    ];
    $kunci = array_keys($langkah);
    $posisi = array_search($aktif, $kunci, true);
@endphp

<ol class="steps ps-0" aria-label="Langkah belanja">
    @foreach($langkah as $nama => $label)
        @php $urutan = array_search($nama, $kunci, true); @endphp
        <li class="{{ $urutan < $posisi ? 'done' : ($urutan === $posisi ? 'current' : '') }}"
            @if($urutan === $posisi) aria-current="step" @endif>{{ $label }}</li>
    @endforeach
</ol>
