<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /** Catat satu kejadian. Jangan pernah mengisi password/token di $lama atau $baru. */
    public static function catat(string $aksi, string $tabel, string $idData, ?array $lama = null, ?array $baru = null, ?string $alasan = null): void
    {
        AuditLog::create([
            'action'     => $aksi,
            'table_name' => $tabel,
            'record_id'  => $idData,
            'old_values' => $lama,
            'new_values' => $baru,
            'reason'     => $alasan,
            'user_id'    => Auth::id(),
        ]);
    }
}