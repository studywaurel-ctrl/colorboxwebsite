<footer class="site-footer">
    <div class="container-xl">
        <div class="row g-4">
            <div class="col-lg-4">
                <span class="brand mb-2">
                    <img src="{{ asset('images/logo/colorbox-mark.png') }}" alt="" class="brand-mark-img" aria-hidden="true">
                    <img src="{{ asset('images/logo/colorbox-wordmark.png') }}" alt="Colorbox" class="brand-word">
                </span>
                <p class="text-muted mt-2 mb-3" style="max-width:320px">
                    Pakaian wanita yang mudah dipadukan, tersedia online dan di toko Colorbox di berbagai kota.
                </p>
                <div class="social" aria-label="Media sosial">
                    <a href="https://www.instagram.com/colorbox_id/" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.tiktok.com/@colorbox_id" target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                    <a href="https://www.youtube.com/@colorboxid" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Layanan Pelanggan</h6>
                <ul>
                    <li><a href="{{ route('bantuan.index') }}">Pusat Bantuan</a></li>
                    <li><a href="{{ route('lacak.index') }}">Lacak Pesanan</a></li>
                    <li><a href="{{ route('keluhan.index') }}">Keluhan Pelanggan</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Belanja</h6>
                <ul>
                    <li><a href="{{ route('produk.baru') }}">New Arrival</a></li>
                    <li><a href="{{ route('bantuan.show', 'retur-dan-penukaran') }}">Retur &amp; Penukaran</a></li>
                    <li><a href="{{ route('bantuan.show', 'pengiriman') }}">Pengiriman</a></li>
                    <li><a href="{{ route('toko.index') }}">Cari Toko</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Layanan Colorbox</h6>
                <ul>
                    <li><a href="{{ route('vto.index') }}">Virtual Try On</a></li>
                    <li><a href="{{ route('ttc.index') }}">Tap Try and Collect</a></li>
                    <li><a href="{{ route('donasi.index') }}">Donasi Poin</a></li>
                    <li><a href="{{ route('bantuan.show', 'panduan-ukuran') }}">Panduan Ukuran</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Colorbox</h6>
                <ul>
                    <li><a href="{{ route('bantuan.show', 'tentang-colorbox') }}">Tentang Kami</a></li>
                    <li><a href="{{ route('bantuan.show', 'cara-belanja') }}">Cara Belanja</a></li>
                    <li><a href="{{ route('bantuan.show', 'pembayaran') }}">Pembayaran</a></li>
                </ul>
            </div>
        </div>
        <div class="border-top mt-4 pt-3 small text-soft">© {{ date('Y') }} Colorbox</div>
    </div>
</footer>
