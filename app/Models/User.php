<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasUuids;

    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_MANAGER = 'MANAGER';
    public const ROLE_PENGAWAS = 'PENGAWAS_KANDANG';
    public const ROLE_KASIR = 'KASIR';

    /**
     * Peta fitur => peran yang boleh MENGISI/MENGUBAH (ADMIN selalu boleh).
     * MANAGER tidak tercantum karena hanya melihat (lihat canView).
     */
    public const FEATURES = [
        'kandang'        => [self::ROLE_PENGAWAS],                    // pembelian, pemasok, stok ayam hidup, mortalitas, opname ayam hidup
        'produksi_input' => [self::ROLE_PENGAWAS],                    // input hasil produksi (kg)
        'opname_hasil'   => [self::ROLE_PENGAWAS, self::ROLE_KASIR],  // opname stok hasil produksi
        'kasir'          => [self::ROLE_KASIR],                       // POS dan piutang
        'pengeluaran'    => [self::ROLE_KASIR],                       // catat uang keluar
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roleName(): string
    {
        return strtoupper((string) $this->role);
    }

    public function isAdmin(): bool
    {
        return $this->roleName() === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->roleName() === self::ROLE_MANAGER;
    }

    public function isPengawasKandang(): bool
    {
        return $this->roleName() === self::ROLE_PENGAWAS;
    }

    public function isKasir(): bool
    {
        return $this->roleName() === self::ROLE_KASIR;
    }

    /** Boleh mengisi/mengubah data pada fitur ini. */
    public function canWrite(string $feature): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($this->roleName(), self::FEATURES[$feature] ?? [], true);
    }

    /** Boleh melihat fitur ini (menu tampil). Manager boleh melihat semua. */
    public function canView(string $feature): bool
    {
        return $this->isManager() || $this->canWrite($feature);
    }

    /** Alias untuk menu: tampilkan jika boleh melihat. */
    public function canAccess(string $feature): bool
    {
        return $this->canView($feature);
    }
}