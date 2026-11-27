<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'vouchers';

    protected $fillable = ['code', 'name', 'type', 'value', 'max_discount', 'min_purchase', 'quota', 'used', 'expired_at', 'is_active'];

    protected $casts = ['expired_at' => 'date', 'is_active' => 'boolean'];
}
