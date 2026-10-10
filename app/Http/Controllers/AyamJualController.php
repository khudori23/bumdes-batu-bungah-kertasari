<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LiveBirdSale;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AyamJualController extends Controller
{
    // Penjualan ayam hidup yang dicatat Pengawas Kandang (ecer). Harga diisi kasir nanti.
    public function store(Request $request, StokService $stok)
    {
        abort_unless(Auth::user()->canWrite('kandang'), 403);

        $data = $request->validate([
            'sale_date'     => \App\Support\Pencatatan::aturan(),
            'customer_name' => ['required', 'string', 'max:100'],
            'birds'         => ['required', 'integer', 'min:1', 'max:100000'],
            'weight_kg'     => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'sale_notes'    => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $stok) {
            $stok->kunci('stok_ayam_hidup');
            $sisa = $stok->ayamHidup();

            if ($data['birds'] > $sisa['ekor']) {
                throw ValidationException::withMessages([
                    'birds' => 'Jumlah melebihi stok ayam hidup saat ini (' . number_format($sisa['ekor'], 0, ',', '.') . ' ekor).',
                ]);
            }

            $rec = LiveBirdSale::create([
                'sale_date'     => $data['sale_date'],
                'customer_name' => trim($data['customer_name']),
                'birds'         => (int) $data['birds'],
                'weight_kg'     => round((float) $data['weight_kg'], 2),
                'notes'         => $data['sale_notes'] ?? null,
                'created_by_id' => Auth::id(),
            ]);

            AuditLog::create([
                'action'     => 'CREATE',
                'table_name' => 'live_bird_sales',
                'record_id'  => $rec->id,
                'new_values' => $rec->getAttributes(),
                'user_id'    => Auth::id(),
            ]);
        });

        return redirect()->route('ayamkeluar.index')->with('status', 'Penjualan ayam hidup tersimpan.');
    }
}