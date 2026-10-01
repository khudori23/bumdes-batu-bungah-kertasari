<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Services\GajiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PengaturanBonusTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'bumdesa_test') {
            throw new \RuntimeException('Test harus memakai database bumdesa_test. Dibatalkan agar data asli aman.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET session_replication_role = replica');
    }

    private function login(string $role = User::ROLE_ADMIN): void
    {
        $this->actingAs(User::create([
            'username' => 'u' . Str::lower(Str::random(8)), 'name' => 'Dummy',
            'password' => 'rahasia12345', 'role' => $role, 'is_active' => true,
        ]));
    }

    private function simpan(array $d)
    {
        return $this->put(route('gaji.bonus.simpan'), $d);
    }

    public function test_halaman_menampilkan_nilai_awal(): void
    {
        $this->login();
        $this->get(route('gaji.bonus'))->assertOk()->assertSee('Pengaturan Bonus Rendemen')->assertSee('1000');
    }

    public function test_admin_bisa_menyimpan(): void
    {
        $this->login();
        $this->simpan(['bonus_rendemen_per_kg' => 2500, 'bonus_rendemen_min' => 70])
            ->assertRedirect(route('gaji.bonus'))->assertSessionHasNoErrors();

        $s = Setting::semua();
        $this->assertEqualsWithDelta(2500.0, (float) $s['bonus_rendemen_per_kg'], 0.001);
        $this->assertEqualsWithDelta(70.0, (float) $s['bonus_rendemen_min'], 0.001);
    }

    public function test_nilai_tidak_valid_ditolak(): void
    {
        $this->login();
        $this->simpan(['bonus_rendemen_per_kg' => -1, 'bonus_rendemen_min' => 70])->assertSessionHasErrors('bonus_rendemen_per_kg');
        $this->simpan(['bonus_rendemen_per_kg' => 1000, 'bonus_rendemen_min' => 150])->assertSessionHasErrors('bonus_rendemen_min');
        $this->simpan(['bonus_rendemen_per_kg' => 'abc', 'bonus_rendemen_min' => 0])->assertSessionHasErrors('bonus_rendemen_per_kg');
        $this->simpan([])->assertSessionHasErrors(['bonus_rendemen_per_kg', 'bonus_rendemen_min']);
    }

    public function test_non_admin_ditolak(): void
    {
        foreach ([User::ROLE_KASIR, User::ROLE_MANAGER, User::ROLE_PENGAWAS] as $role) {
            $this->login($role);
            $this->get(route('gaji.bonus'))->assertForbidden();
            $this->simpan(['bonus_rendemen_per_kg' => 5000, 'bonus_rendemen_min' => 0])->assertForbidden();
        }
        $this->assertArrayNotHasKey('bonus_rendemen_per_kg', Setting::query()->pluck('value', 'key')->all());
    }

    public function test_nilai_baru_dipakai_perhitungan_bonus(): void
    {
        $this->login();
        $this->simpan(['bonus_rendemen_per_kg' => 2000, 'bonus_rendemen_min' => 70])->assertSessionHasNoErrors();

        $e = Employee::create([
            'code' => 'E-' . Str::upper(Str::random(8)), 'name' => 'Relawan', 'type' => 'RELAWAN',
            'daily_rate' => 0, 'per_kg_rate' => 1500, 'is_active' => true,
        ]);

        foreach ([['2026-10-05', 72], ['2026-10-06', 60]] as [$tgl, $hasil]) {
            $id = (string) Str::uuid();
            DB::table('production_batches')->insert([
                'id' => $id, 'batch_number' => 'B-' . Str::random(8), 'production_date' => $tgl,
                'input_birds' => 100, 'input_weight_kg' => 100, 'status' => 'DONE',
                'created_by_id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $w = [
                'id' => (string) Str::uuid(), 'receipt_number' => 'W-' . Str::random(8), 'batch_id' => $id,
                'product_id' => (string) Str::uuid(), 'weight_kg' => $hasil,
                'operator_id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now(),
            ];
            if (Schema::hasColumn('weighing_records', 'storage_location_id')) {
                $w['storage_location_id'] = (string) Str::uuid();
            }
            DB::table('weighing_records')->insert($w);
            Attendance::create([
                'recorded_by_id' => (string) Str::uuid(), 'employee_id' => $e->id,
                'work_date' => $tgl, 'status' => 'HADIR', 'kg_cut' => 10,
            ]);
        }

        $b = (new GajiService())->hitungBonusRendemen($e, 2026, 10);
        $this->assertEqualsWithDelta(10.0, $b['kg'], 0.001);          // hanya hari 72% yang lolos
        $this->assertEqualsWithDelta(20000.0, $b['amount'], 0.001);   // 10 kg x Rp 2.000
    }

    public function test_tarif_nol_menonaktifkan_bonus(): void
    {
        $this->login();
        $this->simpan(['bonus_rendemen_per_kg' => 0, 'bonus_rendemen_min' => 0])->assertSessionHasNoErrors();

        $e = Employee::create([
            'code' => 'E-' . Str::upper(Str::random(8)), 'name' => 'Relawan', 'type' => 'RELAWAN',
            'daily_rate' => 0, 'per_kg_rate' => 1500, 'is_active' => true,
        ]);
        $this->assertEqualsWithDelta(0.0, (new GajiService())->hitungBonusRendemen($e, 2026, 10)['amount'], 0.001);
    }
}