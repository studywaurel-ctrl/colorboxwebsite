<?php

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use Illuminate\Http\Request;

/**
 * Poin donasi milik pelanggan beserta riwayat masuk dan keluarnya.
 */
class PoinController extends Controller
{
    /** Saldo poin, riwayat, dan penjelasan cara memperoleh serta menukarnya. */
    public function index(Request $request)
    {
        $pengguna = $request->user();

        $riwayatPoin = PointTransaction::where('user_id', $pengguna->id)
            ->orderByDesc('id')
            ->paginate(15);

        // Aturan bisnis dibaca dari tabel settings supaya mudah diubah admin
        $nilaiPoin = (int) setting('point_value', 100);
        $batasPersen = (int) setting('points_max_percent', 30);
        $poinPerPotong = (int) setting('donation_points_per_item', 1);

        return view('poin.index', [
            'pengguna' => $pengguna,
            'riwayatPoin' => $riwayatPoin,
            'nilaiPoin' => $nilaiPoin,
            'batasPersen' => $batasPersen,
            'poinPerPotong' => $poinPerPotong,
        ]);
    }
}
