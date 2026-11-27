{{--
    Rincian satu retur yang diantar ke toko, beserta dua langkah kerjanya.

    Ditangani App\Http\Controllers\Kasir\ReturController::show() lewat route
    GET /kasir/retur/{returnRequest} bernama kasir.retur.show.
--}}
@extends('kasir.layout')

@section('judul', 'Retur '.$retur->return_code)

@section('isi')

<div class="cp-kartu">
    <h2 class="cp-kartu__judul">Keterangan Pengajuan</h2>
    <table class="cp-tabel cp-tabel--ringkas">
        <tbody>
        <tr><th>Pelanggan</th><td>{{ $retur->pengguna?->name ?? kosong() }}</td></tr>
        <tr>
            <th>Pesanan asal</th>
            <td>
                {{ $retur->pesanan?->order_code ?? kosong() }}
                @if($retur->pesanan)
                    <span class="cp-lipat__ket d-block">
                        Dibayar {{ cara_bayar($retur->pesanan->pembayaran?->payment_method) }},
                        total {{ rupiah($retur->pesanan->total) }}
                    </span>
                @endif
            </td>
        </tr>
        <tr><th>Jenis</th><td>{{ $retur->type === 'tukar' ? 'Penukaran' : 'Pengembalian dana' }}</td></tr>
        <tr><th>Alasan pelanggan</th><td>{{ $retur->reason ?: kosong() }}</td></tr>
        <tr><th>Jadwal datang</th><td>{{ $retur->return_date ? tanggal_id($retur->return_date, true) : kosong() }}</td></tr>
        <tr><th>Status</th>
            <td>
                <span class="cp-status cp-status--{{ nada_status($retur->status) }}">
                    {{ \App\Models\ReturnRequest::labelStatus($retur->status) }}
                </span>
            </td>
        </tr>
        @if($retur->reject_reason)
            <tr>
                <th>Alasan penolakan</th>
                <td><span class="cp-status cp-status--gagal">{{ \App\Models\ReturnRequest::labelAlasanTolak($retur->reject_reason) }}</span></td>
            </tr>
        @endif
        <tr><th>Catatan pemeriksaan</th><td>{{ $retur->admin_note ?: kosong() }}</td></tr>
        </tbody>
    </table>

    {{-- Bukti yang sama persis dengan yang dilihat admin, lewat
         komponen bersama. Kasir yang memegang barangnya di meja perlu
         membandingkan barang di tangannya dengan foto saat pengajuan. --}}
    <div class="mt-3">
        <x-bukti-retur :retur="$retur" judul="Bukti dari pelanggan" />
    </div>
</div>

<div class="cp-kartu">
    <h2 class="cp-kartu__judul">Barang yang Diretur</h2>
    <p class="cp-kartu__ket">
        Kolom terakhir sisa stok penggantinya di toko ini. Lihat dulu sebelum menjanjikan
        apa pun kepada pelanggan.
    </p>

    <div class="cp-gulir mt-3">
        <table class="cp-tabel">
            <thead>
            <tr><th>Produk</th><th>Varian</th><th>Jumlah</th><th>Stok Pengganti di Toko Ini</th></tr>
            </thead>
            <tbody>
            @foreach($retur->item as $baris)
                @php $sisa = $stokPengganti[$baris->product_variant_id] ?? 0; @endphp
                <tr>
                    <td class="cp-utama-sel">{{ $baris->varian?->produk?->name ?? kosong() }}</td>
                    <td>{{ $baris->varian?->label() ?? kosong() }}</td>
                    <td>{{ $baris->quantity }}</td>
                    <td>
                        <span class="cp-pil cp-pil--{{ $sisa >= $baris->quantity ? 'sukses' : 'bahaya' }}">
                            {{ $sisa }} pcs
                        </span>
                        @if($sisa < $baris->quantity)
                            <span class="cp-lipat__ket d-block">
                                Tidak cukup untuk ditukar di tempat.
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @php
        // Ada satu saja barang yang stoknya kurang, kasir perlu diberi tahu
        // pilihannya sebelum berbicara dengan pelanggan.
        $adaYangKosong = collect($retur->item)->contains(function ($baris) use ($stokPengganti) {
            return ($stokPengganti[$baris->product_variant_id] ?? 0) < $baris->quantity;
        });
    @endphp

    @if($adaYangKosong)
        <div class="cp-info cp-info--blok mt-3">
            <p class="mb-2"><strong>Penggantinya tidak cukup di toko ini. Tawarkan berurutan:</strong></p>
            <ol class="mb-0 ps-3">
                <li>Warna atau ukuran lain yang stoknya ada di toko ini.</li>
                <li>Dikirim dari gudang pusat ke alamat pelanggan, kalau dia bersedia menunggu.</li>
                <li>Pengembalian dana ke cara bayar semula, kalau dua duanya tidak cocok.</li>
            </ol>
            <p class="mb-0 mt-2">
                Pilihan kedua dan ketiga menyangkut gudang dan uang, jadi tuliskan kesepakatannya
                di catatan lalu teruskan ke admin kantor.
            </p>
        </div>
    @endif
</div>

@if(in_array($retur->status, \App\Http\Controllers\Kasir\ReturController::BELUM_DIPERIKSA, true))
    <div class="cp-kartu">
        <h2 class="cp-kartu__judul">Langkah 1: Periksa Barangnya</h2>
        <p class="cp-kartu__ket">
            Centang yang memang terpenuhi. Semuanya harus tercentang supaya retur lanjut.
            Kalau ada yang tidak, alasannya wajib ditulis.
        </p>

        <form action="{{ route('kasir.retur.periksa', $retur) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mt-2">
                @foreach($syarat as $kunci => $tulisan)
                    <label class="d-flex gap-2 align-items-start mb-2">
                        <input type="checkbox" name="syarat[{{ $kunci }}]" value="1"
                               {{ old('syarat.'.$kunci) ? 'checked' : '' }}>
                        <span>{{ $tulisan }}</span>
                    </label>
                @endforeach
            </div>

            {{-- Alasan penolakan dipilih dari daftar, bukan diketik. Alasan
                 inilah yang dibaca pelanggan, dan yang dihitung di laporan.
                 Hanya terpakai kalau ada syarat di atas yang tidak
                 tercentang; kalau semuanya tercentang, pilihan ini diabaikan
                 server. --}}
            <label class="form-label mt-3" for="reject_reason">
                Alasan penolakan <x-tip teks="Diisi hanya kalau ada syarat di atas yang tidak terpenuhi. Alasan ini yang dibaca pelanggan." />
            </label>
            <select class="form-select" id="reject_reason" name="reject_reason" style="max-width: 520px">
                <option value="">Tidak ditolak, semua syarat terpenuhi</option>
                @foreach($alasanTolak as $kode => $tulisan)
                    <option value="{{ $kode }}" @selected(old('reject_reason') === $kode)>{{ $tulisan }}</option>
                @endforeach
            </select>

            <label class="form-label mt-2" for="catatan">
                Catatan pemeriksaan <span class="cp-sub-sel">boleh dikosongkan</span>
            </label>
            <input class="form-control" type="text" id="catatan" name="catatan" maxlength="191"
                   value="{{ old('catatan') }}"
                   placeholder="Rincian tambahan, misalnya bagian mana yang tidak cocok"
                   style="max-width: 520px">

            <button class="btn btn-primary btn-sm mt-3" type="submit">Simpan Hasil Pemeriksaan</button>
        </form>
    </div>
@elseif(in_array($retur->status, \App\Http\Controllers\Kasir\ReturController::SIAP_DISERAHKAN, true))
    <div class="cp-kartu">
        <h2 class="cp-kartu__judul">Langkah 2: Serahkan Penggantinya</h2>
        <p class="cp-kartu__ket">
            Tekan sesudah penggantinya benar benar diserahkan. Stok toko berkurang sendiri.
        </p>

        <form action="{{ route('kasir.retur.selesai', $retur) }}" method="POST">
            @csrf
            @method('PUT')

            <label class="form-label" for="catatanSelesai">Catatan penyerahan</label>
            <input class="form-control" type="text" id="catatanSelesai" name="catatan" maxlength="191"
                   placeholder="Contoh: ditukar ukuran M karena S habis" style="max-width: 520px">

            <button class="btn btn-primary btn-sm mt-3" type="submit">Pengganti Sudah Diserahkan</button>
        </form>
    </div>
@elseif($retur->menungguKonfirmasi())
    {{-- Pekerjaan kasir sudah habis di sini, tetapi returnya BELUM ditutup.
         Yang ditunggu konfirmasi pelanggan, dan kalau tidak datang sampai
         tiga hari, sistem yang menutupnya sendiri. Keterangan ini perlu ada
         supaya kasir tidak mengira pengajuannya tersangkut. --}}
    <div class="cp-kartu">
        <h2 class="cp-kartu__judul">Langkah 3: Menunggu Konfirmasi Pelanggan</h2>
        <p class="cp-kartu__ket mb-0">
            Penggantinya sudah diserahkan. Pengajuannya ditutup begitu pelanggan
            mengonfirmasi, atau otomatis sesudah tiga hari.
        </p>
    </div>
@else
    <div class="cp-kartu">
        <h2 class="cp-kartu__judul">Sudah Ditutup</h2>
        <p class="cp-kartu__ket mb-0">
            Retur ini berstatus {{ \App\Models\ReturnRequest::labelStatus($retur->status) }},
            jadi tidak ada langkah yang perlu dikerjakan lagi.
        </p>
    </div>
@endif

{{-- Membatalkan JADWALNYA, bukan menolak barangnya.

     Ditaruh terpisah di bawah dan tampilannya dibuat tenang, bukan
     tombol besar bersebelahan dengan tombol kerja utama, sebab ini
     jalan keluar untuk keadaan yang tidak biasa, bukan langkah yang
     dikerjakan tiap hari.

     Alasannya dipilih dari daftar, satu tekanan, sebab kasir di meja
     yang sedang antre tidak akan mengetik kalimat panjang. Kalimat
     yang dibaca pelanggan tetap utuh, dan alasannya bisa dihitung di
     laporan. --}}
@unless(in_array($retur->status, \App\Http\Controllers\Kasir\ReturController::SUDAH_DITUTUP, true))
    <details class="cp-kartu">
        <summary style="cursor: pointer; font-weight: 600">
            Pelanggan tidak datang? Batalkan jadwalnya
        </summary>

        <p class="cp-kartu__ket mt-2">
            Dipakai kalau barangnya memang tidak pernah sampai ke meja, jadi tidak ada
            yang bisa diperiksa. Pelanggan diberi tahu beserta alasannya, dan masih bisa
            mengajukan retur baru selama batas waktunya belum lewat.
        </p>

        <form action="{{ route('kasir.retur.batal', $retur) }}" method="POST"
              data-confirm="Batalkan jadwal retur {{ $retur->return_code }}?">
            @csrf
            @method('PUT')

            <label class="form-label" for="alasanBatal">Alasan pembatalan<x-wajib /></label>
            <select class="form-select" id="alasanBatal" name="alasan" required style="max-width: 520px">
                <option value="">Pilih alasannya</option>
                @foreach(\App\Models\ReturnRequest::ALASAN_BATAL_JADWAL as $kode => $tulisan)
                    <option value="{{ $kode }}">{{ $tulisan }}</option>
                @endforeach
            </select>

            <label class="form-label mt-2" for="catatanBatal">
                Catatan tambahan <span class="text-muted">boleh dikosongkan</span>
            </label>
            <input class="form-control" type="text" id="catatanBatal" name="catatan" maxlength="191"
                   placeholder="Contoh: sudah ditelepon dua kali, tidak diangkat" style="max-width: 520px">

            <button class="btn btn-outline-dark btn-sm mt-3" type="submit">Batalkan Jadwal</button>
        </form>
    </details>
@endunless

<a class="btn btn-outline-dark btn-sm" href="{{ route('kasir.retur.index') }}">Kembali ke Antrean</a>

@endsection
