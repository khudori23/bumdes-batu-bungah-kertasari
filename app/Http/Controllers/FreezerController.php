<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StorageLocation;
use App\Services\AuditService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FreezerController extends Controller
{
    private function bolehLihat(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isManager() || $u->isKasir()), 403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    private function bolehUbah(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isKasir()), 403, 'Anda tidak memiliki akses untuk mengubah freezer.');
    }

    public function index(StokService $stok)
    {
        $this->bolehLihat();

        $today = Carbon::today();
        $produk = $stok->produk($today, $today);
        $isi = $produk->flatMap(fn ($p) => $p->lot)
            ->groupBy('freezer_id')
            ->map(fn ($g) => round((float) $g->sum('kg'), 2));

        $u = Auth::user();

        return view('freezer.index', [
            'freezer'  => StorageLocation::orderByDesc('is_active')->orderBy('name')->get(),
            'produk'   => $produk,
            'isi'      => $isi,
            'bisaUbah' => $u->isAdmin() || $u->isKasir(),
        ]);
    }

    public function store(Request $request)
    {
        $this->bolehUbah();

        $d = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:storage_locations,name'],
        ], [
            'name.required' => 'Nama freezer wajib diisi.',
            'name.unique'   => 'Nama freezer sudah dipakai.',
            'name.max'      => 'Nama freezer maksimal 50 karakter.',
        ]);

        $nama = trim($d['name']);
        $dasar = substr(strtoupper(Str::slug($nama)) ?: 'FREEZER', 0, 25);
        $kode = $dasar;
        $i = 1;
        while (StorageLocation::where('code', $kode)->exists()) {
            $i++;
            $kode = $dasar . '-' . $i;
        }

        $f = StorageLocation::create(['code' => $kode, 'name' => $nama, 'is_active' => true]);
        AuditService::catat('CREATE', 'storage_locations', $f->id, null, ['code' => $f->code, 'name' => $f->name]);

        return redirect()->route('freezer.index')->with('status', 'Freezer ditambahkan.');
    }

    public function update(Request $request, string $id, StokService $stok)
    {
        $this->bolehUbah();
        $f = StorageLocation::findOrFail($id);

        $d = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('storage_locations', 'name')->ignore($f->id)],
        ], [
            'name.required' => 'Nama freezer wajib diisi.',
            'name.unique'   => 'Nama freezer sudah dipakai.',
            'name.max'      => 'Nama freezer maksimal 50 karakter.',
        ]);

        $aktif = $request->boolean('is_active');

        if ($f->is_active && ! $aktif) {
            $today = Carbon::today();
            $isi = (float) $stok->produk($today, $today)
                ->flatMap(fn ($p) => $p->lot)
                ->where('freezer_id', $f->id)
                ->sum('kg');

            if ($isi > 0.004) {
                throw ValidationException::withMessages([
                    'is_active' => "Freezer {$f->name} masih berisi " . number_format($isi, 2, ',', '.') . ' kg stok, jadi belum bisa dinonaktifkan.',
                ]);
            }
        }

        $lama = ['name' => $f->name, 'is_active' => (bool) $f->is_active];
        $f->name = trim($d['name']);
        $f->is_active = $aktif;
        $f->save();

        AuditService::catat('UPDATE', 'storage_locations', $f->id, $lama, ['name' => $f->name, 'is_active' => (bool) $f->is_active]);

        return redirect()->route('freezer.index')->with('status', 'Freezer tersimpan.');
    }

    /**
     * Simpan batas simpan (hari) per produk. Kunci array = id produk.
     */
    public function batas(Request $request)
    {
        $this->bolehUbah();

        $request->validate([
            'batas'   => ['required', 'array'],
            'batas.*' => ['required', 'integer', 'min:1', 'max:365'],
        ], [
            'batas.*.required' => 'Batas simpan wajib diisi.',
            'batas.*.integer'  => 'Batas simpan harus berupa angka bulat (hari).',
            'batas.*.min'      => 'Batas simpan minimal 1 hari.',
            'batas.*.max'      => 'Batas simpan maksimal 365 hari.',
        ]);

        $input = (array) $request->input('batas');
        $ids = collect(array_keys($input))->filter(fn ($k) => Str::isUuid((string) $k))->values()->all();
        $jumlah = 0;

        DB::transaction(function () use ($ids, $input, &$jumlah) {
            foreach (Product::whereIn('id', $ids)->where('is_live_bird', false)->get() as $p) {
                $baru = (int) $input[$p->id];
                $lama = (int) ($p->max_storage_days ?: 3);

                if ($baru === $lama) {
                    continue;
                }

                $p->max_storage_days = $baru;
                $p->save();
                $jumlah++;

                AuditService::catat('UPDATE', 'products', $p->id, ['max_storage_days' => $lama], ['max_storage_days' => $baru]);
            }
        });

        return redirect()->route('freezer.index')
            ->with('status', $jumlah ? $jumlah . ' batas simpan diperbarui.' : 'Tidak ada batas yang berubah.');
    }
}