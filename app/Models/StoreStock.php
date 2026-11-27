<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreStock extends Model
{
    protected $table = 'store_stocks';

    protected $fillable = ['store_id', 'product_variant_id', 'stock'];

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function varian(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
