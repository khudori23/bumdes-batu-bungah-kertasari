<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Manager hanya boleh membaca. Pengecualian: keluar dan ganti password sendiri. */
class ManagerBacaSaja
{
    private const BOLEH = ['logout', 'akun.password.update'];

    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();

        if ($u && $u->isManager() && ! $request->isMethodSafe() && ! $request->routeIs(...self::BOLEH)) {
            abort(403, 'Peran Manager hanya dapat melihat data.');
        }

        return $next($request);
    }
}