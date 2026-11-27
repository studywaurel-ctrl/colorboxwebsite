<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Lupa kata sandi memakai fasilitas bawaan Laravel (tabel password_reset_tokens).
 * Pada project ini MAIL_MAILER=log sehingga isi emailnya ditulis ke
 * storage/logs/laravel.log, jadi tautannya bisa disalin dari berkas tersebut.
 */
class LupaSandiController extends Controller
{
    /** Formulir permintaan tautan atur ulang kata sandi. */
    public function formPermintaan()
    {
        return view('auth.lupa-sandi');
    }

    /** Mengirim tautan atur ulang ke email pelanggan. */
    public function kirimTautan(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:100'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
        ]);

        $status = Password::sendResetLink(['email' => strtolower($request->input('email'))]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('berhasil', 'Tautan atur ulang kata sandi sudah dibuat. Karena email dicatat ke berkas log, tautannya dapat dilihat di storage/logs/laravel.log.');
        }

        return back()->withInput()->with('gagal', 'Email tersebut belum terdaftar.');
    }

    /** Formulir mengisi kata sandi baru, dibuka dari tautan pada email. */
    public function formAtur(Request $request, string $token)
    {
        return view('auth.atur-sandi', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** Menyimpan kata sandi baru bila tokennya masih berlaku. */
    public function atur(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'Kata sandi paling sedikit 8 karakter.',
            'password.confirmed' => 'Ulangi kata sandi belum sama.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $pengguna, string $sandiBaru) {
                $pengguna->forceFill([
                    'password' => Hash::make($sandiBaru),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($pengguna));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->with('gagal', 'Tautan atur ulang tidak berlaku atau sudah kedaluwarsa. Silakan minta tautan baru.');
        }

        return redirect()->route('login')
            ->with('berhasil', 'Kata sandi berhasil diganti. Silakan masuk memakai kata sandi baru.');
    }
}
