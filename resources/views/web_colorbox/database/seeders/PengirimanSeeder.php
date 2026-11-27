<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\Voucher;
use Illuminate\Database\Seeder;

/** Jasa kirim dan voucher contoh. Tarifnya adalah contoh. */
class PengirimanSeeder extends Seeder
{
    public function run(): void
    {
        $tarif = [
            ['JNE', 'Reguler', 18000, 2, 3],
            ['JNE', 'YES (Yakin Esok Sampai)', 32000, 1, 1],
            ['Ninja Xpress', 'Standard', 16000, 2, 4],
            ['SPX', 'Standard', 15000, 3, 5],
        ];
        foreach ($tarif as [$kurir, $layanan, $ongkos, $min, $maks]) {
            ShippingRate::updateOrCreate(
                ['courier' => $kurir, 'service' => $layanan],
                ['cost' => $ongkos, 'etd_min' => $min, 'etd_max' => $maks, 'is_active' => true]
            );
        }

        $voucher = [
            ['CBXBARU10', 'Diskon 10% pelanggan baru', 'percent', 10, 50000, 200000, 100, 0, now()->addMonths(3), true],
            ['HEMAT25', 'Potongan Rp25.000', 'amount', 25000, null, 250000, 500, 12, now()->addMonths(3), true],
            ['AKHIRTAHUN', 'Diskon akhir tahun (kedaluwarsa)', 'percent', 20, 75000, 300000, 100, 0, now()->subMonths(2), true],
            ['KUOTAHABIS', 'Potongan Rp50.000 (kuota habis)', 'amount', 50000, null, 100000, 100, 100, now()->addMonth(), true],
        ];
        foreach ($voucher as [$kode, $nama, $jenis, $nilai, $maks, $min, $kuota, $dipakai, $kedaluwarsa, $aktif]) {
            Voucher::updateOrCreate(['code' => $kode], [
                'name' => $nama,
                'type' => $jenis,
                'value' => $nilai,
                'max_discount' => $maks,
                'min_purchase' => $min,
                'quota' => $kuota,
                'used' => $dipakai,
                'expired_at' => $kedaluwarsa,
                'is_active' => $aktif,
            ]);
        }
    }
}
