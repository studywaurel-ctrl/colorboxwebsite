<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun contoh: satu admin, satu pelanggan, dan satu kasir toko.
 * Kata sandinya diambil dari .env, tidak pernah ditulis di dalam kode.
 */
class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        $sandiPelanggan = $this->sandi('colorbox.demo.password_pelanggan', 'pelanggan');
        $sandiAdmin = $this->sandi('colorbox.demo.password_admin', 'admin');

        $pelanggan = User::updateOrCreate(['email' => 'real.notmreli@gmail.com'], [
            'name' => 'Aurelia Widnyani',
            'password' => Hash::make($sandiPelanggan),
            'phone' => '081234567890',
            'birth_date' => '2003-05-14',
            'gender' => 'Perempuan',
            'role' => 'pelanggan',
            'points' => 24,
            'member_level' => 'Classic',
        ]);

        Address::updateOrCreate(
            ['user_id' => $pelanggan->id, 'label' => 'Rumah'],
            [
                'recipient' => 'Aurelia Widnyani',
                'phone' => '081234567890',
                'province' => 'Banten',
                'city' => 'Tangerang Selatan',
                'district' => 'Pondok Aren',
                'postal_code' => '15224',
                'detail' => 'Jl. Bintaro Utama Sektor 3 No. 12, Pondok Karya',
                'is_primary' => true,
            ]
        );

        User::updateOrCreate(['email' => 'admin@colorbox.test'], [
            'name' => 'Admin Colorbox',
            'password' => Hash::make($sandiAdmin),
            'phone' => '02150001000',
            'role' => 'admin',
        ]);

        $toko = Store::where('code', 'BX')->first();
        if ($toko) {
            User::updateOrCreate(['email' => 'kasir.bintaro@colorbox.test'], [
                'name' => 'Kasir Bintaro Jaya Xchange',
                'password' => Hash::make($sandiAdmin),
                'role' => 'kasir',
                'store_id' => $toko->id,
            ]);
        }

        $pin = (string) config('colorbox.demo.pin_staf', '');
        if (! preg_match('/^\d{4,8}$/', $pin)) {
            $this->command?->warn('COLORBOX_STAFF_PIN di .env masih kosong. Isi 4 sampai 8 angka sebelum mencoba verifikasi staf toko.');
        }
    }

    /** Mengambil kata sandi dari .env, atau membuat acak bila kosong. */
    private function sandi(string $kunci, string $untuk): string
    {
        $nilai = (string) config($kunci, '');
        if (strlen($nilai) >= 8) {
            return $nilai;
        }

        $acak = 'cbx-'.bin2hex(random_bytes(4));
        $this->command?->warn("Kata sandi akun {$untuk} belum diisi di .env. Kata sandi sementara: {$acak}");

        return $acak;
    }
}
