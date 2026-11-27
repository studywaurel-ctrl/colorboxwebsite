<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengajuan retur produk beserta rinciannya, dan keluhan pelanggan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->string('return_code', 30)->unique();        // contoh: RTR-20260720-0001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purchase_code', 40)->nullable();    // kode pembelian di toko
            $table->string('type', 20)->default('tukar');       // tukar | refund
            $table->string('method', 20)->default('online');    // online | toko
            $table->string('reason', 191);
            $table->string('photo', 191)->nullable();
            // menunggu_validasi | disetujui | ditolak | dikirim | diperiksa | diproses |
            // selesai | dibatalkan
            $table->string('status', 30)->default('menunggu_validasi');
            $table->string('admin_note', 191)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 150);
            $table->integer('quantity')->default(1);
            $table->string('note', 191)->nullable();
            $table->timestamps();
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject', 120);
            $table->text('message');
            $table->string('status', 20)->default('baru');      // baru | diproses | selesai
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_requests');
    }
};
