<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Pemakaian: ->middleware(RoleMiddleware::class.':KASIR')
     * - ADMIN selalu diizinkan.
     * - MANAGER hanya boleh melihat (GET/HEAD) dan tidak boleh membuka form input
     *   (nama route berakhiran .create atau .edit).
     * - Peran lain harus tercantum di daftar.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Akun tidak aktif.');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->isManager()) {
            if ($this->bacaSaja($request)) {
                return $next($request);
            }

            abort(403, 'Peran Manager hanya dapat melihat data.');
        }

        $allowed = array_map('strtoupper', $roles);

        if (! in_array($user->roleName(), $allowed, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }

    private function bacaSaja(Request $request): bool
    {
        if (! $request->isMethodSafe()) {
            return false;
        }

        $nama = (string) $request->route()?->getName();

        return ! (str_ends_with($nama, '.create') || str_ends_with($nama, '.edit'));
    }
}