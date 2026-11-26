<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Services\Persetujuan\PersetujuanHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersetujuanController extends Controller
{
    public function __construct(private PersetujuanHubService $hub) {}

    /**
     * Pusat Persetujuan Terpadu untuk Pengurus & Pengawas (tanpa modul SHU di mobile).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $permissions = $user->getAllPermissions()->pluck('name');

        $notif = $this->hub->ringkasanNotifikasi($user, $permissions);
        $tabs = array_values(array_filter(
            $this->hub->bangunTabMenu($user, $permissions, $notif),
            fn (array $tab) => ($tab['key'] ?? '') !== 'shu'
        ));

        if (empty($tabs)) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke Pusat Persetujuan.',
            ], 403));
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

        $rawInbox = $this->hub->muatInboxTerpadu($user, $permissions, $viewAktif, $notif);

        $filterList = function (array $items) use ($kategoriFilter, $tahapFilter): array {
            $col = collect($items)->reject(fn (array $item) => ($item['kategori'] ?? '') === 'shu');
            if ($kategoriFilter !== 'semua') {
                $col = $col->where('kategori', $kategoriFilter);
            }
            if ($tahapFilter !== 'semua') {
                $col = $col->where('tahap', $tahapFilter);
            }

            return $col->values()->all();
        };

        $inbox = [
            'kas_ringkas' => $rawInbox['kasRingkas'] ?? null,
            'ketentuan_aktivasi' => $rawInbox['ketentuanAktivasi'] ?? null,
            'bisa_ajukan_penghapusan' => (bool) ($rawInbox['bisaAjukanPenghapusan'] ?? false),
            'peran_penghapusan' => $rawInbox['peranPenghapusan'] ?? null,
            'sedang_berjalan_penghapusan' => $rawInbox['sedangBerjalanPenghapusan'] ?? [],
            'jumlah_berjalan_penghapusan' => (int) ($rawInbox['jumlahBerjalanPenghapusan'] ?? 0),
            'antrean' => $filterList($rawInbox['antrean'] ?? []),
            'riwayat' => $filterList($rawInbox['riwayat'] ?? []),
        ];

        return response()->json([
            'tabs' => $tabs,
            'view_aktif' => $viewAktif,
            'kategori_filter' => $kategoriFilter,
            'tahap_filter' => $tahapFilter,
            'total_menunggu' => (int) ($notif['total_persetujuan'] ?? 0),
            'inbox' => $inbox,
            'pinjaman_menunggak' => $user->can('penghapusan-pinjaman.ajukan')
                ? $this->hub->daftarPinjamanMenunggak($user)
                : [],
        ]);
    }
}
