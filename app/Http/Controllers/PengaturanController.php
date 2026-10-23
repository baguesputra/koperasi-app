<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogExport;
use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\Departemen;
use App\Models\Divisi;
use App\Models\GateSyncLog;
use App\Models\GateSyncSetting;
use App\Models\Jabatan;
use App\Models\Perusahaan;
use App\Models\SettingBunga;
use App\Models\SettingChipNominal;
use App\Models\SettingLimitPinjaman;
use App\Models\SettingSimpanan;
use App\Models\TabelTenor;
use App\Models\User;
use App\Models\WaLog;
use App\Services\Gate\GateClient;
use App\Services\Gate\SinkronisasiAnggotaService;
use App\Services\Gate\SinkronisasiMasterService;
use App\Services\Wa\Gateway\WaGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PengaturanController extends Controller
{
    private const TAB_DIPERBOLEHKAN = ['aturan-pinjaman', 'dana-operasional', 'wa', 'akses', 'organisasi', 'audit'];

    private const TAB_PERMISSIONS = [
        'aturan-pinjaman' => ['pengaturan.kebijakan.lihat', 'pengaturan.kebijakan.kelola', 'pengaturan.kelola'],
        'dana-operasional' => ['pengaturan.kebijakan.lihat', 'pengaturan.kebijakan.kelola', 'pengaturan.kelola'],
        'wa' => ['pengaturan.wa', 'pengaturan.kelola'],
        'akses' => ['user.kelola', 'pengaturan.kelola'],
        'organisasi' => ['pengaturan.organisasi', 'pengaturan.kelola'],
        'audit' => ['pengaturan.audit.lihat', 'pengaturan.kelola'],
    ];

    private const TAB_LEGASI = [
        'bunga' => 'aturan-pinjaman',
        'limit' => 'aturan-pinjaman',
        'tenor' => 'aturan-pinjaman',
        'simpanan' => 'dana-operasional',
        'kas' => 'dana-operasional',
        'chip' => 'dana-operasional',
    ];

    private const PANEL_DIPERBOLEHKAN = ['kelola-pengguna', 'kelola-role'];

    public function __construct(private WaGateway $wa) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $allowedTabs = array_values(array_filter(self::TAB_DIPERBOLEHKAN, function ($tab) use ($user) {
            $perms = self::TAB_PERMISSIONS[$tab] ?? ['pengaturan.kelola'];
            return $user->hasAnyPermission($perms);
        }));

        if (empty($allowedTabs)) {
            abort(403, 'Anda tidak memiliki hak akses ke Pengaturan.');
        }

        $tabDiminta = $request->input('tab');
        if ($tabDiminta && isset(self::TAB_LEGASI[$tabDiminta])) {
            return redirect()->route('pengaturan.index', array_merge(
                $request->except(['tab', 'section']),
                ['tab' => self::TAB_LEGASI[$tabDiminta], 'section' => $tabDiminta]
            ));
        }

        $tabAktif = $tabDiminta;
        if (! $tabAktif || ! in_array($tabAktif, $allowedTabs, true)) {
            $tabAktif = $allowedTabs[0];
        }

        $bisaKelolaKebijakan = $user->hasAnyPermission(['pengaturan.kebijakan.kelola', 'pengaturan.kelola']);

        $panelAktif = $request->input('panel');
        if (! in_array($panelAktif, self::PANEL_DIPERBOLEHKAN, true)) {
            $panelAktif = null;
        }

        $roleList = [];
        $daftarRole = [];
        $semuaPermission = [];
        $pengguna = null;

        if ($tabAktif === 'akses') {
            $canKelolaRole = $user->hasPermissionTo('pengaturan.kelola');

            if ($canKelolaRole) {
                $roleList = Role::withCount('users')->with('permissions')->orderBy('name')->get()
                    ->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'jumlah_user' => $role->users_count,
                        'dilindungi' => in_array($role->name, ['super_admin', 'admin', 'bendahara', 'ketua_koperasi', 'pengawas', 'anggota']),
                        'permissions' => $role->permissions->pluck('name')->values(),
                    ]);

                $daftarRole = $roleList->pluck('name');

                $semuaPermission = Permission::orderBy('name')->get()
                    ->groupBy(fn ($p) => explode('.', $p->name)[0]);
            } else {
                $daftarRole = Role::orderBy('name')->pluck('name');
            }

            $queryPengguna = User::query()->with(['anggota', 'roles']);

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
                        'no_karyawan' => $user->anggota->no_karyawan,
                        'nama' => $user->anggota->nama,
                    ] : null,
                    'dilindungi' => in_array($user->no_karyawan, ['SPADM-000001', 'ADM-000001']),
                ]);
        }

        // Audit log (hanya diambil saat tab audit aktif)
        $auditLogs = null;
        $statAudit = null;
        $daftarUserAudit = [];
        $daftarKategoriAudit = [];
        $daftarAksiAudit = [];

        if ($tabAktif === 'audit') {
            $filters = $request->only(['search', 'user_id', 'kategori', 'aksi', 'date_from', 'date_to']);
            $queryAudit = AuditLog::with('user')->filter($filters)->latest();

            // Statistik ringkasan untuk monitoring audit
            $totalSemua = AuditLog::count();
            $totalFiltered = (clone $queryAudit)->count();
            $totalAktor = AuditLog::whereNotNull('user_id')->distinct('user_id')->count('user_id');
            $aksiKritis = (clone $queryAudit)->where(function ($q) {
                $q->where('aksi', 'like', '%_tolak%')
                    ->orWhere('aksi', 'like', '%_setujui%')
                    ->orWhere('aksi', 'like', '%_cair%')
                    ->orWhere('aksi', 'like', 'hapus_%')
                    ->orWhere('aksi', 'like', '%_resign%');
            })->count();

            $statAudit = [
                'total_semua' => $totalSemua,
                'total_filtered' => $totalFiltered,
                'total_aktor' => $totalAktor,
                'aksi_kritis' => $aksiKritis,
            ];

            // Daftar user yang pernah tercatat di audit log
            $userIds = AuditLog::whereNotNull('user_id')->distinct()->pluck('user_id');
            $daftarUserAudit = User::whereIn('id', $userIds)
                ->orderBy('name')
                ->get(['id', 'name', 'no_karyawan'])
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'no_karyawan' => $u->no_karyawan,
                ]);

            $daftarKategoriAudit = AuditLog::daftarKategori();
            $daftarAksiAudit = AuditLog::distinct()->pluck('aksi')->sort()->values();

            $auditLogs = $queryAudit->paginate(20)
                ->withQueryString()
                ->through(fn ($log) => [
                    'id' => $log->id,
                    'aksi' => $log->aksi,
                    'keterangan' => $log->keterangan,
                    'data_lama' => $log->data_lama,
                    'data_baru' => $log->data_baru,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'no_karyawan' => $log->user->no_karyawan,
                    ] : null,
                    'created_at' => $log->created_at->format('d M Y H:i:s'),
                ]);
        }

        return Inertia::render('Pengaturan/Index', [
            'tabAktif' => $tabAktif,
            'allowedTabs' => $allowedTabs,
            'bisaKelolaKebijakan' => $bisaKelolaKebijakan,
            'panelAktif' => $panelAktif,
            'sectionAktif' => $request->input('section'),
            'pengguna' => $pengguna,
            'filterPengguna' => $request->only(['cari', 'role', 'status']),
            'daftarRole' => $daftarRole,
            'roleList' => $roleList,
            'semuaPermission' => $semuaPermission,
            'limitPinjaman' => $tabAktif === 'aturan-pinjaman' ? SettingLimitPinjaman::orderBy('min_bulan')->get() : [],
            'tabelTenor' => $tabAktif === 'aturan-pinjaman' ? TabelTenor::orderBy('nominal_min')->get() : [],
            'bungaSaatIni' => $tabAktif === 'aturan-pinjaman' ? SettingBunga::orderByDesc('berlaku_dari_tanggal')->first() : null,
            'settingSimpanan' => $tabAktif === 'dana-operasional' ? SettingSimpanan::orderBy('id')->get() : [],
            'chipNominal' => $tabAktif === 'dana-operasional' ? SettingChipNominal::dikelompokkan() : [],
            'ringkasanMaster' => $tabAktif === 'organisasi' ? [
                'perusahaan' => Perusahaan::count(),
                'departemen' => Departemen::count(),
                'divisi' => Divisi::count(),
                'jabatan' => Jabatan::count(),
                'tanpaPerusahaan' => Anggota::whereNull('perusahaan_id')->count(),
                'tanpaDivisi' => Anggota::whereNull('divisi_id')->count(),
            ] : null,
            'gateStatus' => $tabAktif === 'organisasi' ? $this->statusGate() : null,
            'gateSetting' => $tabAktif === 'organisasi' ? GateSyncSetting::current() : null,
            'gateHistory' => $tabAktif === 'organisasi'
                ? GateSyncLog::with('user:id,name')->latest()->take(10)->get([
                    'id', 'kind', 'source', 'is_dry_run', 'count_baru', 'count_diperbarui', 'count_gagal',
                    'count_dilewati', 'count_nonaktif', 'count_perusahaan', 'count_departemen',
                    'count_divisi', 'count_jabatan', 'user_id', 'created_at',
                ])
                : [],
            'auditLogs' => $auditLogs,
            'statAudit' => $statAudit,
            'daftarUserAudit' => $daftarUserAudit,
            'daftarKategoriAudit' => $daftarKategoriAudit,
            'daftarAksiAudit' => $daftarAksiAudit,
            'filterAudit' => $request->only(['search', 'user_id', 'kategori', 'aksi', 'date_from', 'date_to']),
        ]);
    }

    public function updateLimit(Request $request, SettingLimitPinjaman $limit)
    {
        $data = $request->validate([
            'label' => ['sometimes', 'required', 'string', 'max:100'],
            'min_bulan' => ['sometimes', 'required', 'integer', 'min:0', 'max:1200'],
            'maks_bulan' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1200'],
            'limit_maksimal' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);

        $minBaru = $data['min_bulan'] ?? $limit->min_bulan ?? 0;
        $maksBaru = array_key_exists('maks_bulan', $data) ? $data['maks_bulan'] : $limit->maks_bulan;

        if ($maksBaru !== null && $maksBaru < $minBaru) {
            return back()->withErrors(['maks_bulan' => 'Batas atas harus >= batas bawah atau dikosongkan (tanpa batas).']);
        }

        if ($this->rentangLimitBertabrakan($minBaru, $maksBaru, $limit->id)) {
            return back()->withErrors(['min_bulan' => 'Rentang masa bertabrakan dengan baris lain.']);
        }

        $nilaiLama = $limit->limit_maksimal;
        $limit->update($data);

        AuditLog::catat(
            'update_limit_pinjaman',
            "Limit '{$limit->label}' diubah dari Rp ".number_format($nilaiLama, 0, ',', '.').' menjadi Rp '.number_format($limit->limit_maksimal, 0, ',', '.'),
            ['limit_maksimal' => $nilaiLama],
            $limit->fresh()->toArray()
        );

        return back()->with('status', 'Limit pinjaman berhasil diperbarui.');
    }

    public function storeLimit(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'min_bulan' => ['required', 'integer', 'min:0', 'max:1200'],
            'maks_bulan' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'limit_maksimal' => ['required', 'numeric', 'min:0'],
        ]);

        if ($data['maks_bulan'] !== null && $data['maks_bulan'] < $data['min_bulan']) {
            return back()->withErrors(['maks_bulan' => 'Batas atas harus >= batas bawah atau dikosongkan (tanpa batas).']);
        }

        if ($this->rentangLimitBertabrakan($data['min_bulan'], $data['maks_bulan'])) {
            return back()->withErrors(['min_bulan' => 'Rentang masa bertabrakan dengan baris lain.']);
        }

        $data['kategori'] = 'kustom_'.now()->format('YmdHis');
        $limit = SettingLimitPinjaman::create($data);

        AuditLog::catat('tambah_limit_pinjaman', "Rentang limit baru ditambahkan: '{$limit->label}'.", null, $limit->toArray());

        return back()->with('status', 'Rentang limit berhasil ditambahkan.');
    }

    public function destroyLimit(SettingLimitPinjaman $limit)
    {
        AuditLog::catat('hapus_limit_pinjaman', "Rentang limit dihapus: '{$limit->label}'.", $limit->toArray(), null);

        $limit->delete();

        return back()->with('status', 'Rentang limit berhasil dihapus.');
    }

    private function rentangLimitBertabrakan(int $min, ?int $maks, ?int $kecualiId = null): bool
    {
        return SettingLimitPinjaman::where('id', '!=', $kecualiId)
            ->where(function ($q) use ($min, $maks) {
                $q->where(function ($r) use ($min, $maks) {
                    $r->where('min_bulan', '<=', $min)
                        ->where(fn ($s) => $s->whereNull('maks_bulan')->orWhere('maks_bulan', '>=', $min));
                })->orWhere(function ($r) use ($min, $maks) {
                    // Awal baris lain masuk ke dalam [min, maks]
                    $r->where('min_bulan', '>=', $min);
                    if ($maks !== null) {
                        $r->where('min_bulan', '<=', $maks);
                    }
                });
            })
            ->exists();
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

    public function updateChip(Request $request)
    {
        $grupDipakai = array_keys(SettingChipNominal::GRUP_LABEL);

        $request->validate([
            'grup' => ['required', 'string', 'in:'.implode(',', $grupDipakai)],
            'daftar' => ['required', 'array', 'min:1', 'max:'.SettingChipNominal::MAKS_PER_GRUP],
            'daftar.*' => ['required', 'numeric', 'min:1', 'max:999999999999'],
        ]);

        $grup = $request->string('grup')->toString();
        $daftar = collect($request->input('daftar'))
            ->map(fn ($n) => (float) $n)
            ->unique()
            ->values();

        SettingChipNominal::where('grup', $grup)->delete();
        foreach ($daftar as $i => $nominal) {
            SettingChipNominal::create(['grup' => $grup, 'nominal' => $nominal, 'urutan' => $i]);
        }

        Cache::forget('chip_nominal_semua');
        Cache::forget('setting_chip_nominal_grouped');

        AuditLog::catat(
            'update_chip_nominal',
            "Chip nominal '{$grup}' diubah menjadi: ".implode(', ', $daftar->map(fn ($n) => 'Rp '.number_format($n, 0, ',', '.'))->all()),
            null,
            ['grup' => $grup, 'daftar' => $daftar->all()]
        );

        return back()->with('status', 'Chip nominal berhasil diperbarui.');
    }

    private function statusGate(): array
    {
        $adaToken = (bool) config('services.gate.token');
        $terhubung = false;

        if ($adaToken) {
            try {
                app(GateClient::class)->ambilPerusahaan();
                $terhubung = true;
            } catch (\Throwable) {
                $terhubung = false;
            }
        }

        $syncTerakhir = static fn (string $aksi) => AuditLog::where('aksi', $aksi)->latest()->first()?->created_at?->format('d M Y H:i');

        return [
            'adaToken' => $adaToken,
            'terhubung' => $terhubung,
            'masterTerakhir' => $syncTerakhir('sinkron_master'),
            'karyawanTerakhir' => $syncTerakhir('sinkron_gate'),
        ];
    }

    public function sinkronMasterGate(Request $request, SinkronisasiMasterService $sinkron)
    {
        $request->validate([
            'company_id' => ['nullable', 'string', 'max:50'],
            'dry_run' => ['nullable', 'boolean'],
        ]);
        $kering = (bool) $request->input('dry_run', false);

        try {
            $hasil = $sinkron->sinkron($request->input('company_id'), $kering, $request->user()->id);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('status', 'Sinkron master GATE gagal: '.$e->getMessage());
        }

        if (! $kering) {
            GateSyncLog::create([
                'kind' => 'master', 'source' => 'manual', 'is_dry_run' => false, 'user_id' => $request->user()?->id,
                'count_perusahaan' => $hasil['perusahaan'], 'count_departemen' => $hasil['departemen'],
                'count_divisi' => $hasil['divisi'], 'count_jabatan' => $hasil['jabatan'],
                'count_gagal' => count($hasil['gagal']),
            ]);
        }

        $prefix = $kering ? 'Pratinjau: ' : '';

        return back()->with(
            'status',
            $prefix.'Sinkron master GATE selesai: '.$hasil['perusahaan'].' perusahaan, '.$hasil['departemen'].' departemen, '.$hasil['divisi'].' divisi, '.$hasil['jabatan'].' jabatan.'
                .($hasil['gagal'] ? ' Gagal: '.implode(' ', array_slice($hasil['gagal'], 0, 3)) : '')
        );
    }

    public function sinkronGate(Request $request, SinkronisasiAnggotaService $sinkron)
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'string', 'max:50'],
            'level' => ['nullable', 'string', 'max:50'],
            'dry_run' => ['nullable', 'boolean'],
            'batas' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        $kering = (bool) ($validated['dry_run'] ?? false);

        $hasil = $sinkron->sinkron(
            [
                'company_id' => $validated['company_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'level' => $validated['level'] ?? null,
            ],
            $kering,
            $request->user()->id,
            $validated['batas'] ?? null,
            GateSyncSetting::current()->grace_miss_count ?? 2
        );

        if (! $kering) {
            GateSyncLog::create([
                'kind' => 'karyawan', 'source' => 'manual', 'is_dry_run' => false, 'user_id' => $request->user()?->id,
                'count_baru' => count($hasil['baru']), 'count_diperbarui' => count($hasil['diperbarui']),
                'count_gagal' => count($hasil['gagal']), 'count_dilewati' => $hasil['dilewati'],
                'count_nonaktif' => $hasil['nonaktif'] ?? 0,
            ]);
        }

        $prefix = $kering ? 'Pratinjau: ' : '';

        return back()->with(
            'status',
            $prefix.'Sinkron GATE selesai: '.count($hasil['baru']).' baru, '.count($hasil['diperbarui']).' diperbarui, '.count($hasil['gagal']).' gagal.'
                .($hasil['gagal'] ? ' '.implode(' ', array_slice($hasil['gagal'], 0, 3)) : '')
        );
    }

    public function simpanJadwalGate(Request $request)
    {
        $validated = $request->validate([
            'schedule_enabled' => 'nullable|boolean',
            'karyawan_interval_minutes' => 'required|integer|min:5|max:1440',
            'master_daily_at' => 'required|date_format:H:i',
            'jit_enabled' => 'nullable|boolean',
            'grace_miss_count' => 'required|integer|min:1|max:10',
        ]);
        $validated['schedule_enabled'] = (bool) ($validated['schedule_enabled'] ?? false);
        $validated['jit_enabled'] = (bool) ($validated['jit_enabled'] ?? false);

        GateSyncSetting::simpan($validated);

        return back()->with('status', 'Jadwal sinkron otomatis disimpan.');
    }

    public function sinkronSemuaGate(Request $request, SinkronisasiMasterService $master, SinkronisasiAnggotaService $anggota)
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'string', 'max:50'],
            'batas' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'dry_run' => ['nullable', 'boolean'],
        ]);
        $kering = (bool) ($validated['dry_run'] ?? false);

        try {
            $m = $master->sinkron($validated['company_id'] ?? null, $kering, $request->user()->id);
            $k = $anggota->sinkron(
                ['company_id' => $validated['company_id'] ?? null],
                $kering,
                $request->user()->id,
                $validated['batas'] ?? null,
                GateSyncSetting::current()->grace_miss_count ?? 2
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('status', 'Sinkron GATE gagal: '.$e->getMessage());
        }

        if (! $kering) {
            GateSyncLog::create([
                'kind' => 'semua', 'source' => 'manual', 'is_dry_run' => false, 'user_id' => $request->user()?->id,
                'count_baru' => count($k['baru']), 'count_diperbarui' => count($k['diperbarui']),
                'count_gagal' => count($k['gagal']), 'count_dilewati' => $k['dilewati'],
                'count_nonaktif' => $k['nonaktif'] ?? 0,
                'count_perusahaan' => $m['perusahaan'], 'count_departemen' => $m['departemen'],
                'count_divisi' => $m['divisi'], 'count_jabatan' => $m['jabatan'],
            ]);
        }

        $prefix = $kering ? 'Pratinjau: ' : '';

        return back()->with(
            'status',
            $prefix.'Selesai: '.$m['perusahaan'].' perusahaan, '.$m['departemen'].' dept, '.$m['divisi'].' divisi, '.$m['jabatan'].' jabatan; '
                .count($k['baru']).' anggota baru, '.count($k['diperbarui']).' diperbarui, '.($k['nonaktif'] ?? 0).' nonaktif, '.count($k['gagal']).' gagal.'
        );
    }

    public function waData(): JsonResponse
    {
        $status = $this->wa->status();
        $bisaKelola = $this->wa->canManageSession();

        $qr = null;
        if ($bisaKelola && $status && ! $status['connected'] && $status['has_qr']) {
            $qr = $this->wa->qr();
        }

        return response()->json([
            'terhubung' => (bool) ($status['connected'] ?? false),
            'layanan' => $status !== null,
            'bisa_kelola' => $bisaKelola,
            'gateway' => config('services.wa.gateway', 'baileys'),
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
        if (! $this->wa->canManageSession()) {
            return response()->json(['message' => 'Sesi WhatsApp dikelola oleh penyedia layanan.'], 403);
        }

        try {
            $this->wa->logout();
        } catch (ConnectionException|RequestException) {
            return response()->json(['message' => 'Layanan WhatsApp tidak dapat dihubungi.'], 502);
        }

        return response()->json(['message' => 'Perangkat WhatsApp berhasil dikeluarkan.']);
    }

    /**
     * Export log audit ke Excel multi-sheet.
     */
    public function exportAudit(Request $request)
    {
        $filters = $request->only(['search', 'user_id', 'kategori', 'aksi', 'date_from', 'date_to']);
        $query = AuditLog::with('user')->filter($filters)->latest();

        $namaFile = 'Audit_Log_Koperasi_' . now()->format('Ymd_His') . '.xlsx';

        return (new AuditLogExport($query))->download($namaFile);
    }
}
