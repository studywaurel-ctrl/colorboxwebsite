<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonationSlot extends Model
{
    protected $table = 'donation_slots';

    protected $fillable = ['store_id', 'date', 'start_time', 'end_time', 'quota', 'booked_count', 'status'];

    protected $casts = ['date' => 'date'];

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function donasi(): HasMany
    {
        return $this->hasMany(Donation::class, 'donation_slot_id');
    }

    /** Sisa kuota slot donasi. */
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
