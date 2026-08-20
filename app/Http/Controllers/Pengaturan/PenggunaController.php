<?php

namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Pinjaman;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class PenggunaController extends Controller
{
    private const ROLE_DIPERBOLEHKAN = ['admin', 'bendahara', 'ketua_koperasi', 'anggota'];

    private const NO_KARYAWAN_ROOT = 'ADM-000001';

    public function index(Request $request): Response
    {
        $query = User::query()->with('anggota');

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->where(function ($q) use ($cari) {
                $q->where('name', 'like', "%{$cari}%")
                    ->orWhere('no_karyawan', 'like', "%{$cari}%")
                    ->orWhere('email', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->string('role')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $pengguna = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'no_karyawan' => $user->no_karyawan,
                'email' => $user->email,
                'status' => $user->status,
                'roles' => $user->getRoleNames()->values(),
                'harus_ganti_password' => (bool) $user->harus_ganti_password,
                'anggota' => $user->anggota ? [
                    'no_anggota' => $user->anggota->no_anggota,
                    'nama' => $user->anggota->nama,
                ] : null,
                'dilindungi' => $this->akunDilindungi($user),
            ]);

        return Inertia::render('Pengaturan/Pengguna/Index', [
            'pengguna' => $pengguna,
            'daftarRole' => Role::orderBy('name')->pluck('name'),
            'filters' => $request->only(['cari', 'role', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'no_karyawan' => ['required', 'string', 'max:50', 'unique:users,no_karyawan'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:'.implode(',', self::ROLE_DIPERBOLEHKAN)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $passwordDiberikan = filled($validated['password'] ?? null);
        $password = $passwordDiberikan ? $validated['password'] : $validated['no_karyawan'];

        $user = User::create([
            'name' => $validated['name'],
            'no_karyawan' => $validated['no_karyawan'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($password),
            'harus_ganti_password' => ! $passwordDiberikan,
            'status' => 'aktif',
        ]);
        $user->syncRoles([$validated['role']]);

        AuditLog::catat(
            'tambah_pengguna',
            "Akun baru dibuat untuk {$user->name} ({$user->no_karyawan}) dengan role '{$validated['role']}'.",
            null,
            ['name' => $user->name, 'no_karyawan' => $user->no_karyawan, 'role' => $validated['role']]
        );

        return back()->with('status', 'Akun pengguna berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'no_karyawan' => ['required', 'string', 'max:50', 'unique:users,no_karyawan,'.$user->id],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:'.implode(',', self::ROLE_DIPERBOLEHKAN)],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        if ($this->akunDilindungi($user)) {
            return back()->withErrors(['pengguna' => 'Akun utama sistem tidak dapat diubah oleh pengguna lain.']);
        }

        $dataLama = [
            'name' => $user->name,
            'no_karyawan' => $user->no_karyawan,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            'status' => $user->status,
        ];

        $user->update([
            'name' => $validated['name'],
            'no_karyawan' => $validated['no_karyawan'],
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
        ]);
        $user->syncRoles([$validated['role']]);

        AuditLog::catat(
            'update_pengguna',
            "Akun {$user->name} diperbarui.",
            $dataLama,
            [
                'name' => $validated['name'],
                'no_karyawan' => $validated['no_karyawan'],
                'email' => $validated['email'] ?? null,
                'role' => $validated['role'],
                'status' => $validated['status'],
            ]
        );

        return back()->with('status', 'Akun pengguna berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => ['required', 'string', 'min:8']]);

        $user->update([
            'password' => Hash::make($request->password),
            'harus_ganti_password' => ! ((bool) $request->boolean('tanpa_wajib_ganti')),
        ]);

        AuditLog::catat('reset_password_pengguna', "Kata sandi akun {$user->name} ({$user->no_karyawan}) di-reset oleh pengurus.", null, ['user_id' => $user->id]);

        return back()->with('status', 'Kata sandi berhasil di-reset. Pengguna akan diminta menggantinya saat login berikutnya.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['pengguna' => 'Tidak dapat menonaktifkan akun yang sedang Anda gunakan.']);
        }

        if ($user->status === 'nonaktif') {
            $user->update(['status' => 'aktif']);
            $status = 'diaktifkan';
        } else {
            if ($this->akunDilindungi($user)) {
                return back()->withErrors(['pengguna' => 'Akun utama sistem tidak dapat dinonaktifkan.']);
            }
            $user->update(['status' => 'nonaktif']);
            $status = 'dinonaktifkan';
        }

        AuditLog::catat('ubah_status_pengguna', "Akun {$user->name} ({$user->no_karyawan}) di{$status}.", null, ['status' => $user->fresh()->status]);

        return back()->with('status', "Akun {$user->name} berhasil di{$status}.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['pengguna' => 'Tidak dapat menghapus akun yang sedang Anda gunakan.']);
        }

        if ($this->akunDilindungi($user)) {
            return back()->withErrors(['pengguna' => 'Akun utama sistem tidak dapat dihapus.']);
        }

        $punyaRelasi = $user->anggota()->exists()
            || Pinjaman::where('pengaju_user_id', $user->id)->exists()
            || AuditLog::where('user_id', $user->id)->exists();

        if ($punyaRelasi) {
            return back()->withErrors(['pengguna' => 'Akun memiliki riwayat data terkait. Nonaktifkan saja, jangan dihapus.']);
        }

        AuditLog::catat('hapus_pengguna', "Akun {$user->name} ({$user->no_karyawan}) dihapus.", ['name' => $user->name, 'no_karyawan' => $user->no_karyawan], null);

        $user->delete();

        return back()->with('status', 'Akun pengguna berhasil dihapus.');
    }

    private function akunDilindungi(User $user): bool
    {
        return $user->no_karyawan === self::NO_KARYAWAN_ROOT;
    }
}
