<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PemasokController extends Controller
{
    /** Manager hanya membaca (sesuai PRD). */
    private function bolehTulis(): bool
    {
        return strtoupper((string) Auth::user()->role) !== 'MANAGER';
    }

    private function wajibBolehTulis(): void
    {
        abort_unless($this->bolehTulis(), 403, 'Manager tidak dapat mengubah data pemasok.');
    }

    public function index()
    {
        $suppliers = Supplier::withCount('purchaseTransactions')
            ->withSum('purchaseTransactions as total_biaya', 'total_cost')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('pemasok.index', [
            'suppliers'  => $suppliers,
            'bolehTulis' => $this->bolehTulis(),
        ]);
    }

    public function create()
    {
        $this->wajibBolehTulis();

        return view('pemasok.form', ['supplier' => new Supplier(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $this->wajibBolehTulis();

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100', Rule::unique('suppliers', 'name')],
            'phone'   => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $supplier = DB::transaction(function () use ($data) {
            // Kunci supaya dua orang tidak mendapat kode yang sama
            DB::select('select pg_advisory_xact_lock(?)', [(int) crc32('supplier_code')]);

            $prefix   = 'PMS-';
            $terakhir = Supplier::where('code', 'like', $prefix . '%')->max('code');
            $urut     = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

            return Supplier::create([
                'code'      => $prefix . str_pad((string) $urut, 3, '0', STR_PAD_LEFT),
                'name'      => $data['name'],
                'phone'     => $data['phone'] ?? null,
                'address'   => $data['address'] ?? null,
                'is_active' => true,
            ]);
        });

        return redirect()->route('pemasok.index')
            ->with('status', 'Pemasok ' . $supplier->name . ' (' . $supplier->code . ') tersimpan.');
    }

    public function edit(Supplier $supplier)
    {
        $this->wajibBolehTulis();

        return view('pemasok.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->wajibBolehTulis();

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100', Rule::unique('suppliers', 'name')->ignore($supplier->id)],
            'phone'   => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $supplier->update([
            'name'      => $data['name'],
            'phone'     => $data['phone'] ?? null,
            'address'   => $data['address'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('pemasok.index')
            ->with('status', 'Data pemasok ' . $supplier->name . ' diperbarui.');
    }
}