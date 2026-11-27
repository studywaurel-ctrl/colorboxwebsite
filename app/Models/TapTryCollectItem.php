<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TapTryCollectItem extends Model
{
    protected $table = 'tap_try_collect_items';

    protected $fillable = ['tap_try_collect_id', 'product_variant_id', 'quantity', 'purchased'];

    protected $casts = ['purchased' => 'boolean'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TapTryCollect::class, 'tap_try_collect_id');
    }

    public function varian(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
