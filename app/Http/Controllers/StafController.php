<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Notification;
use App\Models\ReturnRequest;
use App\Models\StoreStock;
use App\Models\TapTryCollect;
use App\Models\TapTryCollectItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Halaman verifikasi staf toko. Dibuka staf setelah memindai QR pada
 * layar pelanggan, jadi tidak memakai login. Pengamanannya memakai PIN
 * toko yang disimpan di config('colorbox.demo.pin_staf').
 */
class StafController extends Controller
{
    /** Mencari data yang akan diverifikasi sesuai jenis dan kodenya. */
    private function cari(string $jenis, string $kode)
    {
        if ($jenis === 'donasi') {
            return Donation::where('donation_code', $kode)
                ->with(['pengguna', 'toko', 'slot'])
                ->firstOrFail();
        }

        if ($jenis === 'ttc') {
            return TapTryCollect::where('booking_code', $kode)
                ->with(['pengguna', 'toko', 'slot', 'item.varian.produk'])
                ->firstOrFail();
        }

        return ReturnRequest::where('return_code', $kode)
            ->with(['pengguna', 'toko', 'pesanan', 'item.varian.produk'])
            ->firstOrFail();
    }

    /** Menampilkan rincian singkat dan formulir verifikasi. */
    public function form(string $jenis, string $kode)
    {
        return view('staf.form', [
            'jenis' => $jenis,
            'kode' => $kode,
            'data' => $this->cari($jenis, $kode),
            'poinPerPotong' => (int) setting('donation_points_per_item', 1),
        ]);
    }

    /** Menyimpan hasil pemeriksaan staf toko. */
    public function simpan(Request $request, string $jenis, string $kode)
    {
        $data = $this->cari($jenis, $kode);

        $aturan = [
            'nama_staf' => ['required', 'string', 'max:100'],
            'pin' => ['required', 'string', 'max:20'],
        ];

        if ($jenis === 'donasi') {
            $aturan['jumlah_diterima'] = ['required', 'integer', 'min:0', 'max:200'];
        } elseif ($jenis === 'ttc') {
            $aturan['dibeli'] = ['nullable', 'array'];
            $aturan['dibeli.*'] = ['integer'];
        } else {
            $aturan['keputusan'] = ['required', 'in:diproses,ditolak'];
            $aturan['catatan'] = ['nullable', 'string', 'max:150'];
        }

        $isian = $request->validate($aturan, [
            'nama_staf.required' => 'Nama staf wajib diisi.',
            'pin.required' => 'PIN toko wajib diisi.',
        ]);

        // Pemeriksaan PIN toko menggantikan login staf
        if ((string) $isian['pin'] !== (string) config('colorbox.demo.pin_staf')) {
            return back()->withInput()->with('gagal', 'PIN toko salah.');
        }

        if ($jenis === 'donasi') {
            return $this->simpanDonasi($data, $isian);
        }

        if ($jenis === 'ttc') {
            return $this->simpanTtc($data, $isian);
        }

        return $this->simpanRetur($data, $isian);
    }

    /** Staf mencatat jumlah pakaian donasi yang benar-benar diterima. */
    private function simpanDonasi(Donation $donasi, array $isian)
    {
        if (in_array($donasi->status, ['disetujui', 'ditolak', 'dibatalkan'])) {
            return back()->with('gagal', 'Donasi ini sudah diperiksa sebelumnya.');
        }

        $diterima = (int) $isian['jumlah_diterima'];
        $poinPerPotong = (int) setting('donation_points_per_item', 1);
        $poin = $diterima * $poinPerPotong;

        DB::transaction(function () use ($donasi, $isian, $diterima, $poin) {
            $donasi->status = $diterima > 0 ? 'disetujui' : 'ditolak';
            $donasi->received_items = $diterima;
            $donasi->points_awarded = $poin;
            $donasi->verified_by = $isian['nama_staf'];
            $donasi->verified_at = now();

            if ($diterima < 1) {
                $donasi->reject_reason = 'Pakaian tidak memenuhi syarat penerimaan.';
            }

            $donasi->save();

            $pelanggan = $donasi->pengguna;

            if ($pelanggan && $poin > 0) {
                $pelanggan->ubahPoin($poin, 'donasi', 'Donasi '.$donasi->donation_code.' sebanyak '.$diterima.' potong');
            }

            if ($pelanggan) {
                Notification::create([
                    'user_id' => $pelanggan->id,
                    'title' => $diterima > 0 ? 'Donasi diterima staf toko' : 'Donasi belum bisa diterima',
                    'body' => $diterima > 0
                        ? 'Donasi '.$donasi->donation_code.': '.$diterima.' potong diterima, '.$poin.' poin masuk ke akun Anda.'
                        : 'Donasi '.$donasi->donation_code.' belum bisa diterima karena kondisi pakaiannya.',
                    'url' => route('donasi.show', $donasi),
                ]);
            }
        });

        return back()->with('berhasil', $diterima > 0
            ? 'Donasi tercatat: '.$diterima.' potong, '.$poin.' poin diberikan.'
            : 'Donasi ditandai ditolak karena tidak ada pakaian yang diterima.');
    }

    /** Staf menandai produk yang jadi dibeli pelanggan di toko. */
    private function simpanTtc(TapTryCollect $booking, array $isian)
    {
        if (in_array($booking->status, ['selesai', 'dibatalkan', 'kedaluwarsa'])) {
            return back()->with('gagal', 'Jadwal ini sudah diperiksa sebelumnya.');
        }

        $dibeli = array_map('intval', $isian['dibeli'] ?? []);

        DB::transaction(function () use ($booking, $isian, $dibeli) {
            $total = 0;

            foreach ($booking->item as $item) {
                $jadiDibeli = in_array($item->id, $dibeli);

                TapTryCollectItem::where('id', $item->id)->update(['purchased' => $jadiDibeli]);

                if (! $jadiDibeli || ! $item->varian || ! $item->varian->produk) {
                    continue;
                }

                $total = $total + ($item->varian->produk->hargaJual() * (int) $item->quantity);

                // Stok toko dikurangi bila barisnya memang ada
                $stok = StoreStock::where('store_id', $booking->store_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->first();

                if ($stok && $stok->stock > 0) {
                    $stok->stock = max(0, $stok->stock - (int) $item->quantity);
                    $stok->save();
                }
            }

            $booking->status = 'selesai';
            $booking->verified_by = $isian['nama_staf'];
            $booking->completed_at = now();
            $booking->total_paid = $total;

            if ($total > 0) {
                // Kode pembelian dipakai pelanggan bila nanti ingin mengajukan retur di toko
                $awalan = 'TTC-BELI-'.now()->format('Ymd').'-';
                $urut = TapTryCollect::where('purchase_code', 'like', $awalan.'%')->count() + 1;
                $booking->purchase_code = $awalan.str_pad($urut, 4, '0', STR_PAD_LEFT);
            }

            $booking->save();

            if ($booking->pengguna) {
                Notification::create([
                    'user_id' => $booking->pengguna->id,
                    'title' => 'Kunjungan Tap Try and Collect selesai',
                    'body' => $booking->total_paid > 0
                        ? 'Pembelian '.rupiah($booking->total_paid).' tercatat dengan kode '.$booking->purchase_code.'.'
                        : 'Kunjungan '.$booking->booking_code.' selesai tanpa pembelian.',
                    'url' => route('ttc.show', $booking),
                ]);
            }
        });

        return back()->with('berhasil', 'Kunjungan '.$booking->booking_code.' ditandai selesai.');
    }

    /** Staf menerima atau menolak barang retur yang dibawa ke toko. */
    private function simpanRetur(ReturnRequest $retur, array $isian)
    {
        if (in_array($retur->status, ['selesai', 'ditolak', 'dibatalkan'])) {
            return back()->with('gagal', 'Pengajuan retur ini sudah selesai diperiksa.');
        }

        DB::transaction(function () use ($retur, $isian) {
            $retur->status = $isian['keputusan'];
            // Dipotong 191 huruf supaya muat pada kolom admin_note
            $catatan = trim(($isian['catatan'] ?? '').' (diperiksa '.$isian['nama_staf'].')');
            $retur->admin_note = mb_substr($catatan, 0, 191);

            if ($isian['keputusan'] === 'diproses') {
                $retur->approved_at = now();
            }

            $retur->save();

            if ($retur->pengguna) {
                Notification::create([
                    'user_id' => $retur->pengguna->id,
                    'title' => $isian['keputusan'] === 'diproses' ? 'Retur diterima di toko' : 'Retur ditolak di toko',
                    'body' => 'Pengajuan '.$retur->return_code.' '
                        .($isian['keputusan'] === 'diproses' ? 'sedang diproses staf toko.' : 'ditolak staf toko.'),
                    'url' => route('retur.show', $retur),
                ]);
            }
        });

        return back()->with('berhasil', 'Keputusan retur '.$retur->return_code.' tersimpan.');
    }
}
