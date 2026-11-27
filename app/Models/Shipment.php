<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $table = 'shipments';

    protected $fillable = ['order_id', 'courier', 'service', 'tracking_number', 'cost', 'status', 'shipped_at', 'delivered_at'];

    protected $casts = ['shipped_at' => 'datetime', 'delivered_at' => 'datetime'];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
