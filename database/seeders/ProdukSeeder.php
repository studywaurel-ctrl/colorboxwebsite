<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Mengisi katalog dari database/data/produk.json. Nama, harga, warna, dan
 * ukuran diambil dari situs resmi colorbox.co.id. Foto produknya diunduh
 * terpisah memakai "php tools/unduh_gambar.php" lalu dicatat di sini.
 */
class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $berkas = database_path('data/produk.json');
        if (! is_file($berkas)) {
            $this->command?->warn('database/data/produk.json tidak ditemukan, katalog dilewati.');

            return;
        }

        $data = json_decode(file_get_contents($berkas), true);
        $kategori = Category::pluck('id', 'name');
        $toko = Store::all();
        $adaFoto = 0;

        foreach ($data['produk'] as $i => $p) {
            $idKategori = $kategori[$p['kategori']] ?? $kategori->first();

            $produk = Product::updateOrCreate(['slug' => $p['slug']], [
                'category_id' => $idKategori,
                'name' => $p['nama'],
                'sku' => 'CBX-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'price' => $p['harga'],
                'discount_price' => null,
                'description' => $this->deskripsi($p),
                'material' => $p['bahan'],
                'care' => $p['perawatan'],
                'collection' => $p['koleksi'],
                'is_new' => $i < 16,
                'is_active' => true,
                'weight' => 300,
            ]);

            $produk->varian()->delete();
            $produk->gambar()->delete();

            $urut = 0;
            foreach ($p['warna'] as $w) {
                // gambar: berkas hasil unduhan disimpan memakai pola
                // {slug}-{warna}-{nomor}.png di public/images/products
                $slugWarna = Str::slug($w['nama']);
                for ($n = 1; $n <= 3; $n++) {
                    $jalur = 'images/products/'.$p['slug'].'-'.$slugWarna.'-'.$n.'.png';
                    $ada = file_exists(public_path($jalur));
                    if ($n > 1 && ! $ada) {
                        continue;   // foto kedua dan ketiga hanya dicatat bila berkasnya ada
                    }
                    if ($ada) {
                        $adaFoto++;
                    }
                    ProductImage::create([
                        'product_id' => $produk->id,
                        'color' => $w['nama'],
                        'path' => $jalur,
                        'source_url' => $n === 1 ? $w['gambar_sumber'] : null,
                        'sort_order' => $urut++,
                    ]);
                }

                foreach ($p['ukuran'] as $ukuran) {
                    $varian = ProductVariant::create([
                        'product_id' => $produk->id,
                        'color' => $w['nama'],
                        'color_hex' => $w['hex'],
                        'size' => trim($ukuran),
                        'sku' => $produk->sku.'-'.Str::upper(Str::substr($slugWarna, 0, 3)).'-'.trim($ukuran),
                        'stock' => random_int(3, 25),
                    ]);

                    // stok toko: hanya sebagian toko yang menyimpan tiap varian
                    foreach ($toko->random(min(5, $toko->count())) as $t) {
                        StoreStock::updateOrCreate(
                            ['store_id' => $t->id, 'product_variant_id' => $varian->id],
                            ['stock' => random_int(0, 8)]
                        );
                    }
                }
            }
        }

        $jumlah = Product::count();
        $this->command?->info("Katalog terisi: {$jumlah} produk, {$adaFoto} berkas foto ditemukan.");
        if ($adaFoto === 0) {
            $this->command?->warn('Foto produk belum ada. Jalankan: php tools/unduh_gambar.php lalu ulangi seeder.');
        }
    }

    /** Deskripsi contoh yang disusun dari data produk. */
    private function deskripsi(array $p): string
    {
        $warna = implode(', ', array_map(fn ($w) => $w['nama'], $p['warna']));

        return $p['nama'].' dari koleksi Colorbox. Tersedia dalam warna '.$warna.
            ' dengan ukuran '.implode(', ', $p['ukuran']).
            '. Bahan '.strtolower($p['bahan']).' yang nyaman dipakai sehari-hari. '.
            'Keterangan bahan dan deskripsi ini adalah contoh untuk keperluan penelitian.';
    }
}
