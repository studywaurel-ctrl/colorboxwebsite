<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Pilihan tampilan yang disimpan di session, misalnya tema terang atau gelap. */
class PreferensiController extends Controller
{
    public function tema(Request $request)
    {
        $data = $request->validate([
            'tema' => ['required', 'in:light,dark'],
        ]);

        session(['tema' => $data['tema']]);

        return back();
    }
}
