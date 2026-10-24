<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Wa\WaPesan;
use App\Services\Wa\WaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PinSecurityService
{
    public const MAX_ATTEMPTS = 3;

    /**
     * Set up or update a PIN for the user.
     */
    public function setupPin(User $user, string $pin): void
    {
        $user->pin = $pin;
        $user->pin_failed_attempts = 0;
        $user->save();

        $raw = $user->getAttributes();
        $identitas = ! empty($raw['no_karyawan']) ? $raw['no_karyawan'] : (! empty($raw['email']) ? $raw['email'] : "ID:{$user->id}");

        AuditLog::catat(
            'setup_pin',
            "Pengguna {$user->name} ({$identitas}) berhasil mengatur PIN keamanan.",
            null,
            ['user_id' => $user->id]
        );
    }

    /**
     * Verify PIN. If failed, increments failed attempts.
     * If failed >= MAX_ATTEMPTS, triggers security alert & session termination.
     *
     * @return array{valid: bool, remaining: int, locked_out: bool, message: string}
     */
    public function verify(User $user, string $pin, Request $request): array
    {
        if (! $user->hasPin()) {
            return [
                'valid' => false,
                'remaining' => 0,
                'locked_out' => false,
                'message' => 'PIN belum diatur. Silakan atur PIN terlebih dahulu.',
            ];
        }

        if (Hash::check($pin, $user->pin)) {
            $user->resetPinFailedAttempts();

            return [
                'valid' => true,
                'remaining' => self::MAX_ATTEMPTS,
                'locked_out' => false,
                'message' => 'PIN valid.',
            ];
        }

        $attempts = $user->incrementPinFailedAttempts();

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->handleFailedThreshold($user, $request);

            return [
                'valid' => false,
                'remaining' => 0,
                'locked_out' => true,
                'message' => 'PIN salah 3 kali. Sesi Anda telah ditutup demi keamanan.',
            ];
        }

        $remaining = self::MAX_ATTEMPTS - $attempts;

        return [
            'valid' => false,
            'remaining' => $remaining,
            'locked_out' => false,
            'message' => "PIN salah. Sisa kesempatan: {$remaining} kali.",
        ];
    }

    /**
     * Handle security lockout when attempts reach threshold.
     */
    public function handleFailedThreshold(User $user, Request $request): void
    {
        $raw = $user->getAttributes();
        $identitas = ! empty($raw['no_karyawan']) ? $raw['no_karyawan'] : (! empty($raw['email']) ? $raw['email'] : "ID:{$user->id}");

        AuditLog::catat(
            'security_alert_pin_lockout',
            "Percobaan PIN salah {$user->pin_failed_attempts}x untuk {$user->name} ({$identitas}). Sesi di-logout paksa.",
            null,
            ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
            $user->id
        );

        // Send WhatsApp alert if user has phone number
        $nomorHp = $user->anggota?->no_hp;
        if ($nomorHp) {
            try {
                $waktu = now()->setTimezone('Asia/Makassar')->translatedFormat('d M Y, H:i').' WITA';
                $ip = $request->ip() ?: 'Unknown IP';
                $pesan = WaPesan::buat(
                    $user->name,
                    "Sistem mendeteksi 3 kali percobaan salah memasukkan PIN Keamanan pada sesi Koperasi App Anda.\n\n"
                    ."Detail Kejadian:\n"
                    ."• Waktu: {$waktu}\n"
                    ."• Alamat IP: {$ip}\n"
                    ."• Tindakan Keamanan: Sesi telah otomatis ditutup paksa (Logout).\n\n"
                    ."Jika ini bukan Anda, mohon segera amankan workstation/ponsel Anda atau hubungi pengurus koperasi."
                );
                WaService::keAnggota($user->anggota, 'security_alert_pin', $pesan);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Invalidate current session
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}

