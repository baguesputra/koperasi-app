<?php

namespace App\Http\Middleware;

use App\Models\PengajuanAktivasi;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\SettingChipNominal;
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
                ] : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'importBerhasil' => fn () => $request->session()->get('importBerhasil'),
                'importGagal' => fn () => $request->session()->get('importGagal'),
                'pinjamanTerkirim' => fn () => $request->session()->get('pinjaman_terkirim'),
                'percepatanTerkirim' => fn () => $request->session()->get('percepatan_terkirim'),
                'limitTerkirim' => fn () => $request->session()->get('limit_terkirim'),
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

                // Cache notifikasi berjangka 15 detik agar navigasi antar halaman instan
                return Cache::remember("user_notif_{$user->id}", 15, function () use ($permissions) {
                    $notifications = [];

                    if ($permissions->contains('pinjaman.tinjau-admin')) {
                        $notifications['menunggu_tinjauan_admin'] = Pinjaman::where('status', 'diajukan')->count();
                    }

                    if ($permissions->contains('pinjaman.tinjau-bendahara')) {
                        $notifications['menunggu_tinjauan_bendahara'] = Pinjaman::where('status', 'approved_admin')->count();
                        $notifications['menunggu_perubahan_tenor_bendahara'] = PengajuanPercepatan::where('status', 'diajukan')->count();
                    }

                    if ($permissions->contains('limit.tinjau-bendahara')) {
                        $notifications['menunggu_pengajuan_limit_bendahara'] = PengajuanLimit::where('status', 'diajukan')->count();
                    }

                    if ($permissions->contains('pinjaman.approve-ketua')) {
                        $notifications['menunggu_approval_ketua'] = Pinjaman::where('status', 'approved_bendahara')
                            ->where('cair_oleh_bendahara', false)
                            ->count();
                        $notifications['menunggu_perubahan_tenor_ketua'] = PengajuanPercepatan::where('status', 'approved_bendahara')->count();
                    }

                    if ($permissions->contains('limit.approve-ketua')) {
                        $notifications['menunggu_pengajuan_limit'] = PengajuanLimit::where('status', 'approved_bendahara')->count();
                    }

                    if ($permissions->contains('aktivasi.approve-ketua')) {
                        $notifications['menunggu_aktivasi'] = PengajuanAktivasi::where('status', 'diajukan')->count();
                    }

                    return $notifications;
                });
            },
        ];
    }
}
