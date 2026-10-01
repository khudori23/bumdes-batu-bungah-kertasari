<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HanyaAdmin;
use App\Models\Payroll;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PenerimaanGajiController extends Controller
{
    use HanyaAdmin;

    public function __invoke(Request $request)
    {
        $this->wajibAdmin();

        $s = $request->query('bulan');
        $per = ($s && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $s))
            ? Carbon::createFromFormat('Y-m-d', $s . '-01')->startOfDay()
            : now()->startOfMonth();

        $list = Payroll::with('employee')
            ->where('period_year', $per->year)->where('period_month', $per->month)
            ->whereIn('status', ['FINAL', 'PAID'])
            ->get()->sortBy(fn ($x) => $x->employee->name)->values();

        abort_if($list->isEmpty(), 404, 'Belum ada slip yang diterbitkan pada bulan ini.');

        return view('gaji.penerimaan', [
            'list'  => $list,
            'set'   => Setting::semua(),
            'label' => $per->locale('id')->translatedFormat('F Y'),
        ]);
    }
}