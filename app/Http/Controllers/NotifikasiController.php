<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

/**
 * Notifikasi pelanggan: pesanan, retur, donasi, dan Tap Try and Collect.
 */
class NotifikasiController extends Controller
{
    /** Daftar notifikasi terbaru. */
    public function index(Request $request)
    {
        $daftarNotifikasi = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(15);

        $jumlahBelumDibaca = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return view('notifikasi.index', [
            'daftarNotifikasi' => $daftarNotifikasi,
            'jumlahBelumDibaca' => $jumlahBelumDibaca,
        ]);
    }

    /** Menandai seluruh notifikasi sudah dibaca. */
    public function bacaSemua(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->route('notifikasi.index')
            ->with('berhasil', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /** Membuka notifikasi: ditandai terbaca lalu diarahkan ke halaman terkait. */
    public function buka(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403, 'Notifikasi ini bukan milik Anda.');
        }

        if (! $notification->read_at) {
            $notification->read_at = now();
            $notification->save();
        }

        if ($notification->url) {
            return redirect()->to($notification->url);
        }

        return redirect()->route('akun.index');
    }
}
