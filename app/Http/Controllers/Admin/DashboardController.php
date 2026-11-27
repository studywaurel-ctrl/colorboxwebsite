<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Donation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\TapTryCollect;
use Illuminate\Support\Carbon;

/**
 * Halaman muka dashboard admin.
 *
 * Isinya hanya angka ringkasan dan dua tabel pendek supaya admin langsung
 * tahu pekerjaan apa yang menunggu hari ini.
 */
class DashboardController extends Controller
{
    /** Daftar status pesanan yang uangnya sudah masuk. */
    private array $statusDibayar = [
        'dibayar', 'diproses', 'dikemas', 'dikirim', 'siap_diambil', 'diterima', 'selesai',
    ];

    public function index()
    {
        $hariIni = Carbon::today();
        $awalBulan = Carbon::today()->startOfMonth();
        $akhirBulan = Carbon::today()->endOfMonth();

        // Batas stok menipis ditulis sekali supaya angka di kartu dan di tabel sama
        $batasStok = 5;

        $ringkasan = [
            'pesanan_hari_ini' => Order::whereDate('created_at', $hariIni)->count(),
            'menunggu_pembayaran' => Order::where('status', 'menunggu_pembayaran')->count(),
            'perlu_diproses' => Order::whereIn('status', ['dibayar', 'diproses'])->count(),
            'penjualan_bulan' => (int) Order::whereIn('status', $this->statusDibayar)
                ->whereBetween('created_at', [$awalBulan, $akhirBulan])
                ->sum('total'),
            'produk_aktif' => Product::where('is_active', true)->count(),
            'stok_menipis' => ProductVariant::where('stock', '<', $batasStok)->count(),
            'retur_menunggu' => ReturnRequest::where('status', 'menunggu_validasi')->count(),
            'donasi_menunggu' => Donation::where('status', 'menunggu_validasi')->count(),
            'ttc_hari_ini' => TapTryCollect::whereHas('slot', function ($kueri) use ($hariIni) {
                $kueri->whereDate('date', $hariIni);
            })->count(),
            'keluhan_baru' => Complaint::where('status', 'baru')->count(),
        ];

        $pesananTerbaru = Order::with('pengguna')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $varianMenipis = ProductVariant::with('produk')
            ->where('stock', '<', $batasStok)
            ->orderBy('stock')
            ->orderBy('id')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'ringkasan' => $ringkasan,
            'pesananTerbaru' => $pesananTerbaru,
            'varianMenipis' => $varianMenipis,
            'batasStok' => $batasStok,
            'bulan' => $awalBulan,
        ]);
    }
}
