<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\GajiService;
use App\Services\PoolService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Tahap 1 (pembagian per kg) dan tahap 2 (absen masuk/pulang) dengan data dummy.
 * Semua data dibatalkan otomatis (DatabaseTransactions).
 */
class Tahap3PoolAbsenTest extends TestCase
{
    use DatabaseTransactions;

    /** Tanggal lampau (harus <= hari ini agar lolos validasi lembar presensi). */
    private const TGL = '2026-01-14';

    // ---------- Pembantu ----------

    private function user(string $role): User
    {
        return User::create([
            'username'  => 'uji_' . strtolower(substr($role, 0, 5)) . '_' . Str::lower(Str::random(6)),
            'name'      => 'Uji ' . $role,
            'email'     => null,
            'password'  => 'rahasia12345',
            'role'      => $role,
            'is_active' => true,
        ]);
    }

    /** Sisip baris ke tabel; kolom yang tidak ada di tabel dibuang otomatis. */
    private function sisip(string $tabel, array $row): string
    {
        $row['id'] ??= (string) Str::uuid();
        $row += ['created_at' => now(), 'updated_at' => now()];
        $row = array_filter($row, fn ($k) => Schema::hasColumn($tabel, $k), ARRAY_FILTER_USE_KEY);
        DB::table($tabel)->insert($row);

        return $row['id'];
    }

    private function produk(): Product
    {
        return Product::create([
            'code'         => 'TST' . Str::upper(Str::random(8)),
            'name'         => 'Produk Uji ' . Str::random(4),
            'primary_unit' => 'KG',
            'is_active'    => true,
            'is_live_bird' => false,
        ]);
    }

    private function pegawai(string $tipe = 'KARYAWAN', float $harian = 0): Employee
    {
        return Employee::create([
            'code' => 'E-' . Str::upper(Str::random(8)), 'name' => 'Uji ' . Str::random(5),
            'type' => $tipe, 'daily_rate' => $harian, 'per_kg_rate' => 0, 'is_active' => true,
        ]);
    }

    private function akunPegawai(Employee $e): User
    {
        $u = $this->user('PEGAWAI');
        $u->employee_id = $e->id;
        $u->save();

        return $u;
    }

    private function aturan(): void
    {
        Setting::simpan([
            'pool_tarif_per_kg' => '1000', 'pool_bobot_karyawan' => '1', 'pool_bobot_relawan' => '1',
            'bonus_kuota_bulanan' => '50000', 'absen_min_jam_hadir' => '4',
        ]);
    }

    /** Pengaturan baku + pastikan tanggal uji belum dipakai data lain. Mengembalikan admin uji. */
    private function siapkanHari(): User
    {
        $this->aturan();
        $this->assertEqualsWithDelta(0.0, app(PoolService::class)->hasilKg(self::TGL), 0.001, 'Tanggal uji sudah punya hasil produksi di bumdesa_test');
        $this->assertSame(0, Attendance::where('work_date', self::TGL)->count(), 'Tanggal uji sudah punya presensi di bumdesa_test');
        $this->assertSame(0, AttendanceDay::where('work_date', self::TGL)->count(), 'Tanggal uji sudah ditutup di bumdesa_test');

        return $this->user('ADMIN');
    }

    private function hasilProduksi(User $admin, string $tgl, float ...$kg): void
    {
        $batch = $this->sisip('production_batches', [
            'batch_number' => 'TST-' . Str::upper(Str::random(8)), 'production_date' => $tgl,
            'input_birds' => 400, 'input_weight_kg' => 1500, 'created_by_id' => $admin->id,
        ]);
        $lokasi = $this->sisip('storage_locations', [
            'code' => 'TST' . Str::upper(Str::random(6)), 'name' => 'Uji', 'is_active' => true,
        ]);
        foreach ($kg as $berat) {
            $this->sisip('weighing_records', [
                'receipt_number' => 'TST-' . Str::upper(Str::random(10)), 'batch_id' => $batch,
                'product_id' => $this->produk()->id, 'storage_location_id' => $lokasi,
                'weight_kg' => $berat, 'operator_id' => $admin->id,
            ]);
        }
    }

    private function hadirkan(Employee $e, User $admin, string $status = 'HADIR', string $tgl = self::TGL): void
    {
        Attendance::create([
            'employee_id' => $e->id, 'work_date' => $tgl, 'status' => $status, 'recorded_by_id' => $admin->id,
        ]);
    }

    /** Beberapa pegawai langsung hadir. */
    private function banyakHadir(int $n, User $admin): array
    {
        $orang = [];
        for ($i = 0; $i < $n; $i++) {
            $orang[] = $e = $this->pegawai();
            $this->hadirkan($e, $admin);
        }

        return $orang;
    }

    private function jam(int $h, int $m = 0): void
    {
        $this->travelTo(now()->startOfDay()->setTime($h, $m));
    }

    private function absenHariIni(Employee $e): ?Attendance
    {
        return Attendance::where('employee_id', $e->id)->where('work_date', now()->toDateString())->first();
    }

    // ---------- 0. Skema ----------

    public function test_skema_database_tahap_1_dan_2_sudah_termigrasi(): void
    {
        $this->assertTrue(Schema::hasTable('attendance_days'), 'Jalankan: php artisan migrate --env=testing');
        foreach (['poin', 'kg_bagian', 'bagian_rp', 'clock_in_at', 'clock_out_at'] as $c) {
            $this->assertTrue(Schema::hasColumn('attendances', $c), "Kolom attendances.$c belum ada");
        }
        $this->assertTrue(Schema::hasColumn('users', 'employee_id'));

        $kol = DB::selectOne("select is_nullable from information_schema.columns where table_schema = current_schema() and table_name = 'attendances' and column_name = 'status'");
        $this->assertSame('YES', $kol->is_nullable, 'attendances.status harus boleh kosong');
    }

    // ---------- 1. Pembagian per kg ----------

    public function test_13_orang_hadir_1000_kg_dibagi_tepat_satu_juta(): void
    {
        $admin = $this->siapkanHari();
        $this->banyakHadir(13, $admin);
        $this->hasilProduksi($admin, self::TGL, 600, 400);

        $h = app(PoolService::class)->tutup(self::TGL, (string) $admin->id);

        $this->assertSame(13, $h['peserta']);
        $this->assertEqualsWithDelta(1000.0, $h['pool_kg'], 0.001);
        $this->assertSame(1000000, $h['pool_rp']);

        $rp = collect($h['bagian'])->pluck('rp')->map(fn ($x) => (int) $x);
        $this->assertSame(1000000, $rp->sum(), 'Total pembagian harus tepat sama dengan pool');
        $this->assertSame(76923, $rp->min());
        $this->assertSame(76924, $rp->max());
        $this->assertSame(1, $rp->filter(fn ($x) => $x === 76924)->count(), 'Sisa 1 rupiah hanya untuk satu orang');

        $this->assertEqualsWithDelta(1000000.0, (float) Attendance::where('work_date', self::TGL)->sum('bagian_rp'), 0.01);
        $hari = AttendanceDay::where('work_date', self::TGL)->first();
        $this->assertNotNull($hari->closed_at);
        $this->assertSame(13, (int) $hari->participants);
    }

    public function test_setengah_hari_mendapat_separuh_bagian(): void
    {
        $admin = $this->siapkanHari();
        $hadir = $this->banyakHadir(12, $admin);
        $setengah = $this->pegawai();
        $this->hadirkan($setengah, $admin, 'SETENGAH');
        $this->hasilProduksi($admin, self::TGL, 1000);

        $h = app(PoolService::class)->tutup(self::TGL, (string) $admin->id);

        $this->assertSame(40000, (int) $h['bagian'][$setengah->id]['rp']);
        foreach ($hadir as $e) {
            $this->assertSame(80000, (int) $h['bagian'][$e->id]['rp']);
        }
        $this->assertSame(1000000, (int) array_sum(array_column($h['bagian'], 'rp')));
    }

    public function test_tutup_ditolak_bila_hasil_produksi_belum_diinput(): void
    {
        $admin = $this->siapkanHari();
        $this->banyakHadir(2, $admin);

        try {
            app(PoolService::class)->tutup(self::TGL, (string) $admin->id);
            $this->fail('Tutup hari seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('belum diinput', $e->errors()['tanggal'][0]);
        }
        $this->assertSame(0, AttendanceDay::where('work_date', self::TGL)->count());
    }

    public function test_tutup_ditolak_bila_ada_yang_belum_absen_pulang(): void
    {
        $admin = $this->siapkanHari();
        $this->banyakHadir(2, $admin);
        $lupa = $this->pegawai();
        Attendance::create([
            'employee_id' => $lupa->id, 'work_date' => self::TGL, 'status' => null,
            'clock_in_at' => now(), 'recorded_by_id' => $admin->id,
        ]);
        $this->hasilProduksi($admin, self::TGL, 100);

        try {
            app(PoolService::class)->tutup(self::TGL, (string) $admin->id);
            $this->fail('Tutup hari seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Belum absen pulang', $e->errors()['tanggal'][0]);
        }
        $this->assertSame(0, AttendanceDay::where('work_date', self::TGL)->count());
    }

    public function test_hari_terkunci_bila_ada_peserta_dengan_slip_final(): void
    {
        $admin = $this->siapkanHari();
        $orang = $this->banyakHadir(3, $admin);
        $this->hasilProduksi($admin, self::TGL, 300);
        app(PoolService::class)->tutup(self::TGL, (string) $admin->id);

        $this->sisip('payrolls', [
            'employee_id' => $orang[0]->id, 'period_year' => 2026, 'period_month' => 1,
            'status' => 'FINAL', 'created_by_id' => $admin->id,
        ]);

        $this->actingAs($admin)->post('/presensi/tutup', ['tanggal' => self::TGL])->assertSessionHasErrors('tanggal');

        $this->actingAs($admin)->post('/presensi', [
            'tanggal' => self::TGL, 'rows' => [$orang[1]->id => ['status' => 'IZIN']],
        ])->assertSessionHasErrors('tanggal');

        $this->assertSame('HADIR', Attendance::where('employee_id', $orang[1]->id)->where('work_date', self::TGL)->value('status'));
    }

    public function test_koreksi_admin_pada_hari_tertutup_menghitung_ulang_dan_totalnya_tetap_pas(): void
    {
        $admin = $this->siapkanHari();
        $orang = $this->banyakHadir(12, $admin);
        $this->hasilProduksi($admin, self::TGL, 1000);
        app(PoolService::class)->tutup(self::TGL, (string) $admin->id);

        $this->actingAs($admin)->post('/presensi', [
            'tanggal' => self::TGL, 'rows' => [$orang[0]->id => ['status' => 'IZIN']],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(1000000.0, (float) Attendance::where('work_date', self::TGL)->sum('bagian_rp'), 0.01);
        $this->assertEqualsWithDelta(0.0, (float) Attendance::where('employee_id', $orang[0]->id)->where('work_date', self::TGL)->value('bagian_rp'), 0.001);
        $this->assertNotNull(AttendanceDay::where('work_date', self::TGL)->value('closed_at'));
    }

    // ---------- 2. Hitungan slip ----------

    public function test_slip_memakai_bagian_per_kg_dan_bukan_bonus_rendemen_lama(): void
    {
        $admin = $this->siapkanHari();
        $relawan = $this->pegawai('RELAWAN', 50000);
        $this->hadirkan($relawan, $admin);
        $this->banyakHadir(12, $admin);
        $this->hasilProduksi($admin, self::TGL, 1000);
        app(PoolService::class)->tutup(self::TGL, (string) $admin->id);

        $svc = app(GajiService::class);
        $this->assertEqualsWithDelta(50000.0, $svc->bonusKuota(), 0.001);

        $h = $svc->hitungPresensi($relawan->id, 2026, 1);
        $this->assertSame(0, $h['belum_dibagi']);
        $this->assertEqualsWithDelta(1.0, $h['days'], 0.001);
        $this->assertGreaterThanOrEqual(76923.0, $h['rp']);
        $this->assertLessThanOrEqual(76924.0, $h['rp']);

        $p = new Payroll();
        $p->bonus_amount = 50000;
        $svc->terapkan($p, $relawan, $h);

        $this->assertEqualsWithDelta($h['rp'], (float) $p->kg_amount, 0.001, 'Jumlah per kg harus sama dengan bagian hari');
        $this->assertEqualsWithDelta(50000.0, (float) $p->base_amount, 0.001, '1 hari x upah harian');
        $this->assertEqualsWithDelta(0.0, (float) $p->rendemen_bonus_amount, 0.001, 'Bonus rendemen lama tidak boleh dihitung lagi');
        $this->assertEqualsWithDelta(50000 + $h['rp'] + 50000, (float) $p->gross_amount, 0.01);
        $this->assertEqualsWithDelta((float) $p->gross_amount, (float) $p->net_amount, 0.01);
        // kg dibulatkan 2 desimal di slip, jadi tarif efektif bisa 1.000,04; jumlah rupiah tetap tepat
        $this->assertEqualsWithDelta(1000.0, (float) $p->per_kg_rate, 0.1);

        // Hari hadir yang belum dibagi terdeteksi (dasar pengaman terbit)
        $this->hadirkan($relawan, $admin, 'HADIR', '2026-01-15');
        $h2 = $svc->hitungPresensi($relawan->id, 2026, 1);
        $this->assertSame(1, $h2['belum_dibagi']);
        $this->assertEqualsWithDelta(2.0, $h2['days'], 0.001);
        $this->assertEqualsWithDelta($h['rp'], $h2['rp'], 0.001, 'Hari yang belum dibagi tidak boleh menambah rupiah');
    }

    // ---------- 3. Absen masuk dan pulang ----------

    public function test_pulang_setelah_cukup_jam_tercatat_hadir(): void
    {
        $this->aturan();
        $e = $this->pegawai();
        $u = $this->akunPegawai($e);

        $this->jam(7);
        $this->actingAs($u)->post('/absen/masuk')->assertSessionHasNoErrors();
        $a = $this->absenHariIni($e);
        $this->assertNotNull($a->clock_in_at);
        $this->assertNull($a->status, 'Status baru terisi saat pulang');

        $this->jam(12, 30);
        $this->actingAs($u)->post('/absen/pulang')->assertSessionHasNoErrors();
        $a->refresh();
        $this->assertSame('HADIR', $a->status);
        $this->assertNotNull($a->clock_out_at);
    }

    public function test_pulang_kurang_dari_jam_minimal_tercatat_setengah_hari(): void
    {
        $this->aturan();
        $e = $this->pegawai();
        $u = $this->akunPegawai($e);

        $this->jam(7);
        $this->actingAs($u)->post('/absen/masuk')->assertSessionHasNoErrors();
        $this->jam(9);
        $this->actingAs($u)->post('/absen/pulang')->assertSessionHasNoErrors();

        $this->assertSame('SETENGAH', $this->absenHariIni($e)->status);
    }

    public function test_tepat_jam_minimal_dihitung_hadir(): void
    {
        $this->aturan();
        $e = $this->pegawai();
        $u = $this->akunPegawai($e);

        $this->jam(7);
        $this->actingAs($u)->post('/absen/masuk')->assertSessionHasNoErrors();
        $this->jam(11);
        $this->actingAs($u)->post('/absen/pulang')->assertSessionHasNoErrors();

        $this->assertSame('HADIR', $this->absenHariIni($e)->status);
    }

    public function test_absen_ganda_dan_urutan_salah_ditolak(): void
    {
        $this->aturan();
        $e = $this->pegawai();
        $u = $this->akunPegawai($e);

        $this->jam(7);
        $this->actingAs($u)->post('/absen/pulang')->assertSessionHasErrors('absen');   // pulang sebelum masuk

        $this->actingAs($u)->post('/absen/masuk')->assertSessionHasNoErrors();
        $this->actingAs($u)->post('/absen/masuk')->assertSessionHasErrors('absen');    // masuk ganda
        $this->assertSame(1, Attendance::where('employee_id', $e->id)->where('work_date', now()->toDateString())->count());

        $this->jam(12);
        $this->actingAs($u)->post('/absen/pulang')->assertSessionHasNoErrors();
        $keluar = Attendance::where('employee_id', $e->id)->where('work_date', now()->toDateString())->value('clock_out_at');

        $this->jam(13);
        $this->actingAs($u)->post('/absen/pulang')->assertSessionHasErrors('absen');   // pulang ganda
        $this->assertEquals($keluar, Attendance::where('employee_id', $e->id)->where('work_date', now()->toDateString())->value('clock_out_at'));
    }

    public function test_pegawai_tidak_bisa_absen_pada_hari_dibagi_atau_slip_terbit(): void
    {
        $this->aturan();
        $admin = $this->user('ADMIN');
        $e = $this->pegawai();
        $u = $this->akunPegawai($e);
        $this->jam(8);
        $hariIni = now()->toDateString();

        AttendanceDay::updateOrCreate(['work_date' => $hariIni], ['closed_at' => now(), 'closed_by_id' => $admin->id]);
        $this->actingAs($u)->post('/absen/masuk')->assertSessionHasErrors('absen');
        $this->assertNull($this->absenHariIni($e));

        AttendanceDay::where('work_date', $hariIni)->delete();
        $this->sisip('payrolls', [
            'employee_id' => $e->id, 'period_year' => now()->year, 'period_month' => now()->month,
            'status' => 'FINAL', 'created_by_id' => $admin->id,
        ]);
        $this->actingAs($u)->post('/absen/masuk')->assertSessionHasErrors('absen');
        $this->assertNull($this->absenHariIni($e));
    }

    // ---------- 4. Akses akun Pegawai ----------

    public function test_akun_pegawai_hanya_bisa_absen(): void
    {
        $u = $this->akunPegawai($this->pegawai());

        $this->actingAs($u)->get('/absen')->assertOk();
        $this->actingAs($u)->get('/akun/password')->assertOk();
        $this->actingAs($u)->get('/dashboard')->assertRedirect(route('absen.index'));

        foreach (['/gaji', '/gaji/bonus', '/karyawan', '/presensi', '/pengguna', '/pengaturan', '/pengeluaran',
                  '/kasir', '/kasir/riwayat', '/piutang', '/pembelian', '/produksi', '/opname-produk',
                  '/laporan', '/produk', '/pelanggan'] as $url) {
            $this->actingAs($u)->get($url)->assertForbidden();
        }
        foreach (['/presensi', '/presensi/tutup', '/gaji/buat'] as $url) {
            $this->actingAs($u)->post($url, ['x' => 1])->assertForbidden();
        }
    }

    public function test_akun_pegawai_tanpa_hubungan_atau_nonaktif_tidak_bisa_absen(): void
    {
        $u = $this->user('PEGAWAI');
        $this->actingAs($u)->get('/absen')->assertOk()->assertSee('belum terhubung');
        $this->actingAs($u)->post('/absen/masuk')->assertForbidden();

        $e = $this->pegawai();
        $u2 = $this->akunPegawai($e);
        $e->update(['is_active' => false]);
        $this->actingAs($u2)->post('/absen/masuk')->assertForbidden();

        $this->actingAs($this->user('KASIR'))->get('/absen')->assertForbidden();
    }

    public function test_admin_menghubungkan_akun_ke_satu_pegawai_saja(): void
    {
        $admin = $this->user('ADMIN');
        $e = $this->pegawai();
        $a = $this->user('PEGAWAI');
        $b = $this->user('PEGAWAI');
        $kasir = $this->user('KASIR');

        $this->actingAs($admin)->put(route('pengguna.pegawai', $a), ['employee_id' => $e->id])->assertSessionHasNoErrors();
        $this->assertSame($e->id, $a->fresh()->employee_id);

        $this->actingAs($admin)->put(route('pengguna.pegawai', $b), ['employee_id' => $e->id])->assertSessionHasErrors('employee_id');
        $this->assertNull($b->fresh()->employee_id);

        $this->actingAs($admin)->put(route('pengguna.pegawai', $kasir), ['employee_id' => $this->pegawai()->id])->assertSessionHasErrors('employee_id');
    }
}