<?php

/**
 * Pengaturan teknis. Aturan bisnis yang sering diubah (batas ongkir gratis,
 * poin per potong donasi, batas waktu retur) disimpan di tabel settings
 * supaya dapat diubah lewat phpMyAdmin atau dashboard admin.
 */
return [
    // Akun contoh dibuat seeder, kata sandinya diambil dari .env
    'demo' => [
        'password_pelanggan' => env('COLORBOX_DEMO_PASSWORD'),
        'password_admin' => env('COLORBOX_ADMIN_PASSWORD'),
        'pin_staf' => env('COLORBOX_STAFF_PIN'),
    ],

    // Jam layanan bawaan ketika seeder atau admin membuat slot
    'slot' => [
        // Tap Try and Collect melayani pukul 10.00 sampai 21.00
        'jam_ttc' => ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00',
            '16:00', '17:00', '18:00', '19:00', '20:00'],
        'jam_donasi' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        'kuota_ttc' => 4,
        'kuota_donasi' => 5,
        'hari_ke_depan' => 14,
        // produk disiapkan lebih dulu, jadi jadwal paling cepat 30 menit dari sekarang
        'siap_menit_ttc' => 30,
    ],

    'donasi' => [
        'min_item' => 1,
        'max_item' => 50,
    ],

    'try_on' => [
        // Layanan dipakai hanya bila alamatnya diisi di .env, selain itu hasil
        // disusun di peramban (mode preview)
        'mode' => env('TRYON_API_URL') ? 'api' : 'preview',
        'api_url' => env('TRYON_API_URL'),
        'api_key' => env('TRYON_API_KEY'),
        'timeout' => 60,
        'max_upload_kb' => 8192,
    ],
];
