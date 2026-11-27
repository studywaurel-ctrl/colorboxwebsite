@extends('layouts.app')

@section('judul', 'Retur '.$retur->return_code)

@section('isi')
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
                        <li class="breadcrumb-item active" aria-current="page">{{ $retur->return_code }}</li>
                    </ol>
                </nav>

                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <div>
                        <h1 class="page-title">Penukaran {{ $retur->return_code }}</h1>
                        <p class="text-muted mb-0">
                            @if($retur->pesanan)
                                Pesanan {{ $retur->pesanan->order_code }},
                            @elseif($retur->purchase_code)
                                Pembelian di toko {{ $retur->purchase_code }},
                            @endif
                            diajukan {{ tanggal_id($retur->created_at, true) }} pukul {{ $retur->created_at->format('H:i') }}
                        </p>
                    </div>
                    <x-status-retur :status="$retur->status" />
                </div>

                @include('retur.partials.tab', ['aktif' => 'rincian', 'retur' => $retur])

                {{-- Ringkasan pengajuan: nomor, pesanan asal, status, dan
                     batas kirimnya dalam satu kotak.

                     Empat keterangan ini yang paling sering dicari
                     pelanggan ketika membuka halaman ini lagi beberapa
                     hari kemudian, dan sebelumnya tersebar di tiga tempat
                     berbeda di halaman yang sama. --}}
                <div class="cb-card mb-3">
                    <div class="row g-3 small">
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted">Nomor pengiriman retur</div>
                            <span class="code-pill">{{ $retur->return_code }}</span>
                            <div class="text-muted mt-1">
                                Sebutkan nomor ini kalau menghubungi layanan pelanggan.
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted">Pesanan asal</div>
                            @if($retur->pesanan)
                                <a href="{{ route('pesanan.show', $retur->pesanan) }}">
                                    <strong>{{ $retur->pesanan->order_code }}</strong>
                                </a>
                            @elseif($retur->purchase_code)
                                <strong>{{ $retur->purchase_code }}</strong>
                                <div class="text-muted">Pembelian di toko</div>
                            @else
                                <span class="text-muted">Tidak tercatat</span>
                            @endif
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted">Status</div>
                            <x-status-retur :status="$retur->status" />
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted">Batas barang sampai</div>
                            @if($retur->arrive_by)
                                {{-- Ditulis sebagai tanggal barang harus SUDAH
                                     SAMPAI, bukan tanggal terakhir mengirim.
                                     Bedanya besar: pelanggan yang mengirim pada
                                     hari keenam mengira masih sempat, padahal
                                     paketnya baru tiba hari kesembilan. --}}
                                <strong class="{{ $retur->terlambatTiba() && ! $retur->received_at ? 'text-danger' : '' }}">
                                    {{ tanggal_id($retur->arrive_by, true) }}
                                </strong>
                                <div class="text-muted mt-1">
                                    @if($retur->terlambatTiba() && ! $retur->received_at)
                                        Sudah lewat. Barangnya tetap kami periksa, tetapi petugas
                                        yang menentukan apakah masih bisa ditukar.
                                    @else
                                        Dihitung sampai barangnya tiba di Colorbox, bukan sampai
                                        Anda mengirim.
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Ditentukan sesudah disetujui</span>
                            @endif
                        </div>
                    </div>

                    {{-- Instruksi pengembalian, terlipat.

                         Isinya sama untuk setiap pengajuan dan panjangnya
                         tujuh baris. Dibiarkan terbuka, tujuh baris itu
                         mendorong keterangan yang berbeda beda tiap
                         pengajuan turun ke bawah layar. --}}
                    <div class="accordion mt-3" id="instruksiRetur">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#isiInstruksiRetur"
                                        aria-expanded="false" aria-controls="isiInstruksiRetur">
                                    Cara mengembalikan barangnya
                                </button>
                            </h2>
                            <div id="isiInstruksiRetur" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    <ol class="small text-muted ps-3 mb-2">
                                        <li>Tunggu sampai pengajuan ini berstatus Disetujui. Barang yang
                                            dikirim sebelum disetujui akan dikembalikan lagi.</li>
                                        <li>Pastikan label harga masih menempel dan barangnya belum
                                            dipakai maupun dicuci.</li>
                                        <li>Unduh formulir retur di bawah, cetak, lalu masukkan ke dalam
                                            paket bersama barangnya.</li>
                                        <li>Bungkus rapi dan tuliskan nomor
                                            <strong>{{ $retur->return_code }}</strong> di bagian luar paket.</li>
                                        <li>Kirim ke alamat gudang retur, lalu simpan struk jasa kirimnya.</li>
                                        <li>Masukkan nomor resinya di halaman ini supaya kami tahu paket
                                            Anda sedang dalam perjalanan.</li>
                                    </ol>
                                    <p class="small text-muted mb-0">
                                        Barang diperiksa petugas Colorbox setelah sampai di gudang.
                                        Hasil pemeriksaannya muncul di halaman ini, dan Anda dikabari
                                        lewat email serta pemberitahuan akun.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($retur->reject_reason)
                    {{-- Pengajuan yang ditolak: alasannya ditulis paling atas
                         dan paling jelas, sebab itu yang dicari pelanggan
                         begitu membuka halaman ini. Catatan tambahan dari
                         petugas menyusul di bawahnya kalau ada. --}}
                    <div class="alert alert-danger">
                        <strong class="d-block mb-1">Alasan penolakan</strong>
                        {{ \App\Models\ReturnRequest::labelAlasanTolak($retur->reject_reason) }}
                        @if($retur->admin_note)
                            <div class="small mt-1">{{ $retur->admin_note }}</div>
                        @endif
                    </div>
                @elseif($retur->admin_note)
                    <div class="alert alert-info">
                        <strong class="d-block mb-1">Catatan dari tim Colorbox</strong>
                        {{ $retur->admin_note }}
                    </div>
                @endif


                <div class="row g-3">
                    <div class="col-lg-7">
                        @if($retur->method === 'toko' && $retur->toko && in_array($retur->status, ['menunggu_validasi', 'disetujui']))
                            <div class="cb-card">
                                <h2 class="h6">Bawa barang ke toko</h2>
                                <p class="small mb-1">
                                    <strong>{{ $retur->toko->name }}</strong><br>
                                    {{ $retur->toko->address }}, {{ $retur->toko->city }}{{ $retur->toko->floor ? ', '.$retur->toko->floor : '' }}
                                </p>
                                @if($retur->return_date)
                                    <div class="info-line">
                                        <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                        <span>
                                            Jadwal Anda {{ tanggal_id($retur->return_date, true) }}
                                            pukul {{ $retur->return_time }} WIB
                                        </span>
                                    </div>
                                @endif
                                <div class="info-line mb-3">
                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                    <span>Buka {{ jam_singkat($retur->toko->open_time) }} sampai {{ jam_singkat($retur->toko->close_time) }}</span>
                                </div>
                                <div class="d-flex gap-3 align-items-center flex-wrap">
                                    <div class="qr-box"
                                         data-qr="{{ route('staf.verifikasi', ['jenis' => 'retur', 'kode' => $retur->return_code]) }}"
                                         data-qr-label="{{ $retur->return_code }}"></div>
                                    <p class="small text-muted mb-0" style="max-width: 260px">
                                        Tunjukkan QR ini ke petugas toko. Petugas memeriksa barangnya,
                                        lalu penukarannya diproses.
                                    </p>
                                </div>
                            </div>
                        @elseif($retur->method === 'kurir')
                            {{-- Jalur jasa kirim. Isinya berubah mengikuti sudah sampai
                                 mana pengajuannya, supaya pelanggan selalu melihat satu
                                 langkah yang jelas, bukan daftar panjang yang belum
                                 tentu berlaku untuknya sekarang. --}}
                            <div class="cb-card">
                                <h2 class="h6">Kirim lewat jasa kirim</h2>

                                @if($retur->status === 'menunggu_validasi')
                                    <p class="small text-muted mb-0">
                                        <strong>Jangan dikirim dulu.</strong> Sedang diperiksa, biasanya
                                        satu hari kerja. Alamat tujuannya muncul di sini begitu disetujui.
                                    </p>
                                @elseif($retur->status === 'disetujui')
                                    <p class="small mb-2">Pengajuan disetujui. Kirim barangnya ke alamat ini:</p>
                                    <div class="alert alert-light small mb-3">
                                        <strong class="d-block">Gudang Retur Colorbox</strong>
                                        {{ setting('return_address', 'Jl. Daan Mogot KM 12 No. 9, Cengkareng, Jakarta Barat 11730') }}<br>
                                        Telepon {{ setting('return_phone', '021 5555 1234') }}
                                    </div>
                                    <ol class="small text-muted ps-3">
                                        <li>Bungkus barangnya rapi, sertakan kertas berisi nomor
                                            <strong>{{ $retur->return_code }}</strong>.</li>
                                        <li>Bawa ke kantor {{ $retur->courier ?: 'jasa kirim pilihan Anda' }}.</li>
                                        <li>Simpan struknya, lalu masukkan nomor resinya di bawah ini.</li>
                                    </ol>

                                    {{-- Slip pengembalian. Dicetak lalu ditempel di
                                         bungkus paket, supaya admin bisa
                                         mencocokkan paketnya dengan pengajuan ini. --}}
                                    {{-- Dua tombol, dua kebutuhan yang berbeda.

                                         Unduh menurunkan berkasnya ke komputer,
                                         jadi masih ada besok dan bisa dikirim ke
                                         orang lain. Cetak membuka halaman yang
                                         sama untuk langsung dicetak sekarang.
                                         Sebelumnya hanya ada yang kedua, dan
                                         begitu tabnya ditutup dokumennya hilang. --}}
                                    <div class="d-grid gap-2 mb-3">
                                        <a href="{{ route('retur.slip', [$retur, 'unduh' => 1]) }}"
                                           class="btn btn-dark">
                                            <i class="bi bi-download me-1" aria-hidden="true"></i>
                                            Unduh formulir retur
                                        </a>
                                        <a href="{{ route('retur.slip', $retur) }}" target="_blank" rel="noopener"
                                           class="btn btn-outline-dark">
                                            <i class="bi bi-printer me-1" aria-hidden="true"></i>
                                            Cetak langsung
                                        </a>
                                    </div>

                                    <form action="{{ route('retur.resi', $retur) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        {{-- Nama jasa kirim ditanyakan di sini, bukan waktu
                                             mengajukan. Pelanggan bebas memakai jasa kirim mana
                                             saja, dan dia baru tahu memakai yang mana ketika
                                             struknya sudah ada di tangan. --}}
                                        <label class="form-label" for="courier">Jasa kirim yang Anda pakai</label>
                                        <input type="text" class="form-control mb-3" id="courier" name="courier"
                                               maxlength="40" required autocomplete="off"
                                               value="{{ old('courier', $retur->courier) }}"
                                               placeholder="Tulis sesuai struknya, misalnya JNE atau J&amp;T">

                                        <label class="form-label" for="tracking_number">Nomor resi dari kantor jasa kirim</label>
                                        <div class="d-flex gap-2">
                                            <input type="text" class="form-control" id="tracking_number"
                                                   name="tracking_number" maxlength="50" required
                                                   autocomplete="off" style="text-transform: uppercase"
                                                   placeholder="Contoh: JP1234567890">
                                            <button type="submit" class="btn btn-primary text-nowrap">Simpan Resi</button>
                                        </div>
                                        <div class="form-text">
                                            Ada di struk dari kantor jasa kirim.
                                            <x-tip teks="Nomor resi inilah yang dipakai Colorbox untuk menunggu paket Anda sampai." />
                                        </div>
                                    </form>
                                @elseif($retur->tracking_number)
                                    <div class="info-line">
                                        <i class="bi bi-truck" aria-hidden="true"></i>
                                        <span>
                                            {{ $retur->courier ?: 'Jasa kirim' }},
                                            resi <span class="code-pill">{{ $retur->tracking_number }}</span>
                                        </span>
                                    </div>
                                    @if($retur->shipped_at)
                                        <div class="info-line">
                                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                                            <span>Dikirim {{ tanggal_id($retur->shipped_at, true) }}
                                                pukul {{ $retur->shipped_at->format('H:i') }}</span>
                                        </div>
                                    @endif
                                    <div class="d-flex gap-2 flex-wrap mt-3">
                                        <a href="{{ route('retur.slip', [$retur, 'unduh' => 1]) }}"
                                           class="btn btn-outline-dark btn-sm">
                                            <i class="bi bi-download me-1" aria-hidden="true"></i>
                                            Unduh formulir retur
                                        </a>
                                        <a href="{{ route('retur.slip', $retur) }}" target="_blank" rel="noopener"
                                           class="btn btn-outline-dark btn-sm">
                                            <i class="bi bi-printer me-1" aria-hidden="true"></i>
                                            Cetak ulang
                                        </a>
                                    </div>
                                    @if($retur->replacement_tracking_number)
                                        <div class="info-line">
                                            <i class="bi bi-arrow-return-left" aria-hidden="true"></i>
                                            <span>
                                                Barang pengganti dikirim
                                                {{ $retur->replacement_courier ? 'lewat '.$retur->replacement_courier : '' }}
                                                dengan resi
                                                <span class="code-pill">{{ $retur->replacement_tracking_number }}</span>
                                            </span>
                                        </div>
                                    @endif
                                @else
                                    <p class="small text-muted mb-0">
                                        Pengajuan ini sudah ditutup, jadi tidak ada barang yang perlu dikirim.
                                    </p>
                                @endif
                            </div>
                        @endif

                        {{-- Track Order barang penggantinya. Nomor resinya
                             dicetak tebal beserta tautan ke halaman pelacakan
                             jasa kirimnya, sama persis dengan pesanan biasa,
                             sebab paketnya memang berjalan dengan cara yang
                             sama. --}}
                        @if($retur->method !== 'toko' && $retur->replacement_tracking_number)
                            <x-lacak-resi
                                judul="Track Order Barang Pengganti"
                                :kurir="$retur->replacement_courier"
                                :resi="$retur->replacement_tracking_number"
                                :dikirim-pada="$retur->updated_at" />
                        @endif

                        <div class="cb-card">
                            <h2 class="h6">Status pengajuan</h2>
                            <ul class="event-log mt-3">
                                @foreach($riwayat as $baris)
                                    <li>
                                        <div class="fw-600">{{ $baris['status'] }}</div>
                                        <div class="small text-muted">{{ $baris['keterangan'] }}</div>
                                        <div class="small text-soft">
                                            {{ tanggal_id($baris['waktu'], true) }} pukul {{ $baris['waktu']->format('H:i') }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="cb-card">
                            <h2 class="h6">Barang yang diajukan</h2>
                            @foreach($retur->item as $baris)
                                <div class="d-flex justify-content-between gap-2 small mb-2">
                                    <div>
                                        <div class="fw-600">{{ $baris->product_name }}</div>
                                        <div class="text-muted">{{ $baris->note }}</div>
                                    </div>
                                    <div class="text-nowrap">{{ $baris->quantity }} pcs</div>
                                </div>
                            @endforeach
                            <hr>
                            <div class="summary-row small">
                                <span>Mau ditukar dengan</span>
                                <span class="text-end">{{ \App\Models\ReturnRequest::labelPenukaran($retur->exchange_type ?: 'ganti_ukuran') }}</span>
                            </div>
                            @if($retur->exchange_note)
                                <div class="summary-row small">
                                    <span>Permintaan Anda</span>
                                    <span class="text-end">{{ $retur->exchange_note }}</span>
                                </div>
                            @endif
                            <div class="summary-row small">
                                <span>Cara mengembalikan</span>
                                <span>{{ $retur->method === 'toko' ? 'Antar ke toko' : 'Jasa kirim' }}</span>
                            </div>
                            @if($retur->toko)
                                <div class="summary-row small"><span>Toko</span><span class="text-end">{{ $retur->toko->name }}</span></div>
                            @endif
                            <div class="summary-row small"><span>Alasan</span><span class="text-end">{{ $retur->reason }}</span></div>

                            {{-- Bukti yang pelanggan kirim sendiri,
                                 digambar komponen yang sama dengan yang
                                 dipakai admin dan kasir. Dengan begitu
                                 pelanggan melihat persis apa yang dilihat
                                 petugas yang memeriksanya. --}}
                            <div class="mt-2">
                                <x-bukti-retur :retur="$retur" judul="Bukti yang Anda kirim" />
                            </div>
                        </div>

                        {{-- Penutup pengajuan. Dulu retur berhenti di status
                             Sedang Diproses dan tidak pernah berlanjut, sebab
                             langkah sesudahnya tidak punya pemilik. Yang paling
                             tahu urusannya beres adalah pelanggan, sebab dialah
                             yang memegang barang penggantinya. --}}
                        @if($retur->menungguKonfirmasi())
                            <div class="cb-card mt-3">
                                <h2 class="h6">Sudah terima barang penggantinya?</h2>
                                <p class="small text-muted">
                                    @if($retur->method === 'toko')
                                        Barang pengganti Anda sudah diserahkan di
                                        {{ $retur->toko?->name ?? 'toko' }}.
                                    @else
                                        Barang pengganti Anda sudah dikirim
                                        @if($retur->replacement_tracking_number)
                                            dengan resi
                                            <span class="code-pill">{{ $retur->replacement_tracking_number }}</span>.
                                        @else
                                            ke alamat Anda.
                                        @endif
                                    @endif
                                    Tekan tombol ini supaya pengajuannya ditutup. Kalau sampai tiga hari
                                    tidak ditekan, sistem yang menutupnya sendiri.
                                </p>
                                <form action="{{ route('retur.konfirmasi', $retur) }}" method="POST"
                                      data-confirm="Konfirmasi barang pengganti {{ $retur->return_code }} sudah Anda terima?">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                        Barang Pengganti Sudah Saya Terima
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if($retur->status === 'menunggu_validasi')
                            <form action="{{ route('retur.batal', $retur) }}" method="POST" class="mt-3"
                                  data-confirm="Batalkan pengajuan {{ $retur->return_code }}?">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-outline-dark w-100">Batalkan Pengajuan</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
