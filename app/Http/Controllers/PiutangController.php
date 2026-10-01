<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\PiutangService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PiutangController extends Controller
{
    public function index(Request $request, PiutangService $piutang)
    {
        $ringkasan = $piutang->ringkasanSemua();
        $customers = Customer::whereIn('id', array_keys($ringkasan))->get()->keyBy('id');

        $semua = collect($ringkasan)
            ->filter(fn ($r, $id) => isset($customers[$id]))
            ->map(fn ($r, $id) => (object) array_merge($r, ['customer' => $customers[$id]]))
            ->values();

        $totalHutang        = $semua->sum('saldo');
        $totalTerlambat     = $semua->sum('terlambat_jumlah');
        $pelangganTerlambat = $semua->where('terlambat_hari', '>', 0)->count();

        $filter = $request->query('filter') === 'terlambat' ? 'terlambat' : 'semua';

        // Yang paling lama telat di atas; kalau sama, hutang terbesar dulu
        $daftar = ($filter === 'terlambat' ? $semua->where('terlambat_hari', '>', 0) : $semua)
            ->sortBy([['terlambat_hari', 'desc'], ['saldo', 'desc']])
            ->values();

        return view('piutang.index', compact('daftar', 'totalHutang', 'totalTerlambat', 'pelangganTerlambat', 'filter'));
    }

    public function show(Customer $customer, PiutangService $piutang)
    {
        $saldo   = $piutang->saldo($customer->id);
        $hariIni = Carbon::today();

        $notaTerbuka = $piutang->notaTerbuka($customer->id)->map(function ($n) use ($hariIni) {
            $due = Carbon::parse($n->jatuh_tempo)->startOfDay();
            $n->terlambat_hari = $due->lt($hariIni) ? (int) abs($due->diffInDays($hariIni)) : 0;

            return $n;
        });

        // Termasuk pembayaran yang sudah dibatalkan (voided_at terisi); view yang menandainya
        $riwayat = Payment::whereIn('sale_id', Sale::where('customer_id', $customer->id)->select('id'))
            ->with('sale:id,sale_number')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return view('piutang.show', compact('customer', 'saldo', 'notaTerbuka', 'riwayat'));
    }

    public function bayar(Request $request, Customer $customer, PiutangService $piutang)
    {
        // Manager hanya membaca (sesuai PRD). Admin dan kasir boleh mencatat pelunasan.
        abort_if(strtoupper((string) Auth::user()->role) === 'MANAGER', 403, 'Manager tidak dapat mencatat pembayaran.');

        $data = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_method'   => ['required', 'in:CASH,TRANSFER'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $jumlah = round((float) $data['amount'], 2);

        $sisa = DB::transaction(function () use ($customer, $piutang, $data, $jumlah) {
            // Kunci per pelanggan: cek saldo dan pencatatan terjadi tanpa disela transaksi lain
            DB::select('select pg_advisory_xact_lock(?)', [(int) crc32('cust_' . $customer->id)]);

            $saldo = $piutang->saldo($customer->id);

            if ($saldo <= 0.004) {
                throw ValidationException::withMessages(['amount' => 'Pelanggan ini tidak punya hutang.']);
            }

            if ($jumlah > $saldo + 0.004) {
                throw ValidationException::withMessages([
                    'amount' => 'Jumlah melebihi sisa hutang (Rp ' . number_format($saldo, 0, ',', '.') . ').',
                ]);
            }

            $teralokasi = $piutang->bayar(
                $customer->id,
                $jumlah,
                $data['payment_method'],
                $data['reference_number'] ?? null,
                Auth::id(),
                null
            );

            // Pengaman: semua uang harus tercatat. Kalau tidak, batalkan seluruh transaksi.
            if (abs($teralokasi - $jumlah) > 0.01) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran tidak dapat dialokasikan penuh. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            return $piutang->saldo($customer->id);
        });

        return redirect()
            ->route('piutang.show', $customer)
            ->with('status', 'Pembayaran Rp ' . number_format($jumlah, 0, ',', '.') . ' tercatat. Sisa hutang: Rp ' . number_format($sisa, 0, ',', '.'));
    }
}