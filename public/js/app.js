/* Colorbox — skrip umum situs pelanggan.
   Ditulis dengan JavaScript biasa (tanpa framework) supaya mudah dibaca.
   Alamat endpoint diambil dari atribut data-... pada <body>, jadi tidak ada
   data yang ditanam memakai @json di dalam Blade. */

(function () {
    'use strict';

    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
    var RUTE = document.body ? document.body.dataset : {};

    function aman(teks) {
        return String(teks == null ? '' : teks)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ----------------------------------------------------------- tema ---- */
    try {
        var simpanan = localStorage.getItem('cb-tema');
        if (simpanan) { document.documentElement.setAttribute('data-theme', simpanan); }
    } catch (e) {}

    document.addEventListener('click', function (e) {
        var tombol = e.target.closest('[data-theme-toggle]');
        if (!tombol) { return; }
        var sekarang = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        var baru = sekarang === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', baru);
        try { localStorage.setItem('cb-tema', baru); } catch (e2) {}
        $$('[data-theme-label]').forEach(function (el) {
            el.textContent = baru === 'dark' ? 'Mode gelap' : 'Mode terang';
        });
        if (RUTE.ruteTema) {
            var isi = new FormData();
            isi.append('tema', baru);
            var token = $('meta[name="csrf-token"]');
            isi.append('_token', token ? token.content : '');
            fetch(RUTE.ruteTema, { method: 'POST', body: isi, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .catch(function () {});
        }
    });

    /* ------------------------------------------------- pengatur jumlah ---- */
    function siapkanStepper(kotak) {
        var isian = $('input', kotak);
        var kurang = $('[data-step="-1"]', kotak);
        var tambah = $('[data-step="1"]', kotak);
        if (!isian) { return; }

        function rapikan() {
            var min = parseInt(isian.min, 10) || 1;
            var maks = parseInt(isian.max, 10) || 99;
            var nilai = parseInt(isian.value, 10);
            if (isNaN(nilai)) { nilai = min; }
            nilai = Math.max(min, Math.min(maks, nilai));
            isian.value = nilai;
            if (kurang) { kurang.disabled = nilai <= min; }
            if (tambah) { tambah.disabled = nilai >= maks; }
        }

        kotak.addEventListener('click', function (e) {
            var b = e.target.closest('[data-step]');
            if (!b || b.disabled) { return; }
            isian.value = (parseInt(isian.value, 10) || 0) + parseInt(b.dataset.step, 10);
            rapikan();
            if (kotak.dataset.kirim === 'ya' && isian.form) { isian.form.submit(); }
        });
        isian.addEventListener('input', rapikan);
        rapikan();
    }
    $$('[data-qty]').forEach(siapkanStepper);

    /* ------------------------------------------- konfirmasi sebelum hapus - */
    document.addEventListener('submit', function (e) {
        var borang = e.target;
        if (!borang.dataset || !borang.dataset.confirm) { return; }
        if (!window.confirm(borang.dataset.confirm)) { e.preventDefault(); }
    });

    /* ----------------------------------------------------------- QR code -- */
    $$('[data-qr]').forEach(function (el) {
        if (typeof qrcode === 'undefined') { return; }
        var qr = qrcode(0, 'M');
        qr.addData(el.dataset.qr);
        qr.make();
        el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
        var svg = el.querySelector('svg');
        if (svg) {
            svg.setAttribute('role', 'img');
            svg.setAttribute('aria-label', el.dataset.qrLabel || 'Kode QR');
        }
    });

    /* -------------------------------------------------------- salin teks -- */
    document.addEventListener('click', function (e) {
        var tombol = e.target.closest('[data-copy]');
        if (!tombol || !navigator.clipboard) { return; }
        navigator.clipboard.writeText(tombol.dataset.copy).then(function () {
            var lama = tombol.textContent;
            tombol.textContent = 'Tersalin';
            setTimeout(function () { tombol.textContent = lama; }, 1500);
        });
    });

    /* --------------------------------------------------------- kalender --- */
    function siapkanKalender(kotak) {
        var borang = kotak.closest('form') || document;
        var isian = $('input[name="' + kotak.dataset.name + '"]', borang);
        if (!isian) { return; }

        var min = new Date(kotak.dataset.min + 'T00:00:00');
        var maks = new Date(kotak.dataset.max + 'T00:00:00');
        var hariIni = new Date();
        hariIni.setHours(0, 0, 0, 0);

        var namaHari = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        var namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        function iso(d) {
            return d.getFullYear() + '-' +
                String(d.getMonth() + 1).padStart(2, '0') + '-' +
                String(d.getDate()).padStart(2, '0');
        }

        var tampil = isian.value ? new Date(isian.value + 'T00:00:00') : new Date(min);
        tampil.setDate(1);

        function gambar() {
            var awal = (new Date(tampil.getFullYear(), tampil.getMonth(), 1).getDay() + 6) % 7;
            var jumlahHari = new Date(tampil.getFullYear(), tampil.getMonth() + 1, 0).getDate();
            var mundurMati = new Date(tampil.getFullYear(), tampil.getMonth(), 0) < min;
            var majuMati = new Date(tampil.getFullYear(), tampil.getMonth() + 1, 1) > maks;

            var html = '<div class="calendar-head">' +
                '<button type="button" class="btn-icon" data-nav="-1" ' + (mundurMati ? 'disabled' : '') +
                ' aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></button>' +
                '<strong aria-live="polite">' + namaBulan[tampil.getMonth()] + ' ' + tampil.getFullYear() + '</strong>' +
                '<button type="button" class="btn-icon" data-nav="1" ' + (majuMati ? 'disabled' : '') +
                ' aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></button>' +
                '</div><div class="calendar-grid" role="grid">';

            html += namaHari.map(function (h) { return '<div class="dow" aria-hidden="true">' + h + '</div>'; }).join('');
            for (var k = 0; k < awal; k++) { html += '<div></div>'; }

            for (var d = 1; d <= jumlahHari; d++) {
                var tanggal = new Date(tampil.getFullYear(), tampil.getMonth(), d);
                var mati = tanggal < min || tanggal > maks;
                var kelas = [];
                if (iso(tanggal) === isian.value) { kelas.push('selected'); }
                if (+tanggal === +hariIni) { kelas.push('today'); }
                html += '<button type="button" class="' + kelas.join(' ') + '" data-date="' + iso(tanggal) + '" ' +
                    (mati ? 'disabled' : '') + ' aria-pressed="' + (iso(tanggal) === isian.value) + '">' + d + '</button>';
            }
            kotak.innerHTML = html + '</div>';
        }

        kotak.addEventListener('click', function (e) {
            var nav = e.target.closest('[data-nav]');
            if (nav && !nav.disabled) {
                tampil.setMonth(tampil.getMonth() + parseInt(nav.dataset.nav, 10));
                gambar();
                return;
            }
            var hari = e.target.closest('[data-date]');
            if (hari && !hari.disabled) {
                isian.value = hari.dataset.date;
                gambar();
                isian.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        gambar();
    }
    $$('[data-calendar]').forEach(siapkanKalender);

    /* ------------------------------------------------------ daftar jam ---- */
    function siapkanSlot(kotak) {
        var borang = kotak.closest('form');
        if (!borang) { return; }

        var isianTanggal = $('input[name="' + kotak.dataset.dateField + '"]', borang);
        var terpilih = kotak.dataset.selected || '';
        var alamat = kotak.dataset.slots === 'donasi' ? RUTE.ruteSlotDonasi : RUTE.ruteSlotTtc;

        function tokoTerpilih() {
            var radio = $('[name="' + kotak.dataset.storeField + '"]:checked', borang);
            if (radio) { return radio.value; }
            var pilih = $('select[name="' + kotak.dataset.storeField + '"]', borang);
            return pilih ? pilih.value : '';
        }

        function muat() {
            var toko = tokoTerpilih();
            if (!toko) {
                kotak.innerHTML = '<p class="text-muted small mb-0">Pilih toko terlebih dahulu.</p>';
                return;
            }
            if (!isianTanggal || !isianTanggal.value) {
                kotak.innerHTML = '<p class="text-muted small mb-0">Pilih tanggal terlebih dahulu untuk melihat jam yang tersedia.</p>';
                return;
            }

            var rangka = '';
            for (var i = 0; i < 6; i++) { rangka += '<div class="skeleton" style="height:48px"></div>'; }
            kotak.innerHTML = '<div class="slot-grid">' + rangka + '</div>';

            fetch(alamat + '?toko=' + encodeURIComponent(toko) + '&tanggal=' + encodeURIComponent(isianTanggal.value), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (jawab) { return jawab.json(); })
                .then(function (data) {
                    var daftar = data.slot || [];
                    if (!daftar.length) {
                        kotak.innerHTML = '<div class="alert alert-warning small py-2 mb-0">Belum ada jadwal pada tanggal itu. Silakan pilih tanggal lain.</div>';
                        return;
                    }
                    var isi = daftar.map(function (s) {
                        var ket = s.penuh ? 'Penuh' : (s.sisa + ' slot tersisa');
                        return '<label>' +
                            '<input type="radio" name="' + aman(kotak.dataset.name) + '" value="' + aman(s.id) + '" ' +
                            (s.penuh ? 'disabled' : 'required') + ' ' +
                            (String(s.id) === String(terpilih) && !s.penuh ? 'checked' : '') + '>' +
                            '<span>' + aman(s.jam) + '<small>' + aman(ket) + '</small></span>' +
                            '</label>';
                    }).join('');
                    kotak.innerHTML = '<div class="slot-grid" role="radiogroup">' + isi + '</div>';
                })
                .catch(function () {
                    kotak.innerHTML = '<div class="alert alert-danger small mb-0">Jadwal gagal dimuat. Muat ulang halaman lalu coba lagi.</div>';
                });
        }

        borang.addEventListener('change', function (e) {
            if (e.target === isianTanggal || e.target.name === kotak.dataset.storeField) { muat(); }
        });
        muat();
    }
    $$('[data-slots]').forEach(siapkanSlot);

    /* ------------------------------------------------ halaman pembayaran -- */
    var bayar = $('[data-payment-poll]');
    if (bayar) {
        var sisa = parseInt(bayar.dataset.secondsLeft, 10);
        var hitung = $('[data-countdown]');

        var detik = function () {
            if (hitung && !isNaN(sisa)) {
                var j = Math.floor(sisa / 3600);
                var m = Math.floor((sisa % 3600) / 60);
                var d = sisa % 60;
                hitung.textContent = [j, m, d].map(function (v) { return String(v).padStart(2, '0'); }).join(':');
            }
            if (sisa <= 0) { window.location.reload(); }
            sisa -= 1;
        };
        detik();
        setInterval(detik, 1000);

        setInterval(function () {
            fetch(bayar.dataset.paymentPoll, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (jawab) { return jawab.json(); })
                .then(function (data) {
                    if (data.status && data.status !== 'menunggu') { window.location.reload(); }
                })
                .catch(function () {});
        }, 5000);
    }
})();
