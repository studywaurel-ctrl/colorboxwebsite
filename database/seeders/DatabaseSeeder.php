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
    }
}
