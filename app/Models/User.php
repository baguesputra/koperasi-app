<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'no_karyawan',
        'password',
        'pin',
        'pin_failed_attempts',
        'pin_locked_until',
        'harus_ganti_password',
        'sso_id',
        'gate_id',
        'auth_provider',
        'status',
        'jabatan_pengawas',
    ];

    protected $hidden = [
        'password',
        'pin',
        'remember_token',
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
            'pin' => 'hashed',
            'pin_failed_attempts' => 'integer',
            'pin_locked_until' => 'datetime',
            'harus_ganti_password' => 'boolean',
            'jabatan_pengawas' => 'integer',
        ];
    }

    public function levelPengawas(): ?int
    {
        $raw = $this->getAttributes();
        $jabatan = isset($raw['jabatan_pengawas']) ? (int) $raw['jabatan_pengawas'] : null;

        if (in_array($jabatan, [1, 2, 3], true)) {
            return $jabatan;
        }

        $noKaryawan = $raw['no_karyawan'] ?? null;

        return match ($noKaryawan) {
            'WAS-000001' => 1,
            'WAS-000002' => 2,
            'WAS-000003' => 3,
            default => null,
        };
    }

    public function labelJabatanPengawas(): ?string
    {
        return match ($this->levelPengawas()) {
            1 => 'Pengawas 1 (Ketua Pengawas)',
            2 => 'Pengawas 2',
            3 => 'Pengawas 3',
            default => null,
        };
    }

    public function hasPin(): bool
    {
        return ! empty($this->pin);
    }

    public function resetPinFailedAttempts(): void
    {
        $raw = $this->getAttributes();
        if (($raw['pin_failed_attempts'] ?? 0) !== 0) {
            $this->update(['pin_failed_attempts' => 0]);
        }
    }

    public function incrementPinFailedAttempts(): int
    {
        $this->increment('pin_failed_attempts');

        return (int) ($this->fresh()->getAttributes()['pin_failed_attempts'] ?? 1);
    }

    public function anggota()
    {
        return $this->hasOne(Anggota::class);
    }
}
