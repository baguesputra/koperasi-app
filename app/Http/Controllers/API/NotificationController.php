<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AuditLog;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Notifikasi mobile dari jejak keputusan domain yang sudah diaudit.
 *
 * Tanpa tabel khusus: agregat ringan antrean + keputusan terakhir + jatuh tempo,
 * dengan cache per user agar navigasi instan (pola middleware notifications web).
 * Status dibaca disimpan di cache per user agar kontrak mobile stabil.
 */
class NotificationController extends Controller
{
    /**
     * Daftar notifikasi user login (terbaru dulu) + hitung belum dibaca.
     *
     * @response 200 {"data": [{"id": 1, "tipe": "umum"}], "belum_dibaca": 0}
     */
    public function index(Request $request): JsonResponse
    {
        $rows = $this->daftar($request);
        $dibaca = $this->dibacaIds($request);

        $data = collect($rows)->map(fn ($row) => [
            'id' => $this->sid($row),
            'tipe' => $row['tipe'],
            'judul' => $row['judul'],
            'pesan' => $row['pesan'],
            'dibaca' => in_array($this->sid($row), $dibaca, true),
            'tanggal' => $row['tanggal'],
            'referensi' => $row['referensi'],
        ])->values();

        return response()->json([
            'data' => $data,
            'belum_dibaca' => $data->where('dibaca', false)->count(),
        ]);
    }

    /**
     * Tandai satu notifikasi sudah dibaca. Id tak dikenal → sukses kosong.
     *
     * @response 200 {"message": "Ditandai sudah dibaca."}
     */
    public function read(Request $request, string $id): JsonResponse
    {
        $cocok = collect($this->daftar($request))->first(fn ($baris) => $this->sid($baris) === $id);

        if ($cocok) {
            $this->tandai($request, [$id]);
        }

        return response()->json(['message' => 'Ditandai sudah dibaca.']);
    }

    /**
     * Tandai semua notifikasi user sudah dibaca.
     * Idempoten via middleware + header Idempotency-Key dari mobile.
     *
     * @response 200 {"message": "Semua notifikasi ditandai sudah dibaca."}
     */
    public function readAll(Request $request): JsonResponse
    {
        $sids = collect($this->daftar($request))->map(fn ($row) => $this->sid($row))->all();
        $this->tandai($request, $sids);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }

    private function daftar(Request $request): array
    {
        $user = $request->user();
        $pengurus = $user->roles->pluck('name')->contains(fn ($r) => $r !== 'anggota')
            || $user->can('pinjaman.tinjau-admin')
            || $user->can('pinjaman.tinjau-bendahara')
            || $user->can('pinjaman.approve-ketua')
            || $user->can('limit.tinjau-bendahara')
            || $user->can('limit.approve-ketua');

        return Cache::remember("api_notif_{$user->id}", 60, function () use ($user, $pengurus) {
            $anggota = Anggota::where('user_id', $user->id)->first();

            $rows = $this->sumberAnggota($anggota);
            if ($pengurus) {
                $rows = array_merge($rows, $this->sumberPengurus($user));
            }

            $semua = array_merge($rows, $this->sumberAudit($user));
            usort($semua, fn ($a, $b) => strcmp((string) $b['tanggal'], (string) $a['tanggal']));

            return array_slice($semua, 0, 50);
        });
    }

    private function dibacaIds(Request $request): array
    {
        return Cache::get("api_notif_baca_{$request->user()->id}", []);
    }

    private function tandai(Request $request, array $sids): void
    {
        $kunci = "api_notif_baca_{$request->user()->id}";
        $lama = Cache::get($kunci, []);
        Cache::put($kunci, array_values(array_unique(array_merge($lama, $sids))), 86400);
    }

    private function sid(array $row): string
    {
        return md5(implode('|', [
            $row['tipe'] ?? '',
            $row['judul'] ?? '',
            $row['pesan'] ?? '',
            $row['tanggal'] ?? '',
            $row['referensi']['jenis'] ?? '',
            $row['referensi']['id'] ?? '',
        ]));
    }

    /**
     * Ringkas audit keputusan terakhir user sebagai konteks umum (maksimal 3).
     */
    private function sumberAudit(object $user): array
    {
        return AuditLog::where('user_id', $user->id)
            ->latest('id')
            ->take(3)
            ->get(['aksi', 'keterangan', 'created_at'])
            ->map(fn ($a) => $this->kolomSumber('status_pengajuan', $this->judulAudit($a->aksi),
                (string) $a->keterangan, null, null,
                $a->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s')))
            ->all();
    }

    private function judulAudit(string $aksi): string
    {
        return ucwords(str_replace('_', ' ', $aksi));
    }

    /**
     * Sumber data notifikasi turunan:
     * - Pengajuan berjalan milik anggota (menunggu).
     * - Keputusan terakhir atas pengajuan anggota (disetujui/ditolak/dicairkan).
     * - Angsuran terlambat milik anggota (jatuh tempo).
     */
    private function sumberAnggota(?Anggota $anggota): array
    {
        if (! $anggota) {
            return [];
        }

        $rows = [];

        $berjalan = Pinjaman::where('anggota_id', $anggota->id)
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->latest('tanggal_pengajuan')
            ->get(['id', 'status', 'nominal', 'tanggal_pengajuan']);

        foreach ($berjalan as $p) {
            $rows[] = $this->kolomSumber('pengajuan_baru', 'Pengajuan pinjaman menunggu verifikasi',
                'Pengajuan #'.$p->id.' senilai Rp '.number_format((float) $p->nominal, 0, ',', '.')." menunggu tahap {$p->status}.",
                'pinjaman', $p->id, $p->tanggal_pengajuan?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'));
        }

        $keputusan = Pinjaman::where('anggota_id', $anggota->id)
            ->whereIn('status', ['aktif', 'lunas', 'ditolak'])
            ->latest('updated_at')
            ->take(5)
            ->get(['id', 'status', 'updated_at']);

        foreach ($keputusan as $p) {
            $rows[] = $this->kolomSumber($p->status === 'ditolak' ? 'status_pengajuan' : 'pencairan',
                $p->status === 'ditolak' ? 'Pengajuan pinjaman ditolak' : 'Pinjaman telah dicairkan',
                $p->status === 'ditolak'
                    ? "Pengajuan #{$p->id} ditolak. Buka detail untuk alasan penolakan."
                    : "Pengajuan #{$p->id} telah dicairkan dan aktif (status: {$p->status}).",
                'pinjaman', $p->id, $p->updated_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'));
        }

        $terlambat = Angsuran::whereHas('pinjaman', fn ($q) => $q
            ->where('anggota_id', $anggota->id)->where('status', 'aktif'))
            ->where('status', 'belum_bayar')
            ->whereDate('tanggal_jatuh_tempo', '<', now()->toDateString())
            ->orderBy('tanggal_jatuh_tempo')
            ->take(5)
            ->get(['id', 'cicilan_ke', 'total_bayar', 'tanggal_jatuh_tempo']);

        foreach ($terlambat as $a) {
            $rows[] = $this->kolomSumber('jatuh_tempo', "Jatuh tempo angsuran ke-{$a->cicilan_ke}",
                'Angsuran ke-'.$a->cicilan_ke.' sebesar Rp '.number_format((float) $a->total_bayar, 0, ',', '.')
                .' telah jatuh tempo pada '.$a->tanggal_jatuh_tempo?->format('Y-m-d').'.',
                'angsuran', $a->id, $a->tanggal_jatuh_tempo?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'));
        }

        return $rows;
    }

    private function sumberPengurus(object $user): array
    {
        $rows = [];
        $boleh = fn (string $izin) => $user->can($izin);

        $antrean = [];
        if ($boleh('pinjaman.tinjau-admin')) {
            $antrean['verifikasi Admin'] = Pinjaman::where('status', 'diajukan')->count();
        }
        if ($boleh('pinjaman.tinjau-bendahara')) {
            $antrean['verifikasi Bendahara'] = Pinjaman::where('status', 'approved_admin')->count();
            $antrean['perubahan tenor Bendahara'] = PengajuanPercepatan::where('status', 'diajukan')->count();
            $antrean['limit Bendahara'] = PengajuanLimit::where('status', 'diajukan')->count();
        }
        if ($boleh('pinjaman.approve-ketua')) {
            $antrean['persetujuan Ketua'] = Pinjaman::where('status', 'approved_bendahara')->where('cair_oleh_bendahara', false)->count();
            $antrean['perubahan tenor Ketua'] = PengajuanPercepatan::where('status', 'approved_bendahara')->count();
        }
        if ($boleh('limit.approve-ketua')) {
            $antrean['limit Ketua'] = PengajuanLimit::where('status', 'approved_bendahara')->count();
        }

        foreach ($antrean as $label => $jumlah) {
            if ($jumlah > 0) {
                $rows[] = $this->kolomSumber('pengajuan_baru', "{$jumlah} pengajuan menunggu {$label}",
                    "Ada {$jumlah} pengajuan menunggu tahap {$label}. Buka antrean untuk menindaklanjuti.",
                    null, null, now()->format('Y-m-d H:i:s'));
            }
        }

        return $rows;
    }

    private function kolomSumber(string $tipe, string $judul, string $pesan, ?string $refJenis, mixed $refId, string $tanggal): array
    {
        return [
            'tipe' => $tipe,
            'judul' => $judul,
            'pesan' => $pesan,
            'referensi' => $refJenis ? ['jenis' => $refJenis, 'id' => (int) $refId] : null,
            'tanggal' => $tanggal,
        ];
    }
}
