<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HanyaAdmin;
use App\Models\Employee;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KaryawanController extends Controller
{
    use HanyaAdmin;

    public const JENIS = ['KARYAWAN' => 'Karyawan', 'RELAWAN' => 'Relawan'];
    private const AUDIT = ['code', 'name', 'type', 'position', 'phone', 'daily_rate', 'per_kg_rate', 'is_active'];

    private function aturan(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'type'        => ['required', Rule::in(array_keys(self::JENIS))],
            'position'    => ['nullable', 'string', 'max:60'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'daily_rate'  => ['required', 'numeric', 'min:0', 'max:99999999'],
            'per_kg_rate' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ];
    }

    public function index()
    {
        $this->wajibAdmin();

        $list = Employee::withSum('loans as kasbon', 'amount')->withSum('repayments as cicilan', 'amount')
            ->orderByDesc('is_active')->orderBy('type')->orderBy('name')->get();

        return view('karyawan.index', compact('list'));
    }

    public function create()
    {
        $this->wajibAdmin();

        return view('karyawan.form', [
            'employee' => new Employee(['type' => 'KARYAWAN', 'is_active' => true, 'daily_rate' => 0, 'per_kg_rate' => 0]),
            'jenis'    => self::JENIS,
        ]);
    }

    public function store(Request $request)
    {
        $this->wajibAdmin();
        $d = $request->validate($this->aturan());

        $e = DB::transaction(function () use ($d) {
            $this->kunci('employee_code');
            $prefix   = $d['type'] === 'RELAWAN' ? 'RLW-' : 'KRY-';
            $terakhir = Employee::where('code', 'like', $prefix . '%')->max('code');
            $urut     = $terakhir ? ((int) substr($terakhir, 4)) + 1 : 1;

            $e = Employee::create([
                'code' => $prefix . str_pad((string) $urut, 3, '0', STR_PAD_LEFT),
                'name' => trim($d['name']), 'type' => $d['type'],
                'position' => $d['position'] ?? null, 'phone' => $d['phone'] ?? null,
                'daily_rate' => $d['daily_rate'], 'per_kg_rate' => $d['per_kg_rate'], 'is_active' => true,
            ]);
            AuditService::catat('CREATE', 'employees', $e->id, null, $e->only(self::AUDIT));

            return $e;
        });

        return redirect()->route('karyawan.index')->with('status', $e->name . ' (' . $e->code . ') ditambahkan.');
    }

    public function edit(Employee $employee)
    {
        $this->wajibAdmin();

        return view('karyawan.form', ['employee' => $employee, 'jenis' => self::JENIS]);
    }

    public function update(Request $request, Employee $employee)
    {
        $this->wajibAdmin();
        $d = $request->validate($this->aturan());

        DB::transaction(function () use ($employee, $d, $request) {
            $lama = $employee->only(self::AUDIT);
            $employee->update([
                'name' => trim($d['name']), 'type' => $d['type'],
                'position' => $d['position'] ?? null, 'phone' => $d['phone'] ?? null,
                'daily_rate' => $d['daily_rate'], 'per_kg_rate' => $d['per_kg_rate'],
                'is_active' => $request->boolean('is_active'),
            ]);
            $baru = $employee->fresh()->only(self::AUDIT);
            if ($lama !== $baru) {
                AuditService::catat('UPDATE', 'employees', $employee->id, $lama, $baru);
            }
        });

        return redirect()->route('karyawan.index')
            ->with('status', 'Data ' . $employee->name . ' diperbarui. Tarif baru berlaku untuk draft gaji berikutnya.');
    }
}