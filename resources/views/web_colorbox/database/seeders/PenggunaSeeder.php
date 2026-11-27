<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun contoh: satu pelanggan, satu admin, dan satu kasir untuk tiap toko.
 *
 * Seluruh akun contoh memakai aturan kata sandi yang sama, yaitu tanggal
 * lahirnya sendiri dengan penulisan yyyy-mm-dd. Dengan begitu tidak ada kata
 * sandi yang perlu diisi di .env dan tidak ada kata sandi yang ditulis sebagai
 * teks di dalam kode. Tanggal lahir itu juga disimpan pada kolom birth_date,
 * jadi kalau lupa tinggal dilihat di phpMyAdmin.
 *
 * Akun pelanggan tetap bisa mendaftar sendiri lewat halaman daftar. Akun admin
 * dan kasir dibuatkan perusahaan, nama lengkapnya dipakai sebagai nama
 * pengguna.
 */
class PenggunaSeeder extends Seeder
{
    /** Tanggal lahir pelanggan contoh, sekaligus kata sandi awalnya. */
    private const LAHIR_PELANGGAN = '2003-05-14';

    public function run(): void
    {
        $pelanggan = User::updateOrCreate(['email' => 'real.notmreli@gmail.com'], [
            'name' => 'Aurelia Widnyani',
            'password' => Hash::make(self::LAHIR_PELANGGAN),
            'phone' => '081234567890',
            'birth_date' => self::LAHIR_PELANGGAN,
            'gender' => 'Perempuan',
            'role' => 'pelanggan',
            'points' => 24,
            'member_level' => 'Classic',
        ]);

        // Kode member dipakai kasir untuk mencari pelanggan di meja kasir
        $pelanggan->pastikanKodeMember();

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

        $this->command?->line('  Akun pelanggan: '.$pelanggan->email.'  |  kata sandi: '.self::LAHIR_PELANGGAN);

        $this->petugas('admin@colorbox.test', 'Admin Colorbox', '1995-08-17', 'admin', null,
            '02150001000');

        // Satu akun kasir untuk setiap toko yang melayani Tap Try and Collect
        // atau donasi, supaya alur kerja kasir bisa dicoba di semua toko.
        // Tanggal lahirnya dibuat berbeda-beda supaya kata sandi tiap kasir
        // tidak sama, seperti akun sungguhan yang dibuatkan bagian personalia.
        $hari = 1;
        foreach (Store::where('is_active', true)->get() as $toko) {
            $lahir = sprintf('199%d-%02d-%02d', $hari % 10, ($hari % 12) + 1, ($hari % 27) + 1);
            $this->petugas(
                'kasir.'.strtolower($toko->code).'@colorbox.test',
                'Kasir '.$toko->name,
                $lahir,
                'kasir',
                $toko->id
            );
            $hari++;
        }

        $this->command?->line('  Kata sandi akun contoh adalah tanggal lahirnya masing-masing.');
        $this->command?->line('  PIN petugas toko: '.config('colorbox.demo.pin_staf'));
    }

    /**
     * Membuat akun petugas: kata sandinya tanggal lahir.
     *
     * Ditulis sekali di sini supaya aturannya tidak tersebar, dan supaya
     * tanggal yang disimpan pada kolom birth_date selalu sama dengan kata
     * sandinya.
     */
    private function petugas(string $email, string $nama, string $lahir, string $peran,
        ?int $tokoId = null, ?string $telepon = null): User
    {
        $akun = User::updateOrCreate(['email' => $email], [
            'name' => $nama,
            'password' => Hash::make($lahir),
            'phone' => $telepon,
            'birth_date' => $lahir,
            'role' => $peran,
            'store_id' => $tokoId,
        ]);

        $this->command?->line('  Akun '.$peran.': '.$nama.'  |  kata sandi: '.$lahir);

        return $akun;
    }

}
