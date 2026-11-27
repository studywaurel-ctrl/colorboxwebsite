<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog produk: kategori, produk, warna dan ukuran (variant),
 * gambar produk, toko, serta stok produk di setiap toko.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 60);
            $table->string('slug', 80)->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->string('sku', 40)->nullable();
            $table->integer('price');
            $table->integer('discount_price')->nullable();
            $table->text('description')->nullable();
            $table->string('material', 150)->nullable();
            $table->string('care', 191)->nullable();
            $table->string('collection', 80)->nullable();   // misal: Colorbox X Liunic
            $table->boolean('is_new')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('weight')->default(300);        // gram, untuk ongkos kirim
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('color', 40);
            $table->string('color_hex', 10)->nullable();
            $table->string('size', 10);
            $table->string('sku', 50)->nullable();
            $table->integer('stock')->default(0);           // stok gudang untuk penjualan online
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('color', 40)->nullable();
            $table->string('path', 191);                    // contoh: images/products/produk-001-1.png
            $table->string('source_url', 500)->nullable();  // alamat gambar asli di colorbox.co.id
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 120);
            $table->string('city', 60);
            $table->string('address', 191);
            $table->string('floor', 40)->nullable();
            $table->string('phone', 20)->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->time('open_time')->default('10:00:00');
            $table->time('close_time')->default('22:00:00');
            $table->boolean('has_ttc')->default(true);      // melayani Tap Try and Collect
            $table->boolean('has_donation')->default(true); // menerima donasi pakaian
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('store_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('stock')->default(0);
            // ditahan untuk booking Tap Try and Collect yang belum diambil.
            // stok yang boleh dipesan = stock - reserved
            $table->integer('reserved')->default(0);
            $table->timestamps();
            $table->unique(['store_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_stocks');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
