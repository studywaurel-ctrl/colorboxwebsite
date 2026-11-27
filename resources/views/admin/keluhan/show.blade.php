@extends('admin.layout')

@section('judul', 'Keluhan '.$keluhan->ticket_code)

@section('isi')

<div class="cp-kartu">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 style="font-size: 1.05rem" class="mb-1">Keluhan {{ $keluhan->ticket_code }}</h2>
            <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
                Dikirim {{ tanggal_id($keluhan->created_at, true) }}.
            </p>
        </div>
        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.keluhan.index') }}">Kembali ke Daftar</a>
    </div>

    <div class="ad-angka mt-3">
        <div>
            <p class="ad-angka__nilai mb-0">{{ \App\Models\Complaint::labelStatus($keluhan->status) }}</p>
            <p class="ad-angka__label mb-0">Status tiket</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ \App\Models\Complaint::labelKategori($keluhan->category) }}</p>
            <p class="ad-angka__label mb-0">Pokok urusan</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $keluhan->balasan->count() }}</p>
            <p class="ad-angka__label mb-0">Pesan dalam tiket</p>
        </div>
        <div>
            <p class="ad-angka__nilai mb-0">{{ $keluhan->petugas?->name ?? 'Belum ada' }}</p>
            <p class="ad-angka__label mb-0">Penanggung jawab</p>
        </div>
    </div>
</div>

<div class="cp-kartu">
    <h2 style="font-size: 1.05rem">Keterangan</h2>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <tbody>
            <tr>
                <th>Judul</th>
                <td>{{ $keluhan->subject }}</td>
            </tr>
            <tr>
                <th>Pelanggan</th>
                <td>
                    {{ $keluhan->pengguna?->name ?? kosong() }}
                    @if($keluhan->pengguna)
                        <br><span style="color: var(--cb-ink-3); font-size: .78rem">{{ $keluhan->pengguna->email }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Pesanan terkait</th>
                <td>
                    @if($keluhan->pesanan)
                        <a href="{{ route('admin.pesanan.show', $keluhan->pesanan) }}">{{ $keluhan->pesanan->order_code }}</a>
                    @else
                        Tidak berkaitan dengan pesanan tertentu
                    @endif
                </td>
            </tr>
            <tr>
                <th>Pesan terakhir</th>
                <td>{{ tanggal_id($keluhan->last_reply_at ?: $keluhan->created_at, true) }}</td>
            </tr>
            @if($keluhan->closed_at)
                <tr>
                    <th>Ditutup</th>
                    <td>{{ tanggal_id($keluhan->closed_at, true) }}</td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="cp-kartu">
    <h2 style="font-size: 1.05rem" class="mb-3">Percakapan</h2>
    @include('keluhan.partials.percakapan', ['keluhan' => $keluhan, 'sisi' => 'admin'])
</div>

@if($keluhan->masihTerbuka())
    <form action="{{ route('admin.keluhan.balas', $keluhan) }}" method="POST"
          enctype="multipart/form-data" class="cp-kartu">
        @csrf
        <h2 style="font-size: 1.05rem" class="mb-3">Jawab Pelanggan</h2>

        {{-- Balasan cepat.

             Sebagian besar keluhan berbunyi sama, dan admin mengetik
             jawaban yang isinya sama berulang kali. Tiap pengetikan ulang
             itu satu kesempatan baru untuk jawabannya menjadi lebih
             pendek, lebih ketus, atau lupa menyebut langkah yang
             seharusnya diberitahukan.

             Menekan satu pilihan hanya MENGISI kotak di bawah, tidak
             langsung mengirimnya. Itu disengaja: jawaban siap pakai yang
             terkirim tanpa dibaca cepat atau lambat akan mendarat pada
             keluhan yang tidak cocok. Yang muncul di sini hanya yang
             masuk akal untuk kategori tiket ini, ditambah kalimat umum. --}}
        <div class="mb-2">
            <p class="form-label mb-1">
                Balasan cepat
                <x-tip teks="Menekan salah satunya mengisi kotak jawaban di bawah. Teksnya masih bisa Anda ubah sebelum dikirim." />
            </p>
            <div class="d-flex flex-wrap gap-2">
                @foreach(\App\Models\Complaint::balasanCepat($keluhan->category) as $siap)
                    <button type="button" class="cp-pil cp-pil--info text-start"
                            style="cursor: pointer; border: 0; max-width: 320px"
                            data-balasan-cepat="{{ $siap }}">
                        {{ \Illuminate\Support\Str::limit($siap, 70) }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="mb-3">
            <label class="visually-hidden" for="body">Jawaban</label>
            <textarea class="form-control @error('body') is-invalid @enderror"
                      id="body" name="body" rows="5" maxlength="2000" required
                      placeholder="Tulis jawaban untuk pelanggan">{{ old('body') }}</textarea>
            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="photo">Lampirkan foto (boleh dikosongkan)</label>
            <input type="file" class="form-control @error('photo') is-invalid @enderror"
                   id="photo" name="photo" accept="image/*">
            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Centang penutup digabung ke kotak jawaban, bukan tombol
             tersendiri. Kebanyakan keluhan selesai dalam satu jawaban, jadi
             admin tidak perlu menekan dua tombol untuk satu pekerjaan. --}}
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="tutup" name="tutup">
            <label class="form-check-label" for="tutup">
                Sekalian tutup tiketnya, urusan ini sudah selesai
            </label>
        </div>

        <button type="submit" class="btn btn-dark">Kirim jawaban</button>
    </form>

    @if($keluhan->status === 'dijawab')
        <form action="{{ route('admin.keluhan.tutup', $keluhan) }}" method="POST" class="cp-kartu"
              onsubmit="return confirm('Tutup keluhan {{ $keluhan->ticket_code }}?')">
            @csrf
            @method('PUT')
            <h2 style="font-size: 1.05rem" class="mb-2">Tutup Tanpa Jawaban Baru</h2>
            <p class="mb-3" style="color: var(--cb-ink-2); font-size: .875rem">
                Dipakai kalau pelanggan sudah mengiyakan jawaban sebelumnya dan tidak ada lagi
                yang perlu dituliskan.
            </p>
            <button type="submit" class="btn btn-outline-dark btn-sm">Tandai selesai</button>
        </form>
    @endif
@else
    <div class="cp-kartu">
        <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
            Keluhan ini sudah ditutup, jadi tidak bisa ditulisi lagi. Kalau pelanggan masih
            punya urusan yang sama, dia mengirim keluhan baru dengan nomor tiketnya sendiri.
        </p>
    </div>
@endif

@endsection

@push('skrip')
    <script>
        // Menyisipkan balasan cepat ke kotak jawaban.
        //
        // Yang dikerjakan cuma mengisi dan memberi fokus; pengirimannya
        // tetap lewat tombol Kirim seperti biasa, supaya admin selalu
        // sempat membaca ulang apa yang akan dibaca pelanggan.
        document.addEventListener('DOMContentLoaded', function () {
            var kotak = document.getElementById('body');
            if (!kotak) { return; }

            Array.prototype.forEach.call(
                document.querySelectorAll('[data-balasan-cepat]'),
                function (tombol) {
                    tombol.addEventListener('click', function () {
                        var teks = tombol.dataset.balasanCepat;
                        // Kalau kotaknya sudah ada isinya, teks baru
                        // DITAMBAHKAN di bawahnya, bukan menimpanya.
                        // Admin sering menggabungkan dua kalimat siap
                        // pakai, dan menimpa isian yang sudah diketik
                        // adalah kehilangan pekerjaan tanpa peringatan.
                        kotak.value = kotak.value.trim()
                            ? kotak.value.trim() + '\n\n' + teks
                            : teks;
                        kotak.focus();
                        kotak.setSelectionRange(kotak.value.length, kotak.value.length);
                    });
                }
            );
        });
    </script>
@endpush
