<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HanyaAdmin
{
    /** Hanya pengguna dengan role admin yang boleh membuka dashboard. */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Halaman ini hanya untuk admin.');
        }

        return $next($request);
    }
}
