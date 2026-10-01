<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PelangganController extends Controller
{
    private function bolehLihat(): void
    {
        abort_unless(Auth::user()?->canView('kasir'), 403, 'Halaman pelanggan hanya untuk admin, manager, dan kasir.');
    }

    private function bolehTambah(): void
    {
        abort_unless(Auth::user()?->canWrite('kasir'), 403, 'Hanya admin dan kasir yang boleh menambah pelanggan.');
    }

    private function bolehUbah(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Hanya admin yang boleh mengubah data pelanggan.');
    }

    private function bersih(?string $s): ?string
    {
        $s = trim((string) $s);

        return $s === '' ? null : $s;
    }

    private function kembar(string $nama, ?string $telp, ?string $kecuali = null): bool
    {
        return Customer::whereRaw('lower(name) = ?', [mb_strtolower($nama)])
            ->when($telp === null, fn ($q) => $q->whereNull('phone'), fn ($q) => $q->where('phone', $telp))
            ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))
            ->exists();
    }

    public function index(Request $request)
    {
        $this->bolehLihat();
        $q = trim((string) $request->query('q', ''));

        $list = Customer::with('customerType')->withCount('sales')
            ->when($q !== '', function ($w) use ($q) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
                $w->where(fn ($x) => $x->where('name', 'ilike', $like)->orWhere('phone', 'ilike', $like));
            })
            ->orderByDesc('is_active')->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('pelanggan.index', [
            'list' => $list, 'q' => $q,
            'tipe' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'bisaTambah' => (bool) Auth::user()->canWrite('kasir'),
            'admin' => (bool) Auth::user()->isAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $this->bolehTambah();

        $d = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'address'          => ['nullable', 'string', 'max:255'],
            'customer_type_id' => ['required', Rule::exists('customer_types', 'id')->where('is_active', true)],
        ], ['customer_type_id.exists' => 'Jenis pelanggan tidak valid atau sudah nonaktif.']);

        $nama = trim($d['name']);
        $telp = $this->bersih($d['phone'] ?? null);
        if ($this->kembar($nama, $telp)) {
            throw ValidationException::withMessages(['name' => 'Pelanggan dengan nama dan telepon yang sama sudah ada.']);
        }

        $c = Customer::create([
            'name' => $nama, 'phone' => $telp, 'address' => $this->bersih($d['address'] ?? null),
            'customer_type_id' => $d['customer_type_id'], 'is_active' => true,
        ]);
        AuditService::catat('CREATE', 'customers', $c->id, null, ['name' => $c->name, 'phone' => $c->phone]);

        return redirect()->route('pelanggan.index')->with('status', 'Pelanggan "' . $c->name . '" ditambahkan.');
    }

    public function edit(string $id)
    {
        $this->bolehUbah();

        return view('pelanggan.edit', [
            'c' => Customer::with('customerType')->withCount('sales')->findOrFail($id),
            'tipe' => CustomerType::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $this->bolehUbah();
        $c = Customer::findOrFail($id);

        $d = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'address'          => ['nullable', 'string', 'max:255'],
            'customer_type_id' => ['required', 'exists:customer_types,id'],
        ]);

        $nama = trim($d['name']);
        $telp = $this->bersih($d['phone'] ?? null);
        if ($this->kembar($nama, $telp, $c->id)) {
            throw ValidationException::withMessages(['name' => 'Pelanggan dengan nama dan telepon yang sama sudah ada.']);
        }

        $lama = ['name' => $c->name, 'phone' => $c->phone, 'type' => $c->customer_type_id, 'is_active' => (bool) $c->is_active];
        $c->name = $nama;
        $c->phone = $telp;
        $c->address = $this->bersih($d['address'] ?? null);
        $c->customer_type_id = $d['customer_type_id'];
        $c->is_active = $request->boolean('is_active');
        $c->save();

        AuditService::catat('UPDATE', 'customers', $c->id, $lama, [
            'name' => $c->name, 'phone' => $c->phone, 'type' => $c->customer_type_id, 'is_active' => (bool) $c->is_active,
        ]);

        return redirect()->route('pelanggan.index')->with('status', 'Data pelanggan tersimpan.');
    }
}