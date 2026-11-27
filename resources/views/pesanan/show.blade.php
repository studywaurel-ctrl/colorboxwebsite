@extends('layouts.app')

@section('judul', 'Pesanan '.$pesanan->order_code)

@section('isi')
    @php
        $nilaiPoin = (int) setting('point_value', 1000);
        $pembayaran = $pesanan->pembayaran;
        $labelBayar = [
            'menunggu' => 'Menunggu Pembayaran',
            'lunas' => 'Lunas',
            'gagal' => 'Gagal',
            'kedaluwarsa' => 'Kedaluwarsa',
            'dibatalkan' => 'Dibatalkan',
        ];
        $alasanBatal = [
            'Ingin mengganti warna atau ukuran',
            'Ingin mengganti alamat pengiriman',
            'Ingin memakai voucher lain',
            'Berubah pikiran',
        ];
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
                        <li class="breadcrumb-item"><a href="{{ route('pesanan.index') }}">Pesanan Saya</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $pesanan->order_code }}</li>
                    </ol>
                </nav>

                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <div>
                        <h1 class="page-title">Pesanan {{ $pesanan->order_code }}</h1>
                        <p class="text-muted mb-0">
                            Dibuat {{ tanggal_id($pesanan->created_at, true) }} pukul {{ $pesanan->created_at->format('H:i') }}
                        </p>
                    </div>
                    <x-status-pesanan :status="$pesanan->status" />
                </div>

                @if($pesanan->status === 'PENDING_PAYMENT' && $pembayaran)
                    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span>
                            Selesaikan pembayaran
                            @if($pembayaran->expired_at)
                                sebelum {{ tanggal_id($pembayaran->expired_at, true) }} pukul {{ $pembayaran->expired_at->format('H:i') }}
                            @endif
                            agar pesanan tidak dibatalkan sistem.
                        </span>
                        <a href="{{ route('pembayaran.show', $pembayaran) }}" class="btn btn-dark btn-sm">Bayar Sekarang</a>
                    </div>
                @endif

                @if($pesanan->status === 'CANCELLED')
                    <div class="alert alert-danger">
                        Pesanan ini dibatalkan
                        @if($pesanan->cancelled_at)
                            pada {{ tanggal_id($pesanan->cancelled_at, true) }} pukul {{ $pesanan->cancelled_at->format('H:i') }}
                        @endif
                        . Stok yang sempat dikunci dan poin yang dipakai sudah dikembalikan.
                    </div>
                @endif

                {{-- Pengiriman dan riwayat status --}}
                <div class="cb-card">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <h2 class="h6 mb-0">{{ $pesanan->fulfillment_method === 'STORE_PICKUP' ? 'Pengambilan di Toko' : 'Pengiriman' }}</h2>
                    </div>

                    <div class="row g-3 small mb-2">
                        @if($pesanan->fulfillment_method === 'STORE_PICKUP')
                            <div class="col-sm-6">
                                <div class="text-muted">Toko pengambilan</div>
                                <strong>{{ $pesanan->toko ? $pesanan->toko->name : 'Belum dipilih' }}</strong>
                                @if($pesanan->toko)
                                    <div class="text-muted">{{ $pesanan->toko->address }}, {{ $pesanan->toko->city }}</div>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted">Jam buka toko</div>
                                <strong>
                                    {{ $pesanan->toko ? jam_singkat($pesanan->toko->open_time).' sampai '.jam_singkat($pesanan->toko->close_time) : '-' }}
                                </strong>
                            </div>

                            {{-- Perkiraan siap diambil.

                                 Kalimatnya berubah mengikuti keadaan pesanan:
                                 sebelum dibayar yang diterangkan aturannya,
                                 sesudah dibayar jamnya, dan sesudah kasir
                                 menandainya siap yang ditulis kenyataannya,
                                 bukan dugaan lagi. Semua itu dihitung
                                 App\Services\EstimasiTtc, bukan di view, supaya
                                 halaman kasir dan halaman ini tidak mungkin
                                 menampilkan dua jawaban berbeda. --}}
                            @php $perkiraan = \App\Services\EstimasiTtc::untukPesanan($pesanan); @endphp
                            @if($perkiraan)
                                <div class="col-12">
                                    <div class="text-muted">Perkiraan siap diambil</div>
                                    <strong>{{ $perkiraan['tulisan'] }}</strong>
                                    @if($perkiraan['jadwal'])
                                        <div class="text-muted">
                                            Jadwal yang Anda pilih {{ tanggal_id($perkiraan['jadwal'], true) }}
                                            pukul {{ $perkiraan['jadwal']->format('H.i') }} WIB.
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                            {{-- Rincian pengirimannya ada di kotak Track Order
                                 di bawah, supaya nomor resinya tidak terselip
                                 di antara kolom kolom kecil. --}}
                            <div class="col-sm-6">
                                <div class="text-muted">Cara menerima</div>
                                <strong>Dikirim kurir</strong>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted">Alamat tujuan</div>
                                <strong>{{ $pesanan->alamat?->recipient_name ?? kosong() }}</strong>
                            </div>
                        @endif
                    </div>

                    {{-- Bukti foto penyerahan paket. Fotonya diambil kurir saat
                         barangnya diserahkan, lalu tautannya dikirim penyedia
                         jasa kirim. Colorbox tidak pernah membuat foto ini
                         sendiri, jadi kalau kurirnya tidak memotret, bagian ini
                         memang tidak muncul. --}}
                    @if($pesanan->pengiriman && $pesanan->pengiriman->proof_photo_url)
                        <div class="mt-3">
                            <div class="text-muted small mb-1">Bukti paket diterima</div>
                            <a href="{{ $pesanan->pengiriman->proof_photo_url }}" target="_blank" rel="noopener">
                                <img src="{{ $pesanan->pengiriman->proof_photo_url }}"
                                     alt="Foto paket saat diserahkan kurir"
                                     class="img-fluid rounded" style="max-width: 320px">
                            </a>
                            <div class="small text-soft mt-1">
                                Foto diambil kurir
                                {{ $pesanan->pengiriman->delivered_at ? 'pada '.tanggal_id($pesanan->pengiriman->delivered_at, true).' pukul '.$pesanan->pengiriman->delivered_at->format('H:i') : '' }}
                                saat paket diserahkan.
                            </div>
                        </div>
                    @endif

                    @include('pesanan.partials.riwayat', ['pesanan' => $pesanan, 'riwayat' => $riwayat])
                </div>

                @if($pesanan->fulfillment_method !== 'STORE_PICKUP')
                    {{-- Track Order. Satu kotak berisi nomor resi dan tautan ke
                         halaman pelacakan jasa kirimnya, menggantikan tiga
                         langkah perjalanan yang dulu digambar sendiri oleh
                         sistem padahal tidak pernah dilaporkan siapa pun. --}}
                    <x-lacak-resi
                        :kurir="$pesanan->pengiriman?->courier ?: $pesanan->jasaKirim?->courier"
                        :layanan="$pesanan->pengiriman?->service"
                        :resi="$pesanan->pengiriman?->tracking_number"
                        :tautan="$pesanan->pengiriman?->tracking_url"
                        :dikirim-pada="$pesanan->pengiriman?->shipped_at" />
                @endif

                {{-- Barang yang dipesan --}}
                <div class="cb-card">
                    <h2 class="h6 mb-3">Barang yang Dipesan</h2>
                    @foreach($pesanan->item as $baris)
                        <div class="d-flex gap-3 mb-3">
                            <img src="{{ gambar_produk($baris->image_path) }}" alt="" class="mini-thumb">
                            <div class="flex-fill small">
                                @if($baris->varian && $baris->varian->produk)
                                    <a href="{{ route('produk.show', $baris->varian->produk) }}" class="fw-600">{{ $baris->product_name }}</a>
                                @else
                                    <span class="fw-600">{{ $baris->product_name }}</span>
                                @endif
                                <div class="text-muted">{{ $baris->color }}, {{ $baris->size }}</div>
                                <div class="text-muted">{{ $baris->quantity }} × {{ rupiah($baris->price) }}</div>

                                @if($bisaUlas && ! $baris->reviewed && $baris->varian)
                                    <details class="mt-2">
                                        <summary class="btn btn-link btn-sm p-0">Tulis ulasan</summary>
                                        {{-- enctype diperlukan sebab formulir ini
                                             sekarang membawa berkas foto dan
                                             video, bukan teks saja. --}}
                                        <form action="{{ route('ulasan.store', $pesanan) }}" method="POST"
                                              class="mt-2" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="order_item_id" value="{{ $baris->id }}">

                                            <x-bintang-pilih :kunci="$baris->id" />

                                            <label class="form-label mt-3" for="comment-{{ $baris->id }}">
                                                Ulasan <span class="text-muted fw-normal">opsional</span>
                                            </label>
                                            <textarea class="form-control mb-3" id="comment-{{ $baris->id }}" name="comment"
                                                      rows="3" maxlength="500"
                                                      placeholder="Ceritakan bahannya, ukurannya, dan jatuhnya di badan."></textarea>

                                            {{-- Foto dan video sifatnya pilihan,
                                                 berbeda dari bukti pada pengajuan
                                                 retur yang wajib. Foto ulasan
                                                 adalah keterangan tambahan untuk
                                                 pembeli berikutnya, bukan bukti
                                                 yang dipakai memutuskan tuntutan;
                                                 mewajibkannya hanya membuat orang
                                                 yang ingin memuji satu kalimat
                                                 mengurungkan niatnya. --}}
                                            <x-unggah-media
                                                nama="photos"
                                                :kunci="$baris->id"
                                                judul="Tambah foto"
                                                ket="JPG atau PNG, paling banyak 3 foto, satu foto paling besar 2 MB."
                                                ikon="camera"
                                                terima="image/jpeg,image/png"
                                                :banyak="true" />

                                            <x-unggah-media
                                                nama="video"
                                                :kunci="$baris->id"
                                                judul="Tambah video"
                                                ket="MP4, satu berkas, paling besar 50 MB."
                                                ikon="camera-video"
                                                terima="video/mp4" />

                                            <button type="submit" class="btn btn-dark btn-sm">Kirim ulasan</button>
                                        </form>
                                    </details>
                                @elseif($baris->reviewed)
                                    <span class="status status-success mt-1">Sudah diulas</span>
                                @endif
                            </div>
                            <div class="fw-600 small">{{ rupiah($baris->subtotal) }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="cb-card h-100">
                            @if($pesanan->fulfillment_method === 'STORE_PICKUP')
                                <h2 class="h6">Diambil di Toko</h2>
                                @if($pesanan->toko)
                                    <div class="small">
                                        <strong>{{ $pesanan->toko->name }}</strong>
                                        <div class="text-muted">
                                            {{ $pesanan->toko->address }}, {{ $pesanan->toko->city }}{{ $pesanan->toko->floor ? ', '.$pesanan->toko->floor : '' }}
                                        </div>
                                    </div>
                                @else
                                    <p class="small text-muted mb-0">Toko pengambilan belum tercatat.</p>
                                @endif
                            @else
                                <h2 class="h6">Alamat Pengiriman</h2>
                                @if($pesanan->alamat)
                                    <div class="small">
                                        <strong>{{ $pesanan->alamat->recipient }}</strong>, {{ $pesanan->alamat->phone }}
                                        <div class="text-muted">
                                            {{ $pesanan->alamat->detail }},
                                            {{ $pesanan->alamat->district ? $pesanan->alamat->district.', ' : '' }}{{ $pesanan->alamat->city }},
                                            {{ $pesanan->alamat->province }} {{ $pesanan->alamat->postal_code }}
                                        </div>
                                    </div>
                                @else
                                    <p class="small text-muted mb-0">Alamat pengiriman sudah tidak tersimpan.</p>
                                @endif
                            @endif
                            @if($pesanan->note)
                                <p class="small text-muted mt-2 mb-0">Catatan: {{ $pesanan->note }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="cb-card h-100">
                            <h2 class="h6">Pembayaran</h2>
                            <div class="summary-row small">
                                <span>Status pembayaran</span>
                                <span>{{ $pembayaran ? ($labelBayar[$pembayaran->payment_status] ?? $pembayaran->payment_status) : 'Belum ada tagihan' }}</span>
                            </div>
                            @if($pembayaran && $pembayaran->payment_method)
                                <div class="summary-row small">
                                    <span>Cara pembayaran</span>
                                    <span>{{ cara_bayar($pembayaran->payment_method) }}</span>
                                </div>
                            @endif
                            <div class="summary-row small"><span>Subtotal</span><span>{{ rupiah($pesanan->subtotal) }}</span></div>
                            @if($pesanan->discount > 0)
                                <div class="summary-row small"><span>Potongan voucher</span><span>&minus; {{ rupiah($pesanan->discount) }}</span></div>
                            @endif
                            @if($pesanan->points_used > 0)
                                <div class="summary-row small">
                                    <span>Potongan poin ({{ $pesanan->points_used }} poin)</span>
                                    <span>&minus; {{ rupiah($pesanan->points_used * $nilaiPoin) }}</span>
                                </div>
                            @endif
                            <div class="summary-row small">
                                <span>Ongkos kirim</span>
                                <span>{{ $pesanan->shipping_cost > 0 ? rupiah($pesanan->shipping_cost) : 'Gratis' }}</span>
                            </div>
                            <div class="summary-row total"><span>Total</span><span>{{ rupiah($pesanan->total) }}</span></div>
                        </div>
                    </div>
                </div>

                @if($pesanan->retur->isNotEmpty())
                    <div class="cb-card mt-3">
                        <h2 class="h6">Retur dan Penukaran</h2>
                        @foreach($pesanan->retur as $retur)
                            <div class="d-flex justify-content-between align-items-center small py-1">
                                <a href="{{ route('retur.show', $retur) }}" class="code-pill">{{ $retur->return_code }}</a>
                                <x-status-retur :status="$retur->status" />
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Tombol tindakan --}}
                {{--
                    KONFIRMASI PENERIMAAN BESERTA FOTO BARANGNYA.

                    Bukti foto penyerahan paket itu milik jasa kirim, dan
                    selama APInya belum berlangganan, foto itu tidak pernah
                    sampai ke sini. Mengarangnya berarti memajang gambar
                    yang tidak pernah diambil siapa pun.

                    Jadi yang memotret pelanggannya sendiri. Fotonya nyata,
                    pemiliknya jelas, dan waktunya tercatat. Admin
                    memakainya sebagai bahan pemeriksaan sebelum mengesahkan
                    pesanan.
                --}}
                @if(in_array($pesanan->status, ['IN_TRANSIT', 'READY_FOR_PICKUP']))
                    <div class="cb-card mt-3">
                        {{-- Judul kotak ini diubah dari "Sudah terima
                             barangnya?" menjadi kalimat yang menyebut
                             langkah berikutnya.

                             Pertanyaan yang lama terbaca aneh di sebelah
                             penanda status yang masih bertuliskan Track
                             Order, seolah sistem menanyakan sesuatu yang
                             mestinya sudah ia ketahui sendiri. Padahal
                             tombol inilah yang MEMINDAHKAN statusnya ke
                             Pesanan Diterima.

                             Colorbox memang tidak bisa mengetahuinya
                             sendiri. Kabar paket sudah diantar ada di
                             sistem jasa kirim, dan selama APInya belum
                             berlangganan, kabar itu tidak pernah sampai
                             ke sini. Satu satunya orang yang tahu
                             paketnya sudah di tangan adalah pelanggan.
                             Begitu judulnya menyebutkan hal itu, urutan
                             langkahnya masuk akal dibaca. --}}
                        <h2 class="h6">Konfirmasi Barang Diterima</h2>
                        <p class="small text-muted">
                            @if($pesanan->fulfillment_method === 'DELIVERY')
                                Paket masih tercatat di jalan. Begitu barangnya sampai di tangan Anda,
                                potret barangnya lalu tekan tombol di bawah, dan status pesanan ini
                                berpindah menjadi <strong>Pesanan Diterima</strong>.
                                <x-tip teks="Colorbox tidak menerima kabar otomatis dari jasa kirim, jadi yang menyatakan paketnya sampai adalah Anda. Foto Anda yang dipakai sebagai buktinya." />
                            @else
                                Tekan tombol ini sesudah barangnya Anda bawa pulang dari toko, dan
                                status pesanan ini berpindah menjadi <strong>Selesai Diambil</strong>.
                            @endif
                        </p>

                        <form action="{{ route('pesanan.terima', $pesanan) }}" method="POST"
                              enctype="multipart/form-data"
                              data-confirm="Nyatakan pesanan ini sudah Anda terima?">
                            @csrf
                            @method('PUT')

                            {{-- Wajib untuk jalur kirim, boleh kosong untuk
                                 ambil di toko: barang yang diambil di toko
                                 sudah diserahkan di depan kasir dan tercatat
                                 di sana, jadi tidak perlu bukti tambahan.

                                 Tanda bintang merahnya digambar komponen
                                 x-unggah-media lewat x-wajib, jadi penanda
                                 wajibnya sama dengan seluruh formulir lain
                                 di situs ini. --}}
                            <x-unggah-media
                                nama="receipt_photo"
                                judul="Foto barang yang diterima"
                                ket="JPG atau PNG, paling besar 4 MB. Tanggal dan jamnya dibubuhkan sistem ke atas foto."
                                ikon="camera"
                                terima="image/*"
                                :wajib="$pesanan->fulfillment_method === 'DELIVERY'" />

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                Barang Sudah Diterima
                            </button>
                        </form>
                    </div>
                @endif

                @if($pesanan->receipt_photo)
                    <div class="cb-card mt-3">
                        <h2 class="h6">Foto saat Anda menerima barang</h2>
                        <img src="{{ asset($pesanan->receipt_photo) }}" alt="Foto barang saat diterima"
                             class="img-fluid rounded" style="max-width: 320px">
                        <p class="small text-soft mb-0 mt-2">
                            Diunggah {{ $pesanan->completed_at ? tanggal_id($pesanan->completed_at, true) : '' }}
                            @if($pesanan->completed_at) pukul {{ $pesanan->completed_at->format('H:i') }} @endif
                            bersama konfirmasi Anda. Tanggal dan jamnya juga dibubuhkan ke atas foto,
                            jadi keterangannya ikut terbawa walaupun fotonya diunduh.
                        </p>
                    </div>
                @endif

                {{-- Batas waktunya dipindah ke atas tombol, bukan di
                     sampingnya. Kalimat sepanjang itu berdiri sejajar
                     tombol membuat barisnya melebar sampai tombol Beli
                     Lagi dan Ada Masalah Lain terdorong ke baris
                     berikutnya, dan di layar sempit tulisannya terpotong
                     di tengah. Sebagai keterangan ia memang milik
                     tombolnya, tetapi bukan bagian dari deretan tombol. --}}
                @if($bisaRetur)
                    <p class="small text-muted mb-1 mt-3">
                        Batas pengajuan {{ $hariRetur }} hari setelah barang diterima.
                    </p>
                @endif

                <div class="d-flex flex-wrap gap-2 @unless($bisaRetur) mt-3 @endunless">

                    @if($bisaRetur)
                        {{-- Tulisannya satu bahasa. "Retur" adalah kata
                             serapan yang di layar berbahasa Indonesia
                             berdampingan dengan "Penukaran" terbaca
                             seperti dua bahasa dalam satu tombol. --}}
                        <a href="{{ route('retur.create', $pesanan->order_code) }}" class="btn btn-dark">
                            <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>{{ teks('Ajukan Penukaran Barang', 'Request an Exchange') }}
                        </a>
                    @endif

                    <form action="{{ route('pesanan.beli-lagi', $pesanan) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-light">Beli Lagi</button>
                    </form>

                    {{-- Untuk urusan yang tidak selesai dengan menukar barang,
                         misalnya uang terpotong dua kali atau kurir yang tidak
                         sopan. Nomor pesanannya ikut dibawa, jadi pelanggan
                         tidak perlu mengetiknya lagi di formulir keluhan. --}}
                    <a href="{{ route('keluhan.create', ['pesanan' => $pesanan->id]) }}" class="btn btn-light">
                        <i class="bi bi-chat-left-text me-1" aria-hidden="true"></i>Ada Masalah Lain
                    </a>

                    @if($pesanan->bisaDibatalkan())
                        <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalBatal">
                            Batalkan Pesanan
                        </button>
                    @endif
                </div>

                @if($pesanan->bisaDibatalkan())
                    <div class="modal fade" id="modalBatal" tabindex="-1" aria-labelledby="judulBatal" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form action="{{ route('pesanan.batal', $pesanan) }}" method="POST" class="modal-content">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h2 class="modal-title fs-5" id="judulBatal">Batalkan pesanan {{ $pesanan->order_code }}?</h2>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    {{-- Keterangannya dibedakan menurut sudah dibayar atau belum,
                                         sebab yang ditunggu pelanggan sesudah menekan tombol ini
                                         memang berbeda: yang belum bayar tidak menunggu apa apa,
                                         yang sudah bayar menunggu uangnya kembali. --}}
                                    @if($pesanan->pembayaran && $pesanan->pembayaran->payment_status === 'lunas')
                                        <div class="alert alert-light small">
                                            <strong class="d-block mb-1">Pesanan ini sudah dibayar.</strong>
                                            Barangnya masih ada di gudang dan belum diambil kurir, jadi masih bisa
                                            dibatalkan. Pengembalian dananya diurus tim Colorbox dan masuk dalam
                                            1 sampai 3 hari kerja ke rekening atau dompet digital yang Anda pakai
                                            membayar.
                                        </div>
                                    @endif
                                    <fieldset>
                                        <legend class="form-label">Alasan pembatalan</legend>
                                        @foreach($alasanBatal as $nomor => $alasan)
                                            <label class="form-check">
                                                <input class="form-check-input" type="radio" name="reason"
                                                       value="{{ $alasan }}" @checked($nomor === 0)>
                                                <span class="form-check-label">{{ $alasan }}</span>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tidak jadi</button>
                                    <button type="submit" class="btn btn-danger">Batalkan Pesanan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
