<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class HapusDataController extends Controller
{
    /**
     * Tabel yang boleh dihapus (nama_tabel => label).
     * Hapus baris tabel yang TIDAK ingin bisa dihapus lewat menu ini.
     */
    private function tabel(): array
    {
        return [
            'sale_items' => 'Sale Items',
            'attendances' => 'Attendances',
            'employees' => 'Employees',
            'settings' => 'Settings',
            'audit_logs' => 'Audit Logs',
            'live_bird_mortalities' => 'Live Bird Mortalities',
            'live_bird_opnames' => 'Live Bird Opnames',
            'kitchen_orders' => 'Kitchen Orders',
            'kitchen_order_lines' => 'Kitchen Order Lines',
            'live_bird_sales' => 'Live Bird Sales',
            'attendance_days' => 'Attendance Days',
            'employee_loans' => 'Employee Loans',
            'payrolls' => 'Payrolls',
            'loan_repayments' => 'Loan Repayments',
            'expenses' => 'Expenses',
            'storage_locations' => 'Storage Locations',
            'products' => 'Products',
            'product_prices' => 'Product Prices',
            'customer_types' => 'Customer Types',
            'customers' => 'Customers',
            'suppliers' => 'Suppliers',
            'production_batches' => 'Production Batches',
            'weighing_records' => 'Weighing Records',
            'stock_movements' => 'Stock Movements',
            'stock_adjustments' => 'Stock Adjustments',
            'purchase_transactions' => 'Purchase Transactions',
            'sales' => 'Sales',
            'payments' => 'Payments',
        ];
    }

    private function izin(Request $r): void
    {
        // Batasi hanya admin/pemilik, hapus tanda // lalu sesuaikan:
        // abort_unless($r->user()->role === 'admin', 403);
    }

    private function cekPassword(Request $r): void
    {
        $r->validate(['password' => 'required']);
        if (!Hash::check($r->password, $r->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Password salah.']);
        }
    }

    private function pastikanTabel(string $tabel): void
    {
        abort_unless(array_key_exists($tabel, $this->tabel()) && Schema::hasTable($tabel), 404);
    }

    public function index(Request $r)
    {
        $this->izin($r);
        $daftar = collect($this->tabel())
            ->filter(fn($label, $t) => Schema::hasTable($t))
            ->map(fn($label, $t) => ['label' => $label, 'jumlah' => DB::table($t)->count()]);

        return view('hapus-data.index', compact('daftar'));
    }

    public function show(Request $r, string $tabel)
    {
        $this->izin($r);
        $this->pastikanTabel($tabel);

        $label = $this->tabel()[$tabel];
        $baris = DB::table($tabel)->orderByDesc('id')->paginate(50);

        return view('hapus-data.show', compact('tabel', 'label', 'baris'));
    }

    /** Hapus PERMANEN semua isi tabel yang dicentang. */
    public function destroyTables(Request $r)
    {
        $this->izin($r);
        $r->validate([
            'tabel'      => 'required|array|min:1',
            'tabel.*'    => 'string',
            'konfirmasi' => 'required|in:HAPUS',
        ], [
            'tabel.required' => 'Pilih minimal satu data.',
            'konfirmasi.in'  => 'Ketik HAPUS (huruf besar) untuk konfirmasi.',
        ]);
        $this->cekPassword($r);

        foreach ($r->tabel as $t) {
            $this->pastikanTabel($t);
        }

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($r) {
                foreach (array_keys($this->tabel()) as $t) {
                    if (in_array($t, $r->tabel, true)) {
                        DB::table($t)->delete();
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return redirect()->route('hapus-data.index')->with('sukses', 'Data terpilih berhasil dihapus permanen.');
    }

    /** Hapus PERMANEN baris yang dicentang di satu tabel. */
    public function destroyRows(Request $r, string $tabel)
    {
        $this->izin($r);
        $this->pastikanTabel($tabel);

        $r->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ], ['ids.required' => 'Pilih minimal satu baris.']);
        $this->cekPassword($r);

        Schema::disableForeignKeyConstraints();
        try {
            DB::table($tabel)->whereIn('id', $r->ids)->delete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return redirect()->route('hapus-data.show', $tabel)
            ->with('sukses', count($r->ids) . ' baris berhasil dihapus permanen.');
    }
}