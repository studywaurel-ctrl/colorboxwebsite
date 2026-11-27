<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * Daftar toko Colorbox. Nama mal dan kotanya nyata, sedangkan lantai,
 * nomor unit, dan titik koordinatnya adalah perkiraan untuk contoh.
 */
class TokoSeeder extends Seeder
{
    public const TOKO = [
        // kode, nama, kota, alamat, lantai, lat, lng, ttc, donasi
        ['CP', 'Colorbox Central Park', 'Jakarta Barat', 'Central Park Mall, Jl. Letjen S. Parman Kav. 28, Tanjung Duren Selatan', 'Lantai 1 unit L1-206', -6.177400, 106.790700, true, true],
        ['TA', 'Colorbox Mall Taman Anggrek', 'Jakarta Barat', 'Mall Taman Anggrek, Jl. Letjen S. Parman Kav. 21, Tanjung Duren Selatan', 'Lantai 2 unit 216', -6.178500, 106.792200, true, true],
        ['KG', 'Colorbox Mal Kelapa Gading', 'Jakarta Utara', 'Mal Kelapa Gading 1, Jl. Bulevar Kelapa Gading Blok M', 'Lantai 1 unit 128', -6.157500, 106.908700, true, true],
        ['MOI', 'Colorbox Mall of Indonesia', 'Jakarta Utara', 'Mall of Indonesia, Jl. Raya Boulevard Barat, Kelapa Gading Barat', 'Lantai 1 unit 1-35', -6.150400, 106.892000, true, false],
        ['GC', 'Colorbox Gandaria City', 'Jakarta Selatan', 'Gandaria City, Jl. Sultan Iskandar Muda, Kebayoran Lama', 'Lantai 1 unit 60', -6.244100, 106.783500, true, true],
        ['GI', 'Colorbox Grand Indonesia', 'Jakarta Pusat', 'Grand Indonesia Shopping Town, Jl. M.H. Thamrin No. 1, Menteng', 'West Mall lantai 3 unit 15', -6.195200, 106.820500, true, false],
        ['LP', 'Colorbox Lippo Mall Puri', 'Jakarta Barat', 'Lippo Mall Puri, Jl. Puri Indah Raya Blok U1, Kembangan Selatan', 'Lantai 1 unit 32', -6.187800, 106.739000, true, true],
        ['LK', 'Colorbox Lippo Supermall Karawaci', 'Tangerang', 'Lippo Supermall Karawaci, Jl. Bulevar Diponegoro No. 105', 'Lantai 1 unit 118', -6.226300, 106.607600, true, true],
        ['TC', 'Colorbox Tangerang City', 'Tangerang', 'Tangerang City Mall, Jl. Jenderal Sudirman No. 1, Cikokol', 'Lantai 2 unit 25', -6.194700, 106.639500, true, false],
        ['SMS', 'Colorbox Summarecon Mal Serpong', 'Tangerang', 'Summarecon Mal Serpong, Jl. Boulevard Gading Serpong, Pakulonan Barat', 'Downtown Walk lantai 1 unit 12', -6.241300, 106.628400, true, true],
        ['BX', 'Colorbox Bintaro Jaya Xchange', 'Tangerang Selatan', 'Bintaro Jaya Xchange Mall, Jl. Bintaro Utama Sektor 7, Pondok Jaya', 'Lantai 1 unit 21', -6.285300, 106.727500, true, true],
        ['AEB', 'Colorbox AEON Mall BSD City', 'Tangerang Selatan', 'AEON Mall BSD City, Jl. BSD Raya Utama, Pagedangan', 'Lantai 1 unit 1-42', -6.304400, 106.643700, true, false],
    ];

    public function run(): void
    {
        foreach (self::TOKO as [$kode, $nama, $kota, $alamat, $lantai, $lat, $lng, $ttc, $donasi]) {
            Store::updateOrCreate(['code' => $kode], [
                'name' => $nama,
                'city' => $kota,
                'address' => $alamat,
                'floor' => $lantai,
                'phone' => null,
                'latitude' => $lat,
                'longitude' => $lng,
                'open_time' => '10:00:00',
                'close_time' => '22:00:00',
                'has_ttc' => $ttc,
                'has_donation' => $donasi,
                'is_active' => true,
            ]);
        }
    }
}
