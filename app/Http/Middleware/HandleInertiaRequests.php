<?php

namespace App\Http\Middleware;

use App\Models\SettingChipNominal;
use App\Services\Persetujuan\PersetujuanHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $permissions = null;

        if ($user) {
            $user->loadMissing('anggota');
            $permissions = $user->getAllPermissions()->pluck('name');
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $permissions,
                    'anggota_status' => $user->anggota?->status,
                    'foto_url' => $user->anggota?->foto_url,
                    'has_pin' => $user->hasPin(),
                    'jabatan_pengawas' => $user->levelPengawas(),
                    'label_jabatan_pengawas' => $user->labelJabatanPengawas(),
                ] : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'importBerhasil' => fn () => $request->session()->get('importBerhasil'),
                'importGagal' => fn () => $request->session()->get('importGagal'),
                'pinjamanTerkirim' => fn () => $request->session()->get('pinjaman_terkirim'),
                'percepatanTerkirim' => fn () => $request->session()->get('percepatan_terkirim'),
                'limitTerkirim' => fn () => $request->session()->get('limit_terkirim'),
                'tabunganTerkirim' => fn () => $request->session()->get('tabungan_terkirim'),
            ],
            'chipNominal' => fn () => Cache::remember(
                'setting_chip_nominal_grouped',
                3600,
                fn () => SettingChipNominal::dikelompokkan()
            ),
            'notifications' => function () use ($user, $permissions) {
                if (! $user || ! $permissions) {
                    return [];
                }

                return app(PersetujuanHubService::class)->ringkasanNotifikasi($user, $permissions);
            },
        ];
    }
}
