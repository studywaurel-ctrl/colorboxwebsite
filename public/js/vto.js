/*
 * Virtual Try On.
 *
 * Dua bagian:
 *  1. Halaman pemilihan: daftar warna mengikuti produk yang dipilih.
 *  2. Panggung kamera: kamera menyala langsung, titik tubuh dibaca dengan
 *     TensorFlow.js MoveNet, lalu satu tombol Generate menyusun hasilnya.
 */
(function () {
    'use strict';

    /* ------------------------------------------------ 1. halaman pemilihan */
    function siapkanPemilihan() {
        var borang = document.querySelector('[data-vto-pilih]');
        if (!borang) { return; }

        var pilihProduk = borang.querySelector('[data-vto-produk]');
        var kotakWarna = borang.querySelector('[data-vto-warna]');
        var gambar = borang.querySelector('[data-vto-gambar]');
        var nama = borang.querySelector('[data-vto-nama]');
        var harga = borang.querySelector('[data-vto-harga]');
        var namaWarna = borang.querySelector('[data-vto-nama-warna]');

        if (!pilihProduk || !kotakWarna) { return; }

        function warnaTerpilih() {
            return kotakWarna.querySelector('input[name="color"]:checked');
        }

        function perbaruiTampilan() {
            var pilihan = pilihProduk.options[pilihProduk.selectedIndex];
            if (nama) { nama.textContent = pilihan.dataset.nama || ''; }
            if (harga) { harga.textContent = pilihan.dataset.harga || ''; }

            var warna = warnaTerpilih();
            if (namaWarna) { namaWarna.textContent = warna ? warna.value : '-'; }
            if (gambar) {
                gambar.src = (warna && warna.dataset.gambar) ? warna.dataset.gambar : (pilihan.dataset.gambar || gambar.src);
            }
        }

        function saringWarna() {
            var id = pilihProduk.value;
            var pertama = null;

            kotakWarna.querySelectorAll('.color-option').forEach(function (label) {
                var cocok = (label.dataset.produk === id);
                var isian = label.querySelector('input');
                label.hidden = !cocok;
                isian.disabled = !cocok;
                if (!cocok) { isian.checked = false; }
                if (cocok && !pertama) { pertama = isian; }
            });

            if (pertama && !warnaTerpilih()) { pertama.checked = true; }
            perbaruiTampilan();
        }

        pilihProduk.addEventListener('change', saringWarna);
        kotakWarna.addEventListener('change', perbaruiTampilan);
        saringWarna();
    }

    /* ------------------------------------------------- 2. panggung kamera */
    function siapkanPanggung() {
        var panggung = document.querySelector('[data-vto-panggung]');
        if (!panggung) { return; }

        var video = panggung.querySelector('[data-vto-video]');
        var bingkai = panggung.querySelector('[data-vto-bingkai]');
        var ket = panggung.querySelector('[data-vto-ket]');

        var aksi = document.querySelector('[data-vto-aksi]');
        var tombol = document.querySelector('[data-vto-generate]');
        var memuat = document.querySelector('[data-vto-memuat]');
        var kotakHasil = document.querySelector('[data-vto-hasil]');
        var tombolUlang = document.querySelector('[data-vto-ulang]');
        var pesan = document.querySelector('[data-vto-pesan]');
        var borang = document.querySelector('[data-vto-borang]');
        var kanvas = document.querySelector('[data-vto-kanvas]');

        var isiGambar = borang ? borang.querySelector('[data-vto-data-gambar]') : null;
        var isiFoto = borang ? borang.querySelector('[data-vto-data-foto]') : null;
        var isiWarna = borang ? borang.querySelector('[data-vto-data-warna]') : null;
        var isiUkuran = borang ? borang.querySelector('[data-vto-data-ukuran]') : null;

        var pilihan = document.querySelector('[data-vto-pilihan]');
        var gambarProduk = document.querySelector('[data-vto-gambar-produk]');
        var namaWarna = document.querySelector('[data-vto-nama-warna]');

        var mode = panggung.dataset.mode || 'preview';
        var aliran = null;
        var pendeteksi = null;
        var berjalan = false;
        var titik = {};

        function tulisPesan(teks) {
            if (pesan) { pesan.textContent = teks || ''; }
        }

        function tandaiPosisi(baik) {
            if (bingkai) { bingkai.classList.toggle('good', !!baik); }
            if (ket) {
                ket.classList.toggle('good', !!baik);
                ket.textContent = baik ? 'Posisi sudah sesuai' : 'Posisikan tubuh di dalam bingkai';
            }
        }

        /* ------------------------------------------- warna dan ukuran */
        function perbaruiPilihan() {
            if (!pilihan) { return; }

            var warna = pilihan.querySelector('input[name="warna"]:checked');
            var ukuran = pilihan.querySelector('input[name="product_variant_id"]:checked');

            if (warna) {
                if (isiWarna) { isiWarna.value = warna.value; }
                if (namaWarna) { namaWarna.textContent = warna.value; }
                if (warna.dataset.gambar) {
                    panggung.dataset.gambar = warna.dataset.gambar;
                    if (gambarProduk) { gambarProduk.src = warna.dataset.gambar; }
                }

                // Ukuran yang ditampilkan hanya milik warna yang dipilih
                var adaTerpilih = false;
                pilihan.querySelectorAll('[data-warna-ukuran]').forEach(function (label) {
                    var cocok = (label.dataset.warnaUkuran === warna.value);
                    var isian = label.querySelector('input');
                    label.hidden = !cocok;
                    if (!cocok) { isian.checked = false; }
                    if (cocok && isian.checked) { adaTerpilih = true; }
                });

                if (!adaTerpilih) {
                    var pertama = pilihan.querySelector('[data-warna-ukuran]:not([hidden]) input:not([disabled])');
                    if (pertama) { pertama.checked = true; }
                }

                ukuran = pilihan.querySelector('input[name="product_variant_id"]:checked');
            }

            if (isiUkuran) { isiUkuran.value = ukuran ? (ukuran.dataset.ukuran || '') : ''; }
        }

        if (pilihan) {
            pilihan.addEventListener('change', perbaruiPilihan);
            perbaruiPilihan();
        }

        /* --------------------------------------------- pendeteksi tubuh */
        function muatSkrip(alamat) {
            return new Promise(function (selesai, gagal) {
                var s = document.createElement('script');
                s.src = alamat;
                s.onload = selesai;
                s.onerror = gagal;
                document.head.appendChild(s);
            });
        }

        function muatPendeteksi() {
            var urutan = muatSkrip('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-core@4.22.0/dist/tf-core.min.js')
                .then(function () { return muatSkrip('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-converter@4.22.0/dist/tf-converter.min.js'); })
                .then(function () { return muatSkrip('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-backend-webgl@4.22.0/dist/tf-backend-webgl.min.js'); })
                .then(function () { return muatSkrip('https://cdn.jsdelivr.net/npm/@tensorflow-models/pose-detection@2.1.3/dist/pose-detection.min.js'); })
                .then(function () { return window.tf.ready(); })
                .then(function () {
                    return window.poseDetection.createDetector(
                        window.poseDetection.SupportedModels.MoveNet,
                        { modelType: window.poseDetection.movenet.modelType.SINGLEPOSE_LIGHTNING }
                    );
                });

            // Kalau model tidak selesai dimuat dalam 15 detik, halaman jalan tanpa titik tubuh
            return Promise.race([
                urutan,
                new Promise(function (_, gagal) { window.setTimeout(function () { gagal(new Error('lambat')); }, 15000); })
            ]);
        }

        var NAMA_TITIK = ['nose', 'left_shoulder', 'right_shoulder', 'left_hip', 'right_hip', 'left_knee', 'right_knee', 'left_ankle', 'right_ankle'];

        function bacaTitik(poses) {
            var hasil = {};
            var daftar = (poses && poses[0] && poses[0].keypoints) ? poses[0].keypoints : [];

            daftar.forEach(function (t) {
                if (NAMA_TITIK.indexOf(t.name) >= 0 && t.score > 0.3) {
                    // Gambar dicerminkan seperti tampilan video, jadi sumbu x dibalik
                    hasil[t.name] = [1 - (t.x / video.videoWidth), t.y / video.videoHeight];
                }
            });

            return hasil;
        }

        function posisiSesuai(t) {
            if (!t.left_shoulder || !t.right_shoulder || !t.nose) { return false; }

            var tengah = (t.left_shoulder[0] + t.right_shoulder[0]) / 2;
            if (Math.abs(tengah - 0.5) > 0.16) { return false; }

            var bawah = Math.max(t.left_hip ? t.left_hip[1] : 0, t.right_hip ? t.right_hip[1] : 0);
            if (!bawah) { return false; }

            return (bawah - t.nose[1]) > 0.22;
        }

        function putaran() {
            if (!berjalan) { return; }

            if (pendeteksi && video.readyState >= 2 && video.videoWidth) {
                pendeteksi.estimatePoses(video).then(function (poses) {
                    titik = bacaTitik(poses);
                    tandaiPosisi(posisiSesuai(titik));
                    window.requestAnimationFrame(putaran);
                }).catch(function () {
                    window.requestAnimationFrame(putaran);
                });

                return;
            }

            window.requestAnimationFrame(putaran);
        }

        /* --------------------------------------------------- kamera */
        function nyalakanKamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                tulisPesan('Kamera tidak tersedia pada peramban ini.');
                if (tombol) { tombol.disabled = true; }
                return;
            }

            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 960 } }, audio: false })
                .then(function (media) {
                    aliran = media;
                    video.srcObject = media;
                    return video.play();
                })
                .then(function () {
                    berjalan = true;
                    putaran();

                    return muatPendeteksi().then(function (alat) {
                        pendeteksi = alat;
                    }).catch(function () {
                        // Tanpa model, pakaian ditempel pada perkiraan tengah bingkai
                        pendeteksi = null;
                    });
                })
                .catch(function () {
                    tulisPesan('Kamera tidak dapat dipakai. Izinkan kamera pada peramban Anda.');
                });
        }

        function matikanKamera() {
            berjalan = false;
            if (aliran) {
                aliran.getTracks().forEach(function (jalur) { jalur.stop(); });
                aliran = null;
            }
        }

        /* ------------------------------------------- penyusunan gambar */
        function gambarPakaian(ctx, lebar, tinggi) {
            return new Promise(function (selesai) {
                var alamat = panggung.dataset.gambar;
                if (!alamat) { selesai(); return; }

                var gbr = new Image();
                gbr.onload = function () {
                    var rasio = gbr.naturalHeight / (gbr.naturalWidth || 1);
                    var lebarPakaian;
                    var tengahX;
                    var atas;

                    if (titik.left_shoulder && titik.right_shoulder) {
                        var lebarBahu = Math.abs(titik.left_shoulder[0] - titik.right_shoulder[0]) * lebar;
                        var bahuY = ((titik.left_shoulder[1] + titik.right_shoulder[1]) / 2) * tinggi;

                        lebarPakaian = lebarBahu * 2.1;
                        tengahX = ((titik.left_shoulder[0] + titik.right_shoulder[0]) / 2) * lebar;
                        atas = bahuY - (lebarPakaian * rasio * 0.08);

                        // Panjang badan dipakai supaya pakaian tidak melewati pinggul terlalu jauh
                        if (titik.left_hip || titik.right_hip) {
                            var pinggulY = ((titik.left_hip ? titik.left_hip[1] : titik.right_hip[1])) * tinggi;
                            var panjangBadan = Math.max(pinggulY - bahuY, 1);
                            var panjangTarget = panjangBadan * 1.45;
                            if (lebarPakaian * rasio > panjangTarget) {
                                lebarPakaian = panjangTarget / rasio;
                            }
                        }
                    } else {
                        // Perkiraan tengah bingkai bila titik tubuh tidak terbaca
                        lebarPakaian = lebar * 0.52;
                        tengahX = lebar / 2;
                        atas = tinggi * 0.22;
                    }

                    var tinggiPakaian = lebarPakaian * rasio;

                    ctx.globalAlpha = 0.95;
                    ctx.drawImage(gbr, tengahX - (lebarPakaian / 2), atas, lebarPakaian, tinggiPakaian);
                    ctx.globalAlpha = 1;
                    selesai();
                };
                gbr.onerror = function () { selesai(); };
                gbr.src = alamat;
            });
        }

        function tangkapKamera() {
            var lebar = Math.min(video.videoWidth || 720, 720);
            var tinggi = Math.round((video.videoHeight || 960) * (lebar / (video.videoWidth || 720)));

            kanvas.width = lebar;
            kanvas.height = tinggi;

            var ctx = kanvas.getContext('2d');
            ctx.save();
            ctx.translate(lebar, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, lebar, tinggi);
            ctx.restore();

            return { ctx: ctx, lebar: lebar, tinggi: tinggi };
        }

        function kirim() {
            if (!video.videoWidth) {
                tulisPesan('Kamera belum siap. Tunggu sebentar lalu tekan Generate lagi.');
                return;
            }

            tulisPesan('');
            if (tombol) { tombol.disabled = true; }
            if (aksi) { aksi.hidden = true; }
            if (memuat) { memuat.hidden = false; }

            var papan = tangkapKamera();
            var tangkapan = kanvas.toDataURL('image/jpeg', 0.85);

            if (mode === 'api') {
                // Layanan yang menyusun hasilnya, jadi yang dikirim tangkapan apa adanya
                matikanKamera();
                if (isiGambar) { isiGambar.value = tangkapan; }
                if (isiFoto) { isiFoto.value = ''; }
                borang.submit();
                return;
            }

            gambarPakaian(papan.ctx, papan.lebar, papan.tinggi).then(function () {
                matikanKamera();
                if (isiFoto) { isiFoto.value = tangkapan; }
                if (isiGambar) { isiGambar.value = kanvas.toDataURL('image/png'); }
                borang.submit();
            });
        }

        if (tombol) { tombol.addEventListener('click', kirim); }

        if (tombolUlang) {
            tombolUlang.addEventListener('click', function () {
                if (kotakHasil) { kotakHasil.hidden = true; }
                panggung.hidden = false;
                if (aksi) { aksi.hidden = false; }
                if (tombol) { tombol.disabled = false; }
                tandaiPosisi(false);
                nyalakanKamera();
            });
        }

        // Kamera hanya dinyalakan ketika panggungnya memang sedang terlihat
        if (!panggung.hidden) { nyalakanKamera(); }

        window.addEventListener('pagehide', matikanKamera);
    }

    siapkanPemilihan();
    siapkanPanggung();
})();
