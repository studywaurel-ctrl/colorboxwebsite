<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PengaturanSeeder::class,
            KategoriSeeder::class,
            TokoSeeder::class,
            ProdukSeeder::class,
            PengirimanSeeder::class,
            SlotSeeder::class,
            PenggunaSeeder::class,
            ContohTransaksiSeeder::class,
        ]);

        // Data produk langsung diperiksa kelengkapannya supaya tidak ada kolom
        // kosong yang lolos tanpa ketahuan. Rinciannya:
        // php artisan colorbox:periksa-produk --rinci
        $this->command?->newLine();
        $this->command?->call('colorbox:periksa-produk');
    }
}
