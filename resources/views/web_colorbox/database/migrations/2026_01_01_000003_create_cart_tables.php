<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alamat pengiriman, keranjang belanja, wishlist, voucher, dan tarif kirim.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40)->default('Rumah');
            $table->string('recipient', 100);
            $table->string('phone', 20);
            $table->string('province', 60);
            $table->string('city', 60);
            $table->string('district', 60)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('detail', 191);
            $table->string('landmark', 191)->nullable();   // patokan, misal seberang masjid
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 100)->nullable();  // keranjang tamu sebelum masuk
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->boolean('selected')->default(true);     // dicentang untuk checkout
            $table->timestamps();
            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('type', 20)->default('percent'); // percent | amount
            $table->integer('value');
            $table->integer('max_discount')->nullable();
            $table->integer('min_purchase')->default(0);
            $table->integer('quota')->default(100);
            $table->integer('used')->default(0);
            $table->date('expired_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('courier', 40);
            $table->string('service', 60);
            $table->integer('cost');
            $table->integer('etd_min')->default(1);
            $table->integer('etd_max')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('addresses');
    }
};
