<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PengeluaranController extends Controller
{
    public const KATEGORI = [
        'Es', 'Plastik', 'Transport', 'Listrik & Air',
        'Upah / Gaji', 'Perawatan Alat', 'Kebersihan', 'Lainnya',
    ];

    /** Kasir tidak boleh memilih "Upah / Gaji" (urusan payroll admin). */
    private function kategoriBoleh(): array
    {
        return Auth::user()->isKasir()
            ? array_values(array_diff(self::KATEGORI, ['Upah / Gaji']))
            : self::KATEGORI;
    }

    public function index(Request $request)
    {
        $u = Auth::user();
        abort_unless($u->canView('pengeluaran'), 403);

        $bulan = (string) $request->query('bulan');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
            $bulan = now()->format('Y-m');
        }

        $awal  = Carbon::createFromFormat('Y-m-d', $bulan . '-01')->startOfDay();
        $akhir = $awal->copy()->endOfMonth();
        $kasir = $u->isKasir();

        $items = Expense::with('recordedBy')
            ->whereBetween('expense_date', [$awal->toDateString(), $akhir->toDateString()])
            ->when($kasir, fn ($q) => $q->where('recorded_by_id', $u->id))
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at')
            ->get();

        return view('pengeluaran.index', [
            'items'        => $items,
            'total'        => (float) $items->sum('amount'),
            'perKategori'  => $items->groupBy('category')->map(fn ($g) => (float) $g->sum('amount'))->sortDesc(),
            'bulan'        => $bulan,
            'labelBulan'   => $awal->locale('id')->translatedFormat('F Y'),
            'kategori'     => $this->kategoriBoleh(),
            'bolehTulis'   => $u->canWrite('pengeluaran'),
            'hanyaSendiri' => $kasir,
        ]);
    }

    public function store(Request $request)
    {
        $u = Auth::user();
        abort_unless($u->canWrite('pengeluaran'), 403, 'Anda tidak boleh mencatat pengeluaran.');
        $kasir = $u->isKasir();

        $data = $request->validate([
            'expense_date' => \App\Support\Pencatatan::aturan(false),
            'category'     => ['required', Rule::in($this->kategoriBoleh())],
            'amount'       => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'description'  => ['nullable', 'string', 'max:255'],
        ]);

        $tanggal = $data['expense_date'] ?? now()->toDateString();

        $expense = Expense::create([
            'expense_date'   => $tanggal,
            'category'       => $data['category'],
            'amount'         => round((float) $data['amount'], 2),
            'description'    => $data['description'] ?? null,
            'recorded_by_id' => $u->id,
        ]);
        AuditService::catat('CREATE', 'expenses', $expense->id, null, [
            'category' => $expense->category, 'amount' => (float) $expense->amount,
        ]);

        return redirect()
            ->route('pengeluaran.index', ['bulan' => Carbon::parse($tanggal)->format('Y-m')])
            ->with('status', 'Pengeluaran ' . $expense->category . ' Rp ' . number_format((float) $expense->amount, 0, ',', '.') . ' tersimpan.');
    }
}