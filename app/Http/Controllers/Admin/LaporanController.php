<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Laporan penjualan sederhana dengan tapis rentang tanggal.
 *
 * Yang dihitung hanya pesanan yang uangnya sudah masuk. Pesanan yang masih
 * menunggu pembayaran dan pesanan yang dibatalkan tidak ikut dihitung.
 */
class LaporanController extends Controller
{
    private array $statusDibayar = [
        'dibayar', 'diproses', 'dikemas', 'dikirim', 'siap_diambil', 'diterima', 'selesai',
    ];

    public function index(Request $request)
    {
        // Bawaannya bulan berjalan
        $dari = $request->query('dari') ?: Carbon::today()->startOfMonth()->toDateString();
        $sampai = $request->query('sampai') ?: Carbon::today()->endOfMonth()->toDateString();

        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ], [
            'dari.date' => 'Tanggal mulai tidak terbaca.',
            'sampai.date' => 'Tanggal akhir tidak terbaca.',
        ]);

        $mulai = Carbon::parse($dari)->startOfDay();
        $akhir = Carbon::parse($sampai)->endOfDay();

        if ($akhir->lt($mulai)) {
            $akhir = $mulai->copy()->endOfDay();
            $sampai = $dari;
        }

        $pesanan = Order::whereIn('status', $this->statusDibayar)
            ->whereBetween('created_at', [$mulai, $akhir]);

        $jumlahPesanan = (clone $pesanan)->count();
        $totalPenjualan = (int) (clone $pesanan)->sum('total');
        $rataRata = $jumlahPesanan > 0 ? (int) round($totalPenjualan / $jumlahPesanan) : 0;

        $idPesanan = (clone $pesanan)->pluck('id');

        $produkTerjual = (int) OrderItem::whereIn('order_id', $idPesanan)->sum('quantity');

        // Penjualan per hari
        $perHari = Order::selectRaw('DATE(created_at) as tanggal, COUNT(*) as jumlah, SUM(total) as nilai')
            ->whereIn('status', $this->statusDibayar)
            ->whereBetween('created_at', [$mulai, $akhir])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('tanggal')
            ->get();

        // Sepuluh produk terlaris
        $terlaris = OrderItem::selectRaw('product_name, SUM(quantity) as jumlah, SUM(subtotal) as nilai')
            ->whereIn('order_id', $idPesanan)
            ->groupBy('product_name')
            ->orderByDesc('jumlah')
            ->limit(10)
            ->get();

        // Pembagian metode penerimaan barang
        $perMetode = Order::selectRaw('method, COUNT(*) as jumlah, SUM(total) as nilai')
            ->whereIn('status', $this->statusDibayar)
            ->whereBetween('created_at', [$mulai, $akhir])
            ->groupBy('method')
            ->get();

        // Tombol "Unduh CSV" memakai method yang sama dengan tambahan ?format=csv
        if ($request->query('format') === 'csv') {
            return $this->unduhCsv($dari, $sampai, [
                'jumlahPesanan' => $jumlahPesanan,
                'produkTerjual' => $produkTerjual,
                'totalPenjualan' => $totalPenjualan,
                'rataRata' => $rataRata,
            ], $perHari, $terlaris, $perMetode);
        }

        return view('admin.laporan.index', [
            'dari' => $dari,
            'sampai' => $sampai,
            'jumlahPesanan' => $jumlahPesanan,
            'produkTerjual' => $produkTerjual,
            'totalPenjualan' => $totalPenjualan,
            'rataRata' => $rataRata,
            'perHari' => $perHari,
            'terlaris' => $terlaris,
            'perMetode' => $perMetode,
        ]);
    }

    /** Mengirim laporan yang sama dalam bentuk berkas CSV. */
    private function unduhCsv(string $dari, string $sampai, array $ringkasan, $perHari, $terlaris, $perMetode)
    {
        $namaBerkas = 'laporan-penjualan-'.$dari.'-sampai-'.$sampai.'.csv';

        return response()->streamDownload(function () use ($dari, $sampai, $ringkasan, $perHari, $terlaris, $perMetode) {
            $keluaran = fopen('php://output', 'w');

            fputcsv($keluaran, ['Laporan Penjualan Colorbox']);
            fputcsv($keluaran, ['Periode', $dari, 'sampai', $sampai]);
            fputcsv($keluaran, []);

            fputcsv($keluaran, ['Ringkasan']);
            fputcsv($keluaran, ['Jumlah pesanan', $ringkasan['jumlahPesanan']]);
            fputcsv($keluaran, ['Produk terjual (potong)', $ringkasan['produkTerjual']]);
            fputcsv($keluaran, ['Total penjualan (Rp)', $ringkasan['totalPenjualan']]);
            fputcsv($keluaran, ['Rata-rata nilai pesanan (Rp)', $ringkasan['rataRata']]);
            fputcsv($keluaran, []);

            fputcsv($keluaran, ['Penjualan per hari']);
            fputcsv($keluaran, ['Tanggal', 'Jumlah pesanan', 'Nilai (Rp)']);
            foreach ($perHari as $baris) {
                fputcsv($keluaran, [$baris->tanggal, $baris->jumlah, (int) $baris->nilai]);
            }
            fputcsv($keluaran, []);

            fputcsv($keluaran, ['Sepuluh produk terlaris']);
            fputcsv($keluaran, ['Produk', 'Jumlah terjual', 'Nilai (Rp)']);
            foreach ($terlaris as $baris) {
                fputcsv($keluaran, [$baris->product_name, $baris->jumlah, (int) $baris->nilai]);
            }
            fputcsv($keluaran, []);

            fputcsv($keluaran, ['Metode penerimaan barang']);
            fputcsv($keluaran, ['Metode', 'Jumlah pesanan', 'Nilai (Rp)']);
            foreach ($perMetode as $baris) {
                $nama = $baris->method === 'ambil_toko' ? 'Ambil di toko' : 'Dikirim kurir';
                fputcsv($keluaran, [$nama, $baris->jumlah, (int) $baris->nilai]);
            }

            fclose($keluaran);
        }, $namaBerkas, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
