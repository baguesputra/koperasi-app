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
        'no_karyawan',
        'password',
        'pin',
        'pin_failed_attempts',
        'harus_ganti_password',
        'sso_id',
        'gate_id',
        'auth_provider',
        'status',
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
            'harus_ganti_password' => 'boolean',
        ];
    }

    public function hasPin(): bool
    {
        return ! empty($this->pin);
    }

    public function resetPinFailedAttempts(): void
    {
        if ($this->pin_failed_attempts !== 0) {
            $this->update(['pin_failed_attempts' => 0]);
        }
    }

    public function incrementPinFailedAttempts(): int
    {
        $this->increment('pin_failed_attempts');
        return (int) $this->fresh()->pin_failed_attempts;
    }

    public function anggota()
    {
        return $this->hasOne(Anggota::class);
    }
}
