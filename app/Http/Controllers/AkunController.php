<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\TapTryCollect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Halaman akun pelanggan: ringkasan, ubah profil, dan ganti kata sandi.
 */
class AkunController extends Controller
{
    /** Ringkasan akun beserta tautan ke seluruh halaman akun. */
    public function index(Request $request)
    {
        $pengguna = $request->user();

        // Tiga pesanan terbaru
        $pesananTerbaru = Order::with('item')
            ->where('user_id', $pengguna->id)
            ->orderByDesc('id')
            ->take(3)
            ->get();

        // Jumlah pesanan per tahap, dipakai untuk kotak ringkasan di halaman akun
        $jumlahBelumDibayar = Order::where('user_id', $pengguna->id)
            ->where('status', 'menunggu_pembayaran')
            ->count();

        $jumlahDiproses = Order::where('user_id', $pengguna->id)
            ->where('status', 'diproses')
            ->count();

        $jumlahDikirim = Order::where('user_id', $pengguna->id)
            ->where('status', 'dikirim')
            ->count();

        // Retur yang masih berjalan (belum selesai, ditolak, atau dibatalkan)
        $jumlahReturBerjalan = ReturnRequest::where('user_id', $pengguna->id)
            ->whereNotIn('status', ['selesai', 'ditolak', 'dibatalkan'])
            ->count();

        // Jadwal donasi terdekat yang masih akan datang
        $donasiTerdekat = Donation::with(['slot', 'toko'])
            ->where('user_id', $pengguna->id)
            ->whereIn('status', ['dijadwalkan', 'check_in', 'menunggu_validasi'])
            ->whereHas('slot', function ($kueri) {
                $kueri->whereDate('date', '>=', now()->toDateString());
            })
            ->get()
            ->sortBy(function ($donasi) {
                return $donasi->slot->date->format('Y-m-d').' '.$donasi->slot->start_time;
            })
            ->first();

        // Jadwal Tap Try and Collect terdekat yang masih akan datang
        $ttcTerdekat = TapTryCollect::with(['slot', 'toko'])
            ->where('user_id', $pengguna->id)
            ->whereIn('status', ['dijadwalkan', 'siap'])
            ->whereHas('slot', function ($kueri) {
                $kueri->whereDate('date', '>=', now()->toDateString());
            })
            ->get()
            ->sortBy(function ($booking) {
                return $booking->slot->date->format('Y-m-d').' '.$booking->slot->start_time;
            })
            ->first();

        $notifikasiBelumDibaca = $pengguna->notifikasi()->whereNull('read_at')->count();

        return view('akun.index', [
            'pengguna' => $pengguna,
            'pesananTerbaru' => $pesananTerbaru,
            'jumlahBelumDibayar' => $jumlahBelumDibayar,
            'jumlahDiproses' => $jumlahDiproses,
            'jumlahDikirim' => $jumlahDikirim,
            'jumlahReturBerjalan' => $jumlahReturBerjalan,
            'donasiTerdekat' => $donasiTerdekat,
            'ttcTerdekat' => $ttcTerdekat,
            'notifikasiBelumDibaca' => $notifikasiBelumDibaca,
        ]);
    }

    /** Formulir ubah profil dan ganti kata sandi. */
    public function formProfil(Request $request)
    {
        return view('akun.profil', ['pengguna' => $request->user()]);
    }

    /** Menyimpan perubahan profil, termasuk foto bila diunggah. */
    public function simpanProfil(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:perempuan,laki-laki'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'photo.image' => 'Berkas foto harus berupa gambar.',
            'photo.max' => 'Ukuran foto paling besar 2 MB.',
        ]);

        $pengguna = $request->user();
        $pengguna->name = $data['name'];
        $pengguna->phone = $data['phone'] ?? null;
        $pengguna->birth_date = $data['birth_date'] ?? null;
        $pengguna->gender = $data['gender'] ?? null;

        if ($request->hasFile('photo')) {
            // Foto disimpan di public/images/profil, yang dicatat hanya path relatifnya
            $namaBerkas = 'profil-'.$pengguna->id.'-'.time().'.'.$request->file('photo')->getClientOriginalExtension();
            $request->file('photo')->move(public_path('images/profil'), $namaBerkas);
            $pengguna->photo = 'images/profil/'.$namaBerkas;
        }

        $pengguna->save();

        return redirect()->route('profil.edit')->with('berhasil', 'Profil berhasil diperbarui.');
    }

    /** Mengganti kata sandi setelah kata sandi lama dicocokkan. */
    public function gantiSandi(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru paling sedikit 8 karakter.',
            'password.confirmed' => 'Ulangi kata sandi baru belum sama.',
        ]);

        $pengguna = $request->user();

        if (! Hash::check($data['current_password'], $pengguna->password)) {
            return back()->with('gagal', 'Kata sandi saat ini tidak cocok.');
        }

        // Kolom password otomatis dienkripsi oleh cast "hashed" di model User
        $pengguna->password = $data['password'];
        $pengguna->save();

        return redirect()->route('profil.edit')->with('berhasil', 'Kata sandi berhasil diganti.');
    }
}
