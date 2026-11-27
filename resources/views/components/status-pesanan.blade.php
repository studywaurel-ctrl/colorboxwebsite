@props(['status', 'label' => null])

{{-- Lencana status pesanan. Warnanya ditulis satu kali di sini supaya seluruh
     halaman pesanan memakai nada warna yang sama. --}}
@php
    $nada = [
        'menunggu_pembayaran' => 'warning',
        'dibayar' => 'info',
        'diproses' => 'info',
        'dikemas' => 'info',
        'dikirim' => 'info',
        'siap_diambil' => 'accent',
        'diterima' => 'success',
        'selesai' => 'success',
        'dibatalkan' => 'danger',
    ];
    $tulisan = [
        'menunggu_pembayaran' => 'Menunggu Pembayaran',
        'dibayar' => 'Pembayaran Diterima',
        'diproses' => 'Pesanan Diproses',
        'dikemas' => 'Pesanan Dikemas',
        'dikirim' => 'Dikirim Kurir',
        'siap_diambil' => 'Siap Diambil di Toko',
        'diterima' => 'Diterima Pelanggan',
        'selesai' => 'Pesanan Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];
@endphp

<span class="status status-{{ $nada[$status] ?? 'muted' }}">
    {{ $label ?: ($tulisan[$status] ?? ucfirst(str_replace('_', ' ', $status))) }}
</span>
