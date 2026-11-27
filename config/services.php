<?php

return [
    // Tempat menaruh kunci layanan luar, misalnya penyedia pembuatan
    // gambar Virtual Try On. Bila kosong, sistem memakai pratinjau bawaan.
    'vto' => [
        'kunci' => env('VTO_API_KEY'),
        'alamat' => env('VTO_API_URL'),
    ],
];
