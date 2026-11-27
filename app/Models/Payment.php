<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = ['order_id', 'payment_method', 'transaction_id', 'snap_token', 'snap_url', 'gross_amount', 'transaction_status', 'payment_status', 'raw_response', 'paid_at', 'expired_at'];

    protected $casts = ['paid_at' => 'datetime', 'expired_at' => 'datetime'];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /** Mengubah status Midtrans menjadi status Colorbox. */
    public static function petaStatus(string $status): string
    {
        return match ($status) {
            'settlement', 'capture' => 'lunas',
            'pending' => 'menunggu',
            'expire' => 'kedaluwarsa',
            'cancel' => 'dibatalkan',
            'deny', 'failure' => 'gagal',
            default => 'menunggu',
        };
    }
}
