<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $table = 'stores';

    protected $fillable = ['code', 'name', 'city', 'address', 'floor', 'phone', 'latitude', 'longitude', 'open_time', 'close_time', 'has_ttc', 'has_donation', 'is_active'];

    protected $casts = ['has_ttc' => 'boolean', 'has_donation' => 'boolean', 'is_active' => 'boolean'];

    public function stok(): HasMany
    {
        return $this->hasMany(StoreStock::class, 'store_id');
    }

    public function slotDonasi(): HasMany
    {
        return $this->hasMany(DonationSlot::class, 'store_id');
    }

    public function slotTtc(): HasMany
    {
        return $this->hasMany(TapTryCollectSlot::class, 'store_id');
    }
}
