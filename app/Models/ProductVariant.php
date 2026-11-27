<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = ['product_id', 'color', 'color_hex', 'size', 'sku', 'stock'];

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stokToko(): HasMany
    {
        return $this->hasMany(StoreStock::class, 'product_variant_id');
    }

    /** Nama varian singkat, contoh: OFF WHITE / M */
    public function label(): string
    {
        return $this->color.' / '.$this->size;
    }
}
