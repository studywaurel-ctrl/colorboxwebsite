<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = ['category_id', 'name', 'slug', 'sku', 'price', 'discount_price', 'description', 'material', 'care', 'collection', 'is_new', 'is_active', 'weight'];

    protected $casts = ['is_new' => 'boolean', 'is_active' => 'boolean'];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function varian(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function gambar(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function ulasan(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    /** Harga yang dibayar pelanggan (memakai harga diskon bila ada). */
    public function hargaJual(): int
    {
        return (int) ($this->discount_price ?: $this->price);
    }

    /** Gambar utama produk. */
    public function gambarUtama(): ?ProductImage
    {
        return $this->gambar->sortBy('sort_order')->first();
    }

    /** Total stok online seluruh varian. */
    public function totalStok(): int
    {
        return (int) $this->varian->sum('stock');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
