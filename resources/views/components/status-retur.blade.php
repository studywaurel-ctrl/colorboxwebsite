@props(['status'])

{{-- Lencana status pengajuan retur dan penukaran. --}}
@php
    $nada = [
        'menunggu_validasi' => 'warning',
        'disetujui' => 'info',
        'dikirim' => 'info',
        'diperiksa' => 'info',
        'diproses' => 'info',
        'selesai' => 'success',
        'ditolak' => 'danger',
        'dibatalkan' => 'muted',
    ];
    $tulisan = [
        'menunggu_validasi' => 'Menunggu Validasi',
        'disetujui' => 'Disetujui',
        'dikirim' => 'Barang Dikirim Pelanggan',
        'diperiksa' => 'Sedang Diperiksa',
        'diproses' => 'Sedang Diproses',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
    ];
@endphp

<span class="status status-{{ $nada[$status] ?? 'muted' }}">
    {{ $tulisan[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
</span>
