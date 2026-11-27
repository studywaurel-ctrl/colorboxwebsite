<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'birth_date', 'gender',
        'photo', 'role', 'store_id', 'points', 'member_level',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'password' => 'hashed',
    ];

    public function alamat(): HasMany
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    public function alamatUtama(): ?Address
    {
        return $this->alamat()->orderByDesc('is_primary')->orderBy('id')->first();
    }

    public function keranjang(): HasMany
    {
        return $this->hasMany(Cart::class, 'user_id');
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'user_id');
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function retur(): HasMany
    {
        return $this->hasMany(ReturnRequest::class, 'user_id');
    }

    public function donasi(): HasMany
    {
        return $this->hasMany(Donation::class, 'user_id');
    }

    public function tapTryCollect(): HasMany
    {
        return $this->hasMany(TapTryCollect::class, 'user_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function riwayatPoin(): HasMany
    {
        return $this->hasMany(PointTransaction::class, 'user_id');
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function adalahAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Menambah atau mengurangi poin sekaligus mencatat riwayatnya. */
    public function ubahPoin(int $jumlah, string $sumber, string $catatan = ''): void
    {
        $this->points = max(0, $this->points + $jumlah);
        $this->save();

        PointTransaction::create([
            'user_id' => $this->id,
            'type' => $jumlah >= 0 ? 'masuk' : 'keluar',
            'source' => $sumber,
            'amount' => abs($jumlah),
            'note' => $catatan,
        ]);
    }
}
