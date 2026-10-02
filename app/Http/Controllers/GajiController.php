<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HanyaAdmin;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanRepayment;
use App\Models\Payroll;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\GajiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GajiController extends Controller
{
    use HanyaAdmin;

    private function periode(?string $s): Carbon
    {
        if ($s && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $s)) {
            return Carbon::createFromFormat('Y-m-d', $s . '-01')->startOfDay();
        }

        return now()->startOfMonth();
    }

    private function gagal(string $kunci, string $pesan): never
    {
        throw ValidationException::withMessages([$kunci => $pesan]);
    }

    private function kunciDraft(Payroll $payroll): Payroll
    {
        $this->kunci('kasbon_' . $payroll->employee_id);
        $p = Payroll::with('employee')->whereKey($payroll->id)->lockForUpdate()->firstOrFail();
        if ($p->status !== 'DRAFT') {
            $this->gagal('status', 'Slip ini sudah diterbitkan atau dibatalkan, tidak bisa diubah.');
        }

        return $p;
    }

    public function index(Request $request)
    {
        $this->wajibAdmin();
        $per = $this->periode($request->query('bulan'));

        $payrolls = Payroll::where('period_year', $per->year)->where('period_month', $per->month)
            ->where('status', '!=', 'VOID')->get()->keyBy('employee_id');

        $karyawan = Employee::where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $payrolls->keys()->all()))
            ->orderBy('type')->orderBy('name')->get();

        $presensi = DB::table('attendances')
            ->whereBetween('work_date', [$per->toDateString(), $per->copy()->endOfMonth()->toDateString()])
            ->selectRaw("employee_id, sum(case status when 'HADIR' then 1 when 'SETENGAH' then 0.5 else 0 end) as hari, coalesce(sum(kg_cut),0) as kg")
            ->groupBy('employee_id')->get()->keyBy('employee_id');

        return view('gaji.index', [
            'per' => $per, 'bulan' => $per->format('Y-m'),
            'label' => $per->locale('id')->translatedFormat('F Y'),
            'karyawan' => $karyawan, 'payrolls' => $payrolls, 'presensi' => $presensi,
            'totalTerbit' => (float) $payrolls->whereIn('status', ['FINAL', 'PAID'])->sum('net_amount'),
            'jumlahDraft' => $payrolls->where('status', 'DRAFT')->count(),
            'adaSlip' => $payrolls->whereIn('status', ['FINAL', 'PAID'])->isNotEmpty(),
        ]);
    }

    public function generate(Request $request, GajiService $svc)
    {
        $this->wajibAdmin();
        $per = $this->periode($request->input('bulan'));
        $dibuat = $lewat = 0;

        DB::transaction(function () use ($per, $svc, &$dibuat, &$lewat) {
            $this->kunci('gaji_buat_' . $per->format('Ym'));
            $sudah = Payroll::where('period_year', $per->year)->where('period_month', $per->month)
                ->where('status', '!=', 'VOID')->pluck('employee_id')->flip();

            foreach (Employee::where('is_active', true)->get() as $e) {
                if ($sudah->has($e->id)) {
                    continue;
                }
                $h = $svc->hitungPresensi($e->id, $per->year, $per->month);
                if ($h['days'] <= 0 && $h['kg'] <= 0) {
                    $lewat++;
                    continue;
                }

                $p = new Payroll([
                    'employee_id' => $e->id, 'period_year' => $per->year, 'period_month' => $per->month,
                    'status' => 'DRAFT', 'created_by_id' => Auth::id(),
                ]);
                $p->bonus_amount = $svc->bonusKuota();
                $p->bonus_note   = $p->bonus_amount > 0 ? 'Bonus kuota' : null;
                $svc->terapkan($p, $e, $h);
                $p->save();
                $dibuat++;
            }

            AuditService::catat('GENERATE', 'payrolls', $per->format('Y-m'), null, ['dibuat' => $dibuat, 'dilewati_tanpa_presensi' => $lewat]);
        });

        return redirect()->route('gaji.index', ['bulan' => $per->format('Y-m')])
            ->with('status', "$dibuat draft gaji dibuat" . ($lewat ? ", $lewat orang dilewati karena belum ada presensi." : '.'));
    }

    public function show(Payroll $payroll, GajiService $svc)
    {
        $this->wajibAdmin();
        $payroll->load('employee', 'finalizedBy');

        $h = $svc->hitungPresensi($payroll->employee_id, $payroll->period_year, $payroll->period_month);
        $saldo = $payroll->employee->saldoKasbon();

        return view('gaji.show', [
            'payroll' => $payroll, 'saldo' => $saldo,
            'maks' => max(0, min($saldo, (float) $payroll->gross_amount - (float) $payroll->other_deduction_amount)),
            'basi' => abs($h['days'] - (float) $payroll->days_present) > 0.01 || abs($h['kg'] - (float) $payroll->kg_total) > 0.01,
            'labelBulan' => Carbon::create($payroll->period_year, $payroll->period_month, 1)->locale('id')->translatedFormat('F Y'),
        ]);
    }

    public function update(Request $request, Payroll $payroll, GajiService $svc)
    {
        $this->wajibAdmin();

        $d = $request->validate([
            'bonus_amount'           => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'bonus_note'             => ['nullable', 'string', 'max:150'],
            'other_deduction_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'other_deduction_note'   => ['nullable', 'string', 'max:150'],
            'loan_deduction_amount'  => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ]);

        DB::transaction(function () use ($payroll, $d, $svc) {
            $p = $this->kunciDraft($payroll);
            $p->bonus_amount = round((float) ($d['bonus_amount'] ?? 0), 2);
            $p->bonus_note = $d['bonus_note'] ?? null;
            $p->other_deduction_amount = round((float) ($d['other_deduction_amount'] ?? 0), 2);
            $p->other_deduction_note = $d['other_deduction_note'] ?? null;
            $p->loan_deduction_amount = round((float) ($d['loan_deduction_amount'] ?? 0), 2);

            $saldo = $p->employee->saldoKasbon();
            if ((float) $p->loan_deduction_amount > $saldo + 0.004) {
                $this->gagal('loan_deduction_amount', 'Potongan kasbon melebihi saldo kasbon (Rp ' . number_format($saldo, 0, ',', '.') . ').');
            }

            $svc->hitungTotal($p);
            if ((float) $p->net_amount < -0.004) {
                $this->gagal('loan_deduction_amount', 'Total potongan melebihi pendapatan kotor.');
            }
            $p->save();
        });

        return redirect()->route('gaji.show', $payroll)->with('status', 'Draft gaji disimpan.');
    }

    public function hitungUlang(Payroll $payroll, GajiService $svc)
    {
        $this->wajibAdmin();
        $dikurangi = false;

        DB::transaction(function () use ($payroll, $svc, &$dikurangi) {
            $p = $this->kunciDraft($payroll);
            $e = $p->employee;
            $svc->terapkan($p, $e, $svc->hitungPresensi($e->id, $p->period_year, $p->period_month));

            $maks = max(0, min($e->saldoKasbon(), (float) $p->gross_amount - (float) $p->other_deduction_amount));
            if ((float) $p->loan_deduction_amount > $maks + 0.004) {
                $p->loan_deduction_amount = round($maks, 2);
                $dikurangi = true;
                $svc->hitungTotal($p);
            }
            $p->save();
        });

        return redirect()->route('gaji.show', $payroll)
            ->with('status', 'Dihitung ulang dari presensi dan tarif terbaru.' . ($dikurangi ? ' Potongan kasbon disesuaikan.' : ''));
    }

    public function terbitkan(Payroll $payroll, GajiService $svc)
    {
        $this->wajibAdmin();

        DB::transaction(function () use ($payroll, $svc) {
            $p = $this->kunciDraft($payroll);
            $this->kunci('gaji_nomor');

            $h = $svc->hitungPresensi($p->employee_id, $p->period_year, $p->period_month);
            if (abs($h['days'] - (float) $p->days_present) > 0.01 || abs($h['kg'] - (float) $p->kg_total) > 0.01) {
                $this->gagal('status', 'Presensi berubah sejak draft dibuat. Klik "Hitung ulang" dulu sebelum menerbitkan.');
            }
            if (($h['belum_dibagi'] ?? 0) > 0) {
                $this->gagal('status', $h['belum_dibagi'] . ' hari hadir belum dibagi per kg. Tutup harinya di menu Presensi, lalu klik "Hitung ulang".');
            }
            if ((float) $p->loan_deduction_amount > $p->employee->saldoKasbon() + 0.004) {
                $this->gagal('status', 'Potongan kasbon melebihi saldo kasbon saat ini.');
            }
            if ((float) $p->net_amount < -0.004) {
                $this->gagal('status', 'Gaji bersih negatif. Periksa potongan.');
            }

            $now      = now();
            $prefix   = 'SG-' . sprintf('%04d%02d', $p->period_year, $p->period_month) . '-';
            $terakhir = Payroll::where('payroll_number', 'like', $prefix . '%')->max('payroll_number');
            $urut     = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

            $p->payroll_number  = $prefix . str_pad((string) $urut, 3, '0', STR_PAD_LEFT);
            $p->status          = 'FINAL';
            $p->finalized_at    = $now;
            $p->finalized_by_id = Auth::id();
            $p->save();

            if ((float) $p->loan_deduction_amount > 0) {
                LoanRepayment::create([
                    'employee_id' => $p->employee_id, 'payroll_id' => $p->id,
                    'amount' => $p->loan_deduction_amount, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            AuditService::catat('FINALIZE', 'payrolls', $p->id, null, ['nomor' => $p->payroll_number, 'net' => $p->net_amount]);
        });

        return redirect()->route('gaji.show', $payroll)->with('status', 'Slip gaji diterbitkan. Data terkunci.');
    }

    public function bayar(Payroll $payroll, GajiService $svc)
    {
        $this->wajibAdmin();

        DB::transaction(function () use ($payroll, $svc) {
            $this->kunci('kasbon_' . $payroll->employee_id);
            $p = Payroll::whereKey($payroll->id)->lockForUpdate()->firstOrFail();
            if ($p->status !== 'FINAL') {
                $this->gagal('status', 'Hanya slip berstatus Diterbitkan yang bisa ditandai dibayar.');
            }
            $p->status = 'PAID';
            $p->paid_at = now();
            $p->paid_by_id = Auth::id();
            $p->save();

            $svc->sinkronPengeluaran($p->period_year, $p->period_month);
            AuditService::catat('PAY', 'payrolls', $p->id, null, ['nomor' => $p->payroll_number, 'net' => $p->net_amount]);
        });

        return redirect()->route('gaji.show', $payroll)->with('status', 'Ditandai dibayar dan dicatat ke total pengeluaran bulan ini.');
    }

    public function batal(Request $request, Payroll $payroll, GajiService $svc)
    {
        $this->wajibAdmin();
        $d = $request->validate(['void_reason' => ['required', 'string', 'min:5', 'max:255']]);

        DB::transaction(function () use ($payroll, $d, $svc) {
            $this->kunci('kasbon_' . $payroll->employee_id);
            $p = Payroll::whereKey($payroll->id)->lockForUpdate()->firstOrFail();
            if (! in_array($p->status, ['FINAL', 'PAID'], true)) {
                $this->gagal('void_reason', 'Hanya slip Diterbitkan/Dibayar yang bisa dibatalkan.');
            }
            $wasPaid = $p->status === 'PAID';

            LoanRepayment::where('payroll_id', $p->id)->delete(); // kasbon kembali
            $p->status = 'VOID';
            $p->voided_at = now();
            $p->voided_by_id = Auth::id();
            $p->void_reason = $d['void_reason'];
            $p->save();

            if ($wasPaid) {
                $svc->sinkronPengeluaran($p->period_year, $p->period_month);
            }
            AuditService::catat('VOID', 'payrolls', $p->id, ['nomor' => $p->payroll_number, 'net' => $p->net_amount], null, $d['void_reason']);
        });

        return redirect()->route('gaji.show', $payroll)->with('status', 'Slip dibatalkan. Kasbon dikembalikan dan presensi terbuka lagi.');
    }

    public function hapus(Payroll $payroll)
    {
        $this->wajibAdmin();

        DB::transaction(function () use ($payroll) {
            $p = $this->kunciDraft($payroll);
            AuditService::catat('DELETE', 'payrolls', $p->id, ['employee_id' => $p->employee_id, 'periode' => $p->period_year . '-' . $p->period_month]);
            $p->delete();
        });

        return redirect()->route('gaji.index', ['bulan' => sprintf('%04d-%02d', $payroll->period_year, $payroll->period_month)])
            ->with('status', 'Draft dihapus.');
    }

    /* ---------- Slip ---------- */

    private function dataSlip(Payroll $p, GajiService $svc): array
    {
        $p->loadMissing('employee', 'finalizedBy');
        $set = Setting::semua();
        $e = $p->employee;

        if ($p->finalized_at) {
            $t = $p->finalized_at;
            $sisa = (float) EmployeeLoan::where('employee_id', $e->id)->where('created_at', '<=', $t)->sum('amount')
                  - (float) LoanRepayment::where('employee_id', $e->id)->where('created_at', '<=', $t)->sum('amount');
        } else {
            $sisa = $e->saldoKasbon() - (float) $p->loan_deduction_amount;
        }

        $path = (string) ($set['slip_signature_path'] ?? '');

        return [
            'p' => $p, 'set' => $set,
            'labelBulan' => Carbon::create($p->period_year, $p->period_month, 1)->locale('id')->translatedFormat('F Y'),
            'resmi' => in_array($p->status, ['FINAL', 'PAID'], true),
            'kode' => $p->payroll_number ? $svc->kodeVerifikasi($p) : '-',
            'terbilang' => ucfirst(GajiService::terbilang((int) round((float) $p->net_amount))),
            'sisaKasbon' => round($sisa, 2),
            'ttd' => $path !== '' && Storage::disk('local')->exists($path),
        ];
    }

    public function slip(Payroll $payroll, GajiService $svc)
    {
        $this->wajibAdmin();

        return view('gaji.slip', ['slips' => collect([$this->dataSlip($payroll, $svc)])]);
    }

    public function slipMassal(Request $request, GajiService $svc)
    {
        $this->wajibAdmin();
        $per = $this->periode($request->query('bulan'));

        $list = Payroll::with('employee', 'finalizedBy')->where('period_year', $per->year)->where('period_month', $per->month)
            ->whereIn('status', ['FINAL', 'PAID'])->get()->sortBy(fn ($x) => $x->employee->name)->values();

        abort_if($list->isEmpty(), 404, 'Belum ada slip yang diterbitkan pada bulan ini.');

        return view('gaji.slip', ['slips' => $list->map(fn ($x) => $this->dataSlip($x, $svc))]);
    }

    /* ---------- Pengaturan slip (nama dan tanda tangan manager) ---------- */

    public function pengaturan()
    {
        $this->wajibAdmin();

        return view('gaji.pengaturan', ['s' => Setting::semua()]);
    }

    public function simpanPengaturan(Request $request)
    {
        $this->wajibAdmin();

        $d = $request->validate([
            'slip_manager_name'  => ['required', 'string', 'max:100'],
            'slip_manager_title' => ['required', 'string', 'max:100'],
            'slip_note'          => ['nullable', 'string', 'max:300'],
            'ttd'                => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:512'],
        ]);

        $lama = Setting::semua();
        $simpan = [
            'slip_manager_name'  => trim($d['slip_manager_name']),
            'slip_manager_title' => trim($d['slip_manager_title']),
            'slip_note'          => trim((string) ($d['slip_note'] ?? '')),
        ];

        $hapus = null;
        if ($request->hasFile('ttd')) {
            $simpan['slip_signature_path'] = $request->file('ttd')->store('ttd', 'local'); // privat, tidak publik
            $hapus = (string) ($lama['slip_signature_path'] ?? '');
        } elseif ($request->boolean('hapus_ttd')) {
            $simpan['slip_signature_path'] = '';
            $hapus = (string) ($lama['slip_signature_path'] ?? '');
        }

        Setting::simpan($simpan);
        if ($hapus) {
            Storage::disk('local')->delete($hapus);
        }
        AuditService::catat('UPDATE', 'settings', 'slip', null, ['manager' => $simpan['slip_manager_name'], 'ttd_diubah' => $hapus !== null]);

        return redirect()->route('gaji.pengaturan')->with('status', 'Pengaturan slip tersimpan.');
    }

    public function ttd()
    {
        $this->wajibAdmin();
        $path = (string) (Setting::semua()['slip_signature_path'] ?? '');
        abort_if($path === '' || ! Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }
}