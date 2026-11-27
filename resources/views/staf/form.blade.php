{{-- Halaman verifikasi staf toko. Dibuka staf setelah memindai QR pada layar
     pelanggan, jadi dibuat berdiri sendiri tanpa menu belanja pelanggan. --}}
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Verifikasi Staf · {{ $kode }}</title>
    <link rel="icon" href="{{ asset('images/logo/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $judulJenis = ['donasi' => 'Donasi Pakaian', 'ttc' => 'Tap Try and Collect', 'retur' => 'Retur di Toko'];
    $kodeData = $jenis === 'donasi' ? $data->donation_code : ($jenis === 'ttc' ? $data->booking_code : $data->return_code);
    $sudahSelesai = $jenis === 'donasi'
        ? in_array($data->status, ['disetujui', 'ditolak', 'dibatalkan'])
        : ($jenis === 'ttc'
            ? in_array($data->status, ['selesai', 'dibatalkan', 'kedaluwarsa'])
            : in_array($data->status, ['selesai', 'ditolak', 'dibatalkan']));
@endphp

<div class="container py-4" style="max-width: 560px">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <span class="brand">
            <img src="{{ asset('images/logo/colorbox-mark.png') }}" alt="" class="brand-mark-img" aria-hidden="true">
            <img src="{{ asset('images/logo/colorbox-wordmark.png') }}" alt="Colorbox" class="brand-word">
        </span>
        <span class="chip"><i class="bi bi-person-badge me-1" aria-hidden="true"></i>Verifikasi Staf</span>
    </div>

    @if(session('berhasil'))
        <div class="alert alert-success" role="status">{{ session('berhasil') }}</div>
    @endif
    @if(session('gagal'))
        <div class="alert alert-danger" role="alert">{{ session('gagal') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong class="d-block mb-1">Periksa kembali isian berikut:</strong>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="cb-card">
        <div class="small text-muted">{{ $judulJenis[$jenis] ?? 'Verifikasi' }}</div>
        <h1 class="h4 mb-1">{{ $kodeData }}</h1>
        <div class="small">
            {{ $data->pengguna ? $data->pengguna->name : 'Pelanggan tidak diketahui' }}
            &middot; {{ $data->toko ? $data->toko->name : 'Toko tidak diketahui' }}
        </div>
        @if($jenis !== 'retur' && $data->slot)
            <div class="small text-muted">
                {{ tanggal_id($data->slot->date, true) }} &middot;
                {{ jam_singkat($data->slot->start_time) }} &ndash; {{ jam_singkat($data->slot->end_time) }}
            </div>
        @endif
        <div class="mt-2">
            <span class="status status-info">{{ ucfirst(str_replace('_', ' ', $data->status)) }}</span>
        </div>
    </div>

    {{-- Rincian yang perlu dilihat staf sebelum memutuskan --}}
    <div class="cb-card">
        @if($jenis === 'donasi')
            <h2 class="h6">Rincian donasi</h2>
            <div class="info-line"><i class="bi bi-bag-heart" aria-hidden="true"></i><span>Perkiraan pelanggan {{ $data->estimated_items }} potong</span></div>
            <div class="info-line"><i class="bi bi-chat-left-text" aria-hidden="true"></i><span>{{ $data->condition_note ?: 'Tidak ada catatan kondisi' }}</span></div>
            <div class="info-line"><i class="bi bi-coin" aria-hidden="true"></i><span>Setiap potong yang diterima bernilai {{ $poinPerPotong }} poin</span></div>
        @elseif($jenis === 'ttc')
            <h2 class="h6">Produk yang dicoba</h2>
            @forelse($data->item as $item)
                <div class="info-line">
                    <i class="bi bi-box-seam" aria-hidden="true"></i>
                    <span>
                        {{ $item->varian && $item->varian->produk ? $item->varian->produk->name : 'Produk' }}
                        &middot; {{ $item->varian ? $item->varian->label() : '-' }}
                        &middot; {{ $item->varian && $item->varian->produk ? rupiah($item->varian->produk->hargaJual()) : '-' }}
                    </span>
                </div>
            @empty
                <p class="small text-muted mb-0">Tidak ada produk pada jadwal ini.</p>
            @endforelse
        @else
            <h2 class="h6">Rincian retur</h2>
            <div class="info-line"><i class="bi bi-arrow-left-right" aria-hidden="true"></i><span>{{ ucfirst($data->type) }} &middot; {{ ucfirst($data->method) }}</span></div>
            <div class="info-line"><i class="bi bi-chat-left-text" aria-hidden="true"></i><span>{{ $data->reason ?: 'Tidak ada alasan yang ditulis' }}</span></div>
            @forelse($data->item as $item)
                <div class="info-line"><i class="bi bi-box-seam" aria-hidden="true"></i><span>{{ $item->product_name }} &middot; {{ $item->quantity }} potong</span></div>
            @empty
                <p class="small text-muted mb-0">Tidak ada rincian barang.</p>
            @endforelse
        @endif
    </div>

    @if($sudahSelesai)
        <div class="cb-card">
            <p class="small text-muted mb-0">Tidak ada tindakan untuk status ini. Data sudah diperiksa sebelumnya.</p>
        </div>
    @else
        <form action="{{ route('staf.verifikasi.simpan', ['jenis' => $jenis, 'kode' => $kode]) }}"
              method="POST" class="cb-card">
            @csrf

            @if($jenis === 'donasi')
                <h2 class="h6">Hasil pemeriksaan donasi</h2>
                <label class="form-label" for="jumlahDiterima">Jumlah pakaian yang diterima (potong)</label>
                <input type="number" class="form-control" id="jumlahDiterima" name="jumlah_diterima"
                       value="{{ old('jumlah_diterima', $data->estimated_items) }}" min="0" max="200" required>
                <p class="form-text">Isi 0 bila tidak ada pakaian yang bisa diterima. Poin = jumlah diterima × {{ $poinPerPotong }}.</p>
            @elseif($jenis === 'ttc')
                <h2 class="h6">Produk yang jadi dibeli pelanggan</h2>
                @foreach($data->item as $item)
                    <label class="option-card mb-2">
                        <input type="checkbox" name="dibeli[]" value="{{ $item->id }}" @if($item->purchased) checked @endif>
                        <span class="d-flex gap-2 align-items-start">
                            <span class="check mt-1" aria-hidden="true"></span>
                            <span class="flex-fill">
                                <strong class="d-block">{{ $item->varian && $item->varian->produk ? $item->varian->produk->name : 'Produk' }}</strong>
                                <span class="small text-muted">
                                    {{ $item->varian ? $item->varian->label() : '-' }}
                                    &middot; {{ $item->varian && $item->varian->produk ? rupiah($item->varian->produk->hargaJual()) : '-' }}
                                </span>
                            </span>
                        </span>
                    </label>
                @endforeach
                <p class="form-text">Produk yang tidak dicentang dikembalikan ke rak toko.</p>
            @else
                <h2 class="h6">Keputusan retur</h2>
                <div class="row g-2 mb-2" role="radiogroup" aria-label="Keputusan retur">
                    <div class="col-6">
                        <label class="option-card">
                            <input type="radio" name="keputusan" value="diproses" checked>
                            <span class="d-flex gap-2 align-items-start">
                                <span class="check mt-1" aria-hidden="true"></span>
                                <span><strong class="d-block">Diterima</strong><span class="small text-muted">Lanjut diproses</span></span>
                            </span>
                        </label>
                    </div>
                    <div class="col-6">
                        <label class="option-card">
                            <input type="radio" name="keputusan" value="ditolak">
                            <span class="d-flex gap-2 align-items-start">
                                <span class="check mt-1" aria-hidden="true"></span>
                                <span><strong class="d-block">Ditolak</strong><span class="small text-muted">Barang dikembalikan</span></span>
                            </span>
                        </label>
                    </div>
                </div>
                <label class="form-label" for="catatanRetur">Catatan pemeriksaan</label>
                <input type="text" class="form-control" id="catatanRetur" name="catatan"
                       value="{{ old('catatan') }}" maxlength="150"
                       placeholder="Misalnya: label masih utuh, tidak ada bekas pakai">
            @endif

            <div class="row g-2 mt-2">
                <div class="col-7">
                    <label class="form-label" for="namaStaf">Nama staf</label>
                    <input type="text" class="form-control" id="namaStaf" name="nama_staf"
                           value="{{ old('nama_staf') }}" maxlength="100" required>
                </div>
                <div class="col-5">
                    <label class="form-label" for="pinToko">PIN toko</label>
                    <input type="password" class="form-control" id="pinToko" name="pin"
                           inputmode="numeric" autocomplete="off" maxlength="20" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3">
                @if($jenis === 'donasi')
                    Verifikasi dan beri poin
                @elseif($jenis === 'ttc')
                    Selesaikan kunjungan
                @else
                    Simpan keputusan retur
                @endif
            </button>
        </form>
    @endif

    <p class="small text-muted text-center">
        Halaman ini untuk staf toko. Bila Anda pelanggan, cukup tunjukkan QR kepada staf toko.
    </p>
</div>
</body>
</html>
