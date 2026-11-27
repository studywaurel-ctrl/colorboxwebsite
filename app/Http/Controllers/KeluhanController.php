<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Keluhan pelanggan. Jawaban admin ikut ditampilkan pada halaman yang sama.
 */
class KeluhanController extends Controller
{
    /** Daftar keluhan milik pengguna beserta formulir keluhan baru. */
    public function index(Request $request)
    {
        $daftarKeluhan = Complaint::with('pesanan')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        // Pilihan pesanan terkait, boleh dikosongkan bila keluhannya umum
        $daftarPesanan = Order::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->take(20)
            ->get();

        return view('keluhan.index', [
            'daftarKeluhan' => $daftarKeluhan,
            'daftarPesanan' => $daftarPesanan,
        ]);
    }

    /** Menyimpan keluhan baru dengan status awal "baru". */
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['nullable', 'integer'],
            'subject' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'subject.required' => 'Judul keluhan wajib diisi.',
            'message.required' => 'Isi keluhan wajib diisi.',
        ]);

        // Pesanan yang dipilih harus benar-benar milik pengguna yang masuk
        $idPesanan = null;
        if (! empty($data['order_id'])) {
            $pesanan = Order::where('id', $data['order_id'])
                ->where('user_id', $request->user()->id)
                ->first();

            if (! $pesanan) {
                return back()->withInput()->with('gagal', 'Pesanan yang dipilih tidak ditemukan.');
            }

            $idPesanan = $pesanan->id;
        }

        Complaint::create([
            'user_id' => $request->user()->id,
            'order_id' => $idPesanan,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'baru',
        ]);

        return redirect()->route('keluhan.index')
            ->with('berhasil', 'Keluhan sudah dikirim. Tim kami akan menjawab secepatnya.');
    }
}
