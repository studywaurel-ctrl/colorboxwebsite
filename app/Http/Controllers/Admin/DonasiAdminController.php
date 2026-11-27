<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Notification;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Validasi donasi pakaian.
 *
 * Poin pelanggan baru ditambahkan setelah admin menyetujui donasi dan
 * mengisi jumlah pakaian yang benar-benar diterima staf toko.
 */
class DonasiAdminController extends Controller
{
    private array $statusBoleh = [
        'dijadwalkan', 'check_in', 'menunggu_validasi', 'disetujui', 'ditolak', 'dibatalkan',
    ];

    public function index(Request $request)
    {
        $status = $request->query('status');
        $tokoId = $request->query('toko');

        $kueri = Donation::with(['pengguna', 'toko', 'slot'])->orderByDesc('id');

        if ($status && in_array($status, $this->statusBoleh)) {
            $kueri->where('status', $status);
        }

        if ($tokoId) {
            $kueri->where('store_id', $tokoId);
        }

        return view('admin.donasi.index', [
            'daftar' => $kueri->paginate(20)->withQueryString(),
            'daftarStatus' => $this->statusBoleh,
            'daftarToko' => Store::where('has_donation', true)->orderBy('name')->get(),
            'status' => $status,
            'tokoId' => $tokoId,
        ]);
    }

    public function show(Donation $donation)
    {
        $donation->load(['pengguna', 'toko', 'slot']);

        return view('admin.donasi.show', [
            'donasi' => $donation,
            'poinPerPotong' => (int) setting('donation_points_per_item', 1),
        ]);
    }

    /** Menyetujui atau menolak donasi. */
    public function validasi(Request $request, Donation $donation)
    {
        $data = $request->validate([
            'keputusan' => ['required', 'in:setujui,tolak'],
            'received_items' => ['nullable', 'integer', 'min:0', 'max:500'],
            'condition_note' => ['nullable', 'string', 'max:191'],
            'reject_reason' => ['nullable', 'string', 'max:191'],
        ], [
            'keputusan.required' => 'Pilih setujui atau tolak.',
            'received_items.integer' => 'Jumlah pakaian yang diterima harus berupa angka.',
        ]);

        if (in_array($donation->status, ['disetujui', 'ditolak', 'dibatalkan'])) {
            return back()->with('gagal', 'Donasi ini sudah divalidasi sebelumnya.');
        }

        $admin = auth()->user();

        if ($data['keputusan'] === 'tolak') {
            if (empty($data['reject_reason'])) {
                return back()->with('gagal', 'Tuliskan alasan penolakan donasi.');
            }

            $donation->update([
                'status' => 'ditolak',
                'reject_reason' => $data['reject_reason'],
                'verified_by' => $admin->name,
                'verified_at' => now(),
            ]);

            Notification::create([
                'user_id' => $donation->user_id,
                'title' => 'Donasi '.$donation->donation_code.' ditolak',
                'body' => $data['reject_reason'],
                'url' => route('donasi.show', $donation->id),
            ]);

            return redirect()->route('admin.donasi.show', $donation)
                ->with('berhasil', 'Donasi '.$donation->donation_code.' ditandai ditolak.');
        }

        // Keputusan setujui: jumlah pakaian yang diterima wajib diisi
        if (! isset($data['received_items']) || (int) $data['received_items'] < 1) {
            return back()->with('gagal', 'Isi jumlah pakaian yang diterima (paling sedikit 1) sebelum menyetujui donasi.');
        }

        $diterima = (int) $data['received_items'];
        $poin = $diterima * (int) setting('donation_points_per_item', 1);

        DB::transaction(function () use ($donation, $diterima, $poin, $data, $admin) {
            $donation->update([
                'status' => 'disetujui',
                'received_items' => $diterima,
                'condition_note' => $data['condition_note'] ?? $donation->condition_note,
                'points_awarded' => $poin,
                'verified_by' => $admin->name,
                'verified_at' => now(),
            ]);

            if ($poin > 0 && $donation->pengguna) {
                $donation->pengguna->ubahPoin($poin, 'donasi',
                    'Donasi '.$donation->donation_code.' ('.$diterima.' potong pakaian)');
            }

            Notification::create([
                'user_id' => $donation->user_id,
                'title' => 'Donasi '.$donation->donation_code.' disetujui',
                'body' => 'Terima kasih! '.$diterima.' potong pakaian diterima dan Anda mendapat '.$poin.' poin.',
                'url' => route('donasi.show', $donation->id),
            ]);
        });

        return redirect()->route('admin.donasi.show', $donation)
            ->with('berhasil', 'Donasi '.$donation->donation_code.' disetujui, pelanggan mendapat '.$poin.' poin.');
    }
}
