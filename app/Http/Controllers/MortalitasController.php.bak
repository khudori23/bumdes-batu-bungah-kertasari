<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LiveBirdMortality;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MortalitasController extends Controller
{
    public function index(StokService $stok)
    {
        $items = LiveBirdMortality::with('createdBy:id,name')
            ->orderByDesc('record_date')->orderByDesc('created_at')
            ->simplePaginate(25);

        $total = DB::table('live_bird_mortalities')
            ->selectRaw('coalesce(sum(birds),0) as e, coalesce(sum(weight_kg),0) as k')->first();

        return view('mortalitas.index', [
            'items'      => $items,
            'stok'       => $stok->ayamHidup(),
            'total'      => $total,
            'bolehTulis' => Auth::user()->canWrite('kandang'),
        ]);
    }

    public function store(Request $request, StokService $stok)
    {
        abort_unless(Auth::user()->canWrite('kandang'), 403);

        $data = $request->validate([
            'record_date' => ['required', 'date', 'before_or_equal:today'],
            'birds'       => ['required', 'integer', 'min:1', 'max:100000'],
            'weight_kg'   => ['required', 'numeric', 'min:0', 'max:100000'],
            'cause'       => ['nullable', 'string', 'max:100'],
            'notes'       => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $stok) {
            $stok->kunci('stok_ayam_hidup');
            $sisa = $stok->ayamHidup();

            if ($data['birds'] > $sisa['ekor']) {
                throw ValidationException::withMessages([
                    'birds' => 'Jumlah melebihi stok ayam hidup saat ini (' . number_format($sisa['ekor'], 0, ',', '.') . ' ekor).',
                ]);
            }

            $rec = LiveBirdMortality::create([
                'record_date'   => $data['record_date'],
                'birds'         => (int) $data['birds'],
                'weight_kg'     => round((float) $data['weight_kg'], 2),
                'cause'         => $data['cause'] ?? null,
                'notes'         => $data['notes'] ?? null,
                'created_by_id' => Auth::id(),
            ]);

            AuditLog::create([
                'action'     => 'CREATE',
                'table_name' => 'live_bird_mortalities',
                'record_id'  => $rec->id,
                'new_values' => $rec->getAttributes(),
                'user_id'    => Auth::id(),
            ]);
        });

        return redirect()->route('mortalitas.index')->with('status', 'Data ayam mati tersimpan.');
    }
}