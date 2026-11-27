@extends('layouts.app')

@section('judul', 'Ajukan Penukaran Barang')

@section('isi')
    @php
        $asal = $pesanan ? 'Pesanan '.$pesanan->order_code : 'Pembelian di toko '.$kode;
    @endphp

    <div class="container-xl section-sm">
        <div class="row g-4">
            <aside class="col-lg-3">
                @include('akun.menu')
            </aside>

            <div class="col-lg-9">
                <nav aria-label="Jejak halaman">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('beranda') }}">Beranda</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('retur.index') }}">Retur dan Penukaran</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Pengajuan baru</li>
                    </ol>
                </nav>
                <h1 class="page-title">{{ teks('Ajukan Penukaran Barang', 'Request an Exchange') }}</h1>
                <p class="text-muted">{{ $asal }}</p>

                @include('retur.partials.tab', ['aktif' => 'formulir', 'retur' => null])

                {{-- Batas waktunya ditulis sebagai tanggal barang harus SUDAH
                     SAMPAI, bukan tanggal pengajuan. Bedanya besar: pelanggan
                     yang mengirim pada hari keenam mengira masih sempat,
                     padahal paketnya baru tiba hari kesembilan dan sudah
                     lewat. Dengan ditulis begini, dia bisa menghitung sendiri
                     kapan paling lambat harus mengirim. --}}
                @if($batasTiba)
                    <div class="alert alert-warning">
                        <strong class="d-block mb-1">
                            <i class="bi bi-calendar-check me-1" aria-hidden="true"></i>
                            Barangnya harus sudah sampai di Colorbox paling lambat
                            {{ tanggal_id($batasTiba, true) }}
                        </strong>
                        <span class="small">
                            Batas {{ $hariRetur }} hari itu dihitung sampai barangnya tiba, bukan sampai
                            Anda mengirim. Kalau lewat jasa kirim, kirimkan beberapa hari sebelum tanggal
                            itu supaya masih sempat di jalan.
                        </span>
                    </div>
                @endif

                <form action="{{ route('retur.store', $kode) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <p class="ket-wajib">Isian bertanda <x-wajib /> wajib diisi.</p>

                    {{-- 1. Barang yang diajukan --}}
                    <div class="cb-card">
                        <h2 class="h6 mb-3">1. Pilih barang<x-wajib /></h2>
                        <p class="small text-muted">
                            Satu baris sama dengan satu potong.
                            <x-tip teks="Kalau Anda membeli dua potong baju yang sama, keduanya tampil terpisah, jadi yang ditukar benar benar yang dicentang." />
                        </p>
                        @forelse($barang as $baris)
                            <label class="d-flex flex-wrap gap-3 py-3 border-bottom align-items-center mb-0">
                                <input class="form-check-input mt-0" type="checkbox" name="item[]"
                                       value="{{ $baris['kunci'] }}"
                                       @checked(in_array((string) $baris['kunci'], (array) old('item', []), true))>
                                <img src="{{ gambar_produk($baris['gambar']) }}" alt="" class="mini-thumb">
                                <span class="flex-fill small">
                                    <span class="d-block fw-600">{{ $baris['nama'] }}</span>
                                    {{-- Penomoran "potong ke-1 dari 2" dilepas.
                                         Dua potong baju yang sama persis,
                                         warna sama, ukuran sama, harga sama,
                                         tidak bisa dibedakan pelanggan di
                                         tangannya sendiri, jadi menyuruhnya
                                         memilih potong yang mana hanya
                                         membuat ragu tanpa mengubah apa pun
                                         yang dikerjakan toko. Barisnya tetap
                                         terpisah supaya pelanggan bisa
                                         menukar satu dari dua. --}}
                                    <span class="text-muted">{{ $baris['ket'] }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="small text-muted mb-0">
                                Tidak ada barang yang bisa diajukan pada pembelian ini. Barang yang sudah
                                pernah diretur tidak muncul lagi di sini.
                            </p>
                        @endforelse
                    </div>

                    {{-- 2. Mau ditukar dengan apa --}}
                    <div class="cb-card">
                        <h2 class="h6 mb-2">2. Mau ditukar dengan apa<x-wajib /></h2>
                        <p class="small text-muted">
                            Colorbox tidak mengembalikan uang, hanya menukar barang.
                            <x-tip teks="Ditukar dengan ukuran lain, warna lain, atau produk lain yang harganya sama." />
                        </p>
                        <div class="row g-2" role="radiogroup" aria-label="Mau ditukar dengan apa">
                            @foreach([
                                ['ganti_ukuran', 'Tukar ukuran', 'Produknya sama, ukurannya diganti.'],
                                ['ganti_warna', 'Tukar warna', 'Produknya sama, warnanya diganti.'],
                                ['ganti_produk', 'Tukar produk lain', 'Ditukar produk lain yang harganya sama.'],
                                ['barang_rusak', 'Barang rusak atau cacat', 'Misalnya jahitan terbuka atau ada sobekan.'],
                            ] as [$nilai, $judul, $penjelasan])
                                <div class="col-md-6">
                                    <label class="option-card position-relative">
                                        <input type="radio" name="exchange_type" value="{{ $nilai }}" required
                                               @checked(old('exchange_type', 'ganti_ukuran') === $nilai)>
                                        <span class="d-flex gap-2">
                                            <span class="check mt-1" aria-hidden="true"></span>
                                            <span>
                                                <strong>{{ $judul }}</strong>
                                                <span class="small text-muted d-block">{{ $penjelasan }}</span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <label class="form-label mt-3" for="exchange_note">Ukuran atau warna yang Anda mau (opsional)</label>
                        <input type="text" class="form-control" id="exchange_note" name="exchange_note"
                               maxlength="191" value="{{ old('exchange_note') }}"
                               placeholder="Contoh: ganti ke ukuran L, atau ganti warna BLACK">
                    </div>

                    {{-- 3. Alasan dan foto --}}
                    <div class="cb-card">
                        <h2 class="h6 mb-3">3. Alasan dan bukti</h2>
                        <label class="form-label" for="reason">Alasan<x-wajib /></label>
                        <input type="text" class="form-control" id="reason" name="reason" maxlength="191" required
                               value="{{ old('reason') }}" placeholder="Contoh: ukuran kekecilan, jahitan ketiak sobek">
                        <div class="form-text mb-3">Tulis sejelas mungkin supaya pemeriksaannya cepat.</div>

                        {{-- Dua bukti, dan keduanya wajib karena alasan
                             yang berbeda.

                             Fotonya naik dari satu menjadi paling banyak
                             empat, sebab keluhan yang paling sering
                             menuntut sudut pandang yang berbeda: label
                             ukurannya, bagian yang rusak, dan keseluruhan
                             barangnya. Dengan satu foto, petugas selalu
                             kurang satu sudut lalu harus bertanya lagi,
                             dan pelanggan menunggu sehari hanya untuk
                             mengirim gambar kedua.

                             Videonya wajib sebab foto barang rusak tidak
                             bisa membedakan barang yang DATANG rusak dari
                             barang yang rusak SESUDAH dipakai. Rekaman
                             pembukaan paket menjawab itu sekali jalan,
                             dan tanpanya yang tersisa cuma adu pernyataan
                             antara pembeli dan penjual. --}}
                        <x-unggah-media
                            nama="photos"
                            judul="Foto barang"
                            ket="JPG atau PNG, 1 sampai {{ \App\Http\Controllers\ReturController::FOTO_PALING_BANYAK }} foto, satu foto paling besar 2 MB. Potret bagian yang bermasalah beserta label ukurannya."
                            ikon="camera"
                            terima="image/jpeg,image/png"
                            :banyak="true"
                            :wajib="true" />

                        <x-unggah-media
                            nama="video"
                            judul="Video pembukaan paket"
                            ket="MP4, satu berkas, paling besar 50 MB. Rekam dari kardus masih utuh sampai barangnya terlihat, tanpa dipotong."
                            ikon="camera-video"
                            terima="video/mp4"
                            :wajib="true" />

                        <p class="small text-muted mb-0">
                            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                            Video pembukaan paket diminta supaya barang yang datang rusak bisa
                            dibedakan dari barang yang rusak sesudah dipakai. Tanpa rekaman itu,
                            pengajuan barang rusak atau salah kirim sukar dinilai.
                        </p>
                    </div>

                    {{-- 4. Cara mengembalikan barang --}}
                    <div class="cb-card" data-retur-cara>
                        <h2 class="h6 mb-3">4. Cara mengembalikan barang<x-wajib /></h2>
                        <div class="row g-2 mb-3" role="radiogroup" aria-label="Cara mengembalikan barang">
                            <div class="col-md-6">
                                <label class="option-card position-relative">
                                    <input type="radio" name="method" value="toko" required
                                           @checked(old('method', 'toko') === 'toko')>
                                    <span class="d-flex gap-2">
                                        <span class="check mt-1" aria-hidden="true"></span>
                                        <span>
                                            <strong>Antar sendiri ke toko</strong>
                                            <span class="small text-muted d-block">
                                                Petugas toko memeriksa barangnya langsung di depan Anda.
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="option-card position-relative">
                                    <input type="radio" name="method" value="kurir" @checked(old('method') === 'kurir')>
                                    <span class="d-flex gap-2">
                                        <span class="check mt-1" aria-hidden="true"></span>
                                        <span>
                                            <strong>Kirim lewat jasa kirim</strong>
                                            <span class="small text-muted d-block">
                                                Ongkos kirimnya ditanggung Colorbox dan diganti bersama barang penggantinya.
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- Isian jalur antar ke toko --}}
                        <div data-retur-blok="toko">
                            @if($tokoWajib)
                                {{-- Pembelian Tap Try and Collect dikunci ke toko asalnya.
                                     Bukan sekadar aturan: barang itu dulu keluar dari rak
                                     toko tersebut, jadi kalau dikembalikan ke toko lain,
                                     rak toko asal kekurangan satu dan rak toko penerima
                                     kelebihan satu tanpa bisa ditelusuri siapa pun. --}}
                                <span class="form-label">Toko tujuan</span>
                                <div class="alert alert-light small mb-0">
                                    <strong class="d-block">{{ $tokoWajib->name }}</strong>
                                    {{ $tokoWajib->address }}, {{ $tokoWajib->city }}{{ $tokoWajib->floor ? ', '.$tokoWajib->floor : '' }}
                                    <div class="text-muted mt-2 mb-0">
                                        Barang ini Anda ambil di toko tersebut, jadi pengembaliannya ke sana.
                                    </div>
                                </div>
                                <input type="hidden" name="store_id" value="{{ $tokoWajib->id }}">
                            @else
                                <label class="form-label" for="store_id">Toko tujuan</label>
                                <select class="form-select" id="store_id" name="store_id">
                                    <option value="">Pilih toko</option>
                                    @foreach($daftarToko as $toko)
                                        <option value="{{ $toko->id }}" @selected((int) old('store_id') === $toko->id)>
                                            {{ $toko->name }}, {{ $toko->city }}{{ $toko->floor ? ', '.$toko->floor : '' }}{{ $toko->tulisanJarak() ? ' ('.$toko->tulisanJarak().')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            {{-- Tanggal dan jam kedatangan memakai tampilan yang sama
                                 persis dengan Tap Try and Collect, sebab aturannya
                                 memang sama: jam yang sudah lewat tidak boleh dipilih.
                                 Tanggalnya dikirim sebagai formulir GET terpisah di
                                 ujung halaman, karena HTML melarang formulir di dalam
                                 formulir, lalu jadwal jamnya disusun ulang di server
                                 mengikuti tanggal itu. --}}
                            <span class="form-label mt-3 d-block">Tanggal kedatangan</span>

                            {{-- Kenapa hari ini tidak ada di daftar.

                                 Tanpa kalimat ini, tanggal hari ini sekadar
                                 HILANG dari layar sesudah pukul 15.00, dan
                                 pelanggan yang membuka halaman pukul enam
                                 sore tidak punya cara mengetahui sebabnya.
                                 Dia akan mengira halamannya rusak, atau
                                 mengira Colorbox tidak melayani retur hari
                                 itu sama sekali.

                                 Yang ditutup pemesanannya, bukan tokonya:
                                 toko tetap melayani sampai malam untuk
                                 jadwal yang sudah dipesan sebelumnya. --}}
                            @if($pesanHariIniDitutup)
                                <p class="small text-muted mb-2">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                    Pemesanan hari ini sudah ditutup pukul {{ $batasPesanRetur }},
                                    jadi tanggal paling awal yang bisa dipilih adalah besok.
                                    Toko sendiri tetap melayani retur sampai pukul {{ $layananReturSampai }}
                                    untuk jadwal yang sudah dipesan sebelumnya.
                                </p>
                            @endif

                            @include('partials.pilih-tanggal', [
                                'daftarTanggal' => $daftarTanggal,
                                'terpilih' => $tanggalTerpilih,
                                'idForm' => 'formTanggalRetur',
                            ])
                            <input type="hidden" name="return_date" value="{{ $tanggalTerpilih }}">

                            <span class="form-label mt-3 d-block">Jam kedatangan</span>
                            <p class="slot-jam-sekarang mb-2">
                                <x-jam-wib />, daftar jam di bawah ini mengikuti waktu ini.
                            </p>
                            {{-- Dulu daftar jamnya digambar ulang di sini dengan
                                 tulisan yang sedikit berbeda dari halaman lain.
                                 Sekarang memakai partial yang sama dengan Tap Try
                                 and Collect, donasi, dan checkout, supaya empat
                                 halaman yang meminta hal yang sama juga terlihat
                                 sama. Bedanya cuma nilainya: retur menyebut jam
                                 kedatangan, bukan memesan slot berkuota. --}}
                            @include('partials.pilih-jam', [
                                'nama' => 'return_time',
                                'terpilih' => old('return_time'),
                                'kunciNilai' => 'jam',
                            ])
                        </div>

                        {{-- Isian jalur jasa kirim.

                             Di sini TIDAK ada isian apa pun, dan itu disengaja.
                             Dulu pelanggan diminta mengetik nama jasa kirim yang
                             akan dipakai, tetapi isian itu tidak ada gunanya: jasa
                             kirim yang ada berbeda di tiap daerah, dan pelanggan
                             baru tahu dia memakai yang mana ketika sudah sampai di
                             kantornya. Nama jasa kirimnya baru dicatat belakangan,
                             bersama nomor resi, setelah paketnya benar benar
                             dikirim. Toko tujuan dan jam kedatangan juga tidak ada,
                             sebab barangnya dikirim ke gudang pusat, bukan diantar
                             sendiri ke toko. --}}
                        <div data-retur-blok="kurir">
                            <div class="alert alert-light small mb-0">
                                <strong class="d-block mb-1">Urutannya begini</strong>
                                <ol class="mb-0 ps-3">
                                    <li>Pengajuan ini diperiksa tim Colorbox dulu, biasanya satu hari kerja.</li>
                                    <li>Setelah disetujui, halaman rincian retur ini menampilkan
                                        <strong>slip pengembalian</strong> beserta alamat gudang tujuannya.</li>
                                    <li>Cetak slip itu, lalu tempelkan di paket. Boleh juga ditulis tangan
                                        kalau Anda tidak punya printer, asalkan nomor returnya benar.</li>
                                    <li>Bungkus barangnya, bawa ke kantor jasa kirim mana pun yang paling dekat
                                        dari rumah Anda, lalu simpan struknya.</li>
                                    <li>Masukkan nama jasa kirim dan nomor resi dari struk itu di halaman
                                        rincian retur.</li>
                                    <li>Begitu paketnya sampai, barangnya diperiksa dan penggantinya dikirim balik
                                        beserta nomor resinya.</li>
                                </ol>
                                <p class="mb-0 mt-2">
                                    Bebas memakai jasa kirim mana saja, tidak ada yang diwajibkan. Ongkos kirimnya
                                    ditanggung Colorbox dan diganti bersama barang penggantinya.
                                </p>
                                @if($batasTiba)
                                    <p class="mb-0 mt-2">
                                        <strong>Perhatikan tanggalnya.</strong> Paket Anda harus sudah sampai
                                        paling lambat {{ tanggal_id($batasTiba, true) }}. Jadi jangan menunggu
                                        sampai hari terakhir, sebab perjalanan paketnya butuh beberapa hari.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                        <a href="{{ route('retur.index') }}" class="btn btn-outline-dark">Batal</a>
                        <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Formulir GET pendamping untuk pemilih tanggal kedatangan. Ditulis di
         ujung halaman, di luar formulir pengajuan retur, sebab HTML melarang
         formulir di dalam formulir. --}}
    @include('partials.form-tanggal', [
        'idForm' => 'formTanggalRetur',
        'rute' => 'retur.create',
        'parameter' => $kode,
    ])
@endsection

@push('skrip')
    <script>
        // Blok mana yang terlihat sudah diatur CSS lewat :has(), jadi
        // penukarannya tetap jalan walaupun skrip ini gagal dimuat. Yang
        // dikerjakan di sini hanya mematikan isian pada blok yang sedang
        // disembunyikan, supaya nilainya tidak ikut terkirim dan tidak
        // dinilai wajib oleh peramban. Kalau skrip ini tidak jalan pun,
        // server tetap mengabaikan isian yang bukan milik cara yang dipilih.
        document.addEventListener('DOMContentLoaded', function () {
            var kotak = document.querySelector('[data-retur-cara]');
            if (!kotak) { return; }

            function matikanYangTersembunyi() {
                var terpilih = kotak.querySelector('[name="method"]:checked');
                var cara = terpilih ? terpilih.value : 'toko';

                Array.prototype.forEach.call(kotak.querySelectorAll('[data-retur-blok]'), function (blok) {
                    var dipakai = blok.dataset.returBlok === cara;

                    Array.prototype.forEach.call(blok.querySelectorAll('input, select'), function (satu) {
                        satu.disabled = !dipakai;
                    });
                });
            }

            kotak.addEventListener('change', function (e) {
                if (e.target.name === 'method') { matikanYangTersembunyi(); }
            });

            matikanYangTersembunyi();
        });

        // Nama berkas dan pratinjau foto ditampilkan tanpa data yang ditanam di JavaScript.
        document.addEventListener('DOMContentLoaded', function () {
            var isian = document.getElementById('photo');
            if (!isian) { return; }

            var nama = document.querySelector('[data-nama-foto]');
            var pratinjau = document.querySelector('[data-pratinjau-foto]');

            isian.addEventListener('change', function () {
                var berkas = isian.files[0];
                if (!berkas) {
                    if (nama) { nama.textContent = 'Pilih foto barang'; }
                    if (pratinjau) { pratinjau.hidden = true; }
                    return;
                }
                if (nama) { nama.textContent = berkas.name; }
                if (pratinjau) {
                    pratinjau.src = URL.createObjectURL(berkas);
                    pratinjau.hidden = false;
                }
            });
        });
    </script>
@endpush
