<?php

/**
 * Mengunduh foto produk asli dari situs resmi Colorbox (colorbox.co.id)
 * lalu menyimpannya sebagai PNG di public/images/products.
 *
 * Cara menjalankan dari folder project:
 *     php tools/unduh_gambar.php
 *
 * Pilihan tambahan:
 *     php tools/unduh_gambar.php --ulang     mengunduh ulang walau berkas sudah ada
 *     php tools/unduh_gambar.php --maks=2    jumlah foto tiap warna (bawaan 3)
 *
 * Skrip ini hanya mengunduh berkas yang sudah ada di situs Colorbox dan
 * mengubah formatnya menjadi PNG. Tidak ada gambar yang dibuat ulang.
 */

$akar = dirname(__DIR__);
$berkasData = $akar.'/database/data/produk.json';
$tujuan = $akar.'/public/images/products';

if (! extension_loaded('gd')) {
    exit("Ekstensi GD belum aktif. Aktifkan extension=gd di php.ini lalu ulangi.\n");
}
if (! is_file($berkasData)) {
    exit("Berkas database/data/produk.json tidak ditemukan.\n");
}
if (! is_dir($tujuan)) {
    mkdir($tujuan, 0777, true);
}

$pilihan = getopt('', ['ulang', 'maks::']);
$ulang = isset($pilihan['ulang']);
$maks = isset($pilihan['maks']) ? max(1, (int) $pilihan['maks']) : 3;

$data = json_decode(file_get_contents($berkasData), true);
$produk = $data['produk'] ?? [];

echo "Mengunduh foto produk Colorbox (".count($produk)." produk)\n";
echo "Sumber: https://colorbox.co.id\n\n";

$berhasil = 0;
$gagal = 0;
$lewat = 0;

foreach ($produk as $p) {
    foreach ($p['warna'] as $w) {
        $namaWarna = slugWarna($w['nama']);
        $daftarUrl = ambilDaftarGambar($w['handle'], $w['gambar_sumber'], $maks);

        if (! $daftarUrl) {
            echo "  [gagal]  {$p['slug']} / {$w['nama']}: daftar gambar tidak terbaca\n";
            $gagal++;

            continue;
        }

        $nomor = 1;
        foreach ($daftarUrl as $url) {
            $namaBerkas = $tujuan.'/'.$p['slug'].'-'.$namaWarna.'-'.$nomor.'.png';
            if (! $ulang && is_file($namaBerkas)) {
                $lewat++;
                $nomor++;

                continue;
            }

            $isi = ambilIsi($url);
            if ($isi === null) {
                echo "  [gagal]  ".basename($namaBerkas)."\n";
                $gagal++;
                $nomor++;

                continue;
            }

            if (simpanPng($isi, $namaBerkas)) {
                echo "  [oke]    ".basename($namaBerkas)."\n";
                $berhasil++;
            } else {
                echo "  [gagal]  ".basename($namaBerkas)." (berkas bukan gambar)\n";
                $gagal++;
            }
            $nomor++;
        }
    }
}

echo "\nSelesai. Berhasil: $berhasil, dilewati: $lewat, gagal: $gagal\n";
echo "Berkas tersimpan di public/images/products\n";
echo "Jalankan lagi seeder supaya jalur gambarnya dicatat di database:\n";
echo "    php artisan migrate:fresh --seed\n";

/** Mengambil daftar URL gambar sebuah produk dari situs Colorbox. */
function ambilDaftarGambar(string $handle, ?string $cadangan, int $maks): array
{
    $isi = ambilIsi('https://colorbox.co.id/products/'.$handle.'.json');
    if ($isi !== null) {
        $json = json_decode($isi, true);
        $gambar = $json['product']['images'] ?? [];
        $url = [];
        foreach ($gambar as $g) {
            $src = is_array($g) ? ($g['src'] ?? null) : $g;
            if ($src) {
                $url[] = $src;
            }
            if (count($url) >= $maks) {
                break;
            }
        }
        if ($url) {
            return $url;
        }
    }

    return $cadangan ? [$cadangan] : [];
}

/** Mengunduh isi sebuah alamat. Memakai cURL bila tersedia. */
function ambilIsi(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (colorbox_pelanggan skripsi)',
        ]);
        $isi = curl_exec($ch);
        $kode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($isi !== false && $kode === 200) ? $isi : null;
    }

    $konteks = stream_context_create(['http' => [
        'timeout' => 30,
        'header' => "User-Agent: Mozilla/5.0 (colorbox_pelanggan skripsi)\r\n",
    ]]);
    $isi = @file_get_contents($url, false, $konteks);

    return $isi === false ? null : $isi;
}

/** Menyimpan isi gambar menjadi berkas PNG. */
function simpanPng(string $isi, string $tujuan): bool
{
    $gambar = @imagecreatefromstring($isi);
    if (! $gambar) {
        return false;
    }
    imagealphablending($gambar, false);
    imagesavealpha($gambar, true);
    $hasil = imagepng($gambar, $tujuan, 6);
    imagedestroy($gambar);

    return (bool) $hasil;
}

/** Mengubah nama warna menjadi potongan nama berkas, contoh: LT. PINK -> lt-pink */
function slugWarna(string $warna): string
{
    $s = strtolower($warna);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);

    return trim($s, '-');
}
