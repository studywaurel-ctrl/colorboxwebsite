<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualTryOn extends Model
{
    protected $table = 'virtual_try_ons';

    protected $fillable = ['user_id', 'product_id', 'color', 'size', 'photo_path', 'result_path', 'status', 'engine', 'note'];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
