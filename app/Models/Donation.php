<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    protected $table = 'donations';

    protected $fillable = ['donation_code', 'user_id', 'donation_slot_id', 'store_id', 'estimated_items', 'received_items', 'condition_note', 'status', 'points_awarded', 'verified_by', 'checked_in_at', 'verified_at', 'reject_reason'];

    protected $casts = ['checked_in_at' => 'datetime', 'verified_at' => 'datetime'];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(DonationSlot::class, 'donation_slot_id');
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
