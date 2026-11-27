@extends('admin.layout')

@section('judul', 'Retur '.$retur->return_code)

@section('isi')

<div class="cp-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">Retur {{ $retur->return_code }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                Diajukan {{ tanggal_id($retur->created_at, true) }}.
            </p>
        </div>
        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.retur.index') }}">Kembali ke Daftar</a>
    </div>

    <div class="ad-angka mt-3">
        <div>
            <p class="ad-angka__nilai mb-0">{{ ucfirst(str_replace('_', ' ', $retur->status)) }}</p>
            <p class="ad-angka__label mb-0">Status pengajuan</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ \App\Models\ReturnRequest::labelPenukaran($retur->exchange_type ?: 'ganti_ukuran') }}</p>
            <p class="ad-angka__label mb-0">Mau ditukar dengan</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $retur->method === 'toko' ? 'Diantar ke toko' : 'Jasa kirim' }}</p>
            <p class="ad-angka__label mb-0">Cara pengembalian</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $retur->item->count() }}</p>
            <p class="ad-angka__label mb-0">Jenis barang diretur</p>
        </div>
    </div>
</div>

<div class="cp-kartu">
    <h2 style="font-size: 1.05rem">Keterangan Pengajuan</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <tbody>
            <tr>
                <th>Pelanggan</th>
                <td>
                    {{ $retur->pengguna?->name ?? kosong() }}
                    @if($retur->pengguna)
                        <br><span style="color: var(--cb-ink-3); font-size: .78rem">{{ $retur->pengguna->email }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Pesanan</th>
                <td>
                    @if($retur->pesanan)
                        <a href="{{ route('admin.pesanan.show', $retur->pesanan) }}">{{ $retur->pesanan->order_code }}</a>
                    @else
                        {{ $retur->purchase_code ?: 'Pembelian di toko tanpa kode' }}
                    @endif
                </td>
            </tr>
            <tr>
                <th>Toko</th>
                <td>
                    {{ $retur->toko?->name ?? kosong() }}
                    @if($retur->method === 'toko' && $retur->toko)
                        {{-- Tujuan stoknya ditulis apa adanya, supaya petugas tahu
                             barang ini menambah rak toko, bukan gudang pusat. --}}
                        <span class="ad-angka__label d-block">Stoknya masuk ke rak toko ini, bukan gudang pusat.</span>
                    @elseif($retur->method === 'kurir')
                        <span class="ad-angka__label d-block">Stoknya masuk ke gudang pusat.</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Batas barang tiba</th>
                <td>
                    @if($retur->arrive_by)
                        {{ tanggal_id($retur->arrive_by, true) }}
                        @if($retur->terlambatTiba())
                            {{-- Keterlambatan ditandai jelas, sebab inilah dasar
                                 petugas memutuskan menerima atau menolak. --}}
                            <span class="status status-danger">Lewat batas</span>
                            <span class="ad-angka__label d-block">
                                Barangnya
                                {{ $retur->received_at
                                    ? 'baru tiba '.tanggal_id($retur->received_at, true)
                                    : 'belum tiba sampai sekarang' }}.
                                Aturannya {{ setting('return_window_days', 7) }} hari sejak pelanggan
                                menerima barang, dihitung sampai barangnya tiba.
                            </span>
                        @else
                            <span class="ad-angka__label d-block">
                                {{ $retur->received_at
                                    ? 'Tiba '.tanggal_id($retur->received_at, true).', masih dalam batas.'
                                    : 'Belum tiba, masih dalam batas.' }}
                            </span>
                        @endif
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <th>Alasan pelanggan</th>
                <td>{{ $retur->reason }}</td>
            </tr>
            @if($retur->reject_reason)
                {{-- Alasan penolakan berdiri sebagai barisnya sendiri, bukan
                     dicampur ke catatan, supaya terbaca sekali lihat dan bisa
                     dihitung di laporan sebab penolakan. --}}
                <tr>
                    <th>Alasan penolakan</th>
                    <td>
                        <span class="cp-status cp-status--gagal">
                            {{ \App\Models\ReturnRequest::labelAlasanTolak($retur->reject_reason) }}
                        </span>
                    </td>
                </tr>
            @endif
            <tr>
                <th>Catatan admin</th>
                <td>{{ $retur->admin_note ?: 'Belum ada catatan.' }}</td>
            </tr>
            <tr>
                <th>Disetujui pada</th>
                <td>{{ $retur->approved_at ? tanggal_id($retur->approved_at, true) : kosong() }}</td>
            </tr>
            <tr>
                <th>Selesai pada</th>
                <td>{{ $retur->completed_at ? tanggal_id($retur->completed_at, true) : kosong() }}</td>
            </tr>
            </tbody>
        </table>
    </div>

    {{-- Seluruh bukti digambar komponen x-bukti-retur, yang dipakai
         bersama halaman pelanggan dan halaman kasir. Dulu di sini cuma
         satu kolom foto yang terbaca, jadi tiga foto lain dan videonya
         tidak pernah sampai ke mata admin yang memutuskan. --}}
    <div class="mt-3">
        <x-bukti-retur :retur="$retur" judul="Bukti dari pelanggan" />
    </div>
</div>

<div class="cp-kartu">
    <h2 style="font-size: 1.05rem">Barang yang Diretur</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>Produk</th>
                <th>Varian</th>
                <th>Jumlah</th>
                <th>Catatan</th>
            </tr>
            </thead>
            <tbody>
            @forelse($retur->item as $baris)
                <tr>
                    <td>{{ $baris->product_name }}</td>
                    <td>{{ $baris->varian?->label() ?? kosong() }}</td>
                    <td>{{ $baris->quantity }}</td>
                    <td>{{ $baris->note ?: kosong() }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada barang yang dicatat pada pengajuan ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($retur->diurusKasir())
    {{-- Retur yang diantar sendiri ke toko TIDAK punya tombol di sini.
         Yang memeriksa barangnya kasir toko, sebab kasirlah yang memegang
         bajunya. Admin kantor tidak pernah menyentuh barang itu. --}}
    <div class="cp-kartu">
        <h2 style="font-size: 1.05rem">Siapa yang Memproses Retur Ini</h2>
        <p class="cp-kartu__ket">
            Hanya untuk melihat. Diproses kasir
            <strong>{{ $retur->toko?->name ?? 'toko' }}</strong>, bukan admin kantor.
            <x-tip teks="Pelanggan memilih mengantar barangnya sendiri ke toko itu, jadi kasir yang memegang dan memeriksa bajunya." />
        </p>
        </div>
@else
<div class="cp-kartu">
    <h2 style="font-size: 1.05rem">Langkah Berikutnya</h2>
    {{-- Keterangan panjangnya dipindah ke komentar controllernya. Di layar
         yang perlu terlihat cuma keadaan sekarang dan langkah berikutnya. --}}
    <p class="cp-kartu__ket">Status sekarang <strong>{{ \App\Models\ReturnRequest::labelStatus($retur->status) }}</strong>.</p>

    @if(empty($lanjutan))
        <div class="cp-kosong">
            <i class="bi bi-check2-circle" aria-hidden="true"></i>
            @if(in_array($retur->status, ['selesai', 'ditolak', 'dibatalkan']))
                Pengajuan ini sudah ditutup pada status
                {{ \App\Models\ReturnRequest::labelStatus($retur->status) }}, statusnya tidak bisa
                diubah lagi.
            @elseif($retur->menungguKonfirmasi())
                {{-- Bukan tersangkut, memang begini urutannya. Keterangannya
                     ditulis supaya admin tidak mencari cari tombol yang
                     sengaja tidak ada. --}}
                Barang penggantinya sudah diserahkan atau dikirim, jadi yang ditunggu sekarang
                konfirmasi pelanggan di halaman returnya. Pengajuan ini ditutup begitu dia
                menekan tombolnya, atau ditutup sendiri oleh sistem sesudah tiga hari.
                Admin tidak perlu menekan apa apa.
            @elseif($retur->diurusKasir())
                Retur ini diantar sendiri ke {{ $retur->toko?->name ?? 'toko' }}, jadi yang
                memeriksa dan memprosesnya kasir toko itu, bukan admin kantor.
            @else
                Tidak ada langkah manual untuk status ini.
            @endif
        </div>
    @else
        <div class="d-flex flex-wrap gap-2 mt-2">
            @foreach($lanjutan as $pilihan)
                @php
                    // isianPeralihan() di model ReturnRequest menyebut isian
                    // apa saja yang diperlukan langkah ini. Larik kosong
                    // berarti tombolnya langsung jalan sekali tekan.
                    $isian = \App\Models\ReturnRequest::isianPeralihan($pilihan);

                    // Penolakan diberi tombol merah supaya tidak tertekan
                    // tanpa sengaja, sebab tidak bisa dikembalikan.
                    $bahaya = $pilihan === 'ditolak';
                @endphp

                <div>
                    <x-tombol-langkah
                        :aksi="route('admin.retur.status', $retur)"
                        :status="$pilihan"
                        :label="\App\Models\ReturnRequest::labelPeralihan($pilihan)"
                        :kunci="'retur'.$retur->id.$pilihan"
                        :bahaya="$bahaya"
                        :isian="$isian"
                        :konfirmasi="\App\Models\ReturnRequest::labelPeralihan($pilihan).' untuk retur '.$retur->return_code.'? Pelanggan langsung diberi tahu, dan langkah ini tidak bisa dikembalikan sendiri.'" />
                </div>
            @endforeach
        </div>
    @endif
</div>

@endif

@endsection