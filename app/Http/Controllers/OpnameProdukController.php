<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\StockAdjustment;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpnameProdukController extends Controller
{
    public function index(StokService $stok)
    {
        $today = Carbon::today();

        $riwayat = StockAdjustment::with(['product:id,name', 'adjustedBy:id,name'])
            ->orderByDesc('adjustment_date')->orderByDesc('created_at')
            ->limit(40)->get();

        return view('opname-produk.index', [
            'produk'     => $stok->produk($today, $today),
            'riwayat'    => $riwayat,
            'bolehTulis' => Auth::user()->canWrite('opname_hasil'),
        ]);
    }

    public function store(Request $request, StokService $stok)
    {
        abort_unless(Auth::user()->canWrite('opname_hasil'), 403);

        $data = $request->validate([
            'adjustment_date' => ['required', 'date', 'before_or_equal:today'],
            'fisik'           => ['required', 'array'],
            'fisik.*'         => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'alasan'          => ['nullable', 'array'],
            'alasan.*'        => ['nullable', 'string', 'max:255'],
        ]);

        $diisi = collect($data['fisik'])->filter(fn ($v) => $v !== null && $v !== '');

        if ($diisi->isEmpty()) {
            throw ValidationException::withMessages(['fisik' => 'Isi stok fisik minimal satu produk.']);
        }

        $jumlah = DB::transaction(function () use ($data, $diisi, $stok) {
            $stok->kunci('opname_produk');

            $today = Carbon::today();
            $sistem = $stok->produk($today, $today)->keyBy('id');
            $tanggal = Carbon::parse($data['adjustment_date']);
            $ymd = $tanggal->format('Ymd');

            $prefix = 'OPN-' . $ymd . '-';
            $akhir = StockAdjustment::where('adjustment_number', 'like', $prefix . '%')->max('adjustment_number');
            $no = $akhir ? ((int) substr($akhir, strlen($prefix))) : 0;

            $n = 0;

            foreach ($diisi as $productId => $fisik) {
                $p = $sistem->get($productId);

                if (! $p) {
                    throw ValidationException::withMessages(['fisik' => 'Ada produk yang tidak valid. Muat ulang halaman.']);
                }

                $fisik = round((float) $fisik, 2);
                $selisih = round($fisik - $p->stok, 2);
                $alasan = trim((string) ($data['alasan'][$productId] ?? ''));

                if (abs($selisih) > 0.004 && mb_strlen($alasan) < 5) {
                    throw ValidationException::withMessages([
                        'alasan' => "Produk {$p->name}: ada selisih, isi alasan (minimal 5 huruf).",
                    ]);
                }

                $no++;
                $rec = StockAdjustment::create([
                    'adjustment_number' => $prefix . str_pad((string) $no, 4, '0', STR_PAD_LEFT),
                    'adjustment_date'   => $tanggal->toDateString(),
                    'product_id'        => $p->id,
                    'system_qty'        => $p->stok,
                    'physical_qty'      => $fisik,
                    'difference_qty'    => $selisih,
                    'reason'            => $alasan !== '' ? $alasan : 'Sesuai',
                    'adjusted_by_id'    => Auth::id(),
                ]);

                AuditLog::create([
                    'action'     => 'CREATE',
                    'table_name' => 'stock_adjustments',
                    'record_id'  => $rec->id,
                    'new_values' => $rec->getAttributes(),
                    'user_id'    => Auth::id(),
                ]);

                $n++;
            }

            return $n;
        });

        return redirect()->route('opnameproduk.index')
            ->with('status', "Opname tersimpan untuk {$jumlah} produk.");
    }
}