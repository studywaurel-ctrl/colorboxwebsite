<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Kategori produk mengikuti susunan menu di situs resmi Colorbox. */
class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $pohon = [
            'Pakaian' => ['Atasan', 'Kemeja dan Blus', 'Outerwear', 'Bawahan', 'Rok', 'Denim', 'Basics'],
            'Aksesori' => ['Tas', 'Sepatu', 'Aksesori'],
        ];

        $urut = 1;
        foreach ($pohon as $induk => $anak) {
            $indukModel = Category::updateOrCreate(
                ['slug' => Str::slug($induk)],
                ['name' => $induk, 'parent_id' => null, 'sort_order' => $urut++]
            );

            foreach ($anak as $nama) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($nama)],
                    ['name' => $nama, 'parent_id' => $indukModel->id, 'sort_order' => $urut++]
                );
            }
        }
    }
}
