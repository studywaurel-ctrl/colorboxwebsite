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
            // Pakaian yang sudah dibawa ke toko tidak pernah dikembalikan. Kasir
            // hanya memisahkan mana yang layak pakai dan mana yang tidak.
            $table->integer('accepted_items')->nullable();      // jumlah potong layak pakai
            $table->integer('rejected_items')->nullable();      // jumlah potong tidak layak
            $table->string('condition_note', 191)->nullable();  // catatan pelanggan
            $table->string('verify_note', 191)->nullable();     // catatan kasir
            // SCHEDULED | CHECKED_IN | VERIFIED | COMPLETED | NO_SHOW | CANCELLED
            $table->string('status', 30)->default('SCHEDULED');
            $table->integer('points_awarded')->default(0);
            // kasir toko yang memverifikasi, bukan admin
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('verified_at')->nullable();
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
            // Booking yang lahir dari checkout Ambil di Toko ikut dicatat di sini,
            // supaya antrean kasir hanya ada satu daftar.
            $table->string('source', 20)->default('TTC');       // TTC | STORE_PICKUP
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            // SCHEDULED | READY | COLLECTED | NOT_PICKED_UP | CANCELLED
            $table->string('status', 30)->default('SCHEDULED');
            $table->string('purchase_code', 40)->nullable();    // kode pembelian bila jadi dibeli
            $table->integer('total_paid')->default(0);
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ready_at')->nullable();          // saat kasir menandai barang siap
            $table->timestamp('collected_at')->nullable();      // saat pelanggan mengambil
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
