<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Masuk, daftar, dan keluar untuk pelanggan Colorbox.
 */
class AuthController extends Controller
{
    /** Menampilkan formulir masuk. */
    public function formMasuk()
    {
        return view('auth.masuk');
    }

    /** Memeriksa email dan kata sandi lalu membuat sesi pengguna. */
    public function masuk(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // Kode sesi tamu dicatat lebih dulu karena akan berganti setelah
        // regenerate(), padahal keranjang tamu tersimpan memakai kode ini.
        $sesiTamu = $request->session()->getId();

        $kredensial = [
            'email' => strtolower($data['email']),
            'password' => $data['password'],
        ];

        if (! Auth::attempt($kredensial, $request->boolean('ingat'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('gagal', 'Email atau kata sandi salah. Silakan coba lagi.');
        }

        $request->session()->regenerate();

        $pengguna = $request->user();
        $this->pindahkanKeranjangTamu($pengguna, $sesiTamu);

        // Admin langsung diarahkan ke dashboard, pelanggan kembali ke halaman
        // yang tadi ingin dibukanya (atau beranda bila tidak ada).
        if ($pengguna->adalahAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('berhasil', 'Selamat datang, '.$pengguna->name.'.');
        }

        return redirect()->intended(route('beranda'))
            ->with('berhasil', 'Selamat datang kembali, '.$pengguna->name.'.');
    }

    /** Menampilkan formulir pendaftaran akun baru. */
    public function formDaftar()
    {
        return view('auth.daftar');
    }

    /** Menyimpan akun pelanggan baru lalu langsung memasukkannya. */
    public function daftar(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk saja.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi paling sedikit 8 karakter.',
            'password.confirmed' => 'Ulangi kata sandi belum sama.',
        ]);

        $sesiTamu = $request->session()->getId();

        $pengguna = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            // Kolom password otomatis dienkripsi oleh cast "hashed" di model User
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'role' => 'pelanggan',
            'points' => 0,
            'member_level' => 'Classic',
        ]);

        Auth::login($pengguna);
        $request->session()->regenerate();
        $this->pindahkanKeranjangTamu($pengguna, $sesiTamu);

        return redirect()->route('beranda')
            ->with('berhasil', 'Akun berhasil dibuat. Selamat datang, '.$pengguna->name.'.');
    }

    /** Keluar dari akun dan membersihkan sesi. */
    public function keluar(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda')->with('berhasil', 'Anda sudah keluar dari akun.');
    }

    /**
     * Memindahkan isi keranjang tamu ke keranjang milik pengguna.
     * Barang dengan varian yang sama jumlahnya digabung, keranjang tamu dihapus.
     */
    private function pindahkanKeranjangTamu(User $pengguna, string $sesiTamu): void
    {
        $keranjangTamu = Cart::whereNull('user_id')->where('session_id', $sesiTamu)->first();

        if (! $keranjangTamu) {
            return;
        }

        $keranjangPengguna = Cart::where('user_id', $pengguna->id)->first();

        // Belum punya keranjang sendiri: keranjang tamu tinggal diakui miliknya
        if (! $keranjangPengguna) {
            $keranjangTamu->user_id = $pengguna->id;
            $keranjangTamu->session_id = null;
            $keranjangTamu->save();

            return;
        }

        foreach ($keranjangTamu->item as $barang) {
            $barangSama = CartItem::where('cart_id', $keranjangPengguna->id)
                ->where('product_variant_id', $barang->product_variant_id)
                ->first();

            if ($barangSama) {
                // Varian yang sama: jumlahnya ditambahkan
                $barangSama->quantity = $barangSama->quantity + $barang->quantity;
                $barangSama->save();
                $barang->delete();
            } else {
                $barang->cart_id = $keranjangPengguna->id;
                $barang->save();
            }
        }

        $keranjangTamu->delete();
    }
}
