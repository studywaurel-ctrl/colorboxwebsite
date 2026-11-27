@props(['retur', 'judul' => 'Bukti dari pelanggan'])

{{-- Foto dan video bukti satu pengajuan retur.

     Dipakai bertiga: halaman rincian retur milik pelanggan, halaman
     pemeriksaan admin, dan halaman retur di toko milik kasir. Dibuat satu
     komponen supaya ketiganya menampilkan bukti yang sama persis.
     Sebelumnya tiap halaman menggambar sendiri dan hanya membaca satu
     kolom foto; begitu pengajuan mulai membawa empat foto dan satu video,
     ketiga halaman itu harus diubah satu per satu dan salah satunya pasti
     tertinggal.

     daftarFoto() di model yang memutuskan dari kolom mana fotonya dibaca:
     kolom photos yang baru, atau kolom photo yang lama untuk pengajuan
     yang dibuat sebelum perubahan ini. --}}

@php
    $foto = $retur->daftarFoto();
@endphp

@if($foto !== [] || $retur->video_path)
    <div class="cb-card">
        <h2 class="h6 mb-2">{{ $judul }}</h2>

        @if($foto !== [])
            <div class="ulasan-media">
                @foreach($foto as $nomor => $jalur)
                    {{-- Ditautkan ke berkas aslinya, jadi petugas yang
                         perlu melihat jahitannya dari dekat tinggal
                         menekannya, tanpa jendela bertumpuk yang harus
                         dirawat sendiri. --}}
                    <a href="{{ asset($jalur) }}" target="_blank" rel="noopener"
                       title="Lihat foto {{ $nomor + 1 }} ukuran penuh">
                        <img src="{{ asset($jalur) }}" alt="Foto bukti {{ $nomor + 1 }}" loading="lazy">
                    </a>
                @endforeach
            </div>
        @endif

        @if($retur->video_path)
            <div class="mt-2">
                <div class="small text-muted mb-1">Video pembukaan paket</div>
                {{-- preload metadata supaya berkas 50 MB tidak ikut
                     terunduh sebelum ada yang menekan mainkan. --}}
                <video src="{{ asset($retur->video_path) }}" controls preload="metadata"
                       style="max-width: 320px; width: 100%; border-radius: 12px"></video>
            </div>
        @endif
    </div>
@endif
