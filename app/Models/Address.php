<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $table = 'addresses';

    protected $fillable = ['user_id', 'label', 'recipient', 'phone', 'province', 'city', 'district', 'postal_code', 'detail', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
