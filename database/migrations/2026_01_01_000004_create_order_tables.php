<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesanan, rincian pesanan, pembayaran Midtrans, pengiriman,
 * riwayat status pesanan, dan ulasan produk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 30)->unique();     // contoh: CBX-20260720-0001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 20)->default('kirim'); // kirim | ambil_toko
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('subtotal')->default(0);
            $table->integer('shipping_cost')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('points_used')->default(0);
            $table->integer('total')->default(0);
            // menunggu_pembayaran | dibayar | diproses | dikemas | dikirim | siap_diambil |
            // diterima | selesai | dibatalkan
            $table->string('status', 30)->default('menunggu_pembayaran');
            $table->string('note', 191)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 150);
            $table->string('color', 40)->nullable();
            $table->string('size', 10)->nullable();
            $table->string('image_path', 191)->nullable();
            $table->integer('price');
            $table->integer('quantity');
            $table->integer('subtotal');
            $table->boolean('reviewed')->default(false);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method', 40)->nullable();    // qris, bank_transfer, gopay, ...
            $table->string('transaction_id', 100)->nullable();   // transaction_id dari Midtrans
            $table->string('snap_token', 191)->nullable();
            $table->string('snap_url', 255)->nullable();
            $table->integer('gross_amount');
            // status asli dari Midtrans: pending, settlement, capture, expire, cancel, deny, failure
            $table->string('transaction_status', 30)->default('pending');
            // status yang dipakai Colorbox: menunggu | lunas | gagal | kedaluwarsa | dibatalkan
            $table->string('payment_status', 20)->default('menunggu');
            $table->text('raw_response')->nullable();            // simpanan JSON notifikasi terakhir
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('courier', 40)->nullable();
            $table->string('service', 60)->nullable();
            $table->string('tracking_number', 60)->nullable();
            $table->integer('cost')->default(0);
            $table->string('status', 30)->default('menunggu');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30);
            $table->string('description', 191);
            $table->timestamp('happened_at');
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('rating');                       // 1 sampai 5
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('tracking_events');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
