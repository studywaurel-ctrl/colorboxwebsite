<?php

use Illuminate\Support\Facades\Schedule;

// Opsional: jalankan "php artisan schedule:work" di terminal kedua supaya
// status pesanan dan slot ikut diperbarui otomatis. Tanpa itu pun status
// tetap diperbarui setiap halaman pesanan dibuka.
Schedule::command('colorbox:perbarui')->everyMinute()->withoutOverlapping();
