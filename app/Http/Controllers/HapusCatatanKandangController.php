<?php

namespace App\Http\Controllers;

use App\Models\LiveBirdMortality;
use App\Models\LiveBirdOpname;
use App\Models\User;
use App\Services\AuditService;
use App\Services\StokService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HapusCatatanKandangController extends Controller
{
    private function izin($rec): void
    {
        $u = auth()->user();
        abort_unless($u && in_array($u->roleName(), [User::ROLE_ADMIN, User::ROLE_PENGAWAS], true), 403);

        if ($u->roleName() !== User::ROLE_ADMIN) {
            abort_unless((string) $rec->created_by_id === (string) $u->id, 403, 'Hanya catatan yang Anda input sendiri.');
            abort_unless($rec->created_at && $rec->created_at->isToday(), 403, 'Hanya bisa dihapus pada hari yang sama dengan input.');
        }
    }

    public function mortalitas(string $id)
    {
        $rec = LiveBirdMortality::findOrFail($id);
        $this->izin($rec);

        DB::transaction(function () use ($rec) {
            $lama = $rec->getAttributes();
            $rec->delete();
            AuditService::catat('DELETE', 'live_bird_mortalities', (string) $rec->id, $lama, ['hapus_permanen' => true]);
        });

        return back()->with('status', 'Catatan ayam mati dihapus. Stok ayam hidup sudah dikoreksi.');
    }

    public function opname(string $id, StokService $stok)
    {
        $rec = LiveBirdOpname::findOrFail($id);
        $this->izin($rec);

        DB::transaction(function () use ($rec, $stok) {
            $stok->kunci('stok_ayam_hidup');

            if (LiveBirdOpname::where('created_at', '>', $rec->created_at)->exists()) {
                throw ValidationException::withMessages(['hapus' => 'Sudah ada opname yang lebih baru. Hanya opname terakhir yang bisa dihapus.']);
            }

            $lama = $rec->getAttributes();
            $rec->delete();

            $sisa = $stok->ayamHidup();
            if ($sisa['ekor'] < 0 || $sisa['kg'] < -0.004) {
                throw ValidationException::withMessages(['hapus' => 'Opname ini tidak bisa dihapus karena stok ayam hidup akan menjadi negatif.']);
            }

            AuditService::catat('DELETE', 'live_bird_opnames', (string) $rec->id, $lama, ['hapus_permanen' => true]);
        });

        return back()->with('status', 'Opname ayam dihapus.');
    }

    public function pembelian(string $id, StokService $stok)
    {
        $rec = \App\Models\PurchaseTransaction::findOrFail($id);
        $u = auth()->user();
        abort_unless($u && in_array($u->roleName(), [User::ROLE_ADMIN, User::ROLE_PENGAWAS], true), 403);

        if ($u->roleName() !== User::ROLE_ADMIN) {
            abort_unless((string) $rec->received_by_id === (string) $u->id, 403, 'Hanya pembelian yang Anda terima sendiri.');
            abort_unless($rec->created_at && $rec->created_at->isToday(), 403, 'Hanya bisa dihapus pada hari yang sama dengan input.');
        }

        DB::transaction(function () use ($rec, $stok) {
            $stok->kunci('stok_ayam_hidup');

            if (LiveBirdOpname::whereDate('opname_date', '>=', $rec->purchase_date->toDateString())->exists()) {
                throw ValidationException::withMessages(['hapus' => 'Sudah ada opname pada tanggal ini atau sesudahnya. Hapus opname itu dulu.']);
            }

            $lama = $rec->getAttributes();

            try {
                $rec->delete();
            } catch (\Illuminate\Database\QueryException $e) {
                throw ValidationException::withMessages(['hapus' => 'Pembelian ini sudah dipakai data lain, tidak bisa dihapus.']);
            }

            $sisa = $stok->ayamHidup();
            if ($sisa['ekor'] < 0 || $sisa['kg'] < -0.004) {
                throw ValidationException::withMessages(['hapus' => 'Pembelian ini tidak bisa dihapus karena stok ayam hidup akan menjadi negatif.']);
            }

            AuditService::catat('DELETE', 'purchase_transactions', (string) $rec->id, $lama, ['hapus_permanen' => true]);
        });

        return back()->with('status', 'Pembelian dihapus.');
    }}