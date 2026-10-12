<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WeighingRecord;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class DeleteWeighingRecordController extends Controller
{
    public function __invoke(WeighingRecord $weighingRecord)
    {
        $u = auth()->user();
        abort_unless($u && in_array($u->roleName(), [User::ROLE_ADMIN, User::ROLE_PENGAWAS], true), 403);

        if ($u->roleName() !== User::ROLE_ADMIN) {
            $pemilik = DB::table('production_batches')->where('id', $weighingRecord->batch_id)->value('created_by_id');
            abort_unless((string) $pemilik === (string) $u->id, 403, 'Hanya penimbangan pada batch yang Anda input sendiri.');
            abort_unless($weighingRecord->created_at && $weighingRecord->created_at->isToday(), 403, 'Hanya bisa dihapus pada hari yang sama dengan input.');
        }

        DB::transaction(function () use ($weighingRecord) {
            $weighingRecord->stockMovements()->delete();
            $weighingRecord->delete();

            AuditService::catat('DELETE', 'weighing_records', (string) $weighingRecord->id, null, ['hapus_permanen' => true]);
        });

        return back()->with('success', 'Data berhasil dihapus dan stok sudah dikoreksi.');
    }
}