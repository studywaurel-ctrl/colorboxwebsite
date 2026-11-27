<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Beberapa foto dan satu video unboxing pada pengajuan retur.
 *
 * -------------------------------------------------------------------------
 * KENAPA SATU FOTO TIDAK CUKUP
 * -------------------------------------------------------------------------
 *
 * Sebelumnya pengajuan retur hanya membawa satu kolom photo. Satu gambar
 * cukup untuk membuktikan ada yang salah, tetapi tidak cukup untuk
 * membuktikan APA yang salah. Keluhan yang paling sering, yaitu ukuran
 * tidak sesuai dan jahitan lepas, menuntut sudut pandang yang berbeda:
 * satu foto label ukurannya, satu foto bagian yang rusak, satu foto
 * keseluruhan barangnya. Dengan satu kolom, petugas selalu kurang satu
 * sudut dan harus menanyakannya lagi, dan pelanggan menunggu sehari
 * hanya untuk mengirim gambar kedua.
 *
 * -------------------------------------------------------------------------
 * KENAPA VIDEO UNBOXING DIWAJIBKAN
 * -------------------------------------------------------------------------
 *
 * Foto barang rusak tidak bisa membedakan dua hal yang akibatnya sangat
 * berbeda: barang yang memang datang rusak, dan barang yang rusak sesudah
 * dipakai. Rekaman pembukaan paket yang utuh dari kardus masih tersegel
 * sampai barangnya terlihat menjawab pertanyaan itu sekali jalan.
 *
 * Inilah syarat yang dipakai hampir semua toko daring besar untuk klaim
 * barang rusak, dan alasannya sama: tanpa itu, yang tersisa cuma adu
 * pernyataan antara pembeli dan penjual.
 *
 * -------------------------------------------------------------------------
 * KOLOM photo YANG LAMA TIDAK DIHAPUS
 * -------------------------------------------------------------------------
 *
 * Pengajuan retur yang sudah ada menyimpan fotonya di sana. Menghapus
 * kolomnya berarti menghapus bukti pengajuan yang mungkin masih berjalan.
 * Jadi isinya DISALIN ke kolom photos yang baru sebagai larik berisi satu
 * jalur, dan kolom lamanya dibiarkan apa adanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $tabel) {
            // Larik jalur foto, satu sampai empat. Jumlahnya dibatasi
            // controller, sebab basis data tidak bisa menghitung isi JSON.
            $tabel->json('photos')->nullable()->after('photo');
            $tabel->string('video_path')->nullable()->after('photos');
        });

        // Foto pengajuan lama dipindahkan ke kolom baru, supaya halaman
        // rincian retur cukup membaca satu tempat.
        foreach (DB::table('return_requests')->whereNotNull('photo')->get(['id', 'photo']) as $baris) {
            DB::table('return_requests')
                ->where('id', $baris->id)
                ->update(['photos' => json_encode([$baris->photo])]);
        }
    }

    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $tabel) {
            $tabel->dropColumn(['photos', 'video_path']);
        });
    }
};
