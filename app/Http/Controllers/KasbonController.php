<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HanyaAdmin;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KasbonController extends Controller
{
    use HanyaAdmin;

    public function index()
    {
        $this->wajibAdmin();

        return view('kasbon.index', [
            'karyawan' => Employee::withSum('loans as kasbon', 'amount')->withSum('repayments as cicilan', 'amount')
                ->where('is_active', true)->orderBy('name')->get(),
            'riwayat'  => EmployeeLoan::with('employee')->orderByDesc('loan_date')->orderByDesc('created_at')->limit(100)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->wajibAdmin();

        $d = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'loan_date'   => ['required', 'date', 'before_or_equal:today'],
            'amount'      => ['required', 'numeric', 'min:1', 'max:999999999'],
            'notes'       => ['nullable', 'string', 'max:255'],
        ]);

        $loan = DB::transaction(function () use ($d) {
            $this->kunci('kasbon_' . $d['employee_id']);
            $l = EmployeeLoan::create([
                'employee_id' => $d['employee_id'], 'loan_date' => $d['loan_date'],
                'amount' => round((float) $d['amount'], 2), 'notes' => $d['notes'] ?? null, 'recorded_by_id' => Auth::id(),
            ]);
            AuditService::catat('CREATE', 'employee_loans', $l->id, null, $l->only(['employee_id', 'loan_date', 'amount', 'notes']));

            return $l;
        });

        return redirect()->route('kasbon.index')
            ->with('status', 'Kasbon ' . $loan->employee->name . ' Rp ' . number_format((float) $loan->amount, 0, ',', '.') . ' tercatat.');
    }

    public function destroy(EmployeeLoan $loan)
    {
        $this->wajibAdmin();

        DB::transaction(function () use ($loan) {
            $this->kunci('kasbon_' . $loan->employee_id);
            $e = Employee::findOrFail($loan->employee_id);

            if ($e->saldoKasbon() - (float) $loan->amount < -0.004) {
                throw ValidationException::withMessages(['loan' => 'Kasbon ini sebagian sudah dipotong dari gaji, tidak bisa dihapus.']);
            }

            AuditService::catat('DELETE', 'employee_loans', $loan->id, $loan->only(['employee_id', 'loan_date', 'amount', 'notes']));
            $loan->delete();
        });

        return redirect()->route('kasbon.index')->with('status', 'Catatan kasbon dihapus.');
    }
}