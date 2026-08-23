<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SettingBunga;
use App\Models\SettingLimitPinjaman;
use App\Models\SettingSimpanan;
use App\Models\TabelTenor;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Models\WhatsappSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PengaturanController extends Controller
{
    private const TAB_DIPERBOLEHKAN = ['bunga', 'limit', 'tenor', 'simpanan'];

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

    public function whatsapp(Request $request): Response
    {
        $sessions = WhatsappSession::orderBy('is_default', 'desc')->orderBy('name')->get()
            ->map(fn ($s) => $this->formatSession($s));

        $logsQuery = WhatsappLog::query()->with('session:id,session_id,name')
            ->orderByDesc('created_at');

        if ($request->filled('session_id')) {
            $logsQuery->where('session_id', $request->string('session_id'));
        }

        if ($request->filled('status')) {
            $logsQuery->where('status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $logsQuery->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $logsQuery->whereDate('created_at', '<=', $request->date('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $logsQuery->where(function ($q) use ($search) {
                $q->where('to', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $logs = $logsQuery->paginate(20)->withQueryString()
            ->through(fn ($log) => [
                'id' => $log->id,
                'session_id' => $log->session_id,
                'session_name' => $log->session?->name ?? $log->session_id,
                'to' => $log->to,
                'message' => $log->message,
                'status' => $log->status,
                'reference_type' => $log->reference_type,
                'reference_id' => $log->reference_id,
                'error' => $log->error,
                'created_at' => $log->created_at->format('d M Y H:i:s'),
            ]);

        return Inertia::render('Pengaturan/WhatsApp', [
            'sessions' => $sessions,
            'logs' => $logs,
            'filters' => $request->only(['session_id', 'status', 'date_from', 'date_to', 'search']),
            'statusOptions' => ['pending', 'sent', 'failed'],
        ]);
    }

    public function whatsappQr(Request $request)
    {
        $sessionId = $request->query('session_id', 'main');
        $url = config('services.baileys.url');
        $token = config('services.baileys.token');

        if (! $url || ! $token) {
            return response()->json(['error' => 'Baileys not configured'], 500);
        }

        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->get("{$url}/api/qr", ['sessionId' => $sessionId]);

            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function whatsappStatus(Request $request)
    {
        $sessionId = $request->query('session_id');
        $url = config('services.baileys.url');
        $token = config('services.baileys.token');

        if (! $url || ! $token) {
            return response()->json(['error' => 'Baileys not configured'], 500);
        }

        try {
            $query = $sessionId ? ['sessionId' => $sessionId] : [];
            $response = Http::timeout(10)
                ->withToken($token)
                ->get("{$url}/api/health", $query);

            if ($response->successful()) {
                $data = $response->json();

                if ($sessionId && ($data['connected'] ?? false)) {
                    WhatsappSession::where('session_id', $sessionId)
                        ->update([
                            'phone_number' => $data['user']['id'] ?? null,
                            'phone_name' => $data['user']['name'] ?? null,
                            'last_connected_at' => now(),
                        ]);
                }

                return response()->json($data);
            }

            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function whatsappDisconnect(Request $request)
    {
        $request->validate(['session_id' => ['required', 'string']]);
        $sessionId = $request->session_id;
        $url = config('services.baileys.url');
        $token = config('services.baileys.token');

        if (! $url || ! $token) {
            return response()->json(['error' => 'Baileys not configured'], 500);
        }

        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post("{$url}/api/health/disconnect", ['sessionId' => $sessionId]);

            if ($response->successful()) {
                WhatsappSession::where('session_id', $sessionId)
                    ->update([
                        'phone_number' => null,
                        'phone_name' => null,
                        'last_connected_at' => null,
                    ]);
            }

            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function whatsappCreateSession(Request $request)
    {
        $request->validate([
            'session_id' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
        ]);

        $existing = WhatsappSession::where('session_id', $request->session_id)->first();
        if ($existing) {
            return back()->withErrors(['session_id' => 'Session ID sudah digunakan.']);
        }

        if ($request->boolean('is_default')) {
            WhatsappSession::where('is_default', true)->update(['is_default' => false]);
        }

        $session = WhatsappSession::create([
            'session_id' => $request->session_id,
            'name' => $request->name,
            'description' => $request->description,
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
        ]);

        AuditLog::catat('create_whatsapp_session', "Sesi WhatsApp '{$session->name}' dibuat.", null, $session->toArray());

        return back()->with('status', 'Sesi WhatsApp berhasil dibuat. Buka halaman QR untuk memindai.');
    }

    public function whatsappUpdateSession(Request $request, WhatsappSession $session)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if ($request->boolean('is_default') && ! $session->is_default) {
            WhatsappSession::where('is_default', true)->update(['is_default' => false]);
        }

        $dataLama = $session->toArray();
        $session->update($request->only('name', 'description', 'is_default', 'is_active'));

        AuditLog::catat('update_whatsapp_session', "Sesi WhatsApp '{$session->name}' diperbarui.", $dataLama, $session->fresh()->toArray());

        return back()->with('status', 'Sesi WhatsApp berhasil diperbarui.');
    }

    public function whatsappDeleteSession(WhatsappSession $session)
    {
        if ($session->is_default) {
            return back()->withErrors(['session_id' => 'Tidak bisa menghapus sesi default.']);
        }

        $sessionId = $session->session_id;
        $sessionName = $session->name;

        $url = config('services.baileys.url');
        $token = config('services.baileys.token');

        if ($url && $token) {
            try {
                Http::timeout(10)
                    ->withToken($token)
                    ->post("{$url}/api/health/disconnect", ['sessionId' => $sessionId]);
            } catch (\Throwable) {
                // Ignore disconnect errors
            }
        }

        WhatsappLog::where('session_id', $sessionId)->delete();
        $session->delete();

        AuditLog::catat('delete_whatsapp_session', "Sesi WhatsApp '{$sessionName}' dihapus.", ['session_id' => $sessionId], null);

        return back()->with('status', 'Sesi WhatsApp berhasil dihapus.');
    }

    public function whatsappTestSend(Request $request)
    {
        $request->validate([
            'session_id' => ['required', 'string'],
            'to' => ['required', 'string', 'regex:/^(\+62|0)8\d{8,11}$/'],
            'message' => ['required', 'string', 'max:4096'],
        ]);

        $url = config('services.baileys.url');
        $token = config('services.baileys.token');

        if (! $url || ! $token) {
            return response()->json(['error' => 'Baileys not configured'], 500);
        }

        try {
            $response = Http::timeout(15)
                ->withToken($token)
                ->post("{$url}/api/send", [
                    'to' => $request->to,
                    'message' => $request->message,
                    'sessionId' => $request->session_id,
                ]);

            return response()->json($response->json(), $response->status());
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function formatSession(WhatsappSession $session): array
    {
        return [
            'id' => $session->id,
            'session_id' => $session->session_id,
            'name' => $session->name,
            'description' => $session->description,
            'is_default' => $session->is_default,
            'is_active' => $session->is_active,
            'phone_number' => $session->phone_number,
            'phone_name' => $session->phone_name,
            'last_connected_at' => $session->last_connected_at?->format('d M Y H:i:s'),
            'created_at' => $session->created_at->format('d M Y H:i:s'),
        ];
    }
}
