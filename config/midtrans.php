<?php

$produksi = (bool) env('MIDTRANS_IS_PRODUCTION', false);

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'is_production' => $produksi,

    // Alamat Snap. Selama pengembangan dipakai sandbox.
    'snap_url' => $produksi
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
    'snap_js' => $produksi
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js',
    'api_url' => $produksi
        ? 'https://api.midtrans.com/v2'
        : 'https://api.sandbox.midtrans.com/v2',

    // Batas waktu pembayaran dalam jam
    'expiry_hours' => 24,
];
