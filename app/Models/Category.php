<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = ['parent_id', 'name', 'slug', 'sort_order'];

    public function induk(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function produk(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
