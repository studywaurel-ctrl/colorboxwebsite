<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Notification;
use Illuminate\Http\Request;

/**
 * Keluhan pelanggan beserta jawaban admin.
 */
class KeluhanAdminController extends Controller
{
    private array $statusBoleh = ['baru', 'diproses', 'selesai'];

    public function index(Request $request)
    {
        $status = $request->query('status');

        $kueri = Complaint::with(['pengguna', 'pesanan'])->orderByDesc('id');

        if ($status && in_array($status, $this->statusBoleh)) {
            $kueri->where('status', $status);
        }

        return view('admin.keluhan.index', [
            'daftar' => $kueri->paginate(20)->withQueryString(),
            'daftarStatus' => $this->statusBoleh,
            'status' => $status,
        ]);
    }

    public function show(Complaint $complaint)
    {
        $complaint->load(['pengguna', 'pesanan']);

        return view('admin.keluhan.show', ['keluhan' => $complaint]);
    }

    /** Menjawab keluhan lalu menandainya selesai. */
    public function jawab(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'response' => ['required', 'string', 'max:2000'],
        ], [
            'response.required' => 'Tuliskan jawaban untuk pelanggan.',
        ]);

        $complaint->update([
            'response' => $data['response'],
            'responded_at' => now(),
            'status' => 'selesai',
        ]);

        Notification::create([
            'user_id' => $complaint->user_id,
            'title' => 'Keluhan Anda sudah dijawab',
            'body' => 'Keluhan "'.$complaint->subject.'" sudah kami jawab. Silakan dibaca pada halaman keluhan.',
            'url' => route('keluhan.index'),
        ]);

        return redirect()->route('admin.keluhan.show', $complaint)
            ->with('berhasil', 'Jawaban sudah dikirim kepada pelanggan.');
    }
}
