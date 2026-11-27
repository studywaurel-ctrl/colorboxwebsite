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
            // Satu percobaan bisa memakai beberapa produk sekaligus (Mix and Match),
            // jadi produknya disimpan di virtual_try_on_items.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('color', 40)->nullable();
            $table->string('size', 10)->nullable();
            $table->string('look_name', 80)->nullable();      // nama gaya bila disimpan pelanggan
            $table->boolean('saved')->default(false);         // masuk halaman My Looks
            $table->string('photo_path', 191)->nullable();    // foto atau tangkapan kamera pelanggan
            $table->string('result_path', 191)->nullable();   // gambar hasil dari layanan AI
            // DRAFT | UPLOADING | PROCESSING | SUCCESS | FAILED
            $table->string('status', 20)->default('DRAFT');
            $table->string('driver', 20)->nullable();         // replicate | fal
            // Mix and Match dikerjakan bertahap: satu produk satu kali proses,
            // hasil tahap sebelumnya menjadi foto masukan tahap berikutnya.
            $table->integer('step')->default(0);
            $table->string('external_id', 100)->nullable();   // id pekerjaan di layanan AI
            $table->string('error_message', 191)->nullable(); // sebab gagal, ditulis apa adanya
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('virtual_try_on_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_try_on_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Atasan | Bawahan | Terusan | Luaran | Sepatu | Aksesori
            $table->string('category_slot', 30);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_try_on_items');
        Schema::dropIfExists('virtual_try_ons');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('point_transactions');
    }
};
