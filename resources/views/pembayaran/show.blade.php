@extends('layouts.app')

@section('judul', 'Pembayaran Pesanan '.$pesanan->order_code)

@section('isi')
    @php
        $menunggu = $pembayaran->payment_status === 'menunggu';
        $sisaDetik = $pembayaran->expired_at ? max(0, (int) now()->diffInSeconds($pembayaran->expired_at, false)) : 0;
        $nilaiPoin = (int) setting('point_value', 100);
    @endphp

    <div class="container-xl section-sm" style="max-width: 940px">
        <x-langkah-belanja :aktif="$pembayaran->payment_status === 'lunas' ? 'selesai' : 'pembayaran'" />

        @if($pembayaran->payment_status === 'lunas')
            <div class="cb-card text-center py-5">
                <span class="avatar lg mx-auto mb-3"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                <h1 class="h3">Pembayaran diterima</h1>
                <p class="text-muted mb-1">
                    Pesanan <span class="code-pill">{{ $pesanan->order_code }}</span> sudah dibuat dan segera kami proses.
                </p>
                @if($pembayaran->paid_at)
                    <p class="small text-muted">
                        Dibayar {{ tanggal_id($pembayaran->paid_at, true) }} pukul {{ $pembayaran->paid_at->format('H:i') }}
                    </p>
                @endif
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    <a href="{{ route('pesanan.show', $pesanan) }}" class="btn btn-primary">Rincian Pesanan</a>
                    <a href="{{ route('lacak.index', ['kode' => $pesanan->order_code]) }}" class="btn btn-outline-dark">
                        <i class="bi bi-truck me-1" aria-hidden="true"></i>Lacak Pesanan
                    </a>
                    <a href="{{ route('produk.index') }}" class="btn btn-light">Lanjut belanja</a>
                </div>
            </div>
        @elseif($menunggu)
            {{-- Status diperbarui sendiri oleh app.js lewat atribut di bawah --}}
            <div class="row g-4"
                 data-payment-poll="{{ route('pembayaran.status', $pembayaran) }}"
                 data-seconds-left="{{ $sisaDetik }}">
                <div class="col-md-7">
                    <div class="cb-card">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="small text-muted">Pesanan <span class="code-pill">{{ $pesanan->order_code }}</span></div>
                                <h1 class="h4 mt-2 mb-0">Selesaikan Pembayaran</h1>
                            </div>
                            <span class="status status-warning">Menunggu Pembayaran</span>
                        </div>
                        <hr>

                        <div class="small text-muted">Jumlah yang harus dibayar</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="pay-code">{{ rupiah($pembayaran->gross_amount) }}</span>
                            <button type="button" class="btn btn-light btn-sm" data-copy="{{ $pembayaran->gross_amount }}">
                                <i class="bi bi-copy me-1" aria-hidden="true"></i>Salin
                            </button>
                        </div>

                        @if($pembayaran->expired_at)
                            <div class="alert alert-warning small mt-3 mb-0">
                                Bayar sebelum
                                <strong>{{ tanggal_id($pembayaran->expired_at, true) }} pukul {{ $pembayaran->expired_at->format('H:i') }}</strong>
                                (sisa <span class="countdown" data-countdown>00:00:00</span>).
                                Lewat dari itu pesanan dibatalkan sistem dan stoknya dikembalikan.
                            </div>
                        @endif

                        @if(! $pembayaran->snap_token && ! $midtransAktif)
                            <div class="alert alert-danger small mt-3 mb-0">
                                Kunci Midtrans belum diisi pada berkas <code>.env</code>
                                (<code>MIDTRANS_SERVER_KEY</code> dan <code>MIDTRANS_CLIENT_KEY</code>), sehingga
                                jendela pembayaran belum dapat dibuka. Pesanan Anda tetap tersimpan.
                            </div>
                        @elseif(! $pembayaran->snap_token)
                            <div class="alert alert-warning small mt-3 mb-0">
                                Jendela pembayaran untuk pesanan ini belum siap. Silakan muat ulang halaman ini
                                beberapa saat lagi.
                            </div>
                        @else
                            <button type="button" class="btn btn-primary w-100 mt-3"
                                    data-bayar
                                    data-token="{{ $pembayaran->snap_token }}"
                                    data-selesai="{{ route('pembayaran.selesai', $pembayaran) }}">
                                <i class="bi bi-credit-card me-1" aria-hidden="true"></i>Bayar Sekarang
                            </button>
                            @if($pembayaran->snap_url)
                                <p class="small text-muted mt-2 mb-0">
                                    Jendela tidak terbuka?
                                    <a href="{{ $pembayaran->snap_url }}" target="_blank" rel="noopener">Buka halaman pembayaran Midtrans</a>.
                                </p>
                            @endif
                        @endif
                    </div>

                    <div class="cb-card">
                        <h2 class="h6">Cara membayar</h2>
                        <ol class="small text-muted ps-3 mb-0">
                            <li>Tekan tombol Bayar Sekarang, jendela pembayaran Midtrans akan terbuka.</li>
                            <li>Pilih QRIS, Virtual Account bank, atau dompet digital yang Anda pakai.</li>
                            <li>Ikuti petunjuk pada jendela tersebut sampai pembayaran selesai.</li>
                            <li>Status di halaman ini berubah sendiri setelah pembayaran Anda diterima.</li>
                        </ol>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="cb-card">
                        <h2 class="h6 mb-3">Rincian pesanan</h2>
                        @foreach($pesanan->item as $baris)
                            <div class="d-flex gap-2 mb-2 small">
                                <img src="{{ gambar_produk($baris->image_path) }}" alt="" class="mini-thumb" style="width: 44px">
                                <div class="flex-fill">
                                    <div class="fw-600">{{ $baris->product_name }}</div>
                                    <div class="text-muted">{{ $baris->color }} · {{ $baris->size }} · {{ $baris->quantity }} potong</div>
                                </div>
                            </div>
                        @endforeach
                        <hr>
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

                        <div class="info-line mt-3">
                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                            <span>{{ $pesanan->method === 'ambil_toko' ? 'Diambil di toko' : 'Dikirim kurir' }}</span>
                        </div>
                        @if($pesanan->alamat)
                            <div class="info-line">
                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                <span>{{ $pesanan->alamat->recipient }} · {{ $pesanan->alamat->detail }}, {{ $pesanan->alamat->city }}</span>
                            </div>
                        @endif
                        @if($pesanan->toko)
                            <div class="info-line">
                                <i class="bi bi-shop" aria-hidden="true"></i>
                                <span>{{ $pesanan->toko->name }}, {{ $pesanan->toko->city }}</span>
                            </div>
                        @endif

                        <a href="{{ route('pesanan.show', $pesanan) }}" class="btn btn-light btn-sm w-100 mt-3">Rincian Pesanan</a>
                    </div>
                </div>
            </div>
        @else
            <x-kosong ikon="x-circle" judul="Tagihan ini sudah tidak aktif"
                      teks="Pembayaran pesanan {{ $pesanan->order_code }} tercatat {{ ucfirst(str_replace('_', ' ', $pembayaran->payment_status)) }}.">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('pesanan.show', $pesanan) }}" class="btn btn-outline-dark">Rincian Pesanan</a>
                    <form action="{{ route('pesanan.beli-lagi', $pesanan) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-dark">Beli Lagi</button>
                    </form>
                </div>
            </x-kosong>
        @endif
    </div>
@endsection

@push('skrip')
    {{-- Skrip Snap Midtrans. Kunci klien dan token dibaca dari atribut data-... --}}
    <script src="{{ config('midtrans.snap_js') }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tombol = document.querySelector('[data-bayar]');
            if (!tombol) { return; }

            tombol.addEventListener('click', function () {
                if (!window.snap) {
                    window.alert('Jendela pembayaran belum siap. Silakan muat ulang halaman ini.');
                    return;
                }
                var lanjut = function () { window.location.href = tombol.dataset.selesai; };
                window.snap.pay(tombol.dataset.token, {
                    onSuccess: lanjut,
                    onPending: lanjut,
                    onError: lanjut,
                    onClose: lanjut
                });
            });
        });
    </script>
@endpush
