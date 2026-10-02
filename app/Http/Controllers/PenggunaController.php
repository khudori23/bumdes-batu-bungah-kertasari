<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PenggunaController extends Controller
{
    public const PERAN = [
        'ADMIN'            => 'Admin',
        'MANAGER'          => 'Manager',
        'PENGAWAS_KANDANG' => 'Pengawas Kandang',
        'KASIR'            => 'Kasir',
        'PEGAWAI'          => 'Pegawai (hanya absen)',
    ];

    private const PESAN = [
        'username.regex'      => 'Username hanya boleh huruf kecil, angka, titik, garis bawah, dan strip.',
        'username.unique'     => 'Username sudah dipakai.',
        'email.unique'        => 'Email sudah dipakai.',
        'password.confirmed'  => 'Konfirmasi password tidak sama.',
        'password.min'        => 'Password minimal 8 karakter.',
    ];

    private const FIELD_AUDIT = ['username', 'name', 'email', 'role', 'is_active'];

    private function wajibAdmin(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya admin yang boleh mengelola pengguna.');
    }

    private function kunci(): void
    {
        DB::select('select pg_advisory_xact_lock(?)', [(int) crc32('admin_guard')]);
    }

    private function rapikanUsername(Request $request): void
    {
        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);
    }

    private function keluarkanSesi(string $userId): void
    {
        DB::table('sessions')->where('user_id', $userId)->delete();
    }

    public function index()
    {
        $this->wajibAdmin();

        return view('pengguna.index', [
            'users' => User::orderByDesc('is_active')->orderBy('role')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->wajibAdmin();

        return view('pengguna.form', ['user' => new User(['role' => 'KASIR', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $this->wajibAdmin();
        $this->rapikanUsername($request);

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'username')],
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')],
            'role'     => ['required', Rule::in(array_keys(self::PERAN))],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ], self::PESAN);

        $user = DB::transaction(function () use ($data) {
            $u = User::create([
                'username'  => $data['username'],
                'name'      => trim($data['name']),
                'email'     => $data['email'] ?? null,
                'role'      => $data['role'],
                'password'  => $data['password'],
                'is_active' => true,
            ]);

            AuditService::catat('CREATE', 'users', $u->id, null, $u->only(self::FIELD_AUDIT));

            return $u;
        });

        return redirect()->route('pengguna.index')
            ->with('status', 'Pengguna ' . $user->username . ' (' . self::PERAN[$user->role] . ') dibuat.');
    }

    public function edit(User $user)
    {
        $this->wajibAdmin();

        return view('pengguna.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->wajibAdmin();
        $this->rapikanUsername($request);

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => ['required', Rule::in(array_keys(self::PERAN))],
        ], self::PESAN);

        $diriSendiri = $user->id === Auth::id();

        DB::transaction(function () use ($user, $data, $request, $diriSendiri) {
            $this->kunci();

            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            // Akun sendiri: peran dan status tidak boleh diubah dari sini
            $role  = $diriSendiri ? $u->role : $data['role'];
            $aktif = $diriSendiri ? (bool) $u->is_active : $request->boolean('is_active');

            if ($u->isAdmin() && $u->is_active && ($role !== 'ADMIN' || ! $aktif)) {
                $lain = User::where('role', 'ADMIN')->where('is_active', true)->where('id', '!=', $u->id)->count();
                if ($lain === 0) {
                    throw ValidationException::withMessages(['role' => 'Harus tersisa minimal satu admin aktif.']);
                }
            }

            $lama = $u->only(self::FIELD_AUDIT);
            $u->update([
                'username'  => $data['username'],
                'name'      => trim($data['name']),
                'email'     => $data['email'] ?? null,
                'role'      => $role,
                'is_active' => $aktif,
            ]);
            $baru = $u->fresh()->only(self::FIELD_AUDIT);

            if ($lama !== $baru) {
                AuditService::catat('UPDATE', 'users', $u->id, $lama, $baru);
            }

            // Perubahan peran atau penonaktifan berlaku seketika
            if (! $diriSendiri && ($lama['role'] !== $baru['role'] || ($lama['is_active'] && ! $baru['is_active']))) {
                $this->keluarkanSesi($u->id);
            }
        });

        return redirect()->route('pengguna.index')->with('status', 'Data pengguna ' . $data['username'] . ' diperbarui.');
    }

    public function hubungkanPegawai(Request $request, User $user)
    {
        $this->wajibAdmin();

        $d = $request->validate([
            'employee_id' => ['nullable', 'uuid', Rule::exists('employees', 'id')->where('is_active', true)],
        ]);
        $eid = $d['employee_id'] ?? null;

        DB::transaction(function () use ($user, $eid) {
            $this->kunci();
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($eid && $u->roleName() !== 'PEGAWAI') {
                throw ValidationException::withMessages(['employee_id' => 'Hanya akun berperan Pegawai yang bisa dihubungkan.']);
            }
            if ($eid && User::where('employee_id', $eid)->where('id', '!=', $u->id)->exists()) {
                throw ValidationException::withMessages(['employee_id' => 'Pegawai ini sudah terhubung ke akun lain.']);
            }

            $lama = ['employee_id' => $u->employee_id];
            $u->employee_id = $eid;
            $u->save();
            AuditService::catat('UPDATE', 'users', $u->id, $lama, ['employee_id' => $eid]);
        });

        return redirect()->route('pengguna.edit', $user)->with('status', 'Hubungan akun ke data pegawai disimpan.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->wajibAdmin();

        if ($user->id === Auth::id()) {
            return redirect()->route('akun.password');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ], self::PESAN);

        DB::transaction(function () use ($user, $data) {
            $user->password = $data['password'];
            $user->setRememberToken(Str::random(60));
            $user->save();

            AuditService::catat('RESET_PASSWORD', 'users', $user->id, null, null, 'Admin mengatur ulang password');
            $this->keluarkanSesi($user->id);
        });

        return redirect()->route('pengguna.edit', $user)
            ->with('status', 'Password ' . $user->username . ' diatur ulang. Pengguna harus login ulang.');
    }
}