<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait HanyaAdmin
{
    protected function wajibAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Modul SDM & Gaji hanya untuk admin.');
    }

    protected function kunci(string $kunci): void
    {
        DB::select('select pg_advisory_xact_lock(?)', [(int) crc32($kunci)]);
    }
}