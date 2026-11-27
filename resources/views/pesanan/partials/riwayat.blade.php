{{-- Jalur status pesanan (track-steps) beserta catatan kejadiannya (event-log). --}}
@php
    // Urutan langkah menyesuaikan cara menerima barang yang dipilih pelanggan.
    $urutan = $pesanan->method === 'ambil_toko'
        ? ['menunggu_pembayaran', 'dibayar', 'diproses', 'dikemas', 'siap_diambil', 'diterima', 'selesai']
        : ['menunggu_pembayaran', 'dibayar', 'diproses', 'dikemas', 'dikirim', 'diterima', 'selesai'];

    $namaLangkah = [
        'menunggu_pembayaran' => 'Menunggu Pembayaran',
        'dibayar' => 'Pembayaran Diterima',
        'diproses' => 'Pesanan Diproses',
        'dikemas' => 'Pesanan Dikemas',
        'dikirim' => 'Dikirim Kurir',
        'siap_diambil' => 'Siap Diambil',
        'diterima' => 'Barang Diterima',
        'selesai' => 'Pesanan Selesai',
    ];

    // Waktu tiap langkah diambil dari riwayat pelacakan yang tercatat.
    $waktuLangkah = [];
    foreach ($riwayat as $kejadian) {
        if (! isset($waktuLangkah[$kejadian->status])) {
            $waktuLangkah[$kejadian->status] = $kejadian->happened_at;
        }
    }

    $posisi = array_search($pesanan->status, $urutan, true);
    $dibatalkan = $pesanan->status === 'dibatalkan';
@endphp

@if(! $dibatalkan)
    <div class="track-steps" role="list" aria-label="Status pesanan"
         style="grid-template-columns: repeat({{ count($urutan) }}, 1fr)">
        @foreach($urutan as $nomor => $langkah)
            @php
                $sudah = $posisi !== false && $nomor < $posisi;
                $sekarang = $posisi !== false && $nomor === $posisi;
            @endphp
            <div class="ts {{ $sudah ? 'done' : '' }} {{ $sekarang ? 'current' : '' }}" role="listitem"
                 @if($sekarang) aria-current="step" @endif>
                <span class="dot" aria-hidden="true">
                    @if($sudah)<i class="bi bi-check-lg"></i>@else{{ $nomor + 1 }}@endif
                </span>
                <span>
                    <span class="d-block fw-600">{{ $namaLangkah[$langkah] }}</span>
                    @if(isset($waktuLangkah[$langkah]))
                        <time>{{ tanggal_id($waktuLangkah[$langkah]) }} · {{ $waktuLangkah[$langkah]->format('H:i') }}</time>
                    @endif
                </span>
            </div>
        @endforeach
    </div>
@endif

@if($riwayat->isNotEmpty())
    <h3 class="h6 mt-4">Riwayat</h3>
    <ul class="event-log">
        @foreach($riwayat as $kejadian)
            <li>
                <div class="fw-600">{{ $namaLangkah[$kejadian->status] ?? ucfirst(str_replace('_', ' ', $kejadian->status)) }}</div>
                <div class="small text-muted">{{ $kejadian->description }}</div>
                <div class="small text-soft">
                    {{ tanggal_id($kejadian->happened_at, true) }} pukul {{ $kejadian->happened_at->format('H:i') }}
                </div>
            </li>
        @endforeach
    </ul>
@endif
