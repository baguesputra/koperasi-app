<?php

namespace App\Http\Controllers;

use App\Services\Persetujuan\PersetujuanHubService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PersetujuanController extends Controller
{
    public function __construct(private PersetujuanHubService $hub) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $permissions = $user->getAllPermissions()->pluck('name');

        $notif = $this->hub->ringkasanNotifikasi($user, $permissions);
        $tabs = $this->hub->bangunTabMenu($user, $permissions, $notif);

        if (empty($tabs)) {
            abort(403);
        }

        $viewReq = $request->query('view');
        $viewAktif = in_array($viewReq, ['menunggu', 'berjalan', 'riwayat'], true) ? $viewReq : 'menunggu';

        $kategoriReq = $request->query('kategori');
        $kategoriKeys = array_column($tabs, 'key');
        $kategoriFilter = is_string($kategoriReq) && in_array($kategoriReq, $kategoriKeys, true)
            ? $kategoriReq
            : 'semua';

        $tahapReq = $request->query('tahap');
        $tahapFilter = is_string($tahapReq) && in_array($tahapReq, ['admin', 'bendahara', 'ketua'], true)
            ? $tahapReq
            : 'semua';

        $inbox = $this->hub->muatInboxTerpadu($user, $permissions, $viewAktif);

        return Inertia::render('Persetujuan/Index', [
            'tabs' => $tabs,
            'viewAktif' => $viewAktif,
            'kategoriFilter' => $kategoriFilter,
            'tahapFilter' => $tahapFilter,
            'totalMenunggu' => $notif['total_persetujuan'] ?? 0,
            'inbox' => $inbox,
        ]);
    }
}
