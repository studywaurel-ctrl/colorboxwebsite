<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['key', 'value', 'description'];

    /** Isi pengaturan yang sudah dibaca, supaya tidak berulang kali query. */
    protected static array $simpanan = [];

    public static function ambil(string $kunci, $bawaan = null)
    {
        if (array_key_exists($kunci, static::$simpanan)) {
            return static::$simpanan[$kunci];
        }

        if (! Schema::hasTable('settings')) {
            return $bawaan;
        }

        $baris = static::where('key', $kunci)->first();
        $nilai = $baris ? $baris->value : $bawaan;
        if (is_numeric($nilai)) {
            $nilai = $nilai + 0;
        }

        return static::$simpanan[$kunci] = $nilai;
    }

    public static function simpan(string $kunci, $nilai): void
    {
        static::updateOrCreate(['key' => $kunci], ['value' => (string) $nilai]);
        static::$simpanan[$kunci] = is_numeric($nilai) ? $nilai + 0 : $nilai;
    }
}
