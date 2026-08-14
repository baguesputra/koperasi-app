<?php

namespace App\Http\Middleware;

use App\Models\Pinjaman;
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
                ] : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'importBerhasil' => fn () => $request->session()->get('importBerhasil'),
                'importGagal' => fn () => $request->session()->get('importGagal'),
            ],
            'notifications' => function () use ($request) {
                $user = $request->user();

                if (! $user) {
                    return [];
                }

                $permissions = $user->getAllPermissions()->pluck('name');
                $notifications = [];

                if ($permissions->contains('pinjaman.tinjau-bendahara')) {
                    $notifications['menunggu_tinjauan_bendahara'] = Pinjaman::where('status', 'diajukan')->count();
                }

                if ($permissions->contains('pinjaman.approve-ketua')) {
                    $notifications['menunggu_approval_ketua'] = Pinjaman::where('status', 'approved_bendahara')
                        ->where('cair_oleh_bendahara', false)
                        ->count();
                }

                return $notifications;
            },
        ];
    }
}
