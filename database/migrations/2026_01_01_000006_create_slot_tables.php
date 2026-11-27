<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal layanan di toko. Donasi dan Tap Try and Collect memakai tabel slot
 * yang benar-benar terpisah: kuota, jumlah terisi, dan validasinya sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------- Donasi ----------------
        Schema::create('donation_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('quota')->default(5);
            $table->integer('booked_count')->default(0);
            $table->string('status', 20)->default('aktif');     // aktif | nonaktif
            $table->timestamps();
            $table->unique(['store_id', 'date', 'start_time'], 'slot_donasi_unik');
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('donation_code', 30)->unique();      // contoh: DON-20260720-0001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donation_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->integer('estimated_items');                 // perkiraan jumlah pakaian
            $table->integer('received_items')->nullable();      // jumlah yang diterima staf
            $table->string('condition_note', 191)->nullable();
            // dijadwalkan | check_in | menunggu_validasi | disetujui | ditolak | dibatalkan
            $table->string('status', 30)->default('dijadwalkan');
            $table->integer('points_awarded')->default(0);
            $table->string('verified_by', 100)->nullable();     // nama staf toko
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('reject_reason', 191)->nullable();
            $table->timestamps();
        });

        // ---------------- Tap Try and Collect ----------------
        Schema::create('tap_try_collect_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('quota')->default(4);
            $table->integer('booked_count')->default(0);
            $table->string('status', 20)->default('aktif');     // aktif | nonaktif
            $table->timestamps();
            $table->unique(['store_id', 'date', 'start_time'], 'slot_ttc_unik');
        });

        Schema::create('tap_try_collects', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 30)->unique();       // contoh: TTC-20260720-0001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tap_try_collect_slot_id')->constrained('tap_try_collect_slots')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            // dijadwalkan | siap | selesai | dibatalkan | kedaluwarsa
            $table->string('status', 30)->default('dijadwalkan');
            $table->string('purchase_code', 40)->nullable();    // kode pembelian bila jadi dibeli
            $table->integer('total_paid')->default(0);
            $table->string('verified_by', 100)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tap_try_collect_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tap_try_collect_id')->constrained('tap_try_collects')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->boolean('purchased')->default(false);       // dicentang kasir saat dibeli
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tap_try_collect_items');
        Schema::dropIfExists('tap_try_collects');
        Schema::dropIfExists('tap_try_collect_slots');
        Schema::dropIfExists('donations');
        Schema::dropIfExists('donation_slots');
    }
};
