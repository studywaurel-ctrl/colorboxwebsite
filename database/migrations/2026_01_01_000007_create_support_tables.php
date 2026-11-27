<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Poin pelanggan, notifikasi, dan percobaan Virtual Try On.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);              // masuk | keluar
            $table->string('source', 30);            // donasi | pesanan | retur | manual
            $table->integer('amount');
            $table->string('note', 191)->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('body', 255);
            $table->string('url', 191)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('virtual_try_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('color', 40)->nullable();
            $table->string('size', 10)->nullable();
            $table->string('photo_path', 191)->nullable();   // foto/tangkapan kamera pelanggan
            $table->string('result_path', 191)->nullable();  // hasil bila layanan try-on tersedia
            // dibuat | diproses | selesai | gagal
            $table->string('status', 20)->default('dibuat');
            $table->string('engine', 20)->default('preview'); // preview | api
            $table->string('note', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_try_ons');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('point_transactions');
    }
};
