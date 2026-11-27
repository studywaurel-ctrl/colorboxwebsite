<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TapTryCollect extends Model
{
    protected $table = 'tap_try_collects';

    protected $fillable = ['booking_code', 'user_id', 'tap_try_collect_slot_id', 'store_id', 'status', 'purchase_code', 'total_paid', 'verified_by', 'completed_at'];

    protected $casts = ['completed_at' => 'datetime'];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(TapTryCollectSlot::class, 'tap_try_collect_slot_id');
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function item(): HasMany
    {
        return $this->hasMany(TapTryCollectItem::class, 'tap_try_collect_id');
    }
}
