<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Membubuhkan cap waktu ke atas foto bukti penerimaan barang.
 *
 * -------------------------------------------------------------------------
 * KENAPA DICAP DI SERVER, BUKAN DITULIS DI BAWAH GAMBAR
 * -------------------------------------------------------------------------
 *
 * Waktu unggahnya memang sudah tersimpan di kolom completed_at, dan halaman
 * rincian pesanan sudah menuliskannya di bawah gambar. Tetapi tulisan itu
 * terpisah dari gambarnya: begitu fotonya diunduh, dikirim lewat pesan, atau
 * ditempel ke berkas lain, keterangan waktunya tertinggal dan yang tersisa
 * gambar tanpa keterangan.
 *
 * Cap yang menyatu dengan gambar ikut ke mana pun gambarnya pergi. Inilah
 * sebabnya kamera pengawas dan aplikasi absensi membubuhkan waktu di atas
 * gambarnya sendiri, bukan di sebelahnya.
 *
 * -------------------------------------------------------------------------
 * APA YANG DIBUKTIKAN CAP INI, DAN APA YANG TIDAK
 * -------------------------------------------------------------------------
 *
 * Cap ini menyatakan KAPAN FOTONYA DITERIMA SERVER, menurut jam server,
 * bukan kapan gambarnya diambil. Pelanggan bisa saja memotret kemarin lalu
 * mengunggahnya hari ini. Jadi kalimat capnya tidak boleh berbunyi "diambil
 * pada", sebab itu klaim yang tidak bisa dijamin siapa pun di sini.
 *
 * Yang dijamin cuma satu, dan itu memang yang dibutuhkan: berkas ini sampai
 * ke Colorbox pada waktu tersebut.
 *
 * -------------------------------------------------------------------------
 * KENAPA MEMAKAI HURUF BAWAAN GD
 * -------------------------------------------------------------------------
 *
 * imagettftext() menghasilkan huruf yang jauh lebih bagus, tetapi menuntut
 * berkas huruf TrueType yang ada di komputernya. Proyek ini dijalankan di
 * XAMPP pada Windows, tempat berkas huruf tidak bisa dipastikan ada di
 * jalur yang sama. Kalau hurufnya tidak ketemu, fungsinya gagal dan foto
 * buktinya tidak tersimpan sama sekali.
 *
 * imagestring() memakai huruf bitmap yang sudah menyatu di dalam GD, jadi
 * selalu ada. Ukurannya memang kecil, maka tulisannya digambar dulu di
 * petak kecil lalu diperbesar dengan imagecopyresampled, cara yang sama
 * dipakai banyak alat pemberi cap waktu.
 */
class CapWaktuFoto
{
    /**
     * Membubuhkan cap waktu ke berkas foto, menimpa berkas aslinya.
     *
     * Mengembalikan true kalau capnya berhasil dibubuhkan. Kalau gagal,
     * mengembalikan false dan berkas aslinya DIBIARKAN UTUH, sebab foto
     * tanpa cap tetap jauh lebih berguna daripada tidak ada foto sama
     * sekali. Kegagalan di sini tidak boleh membatalkan konfirmasi
     * penerimaan barang.
     */
    public static function bubuhkan(string $jalurLengkap, ?Carbon $waktu = null): bool
    {
        if (! extension_loaded('gd') || ! is_file($jalurLengkap)) {
            return false;
        }

        $waktu = $waktu ?: now();

        $info = @getimagesize($jalurLengkap);
        if (! $info) {
            return false;
        }

        $gambar = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($jalurLengkap),
            IMAGETYPE_PNG => @imagecreatefrompng($jalurLengkap),
            IMAGETYPE_WEBP => @imagecreatefromwebp($jalurLengkap),
            default => null,
        };

        if (! $gambar) {
            return false;
        }

        $lebar = imagesx($gambar);
        $tinggi = imagesy($gambar);

        // Gambar yang terlalu kecil tidak dicap. Bilah capnya akan
        // menutupi sebagian besar isinya dan tulisannya tetap tidak
        // terbaca, jadi hasilnya cuma merusak fotonya.
        if ($lebar < 200 || $tinggi < 150) {
            imagedestroy($gambar);

            return false;
        }

        // Kalimat capnya. Zona waktunya ikut ditulis, sebab foto ini bisa
        // dibaca orang yang jam komputernya berbeda.
        $tulisan = $waktu->format('d/m/Y H:i').' WIB  COLORBOX';

        // Tinggi bilah cap mengikuti ukuran gambarnya, jadi cap pada foto
        // 4000 piksel tidak tampil sebagai garis tipis yang tak terbaca,
        // dan cap pada foto 600 piksel tidak menutupi setengah gambarnya.
        $tinggiBilah = max(28, (int) round($tinggi * 0.065));
        $perbesar = max(1, (int) round($tinggiBilah / 16));

        // Bilah gelap setengah tembus di bagian bawah, supaya tulisannya
        // terbaca di atas foto terang maupun gelap.
        $hitam = imagecolorallocatealpha($gambar, 0, 0, 0, 55);
        imagefilledrectangle($gambar, 0, $tinggi - $tinggiBilah, $lebar, $tinggi, $hitam);

        // Tulisannya digambar di petak kecil memakai huruf bitmap bawaan,
        // lalu diperbesar ke petak sebenarnya.
        $lebarHuruf = imagefontwidth(5) * strlen($tulisan);
        $tinggiHuruf = imagefontheight(5);

        $petak = imagecreatetruecolor($lebarHuruf, $tinggiHuruf);
        imagesavealpha($petak, true);
        $bening = imagecolorallocatealpha($petak, 0, 0, 0, 127);
        imagefill($petak, 0, 0, $bening);
        imagestring($petak, 5, 0, 0, $tulisan, imagecolorallocate($petak, 255, 255, 255));

        $lebarAkhir = min($lebar - 24, $lebarHuruf * $perbesar);
        $tinggiAkhir = (int) round($tinggiHuruf * ($lebarAkhir / $lebarHuruf));

        imagecopyresampled(
            $gambar, $petak,
            12, $tinggi - $tinggiBilah + (int) round(($tinggiBilah - $tinggiAkhir) / 2),
            0, 0,
            $lebarAkhir, $tinggiAkhir,
            $lebarHuruf, $tinggiHuruf
        );

        imagedestroy($petak);

        $berhasil = match ($info[2]) {
            IMAGETYPE_JPEG => imagejpeg($gambar, $jalurLengkap, 88),
            IMAGETYPE_PNG => imagepng($gambar, $jalurLengkap),
            IMAGETYPE_WEBP => imagewebp($gambar, $jalurLengkap),
            default => false,
        };

        imagedestroy($gambar);

        return (bool) $berhasil;
    }
}
