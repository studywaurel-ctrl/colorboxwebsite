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
            ['donation_points_per_item', '1', 'Poin untuk setiap pcs pakaian donasi yang layak pakai'],
            ['point_value', '1000', 'Nilai satu poin (Rp) ketika ditukar saat checkout'],
            ['points_max_percent', '30', 'Batas pemakaian poin terhadap subtotal (persen)'],
            // Di bawah angka ini, stok dinyatakan hampir habis di seluruh
            // situs: katalog, detail produk, cek stok toko, panel kasir, dan
            // panel admin. Satu angka untuk semuanya, dibaca lewat
            // App\Services\StatusStok, supaya tidak ada dua halaman yang
            // berbeda pendapat tentang barang yang sama.
            ['low_stock_threshold', '3', 'Stok dinyatakan hampir habis bila tersisa sebanyak ini atau kurang'],
            ['return_window_days', '7', 'Batas hari pengajuan retur setelah pesanan diterima atau diambil'],
            ['return_name', 'Gudang Retur Colorbox', 'Nama penerima pada slip pengembalian barang'],
            ['contact_email', 'customercare@colorbox.co.id', 'Email layanan pelanggan pada halaman Contact Us'],
            ['contact_phone', '021 5555 1234', 'Nomor telepon kantor pusat pada halaman Contact Us'],
            ['contact_whatsapp', '0811 1000 2000', 'Nomor WhatsApp layanan pelanggan'],
            ['contact_hours', 'Senin sampai Jumat, pukul 09.00 sampai 18.00 WIB', 'Jam layanan pelanggan'],
            ['company_address', 'Jl. Daan Mogot KM 12 No. 9, Cengkareng, Jakarta Barat 11730', 'Alamat kantor pusat pada halaman Contact Us'],
            ['return_address', 'Jl. Daan Mogot KM 12 No. 9, Cengkareng, Jakarta Barat 11730', 'Alamat gudang tujuan pengiriman barang retur'],
            ['return_phone', '021 5555 1234', 'Nomor telepon yang ditulis pada paket retur'],
            ['payment_expiry_hours', '24', 'Batas waktu pembayaran (jam) sebelum pesanan dibatalkan'],
            ['ttc_slot_quota', '4', 'Kuota bawaan satu slot Tap Try and Collect'],
            ['donation_slot_quota', '5', 'Kuota bawaan satu slot donasi'],
            ['booking_window_days', '14', 'Jumlah hari ke depan yang boleh dipesan pelanggan'],
            ['ttc_prep_minutes', '30', 'Waktu penyiapan produk Tap Try and Collect (menit) sebelum jadwal'],
            // Batas atas penyiapan. Dua angka, bukan satu, sebab yang
            // dijanjikan ke pelanggan adalah RENTANG: paling cepat 30 menit
            // kalau ukurannya memang ada di rak, paling lambat 60 menit pada
            // penyiapan biasa. Satu angka tunggal memaksa petugas memilih
            // antara menjanjikan terlalu cepat lalu terlambat, atau
            // menjanjikan terlalu lama lalu pelanggan tidak jadi memesan.
            ['ttc_prep_max_minutes', '60', 'Batas paling lambat penyiapan Tap Try and Collect (menit) sejak pembayaran berhasil'],
            ['ttc_same_day_cutoff', '15:00', 'Batas jam pemesanan Tap Try and Collect untuk hari yang sama'],
            ['ttc_last_slot', '15:00', 'Jam mulai slot terakhir Tap Try and Collect yang boleh dipilih'],
            ['ttc_pickup_until', '21:00', 'Batas jam pengambilan Tap Try and Collect di toko'],
            ['booking_expire_days', '1', 'Booking dan donasi hangus setelah berapa hari dari jadwalnya'],
            // Tiga aturan jam untuk antar barang retur ke toko.
            //
            // Pemesanannya ditutup pukul 15.00, tetapi pelayanannya jalan
            // sampai pukul 21.00. Dua angka yang berbeda itu disengaja:
            // kasir perlu waktu menyiapkan barang pengganti dan memeriksa
            // barang yang dibawa, dan pekerjaan itu tidak bisa dimulai
            // menjelang toko tutup. Jadi yang dibatasi KAPAN pelanggan
            // boleh memesan, bukan sampai jam berapa toko melayaninya.
            ['retur_same_day_cutoff', '15:00', 'Batas jam pemesanan jadwal antar retur untuk hari yang sama'],
            ['retur_last_slot', '21:00', 'Jam mulai slot terakhir kedatangan retur di toko'],
            // Barang retur yang dijadwalkan tetapi tidak pernah dibawa
            // menahan stok pengganti tanpa batas waktu. Tiga hari memberi
            // kelonggaran untuk halangan biasa, tanpa menggantung rak toko
            // selamanya.
            ['retur_batal_hari', '3', 'Jadwal antar retur batal otomatis setelah berapa hari dari jadwalnya'],
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
