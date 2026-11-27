@extends('layouts.app')

@section('judul', 'Donasi Pakaian')

@section('isi')
<div class="container-xl section-sm">
    <x-remah :tautan="['Donasi Pakaian' => null]" />

    <div class="row g-4 mb-3">
        <div class="col-lg-7">
            <h1 class="page-title">Donasi Pakaian</h1>
            <p class="text-muted mb-0">
                Bawa pakaian dan tekstil yang masih layak pakai ke toko Colorbox. Staf toko menghitung
                pakaian yang diterima, dan setiap potong menjadi poin belanja.
            </p>
        </div>
        <div class="col-lg-5">
            <div class="cb-card">
                <h2 class="h6">Aturan poin</h2>
                <p class="small text-muted mb-0">
                    1 potong yang diterima bernilai {{ $poinPerPotong }} poin. Poin dihitung dari jumlah
                    yang diperiksa staf toko, bukan dari perkiraan saat membuat jadwal.
                </p>
                @auth
                    <div class="info-line mt-2">
                        <i class="bi bi-coin" aria-hidden="true"></i>
                        <span>Saldo poin Anda {{ auth()->user()->points }} &middot;
                            <a href="{{ route('donasi.riwayat') }}">Riwayat donasi</a></span>
                    </div>
                @endauth
            </div>
        </div>
    </div>

    <ol class="steps">
        <li class="current">Pilih toko</li>
        <li>Pilih tanggal</li>
        <li>Pilih jam</li>
        <li>Serahkan pakaian</li>
        <li>Terima poin</li>
    </ol>

    <div class="cb-card mb-4">
        <h2 class="h6">Yang diterima dan tidak diterima</h2>
        <p class="small text-muted mb-1">
            <strong>Diterima:</strong> pakaian dan tekstil yang bersih dan layak pakai, seperti atasan,
            bawahan, dress, outerwear, rajutan, pakaian anak, sprei, handuk, dan taplak.
        </p>
        <p class="small text-muted mb-0">
            <strong>Tidak diterima:</strong> pakaian dalam, barang basah atau berjamur, serta barang non tekstil.
        </p>
    </div>

    @if($toko->count() < 1)
        <x-kosong ikon="shop" judul="Belum ada toko penerima donasi"
                  teks="Program donasi pakaian belum tersedia di toko mana pun. Silakan periksa kembali nanti.">
            <a href="{{ route('toko.index') }}" class="btn btn-primary">Lihat daftar toko</a>
        </x-kosong>
    @else
        <form action="{{ route('donasi.store') }}" method="POST">
            @csrf
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="cb-card">
                        <h2 class="h6">1. Pilih toko</h2>
                        <p class="small text-muted">Hanya toko yang menerima donasi pakaian yang ditampilkan.</p>

                        <div class="row g-2" role="radiogroup" aria-label="Toko penerima donasi">
                            @foreach($toko as $satu)
                                <div class="col-md-6">
                                    <label class="option-card">
                                        <input type="radio" name="store_id" value="{{ $satu->id }}"
                                               @if((int) old('store_id', $toko->first()->id) === (int) $satu->id) checked @endif>
                                        <span class="d-flex gap-2 align-items-start">
                                            <span class="check mt-1" aria-hidden="true"></span>
                                            <span class="flex-fill">
                                                <strong class="d-block">{{ $satu->name }}</strong>
                                                <span class="small text-muted d-block">{{ $satu->address }}, {{ $satu->city }}</span>
                                                @if($satu->floor)
                                                    <span class="info-line"><i class="bi bi-building" aria-hidden="true"></i><span>{{ $satu->floor }}</span></span>
                                                @endif
                                                <span class="info-line">
                                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                                    <span>Buka {{ jam_singkat($satu->open_time) }} &ndash; {{ jam_singkat($satu->close_time) }}</span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="cb-card">
                        <h2 class="h6">2. Tanggal dan jam penyerahan</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <span class="form-label" id="labelTanggalDonasi">Tanggal kedatangan</span>
                                <input type="hidden" name="tanggal" value="{{ $tanggalAwal }}">
                                <div class="calendar" data-calendar data-name="tanggal"
                                     data-min="{{ $tanggalAwal }}" data-max="{{ $tanggalAkhir }}"
                                     aria-labelledby="labelTanggalDonasi"></div>
                            </div>
                            <div class="col-md-6">
                                <span class="form-label">Jam kedatangan</span>
                                <div data-slots="donasi" data-name="donation_slot_id"
                                     data-date-field="tanggal" data-store-field="store_id" aria-live="polite"></div>
                                <p class="form-text mt-2 mb-0">
                                    Sisa kuota setiap jam ditulis apa adanya. Jam yang penuh tidak dapat dipilih.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="cb-card">
                        <h2 class="h6">3. Perkiraan pakaian</h2>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="jumlahPakaian">Perkiraan jumlah (potong)</label>
                                <input type="number" class="form-control" id="jumlahPakaian" name="estimated_items"
                                       value="{{ old('estimated_items', $minItem > 5 ? $minItem : 5) }}"
                                       min="{{ $minItem }}" max="{{ $maksItem }}" required>
                                <p class="form-text mb-0">
                                    Paling sedikit {{ $minItem }} potong dan paling banyak {{ $maksItem }} potong.
                                    Angka ini hanya perkiraan untuk persiapan staf toko.
                                </p>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="catatanKondisi">Catatan kondisi pakaian</label>
                                <input type="text" class="form-control" id="catatanKondisi" name="condition_note"
                                       value="{{ old('condition_note') }}" maxlength="191"
                                       placeholder="Misalnya: sudah dicuci, sebagian pakaian anak">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="cb-card sticky-summary">
                        <h2 class="h6">Ringkasan</h2>
                        <div class="info-line">
                            <i class="bi bi-bag-heart" aria-hidden="true"></i>
                            <span>Bawa pakaian dalam kantong. Tidak ada penjemputan ke rumah.</span>
                        </div>
                        <div class="info-line">
                            <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                            <span>Tunjukkan QR jadwal donasi kepada staf toko saat tiba.</span>
                        </div>
                        <div class="info-line">
                            <i class="bi bi-coin" aria-hidden="true"></i>
                            <span>Poin masuk setelah staf toko selesai memeriksa pakaian.</span>
                        </div>
                        <ul class="small text-muted ps-3 mt-3">
                            <li>Pakaian harus bersih, layak pakai, dan bukan pakaian dalam.</li>
                            <li>Jadwal dapat dibatalkan sebelum kedatangan.</li>
                        </ul>
                        @auth
                            <button type="submit" class="btn btn-primary w-100">Buat Jadwal Donasi</button>
                        @else
                            <p class="small text-muted">Anda perlu masuk ke akun untuk membuat jadwal.</p>
                            <a href="{{ route('login') }}" class="btn btn-primary w-100">Masuk untuk membuat jadwal</a>
                        @endauth
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection
