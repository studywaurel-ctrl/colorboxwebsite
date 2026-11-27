<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingEvent extends Model
{
    protected $table = 'tracking_events';

    protected $fillable = ['order_id', 'status', 'description', 'happened_at'];

    protected $casts = ['happened_at' => 'datetime'];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
