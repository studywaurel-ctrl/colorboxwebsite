<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/** Aturan bisnis yang boleh diubah lewat dashboard admin atau phpMyAdmin. */
class PengaturanSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            ['free_shipping_threshold', '350000', 'Belanja minimal (Rp) supaya ongkos kirim gratis'],
            ['donation_points_per_item', '1', 'Poin untuk setiap potong pakaian donasi yang layak pakai'],
            ['point_value', '1000', 'Nilai satu poin (Rp) ketika ditukar saat checkout'],
            ['points_max_percent', '30', 'Batas pemakaian poin terhadap subtotal (persen)'],
            ['return_window_days', '7', 'Batas hari pengajuan retur setelah pesanan diterima atau diambil'],
            ['payment_expiry_hours', '24', 'Batas waktu pembayaran (jam) sebelum pesanan dibatalkan'],
            ['ttc_slot_quota', '4', 'Kuota bawaan satu slot Tap Try and Collect'],
            ['donation_slot_quota', '5', 'Kuota bawaan satu slot donasi'],
            ['booking_window_days', '14', 'Jumlah hari ke depan yang boleh dipesan pelanggan'],
            ['ttc_prep_minutes', '30', 'Waktu penyiapan produk Tap Try and Collect (menit) sebelum jadwal'],
            ['ttc_same_day_cutoff', '15:00', 'Batas jam pemesanan Tap Try and Collect untuk hari yang sama'],
            ['booking_expire_days', '1', 'Booking dan donasi hangus setelah berapa hari dari jadwalnya'],
            ['min_donation_items', '1', 'Jumlah pakaian donasi paling sedikit'],
            ['max_donation_items', '50', 'Jumlah pakaian donasi paling banyak'],
        ];

        foreach ($daftar as [$kunci, $nilai, $keterangan]) {
            Setting::updateOrCreate(['key' => $kunci], [
                'value' => $nilai,
                'description' => $keterangan,
            ]);
        }
    }
}
