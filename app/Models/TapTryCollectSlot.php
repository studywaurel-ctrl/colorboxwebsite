<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TapTryCollectSlot extends Model
{
    protected $table = 'tap_try_collect_slots';

    protected $fillable = ['store_id', 'date', 'start_time', 'end_time', 'quota', 'booked_count', 'status'];

    protected $casts = ['date' => 'date'];

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function booking(): HasMany
    {
        return $this->hasMany(TapTryCollect::class, 'tap_try_collect_slot_id');
    }

    /** Sisa kuota slot Tap Try and Collect. */
    public function sisaKuota(): int
    {
        return max(0, $this->quota - $this->booked_count);
    }

    /** Slot masih bisa dipesan? */
    public function tersedia(): bool
    {
        return $this->status === 'aktif' && $this->sisaKuota() > 0;
    }
}
