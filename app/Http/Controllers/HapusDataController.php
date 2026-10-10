<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HapusDataController extends Controller
{
    /** Tabel yang kolom referensinya hanya dikosongkan (bukan ikut dihapus) saat induknya dihapus. */
    private const LEPAS = ['live_bird_sales'];

    /**
     * Kelompok DATA TRANSAKSI yang boleh dihapus (BUKAN data induk).
     * Tabel yang tidak ada di database otomatis dilewati.
     * Ingin menambah/mengurangi? Edit daftar ini saja.
     */
    private function kelompok(): array
    {
        return [
            'KASIR' => [
                'judul' => 'Data Input Kasir',
                'tabel' => [
                    'sales'      => 'Penjualan (Nota)',
                    'sale_items' => 'Item Penjualan',
                    'payments'   => 'Pembayaran',
                ],
            ],
            'PENGAWAS' => [
                'judul' => 'Data Input Pengawas Kandang',
                'tabel' => [
                    'purchase_transactions' => 'Pembelian',
                    'production_batches'    => 'Batch Produksi',
                    'weighing_records'      => 'Penimbangan',
                    'live_bird_sales'       => 'Penjualan Ayam Hidup (Ecer)',
                    'live_bird_mortalities' => 'Ayam Mati',
                    'live_bird_opnames'     => 'Opname Ayam Hidup',
                    'kitchen_orders'        => 'Pesanan Dapur',
                    'kitchen_order_lines'   => 'Rincian Pesanan Dapur',
                    'stock_movements'       => 'Mutasi Stok',
                    'stock_adjustments'     => 'Penyesuaian Stok',
                ],
            ],
        ];
    }

    private function semuaTabel(): array
    {
        $hasil = [];
        foreach ($this->kelompok() as $k) {
            $hasil += $k['tabel'];
        }
        return $hasil;
    }

    private function izin(Request $r): void
    {
        // Hanya admin. Ganti bila pemilik/manager juga boleh, misalnya:
        // abort_unless($r->user()->isAdmin() || $r->user()->isManager(), 403);
        abort_unless($r->user() && $r->user()->isAdmin(), 403);
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
        abort_unless(
            array_key_exists($tabel, $this->semuaTabel())
            && Schema::hasTable($tabel)
            && Schema::hasColumn($tabel, 'id'),
            404
        );
    }

    /**
     * Hapus baris + semua data turunannya (tabel anak dengan kolom <induk>_id),
     * supaya tidak ada data yatim.
     */
    private function hapusBaris(string $tabel, array $ids, int $depth = 0): void
    {
        if (!$ids || $depth > 5 || !Schema::hasColumn($tabel, 'id')) {
            return;
        }

        $kolom = Str::singular($tabel) . '_id';
        foreach (array_keys($this->semuaTabel()) as $anak) {
            if ($anak === $tabel || !Schema::hasTable($anak)
                || !Schema::hasColumn($anak, $kolom) || !Schema::hasColumn($anak, 'id')) {
                continue;
            }
            if (in_array($anak, self::LEPAS, true)) {
                DB::table($anak)->whereIn($kolom, $ids)->update([$kolom => null]);
                continue;
            }
            $idAnak = DB::table($anak)->whereIn($kolom, $ids)->pluck('id')->all();
            $this->hapusBaris($anak, $idAnak, $depth + 1);
        }

        DB::table($tabel)->whereIn('id', $ids)->delete();
    }

    public function index(Request $r)
    {
        $this->izin($r);

        $kelompok = $this->kelompok();
        foreach ($this->kelompok() as $kode => $k) {
            foreach ($k['tabel'] as $t => $label) {
                if (!Schema::hasTable($t)) {
                    unset($kelompok[$kode]['tabel'][$t]);
                    continue;
                }
                $kelompok[$kode]['tabel'][$t] = ['label' => $label, 'jumlah' => DB::table($t)->count()];
            }
        }

        return view('hapus-data.index', compact('kelompok'));
    }

    public function show(Request $r, string $tabel)
    {
        $this->izin($r);
        $this->pastikanTabel($tabel);

        $label = $this->semuaTabel()[$tabel];
        $urut = Schema::hasColumn($tabel, 'created_at') ? 'created_at' : 'id';
        $baris = DB::table($tabel)->orderByDesc($urut)->simplePaginate(50);

        return view('hapus-data.show', compact('tabel', 'label', 'baris'));
    }

    /** Hapus PERMANEN seluruh isi tabel yang dicentang. */
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
                foreach (array_keys($this->semuaTabel()) as $t) {
                    if (!in_array($t, $r->tabel, true) || !Schema::hasTable($t)) {
                        continue;
                    }
                    DB::table($t)->pluck('id')->chunk(500)->each(function ($ids) use ($t) {
                        $this->hapusBaris($t, $ids->all());
                    });
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return redirect()->route('hapus-data.index')->with('sukses', 'Data terpilih berhasil dihapus permanen.');
    }

    /** Hapus PERMANEN baris yang dicentang pada satu tabel. */
    public function destroyRows(Request $r, string $tabel)
    {
        $this->izin($r);
        $this->pastikanTabel($tabel);

        $r->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'string',
        ], ['ids.required' => 'Pilih minimal satu baris.']);
        $this->cekPassword($r);

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($r, $tabel) {
                $this->hapusBaris($tabel, array_values($r->ids));
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return redirect()->route('hapus-data.show', $tabel)
            ->with('sukses', count($r->ids) . ' baris berhasil dihapus permanen.');
    }
}