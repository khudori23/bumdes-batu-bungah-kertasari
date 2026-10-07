<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WeighingRecord;
use Illuminate\Support\Facades\DB;

class DeleteWeighingRecordController extends Controller
{
    public function __invoke(WeighingRecord $weighingRecord)
    {
        abort_unless(
            in_array(auth()->user()->roleName(), [User::ROLE_ADMIN, User::ROLE_KASIR], true),
            403
        );

        DB::transaction(function () use ($weighingRecord) {
            $weighingRecord->stockMovements()->delete();
            $weighingRecord->delete();
        });

        return back()->with('success', 'Data berhasil dihapus dan stok sudah dikoreksi.');
    }
}