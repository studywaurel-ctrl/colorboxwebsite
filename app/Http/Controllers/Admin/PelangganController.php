<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Daftar pelanggan beserta poin dan riwayat kegiatannya.
 * Halaman ini hanya untuk melihat, bukan untuk mengubah data pelanggan.
 */
class PelangganController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));

        $kueri = User::where('role', 'pelanggan')
            ->withCount('pesanan')
            ->orderByDesc('id');

        if ($cari !== '') {
            $kueri->where(function ($bagian) use ($cari) {
                $bagian->where('name', 'like', '%'.$cari.'%')
                    ->orWhere('email', 'like', '%'.$cari.'%')
                    ->orWhere('phone', 'like', '%'.$cari.'%');
            });
        }

        return view('admin.pelanggan.index', [
            'daftar' => $kueri->paginate(20)->withQueryString(),
            'cari' => $cari,
        ]);
    }

    public function show(User $user)
    {
        return view('admin.pelanggan.show', [
            'pelanggan' => $user,
            'alamat' => $user->alamat()->orderByDesc('is_primary')->get(),
            'pesanan' => $user->pesanan()->orderByDesc('id')->limit(20)->get(),
            'donasi' => $user->donasi()->with('toko')->orderByDesc('id')->limit(10)->get(),
            'retur' => $user->retur()->orderByDesc('id')->limit(10)->get(),
            'riwayatPoin' => $user->riwayatPoin()->orderByDesc('id')->limit(20)->get(),
        ]);
    }
}
