<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LiveBirdMortality;
use App\Models\LiveBirdOpname;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpnameAyamController extends Controller
{
    public function index(StokService $stok)
    {
        $items = LiveBirdOpname::with('createdBy:id,name')
            ->orderByDesc('opname_date')->orderByDesc('created_at')
            ->simplePaginate(25);

        $mati = LiveBirdMortality::with('createdBy:id,name')->orderByDesc('record_date')->orderByDesc('created_at')->simplePaginate(25, ['*'], 'mati');
        $totalMati = DB::table('live_bird_mortalities')->selectRaw('coalesce(sum(birds),0) as e, coalesce(sum(weight_kg),0) as k')->first();

        return view('opname-ayam.index', [
            'mati'       => $mati,
            'totalMati'  => $totalMati,
            'items'      => $items,
            'stok'       => $stok->ayamHidup(),
            'bolehTulis' => Auth::user()->canWrite('kandang'),
        ]);
    }

    public function store(Request $request, StokService $stok)
    {
        abort_unless(Auth::user()->canWrite('kandang'), 403);

        $data = $request->validate([
            'opname_date'  => \App\Support\Pencatatan::aturan(),
            'actual_birds' => ['required', 'integer', 'min:0', 'max:1000000'],
            'actual_kg'    => ['required', 'numeric', 'min:0', 'max:10000000'],
            'reason'       => ['nullable', 'string', 'max:255'],
        ]);

        $hasil = DB::transaction(function () use ($data, $stok) {
            $stok->kunci('stok_ayam_hidup');
            $sis = $stok->ayamHidup();

            $aktualE = (int) $data['actual_birds'];
            $aktualK = round((float) $data['actual_kg'], 2);
            $selisihE = $aktualE - $sis['ekor'];
            $selisihK = round($aktualK - $sis['kg'], 2);

            $adaSelisih = $selisihE !== 0 || abs($selisihK) > 0.004;
            $alasan = trim((string) ($data['reason'] ?? ''));

            if ($adaSelisih && mb_strlen($alasan) < 5) {
                throw ValidationException::withMessages([
                    'reason' => 'Ada selisih dengan stok sistem. Isi alasan selisih (minimal 5 huruf).',
                ]);
            }

            $rec = LiveBirdOpname::create([
                'opname_date'   => $data['opname_date'],
                'system_birds'  => $sis['ekor'],
                'actual_birds'  => $aktualE,
                'diff_birds'    => $selisihE,
                'system_kg'     => $sis['kg'],
                'actual_kg'     => $aktualK,
                'diff_kg'       => $selisihK,
                'reason'        => $alasan !== '' ? $alasan : null,
                'created_by_id' => Auth::id(),
            ]);

            AuditLog::create([
                'action'     => 'CREATE',
                'table_name' => 'live_bird_opnames',
                'record_id'  => $rec->id,
                'new_values' => $rec->getAttributes(),
                'user_id'    => Auth::id(),
            ]);

            return [$selisihE, $selisihK];
        });

        $pesan = 'Opname ayam hidup tersimpan.';
        if ($hasil[0] !== 0 || abs($hasil[1]) > 0.004) {
            $pesan .= ' Selisih: ' . ($hasil[0] > 0 ? '+' : '') . $hasil[0] . ' ekor, '
                . ($hasil[1] > 0 ? '+' : '') . number_format($hasil[1], 2, ',', '.') . ' kg.';
        } else {
            $pesan .= ' Stok fisik sesuai dengan sistem.';
        }

        return redirect()->route('opnameayam.index')->with('status', $pesan);
    }
}