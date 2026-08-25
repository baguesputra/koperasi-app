<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SettingBunga;
use App\Models\SettingLimitPinjaman;
use App\Models\SettingSimpanan;
use App\Models\TabelTenor;
use App\Models\User;
use App\Models\WaLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PengaturanController extends Controller
{
    private const TAB_DIPERBOLEHKAN = ['bunga', 'limit', 'tenor', 'simpanan', 'wa'];

    private const PANEL_DIPERBOLEHKAN = ['kelola-pengguna', 'kelola-role'];

    public function index(Request $request): Response
    {
        $tabAktif = $request->input('tab', 'bunga');
        if (! in_array($tabAktif, self::TAB_DIPERBOLEHKAN, true)) {
            $tabAktif = 'bunga';
        }

        $panelAktif = $request->input('panel');
        if (! in_array($panelAktif, self::PANEL_DIPERBOLEHKAN, true)) {
            $panelAktif = null;
        }

        $roleList = Role::withCount('users')->with('permissions')->orderBy('name')->get()
            ->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'jumlah_user' => $role->users_count,
                'dilindungi' => in_array($role->name, ['admin', 'bendahara', 'ketua_koperasi', 'anggota']),
                'permissions' => $role->permissions->pluck('name')->values(),
            ]);

        $daftarRole = $roleList->pluck('name');

        $semuaPermission = Permission::orderBy('name')->get()
            ->groupBy(fn ($p) => explode('.', $p->name)[0]);

        $queryPengguna = User::query()->with('anggota');

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $queryPengguna->where(function ($q) use ($cari) {
                $q->where('name', 'like', "%{$cari}%")
                    ->orWhere('no_karyawan', 'like', "%{$cari}%")
                    ->orWhere('email', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('role')) {
            $queryPengguna->whereHas('roles', fn ($q) => $q->where('name', $request->string('role')));
        }

        if ($request->filled('status')) {
            $queryPengguna->where('status', $request->string('status'));
        }

        $pengguna = $queryPengguna->orderBy('name')
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
                'dilindungi' => $user->no_karyawan === 'ADM-000001',
            ]);

        return Inertia::render('Pengaturan/Index', [
            'tabAktif' => $tabAktif,
            'panelAktif' => $panelAktif,
            'pengguna' => $pengguna,
            'filterPengguna' => $request->only(['cari', 'role', 'status']),
            'daftarRole' => $daftarRole,
            'roleList' => $roleList,
            'semuaPermission' => $semuaPermission,
            'limitPinjaman' => SettingLimitPinjaman::orderBy('id')->get(),
            'tabelTenor' => TabelTenor::orderBy('nominal_min')->get(),
            'bungaSaatIni' => SettingBunga::orderByDesc('berlaku_dari_tanggal')->first(),
            'settingSimpanan' => SettingSimpanan::orderBy('id')->get(),
        ]);
    }

    public function updateLimit(Request $request, SettingLimitPinjaman $limit)
    {
        $request->validate(['limit_maksimal' => ['required', 'numeric', 'min:0']]);

        $nilaiLama = $limit->limit_maksimal;
        $limit->update(['limit_maksimal' => $request->limit_maksimal]);

        AuditLog::catat(
            'update_limit_pinjaman',
            "Limit '{$limit->label}' diubah dari Rp ".number_format($nilaiLama, 0, ',', '.').' menjadi Rp '.number_format($request->limit_maksimal, 0, ',', '.'),
            ['limit_maksimal' => $nilaiLama],
            ['limit_maksimal' => $request->limit_maksimal]
        );

        return back()->with('status', 'Limit pinjaman berhasil diperbarui.');
    }

    public function storeTenor(Request $request)
    {
        $request->validate([
            'nominal_min' => ['required', 'numeric', 'min:0'],
            'nominal_max' => ['required', 'numeric', 'gt:nominal_min'],
            'tenor_maksimal_bulan' => ['required', 'integer', 'min:1'],
        ]);

        $tenor = TabelTenor::create($request->only('nominal_min', 'nominal_max', 'tenor_maksimal_bulan'));

        AuditLog::catat(
            'tambah_tenor',
            'Rentang tenor baru ditambahkan: Rp '.number_format($tenor->nominal_min, 0, ',', '.').' - Rp '.number_format($tenor->nominal_max, 0, ',', '.')." ({$tenor->tenor_maksimal_bulan} bulan)",
            null,
            $tenor->toArray()
        );

        return back()->with('status', 'Rentang tenor berhasil ditambahkan.');
    }

    public function updateTenor(Request $request, TabelTenor $tenor)
    {
        $request->validate([
            'nominal_min' => ['required', 'numeric', 'min:0'],
            'nominal_max' => ['required', 'numeric', 'gt:nominal_min'],
            'tenor_maksimal_bulan' => ['required', 'integer', 'min:1'],
        ]);

        $dataLama = $tenor->toArray();
        $tenor->update($request->only('nominal_min', 'nominal_max', 'tenor_maksimal_bulan'));

        AuditLog::catat('update_tenor', 'Rentang tenor diperbarui.', $dataLama, $tenor->fresh()->toArray());

        return back()->with('status', 'Rentang tenor berhasil diperbarui.');
    }

    public function destroyTenor(TabelTenor $tenor)
    {
        AuditLog::catat('hapus_tenor', 'Rentang tenor dihapus: Rp '.number_format($tenor->nominal_min, 0, ',', '.').' - Rp '.number_format($tenor->nominal_max, 0, ',', '.'), $tenor->toArray(), null);

        $tenor->delete();

        return back()->with('status', 'Rentang tenor berhasil dihapus.');
    }

    public function updateBunga(Request $request)
    {
        $request->validate(['persentase' => ['required', 'numeric', 'min:0', 'max:100']]);

        $bungaLama = SettingBunga::orderByDesc('berlaku_dari_tanggal')->first();

        SettingBunga::create([
            'persentase' => $request->persentase,
            'berlaku_dari_tanggal' => now(),
        ]);

        AuditLog::catat(
            'update_bunga',
            "Persentase bunga diubah dari {$bungaLama?->persentase}% menjadi {$request->persentase}%",
            ['persentase' => $bungaLama?->persentase],
            ['persentase' => $request->persentase]
        );

        return back()->with('status', 'Persentase bunga berhasil diperbarui. Berlaku untuk pengajuan baru mulai sekarang.');
    }

    public function updateSimpanan(Request $request, SettingSimpanan $setting)
    {
        $request->validate(['nominal' => ['required', 'numeric', 'min:0']]);

        $nilaiLama = $setting->nominal;
        $setting->update(['nominal' => $request->nominal]);

        AuditLog::catat(
            'update_setting_simpanan',
            "Nominal '{$setting->label}' diubah dari Rp ".number_format($nilaiLama, 0, ',', '.').' menjadi Rp '.number_format($request->nominal, 0, ',', '.'),
            ['nominal' => $nilaiLama],
            ['nominal' => $request->nominal]
        );

        return back()->with('status', 'Nominal simpanan berhasil diperbarui.');
    }

    public function waData(): JsonResponse
    {
        $status = $this->baileysGet('/status');

        $qr = null;
        if (! ($status['connected'] ?? false) && ($status['hasQR'] ?? false)) {
            $qr = $this->baileysGet('/qr')['qr'] ?? null;
        }

        return response()->json([
            'terhubung' => (bool) ($status['connected'] ?? false),
            'layanan' => $status !== null,
            'qr' => $qr,
            'logs' => WaLog::query()
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'waktu' => $log->created_at?->format('d M Y H:i'),
                    'penerima' => $log->penerima,
                    'event' => $log->event,
                    'pesan' => $log->pesan,
                    'status' => $log->status,
                    'error' => $log->error,
                ]),
        ]);
    }

    public function waLogout(): JsonResponse
    {
        try {
            Http::baseUrl(config('services.wa.url'))
                ->withToken(config('services.wa.token'))
                ->timeout(config('services.wa.timeout'))
                ->post('/logout')
                ->throw();
        } catch (ConnectionException|RequestException) {
            return response()->json(['message' => 'Layanan WhatsApp tidak dapat dihubungi.'], 502);
        }

        return response()->json(['message' => 'Perangkat WhatsApp berhasil dikeluarkan.']);
    }

    private function baileysGet(string $path): ?array
    {
        try {
            return Http::baseUrl(config('services.wa.url'))
                ->withToken(config('services.wa.token'))
                ->timeout(config('services.wa.timeout'))
                ->get($path)
                ->throw()
                ->json() ?? null;
        } catch (ConnectionException|RequestException) {
            return null;
        }
    }
}
