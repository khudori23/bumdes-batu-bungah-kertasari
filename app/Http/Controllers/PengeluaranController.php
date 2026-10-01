<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PengeluaranController extends Controller
{
    public const KATEGORI = [
        'Es', 'Plastik', 'Transport', 'Listrik & Air',
        'Upah / Gaji', 'Perawatan Alat', 'Kebersihan', 'Lainnya',
    ];

    private function bolehTulis(): bool
    {
        return Auth::user()->isAdmin();
    }

    public function index(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isManager(), 403);

        $bulan = (string) $request->query('bulan');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
            $bulan = now()->format('Y-m');
        }

        $awal  = Carbon::createFromFormat('Y-m-d', $bulan . '-01')->startOfDay();
        $akhir = $awal->copy()->endOfMonth();

        $items = Expense::with('recordedBy')
            ->whereBetween('expense_date', [$awal->toDateString(), $akhir->toDateString()])
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at')
            ->get();

        return view('pengeluaran.index', [
            'items'       => $items,
            'total'       => (float) $items->sum('amount'),
            'perKategori' => $items->groupBy('category')->map(fn ($g) => (float) $g->sum('amount'))->sortDesc(),
            'bulan'       => $bulan,
            'labelBulan'  => $awal->locale('id')->translatedFormat('F Y'),
            'kategori'    => self::KATEGORI,
            'bolehTulis'  => $this->bolehTulis(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->bolehTulis(), 403, 'Hanya admin yang boleh mencatat pengeluaran.');

        $data = $request->validate([
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'category'     => ['required', 'in:' . implode(',', self::KATEGORI)],
            'amount'       => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'description'  => ['nullable', 'string', 'max:255'],
        ]);

        $expense = Expense::create([
            'expense_date'   => $data['expense_date'],
            'category'       => $data['category'],
            'amount'         => round((float) $data['amount'], 2),
            'description'    => $data['description'] ?? null,
            'recorded_by_id' => Auth::id(),
        ]);

        return redirect()
            ->route('pengeluaran.index', ['bulan' => $expense->expense_date->format('Y-m')])
            ->with('status', 'Pengeluaran ' . $expense->category . ' Rp ' . number_format((float) $expense->amount, 0, ',', '.') . ' tersimpan.');
    }
}