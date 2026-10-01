<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HanyaAdmin;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class PengaturanBonusController extends Controller
{
    use HanyaAdmin;

    public function edit()
    {
        $this->wajibAdmin();
        $s = Setting::semua();

        return view('gaji.bonus', [
            'perKg'  => (float) ($s['bonus_rendemen_per_kg'] ?? 1000),
            'ambang' => (float) ($s['bonus_rendemen_min'] ?? 0),
        ]);
    }

    public function update(Request $request)
    {
        $this->wajibAdmin();

        $d = $request->validate([
            'bonus_rendemen_per_kg' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'bonus_rendemen_min'    => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $lama = Setting::semua();
        $baru = [
            'bonus_rendemen_per_kg' => (string) round((float) $d['bonus_rendemen_per_kg'], 2),
            'bonus_rendemen_min'    => (string) round((float) $d['bonus_rendemen_min'], 2),
        ];

        Setting::simpan($baru);
        AuditService::catat('UPDATE', 'settings', 'bonus_rendemen', [
            'per_kg' => $lama['bonus_rendemen_per_kg'] ?? null,
            'min'    => $lama['bonus_rendemen_min'] ?? null,
        ], $baru);

        return redirect()->route('gaji.bonus')
            ->with('status', 'Pengaturan bonus tersimpan. Draft yang sudah ada baru memakai nilai ini setelah diklik "Hitung ulang".');
    }
}