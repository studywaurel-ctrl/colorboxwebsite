<?php

use App\Models\Setting;

if (! function_exists('rupiah')) {
    /** Mengubah angka menjadi tulisan rupiah, contoh: Rp199.900 */
    function rupiah($jumlah): string
    {
        return 'Rp'.number_format((float) $jumlah, 0, ',', '.');
    }
}

if (! function_exists('setting')) {
    /** Mengambil aturan bisnis dari tabel settings. */
    function setting(string $kunci, $bawaan = null)
    {
        return Setting::ambil($kunci, $bawaan);
    }
}

if (! function_exists('gambar_produk')) {
    /** URL gambar produk. Memakai placeholder bila berkas belum diunduh. */
    function gambar_produk(?string $path): string
    {
        if ($path && file_exists(public_path($path))) {
            return asset($path);
        }

        return asset('images/products/placeholder.png');
    }
}

if (! function_exists('tanggal_id')) {
    /** Menulis tanggal dalam bahasa Indonesia, contoh: 20 Juli 2026 */
    function tanggal_id($tanggal, bool $pakai_hari = false): string
    {
        if (! $tanggal) {
            return '-';
        }
        $waktu = $tanggal instanceof \DateTimeInterface ? $tanggal : new \DateTime($tanggal);
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $teks = $waktu->format('j').' '.$bulan[(int) $waktu->format('n')].' '.$waktu->format('Y');

        return $pakai_hari ? $hari[(int) $waktu->format('w')].', '.$teks : $teks;
    }
}

if (! function_exists('jam_singkat')) {
    /** Menulis jam dalam bentuk 10:00 dari kolom time MySQL. */
    function jam_singkat(?string $jam): string
    {
        return $jam ? substr($jam, 0, 5) : '-';
    }
}
