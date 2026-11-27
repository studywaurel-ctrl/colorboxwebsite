<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = ['order_code', 'user_id', 'address_id', 'store_id', 'method', 'voucher_id', 'subtotal', 'shipping_cost', 'discount', 'points_used', 'total', 'status', 'note', 'paid_at', 'completed_at', 'cancelled_at'];

    protected $casts = ['paid_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function alamat(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function item(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Payment::class, 'order_id');
    }

    public function pengiriman(): HasOne
    {
        return $this->hasOne(Shipment::class, 'order_id');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(TrackingEvent::class, 'order_id');
    }

    public function retur(): HasMany
    {
        return $this->hasMany(ReturnRequest::class, 'order_id');
    }

    /** Daftar status yang boleh dibatalkan pelanggan. */
    public function bisaDibatalkan(): bool
    {
        return in_array($this->status, ['menunggu_pembayaran', 'dibayar', 'diproses']);
    }

    /** Tulisan status untuk ditampilkan. */
    public function labelStatus(): string
    {
        return match ($this->status) {
            'menunggu_pembayaran' => 'Menunggu Pembayaran',
            'dibayar' => 'Pembayaran Diterima',
            'diproses' => 'Pesanan Diproses',
            'dikemas' => 'Pesanan Dikemas',
            'dikirim' => 'Dikirim Kurir',
            'siap_diambil' => 'Siap Diambil di Toko',
            'diterima' => 'Diterima Pelanggan',
            'selesai' => 'Pesanan Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getRouteKeyName(): string
    {
        return 'order_code';
    }
}
