<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HanyaAdmin;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class PengaturanBonusController extends Controller
{
    use HanyaAdmin;

    private const KUNCI = ['pool_tarif_per_kg', 'pool_bobot_karyawan', 'pool_bobot_relawan', 'bonus_kuota_bulanan'];

    public function edit()
    {
        $this->wajibAdmin();
        $s = Setting::semua();

        return view('gaji.bonus', ['s' => [
            'pool_tarif_per_kg'   => (float) ($s['pool_tarif_per_kg'] ?? 1000),
            'pool_bobot_karyawan' => (float) ($s['pool_bobot_karyawan'] ?? 1),
            'pool_bobot_relawan'  => (float) ($s['pool_bobot_relawan'] ?? 1),
            'bonus_kuota_bulanan' => (float) ($s['bonus_kuota_bulanan'] ?? 50000),
        ]]);
    }

    public function update(Request $request)
    {
        $this->wajibAdmin();

        $d = $request->validate([
            'pool_tarif_per_kg'   => ['required', 'numeric', 'min:0', 'max:1000000'],
            'pool_bobot_karyawan' => ['required', 'numeric', 'min:0', 'max:10'],
            'pool_bobot_relawan'  => ['required', 'numeric', 'min:0', 'max:10'],
            'bonus_kuota_bulanan' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $lama = Setting::semua();
        $baru = [];
        foreach (self::KUNCI as $k) {
            $baru[$k] = (string) round((float) $d[$k], 2);
        }

        Setting::simpan($baru);
        AuditService::catat('UPDATE', 'settings', 'pembagian_bonus',
            array_intersect_key($lama, array_flip(self::KUNCI)), $baru);

        return redirect()->route('gaji.bonus')
            ->with('status', 'Pengaturan tersimpan. Hari yang sudah dibagi tidak berubah; pengaturan baru dipakai saat hari berikutnya dibagi atau dihitung ulang.');
    }
}