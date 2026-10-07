<?php

namespace App\Http\Middleware;

use App\Models\KlaimDanaSosial;
use App\Models\PengajuanAktivasi;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\SettingChipNominal;
use Illuminate\Http\Request;
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
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                    'anggota_status' => $request->user()->anggota?->status,
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
            'chipNominal' => fn () => SettingChipNominal::dikelompokkan(),
            'notifications' => function () use ($request) {
                $user = $request->user();

                if (! $user) {
                    return [];
                }

                $permissions = $user->getAllPermissions()->pluck('name');
                $notifications = [];

                if ($permissions->contains('pinjaman.tinjau-bendahara')) {
                    $notifications['menunggu_tinjauan_bendahara'] = Pinjaman::where('status', 'diajukan')->count();
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

                if ($permissions->contains('klaim.tinjau-bendahara')) {
                    $notifications['menunggu_klaim_bendahara'] = KlaimDanaSosial::where('status', 'diajukan')->count();
                }

                if ($permissions->contains('klaim.approve-ketua')) {
                    $notifications['menunggu_klaim_ketua'] = KlaimDanaSosial::where('status', 'approved_bendahara')->count();
                }

                return $notifications;
            },
        ];
    }
}
