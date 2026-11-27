<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = ['order_id', 'product_variant_id', 'product_name', 'color', 'size', 'image_path', 'price', 'quantity', 'subtotal', 'reviewed'];

    protected $casts = ['reviewed' => 'boolean'];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function varian(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
