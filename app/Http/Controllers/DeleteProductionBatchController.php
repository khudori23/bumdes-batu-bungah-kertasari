<?php

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteProductionBatchController extends Controller
{
    public function __invoke(ProductionBatch $productionBatch)
    {
        abort_unless(
            in_array(auth()->user()->roleName(), [User::ROLE_ADMIN, User::ROLE_KASIR], true),
            403
        );

        DB::transaction(function () use ($productionBatch) {
            $ids = $productionBatch->weighingRecords()->pluck('id');
            StockMovement::whereIn('weighing_record_id', $ids)->delete();
            $productionBatch->weighingRecords()->delete();
            $productionBatch->delete();
        });

        return back()->with('success', 'Batch produksi dihapus dan stok sudah dikoreksi.');
    }
}