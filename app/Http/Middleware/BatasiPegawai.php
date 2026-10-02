<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Akun PEGAWAI hanya boleh membuka halaman absen, ganti password, dan keluar. */
class BatasiPegawai
{
    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();

        if ($u && $u->roleName() === 'PEGAWAI'
            && ! $request->is('/')
            && ! $request->routeIs('absen.*', 'akun.*', 'dashboard', 'logout')) {
            abort(403, 'Akun pegawai hanya dapat dipakai untuk absen.');
        }

        return $next($request);
    }
}