<?php

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class DeleteProductionBatchController extends Controller
{
    public function __invoke(ProductionBatch $productionBatch)
    {
        $u = auth()->user();
        abort_unless($u && in_array($u->roleName(), [User::ROLE_ADMIN, User::ROLE_PENGAWAS], true), 403);

        if ($u->roleName() !== User::ROLE_ADMIN) {
            abort_unless((string) $productionBatch->created_by_id === (string) $u->id, 403, 'Hanya batch yang Anda input sendiri.');
            abort_unless($productionBatch->created_at && $productionBatch->created_at->isToday(), 403, 'Hanya bisa dihapus pada hari yang sama dengan input.');
        }

        if (DB::table('kitchen_orders')->where('production_batch_id', $productionBatch->id)->exists()) {
            return back()->withErrors(['hapus' => 'Batch ini sudah dipakai pesanan dapur, tidak bisa dihapus. Hubungi admin.']);
        }

        DB::transaction(function () use ($productionBatch) {
            $ids = $productionBatch->weighingRecords()->pluck('id');
            StockMovement::whereIn('weighing_record_id', $ids)->delete();
            StockMovement::where('batch_id', $productionBatch->id)->delete();
            $productionBatch->weighingRecords()->delete();
            $productionBatch->delete();

            AuditService::catat('DELETE', 'production_batches', (string) $productionBatch->id, null, ['hapus_permanen' => true]);
        });

        return back()->with('success', 'Batch produksi dihapus dan stok sudah dikoreksi.');
    }
}