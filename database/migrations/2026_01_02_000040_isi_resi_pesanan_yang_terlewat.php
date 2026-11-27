<?php

use App\Services\PenyerahanPaket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi nomor resi pesanan yang sudah berangkat tetapi kolomnya kosong.
 *
 * -------------------------------------------------------------------------
 * KENAPA PERLU MIGRASI, BUKAN CUKUP MEMPERBAIKI KODENYA
 * -------------------------------------------------------------------------
 *
 * Penyebabnya sudah diperbaiki di PesananAdminController: jalur ubah status
 * milik admin dulu membuat baris pengiriman dengan tracking_number kosong,
 * sedangkan jalur satunya, App\Services\PenyerahanPaket, sejak awal membuat
 * nomor peragaan untuk keadaan yang sama.
 *
 * Tetapi perbaikan kode hanya berlaku untuk pesanan yang BERANGKAT SESUDAH
 * ini. Pesanan yang sudah terlanjur berangkat lewat jalur yang salah tetap
 * memegang kolom kosong, dan di layar pelanggan tetap tertulis "Nomor
 * resinya muncul di sini begitu paketnya diserahkan kepada kurir" padahal
 * statusnya sudah Track Order. Dua kalimat yang saling bertentangan di satu
 * layar itulah yang dikeluhkan, dan hanya migrasi yang bisa membereskannya.
 *
 * -------------------------------------------------------------------------
 * YANG TIDAK DISENTUH
 * -------------------------------------------------------------------------
 *
 * Baris yang tracking_number-nya SUDAH terisi dilewati sepenuhnya. Nomor di
 * sana bisa jadi resi sungguhan yang disalin petugas dari struk kurir, dan
 * menimpanya dengan nomor peragaan akan menghapus satu satunya angka yang
 * benar benar bisa dicari pelanggan di situs jasa kirim.
 */
return new class extends Migration
{
    public function up(): void
    {
        $kosong = DB::table('shipments')
            ->join('orders', 'orders.id', '=', 'shipments.order_id')
            ->whereNull('shipments.tracking_number')
            ->select('shipments.id as nomor_kiriman', 'orders.order_code as kode')
            ->get();

        foreach ($kosong as $baris) {
            DB::table('shipments')
                ->where('id', $baris->nomor_kiriman)
                ->update([
                    // Memanggil method yang sama dengan yang dipakai kedua
                    // jalur pengiriman, bukan merangkai nomornya sendiri di
                    // sini, supaya bentuknya pasti sama.
                    'tracking_number' => PenyerahanPaket::nomorSimulasi($baris->kode),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Nomor peragaan dikosongkan lagi. Yang dihapus hanya yang
        // berawalan SIM-, jadi resi sungguhan tidak ikut hilang kalau
        // migrasi ini dibatalkan.
        DB::table('shipments')
            ->where('tracking_number', 'like', PenyerahanPaket::AWALAN_SIMULASI.'%')
            ->update(['tracking_number' => null]);
    }
};
