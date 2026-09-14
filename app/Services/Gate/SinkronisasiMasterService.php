<?php

namespace App\Services\Gate;

use App\Models\AuditLog;
use App\Models\Departemen;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\DB;

class SinkronisasiMasterService
{
    public function __construct(private GateClient $gate) {}

    public function sinkron(int|string|null $companyId = null, bool $kering = false, ?int $aktorId = null): array
    {
        $hasil = ['perusahaan' => 0, 'departemen' => 0, 'divisi' => 0, 'jabatan' => 0, 'gagal' => []];

        if ($companyId && (strlen((string) $companyId) > 50 || ! preg_match('/^[A-Za-z0-9-]+$/', (string) $companyId))) {
            $hasil['gagal'][] = 'company_id tidak valid.';

            return $hasil;
        }

        try {
            $daftarPerusahaan = $companyId
                ? array_filter([$this->gate->ambilPerusahaanSatu($companyId)])
                : $this->gate->ambilPerusahaan();
        } catch (\Throwable $e) {
            report($e);
            $hasil['gagal'][] = 'perusahaan: '.$e->getMessage();

            return $hasil;
        }

        foreach ($daftarPerusahaan as $baris) {
            try {
                $c = $this->sinkronPerusahaan($baris, $kering);
                $hasil['perusahaan'] += $c['perusahaan'];
                $hasil['departemen'] += $c['departemen'];
                $hasil['divisi'] += $c['divisi'];
                $hasil['jabatan'] += $c['jabatan'];
            } catch (\Throwable $e) {
                report($e);
                $hasil['gagal'][] = ($baris['name'] ?? 'perusahaan').': '.$e->getMessage();
            }
        }

        if (! $kering && array_sum([$hasil['perusahaan'], $hasil['departemen'], $hasil['divisi'], $hasil['jabatan']])) {
            AuditLog::catat(
                'sinkron_master',
                'Sinkron master GATE: '.$hasil['perusahaan'].' perusahaan, '.$hasil['departemen'].' departemen, '.$hasil['divisi'].' divisi, '.$hasil['jabatan'].' jabatan.',
                null,
                $hasil,
                $aktorId
            );
        }

        return $hasil;
    }

    private function sinkronPerusahaan(array $baris, bool $kering): array
    {
        $hitung = ['perusahaan' => 0, 'departemen' => 0, 'divisi' => 0, 'jabatan' => 0];
        $gateId = $baris['id'] ?? null;
        if (! $gateId || empty($baris['name'])) {
            return $hitung;
        }

        $perusahaan = Perusahaan::where('gate_id', $gateId)->first();
        if ($kering) {
            $hitung['perusahaan'] = $perusahaan ? 0 : 1;

            return $hitung;
        }

        $perusahaan = DB::transaction(fn () => Perusahaan::updateOrCreate(
            ['gate_id' => $gateId],
            [
                'kode' => $baris['code'] ?? null,
                'nama' => $baris['name'],
                'direktur_gate_id' => $baris['director_user_id'] ?? null,
                'gm_gate_id' => $baris['gm_user_id'] ?? null,
            ]
        ));
        $hitung['perusahaan'] = $perusahaan->wasRecentlyCreated ? 1 : 0;

        try {
            $daftarDept = $this->gate->ambilDepartemen($gateId);
        } catch (\Throwable $e) {
            report($e);
            throw new \RuntimeException('departemen: '.$e->getMessage(), 0, $e);
        }

        $daftarDept = array_values(array_filter(
            $daftarDept,
            fn ($dept) => empty($dept['company_id']) || (string) $dept['company_id'] === (string) $gateId
        ));

        foreach ($daftarDept as $dept) {
            if (empty($dept['id']) || empty($dept['name'])) {
                continue;
            }
            $model = Departemen::updateOrCreate(
                ['gate_id' => $dept['id']],
                [
                    'perusahaan_id' => $perusahaan->id,
                    'kode' => $dept['code'] ?? null,
                    'nama' => $dept['name'],
                    'leadership_title' => $dept['leadership_title'] ?? null,
                    'users_count' => $dept['users_count'] ?? null,
                    'positions_count' => $dept['positions_count'] ?? null,
                ]
            );
            $hitung['departemen'] += $model->wasRecentlyCreated ? 1 : 0;
        }

        try {
            $daftarDivisi = $this->gate->ambilDivisi($gateId);
        } catch (\Throwable $e) {
            report($e);
            throw new \RuntimeException('divisi: '.$e->getMessage(), 0, $e);
        }

        $daftarDivisi = array_values(array_filter(
            $daftarDivisi,
            fn ($div) => empty($div['company_id']) || (string) $div['company_id'] === (string) $gateId
        ));

        try {
            $daftarJabatan = $this->gate->ambilJabatan($gateId);
        } catch (\Throwable $e) {
            report($e);
            throw new \RuntimeException('jabatan: '.$e->getMessage(), 0, $e);
        }

        $deptGateIds = array_flip(array_filter(array_column($daftarDept, 'id')));
        $divisiGateIds = array_flip(array_filter(array_column($daftarDivisi, 'id')));
        $daftarJabatan = array_values(array_filter(
            $daftarJabatan,
            fn ($jab) => (! empty($jab['department_id']) && isset($deptGateIds[$jab['department_id']]))
                || (! empty($jab['division_id']) && isset($divisiGateIds[$jab['division_id']]))
                || (empty($jab['department_id']) && empty($jab['division_id']))
        ));

        $divisiBaru = [];
        foreach ($daftarDivisi as $div) {
            if (empty($div['id']) || empty($div['name'])) {
                continue;
            }
            $model = Divisi::updateOrCreate(
                ['gate_id' => $div['id']],
                [
                    'perusahaan_id' => $perusahaan->id,
                    'kode' => $div['code'] ?? null,
                    'nama' => $div['name'],
                    'leadership_title' => $div['leadership_title'] ?? null,
                    'users_count' => $div['users_count'] ?? null,
                    'positions_count' => $div['positions_count'] ?? null,
                ]
            );
            $hitung['divisi'] += $model->wasRecentlyCreated ? 1 : 0;
            $divisiBaru[$div['id']] = $model->id;
        }

        foreach ($daftarJabatan as $jab) {
            if (empty($jab['division_id']) || empty($jab['department_id']) || ! isset($divisiBaru[$jab['division_id']])) {
                continue;
            }
            $deptId = Departemen::where('gate_id', $jab['department_id'])->value('id');
            if ($deptId) {
                Divisi::whereKey($divisiBaru[$jab['division_id']])->whereNull('departemen_id')->update(['departemen_id' => $deptId]);
            }
        }

        $divisiByDept = Divisi::where('perusahaan_id', $perusahaan->id)
            ->whereNotNull('departemen_id')
            ->pluck('id', 'departemen_id');

        foreach ($daftarJabatan as $jab) {
            if (empty($jab['id']) || empty($jab['name'])) {
                continue;
            }
            $deptId = ! empty($jab['department_id'])
                ? Departemen::where('gate_id', $jab['department_id'])->value('id')
                : null;
            $model = Jabatan::updateOrCreate(
                ['gate_id' => $jab['id']],
                [
                    'perusahaan_id' => $perusahaan->id,
                    'departemen_id' => $deptId,
                    'division_id' => ! empty($jab['division_id'])
                        ? Divisi::where('gate_id', $jab['division_id'])->value('id')
                        : ($deptId && isset($divisiByDept[$deptId]) ? $divisiByDept[$deptId] : null),
                    'nama' => $jab['name'],
                    'level' => $jab['level'] ?? null,
                    'level_label' => $jab['level_label'] ?? null,
                ]
            );
            $hitung['jabatan'] += $model->wasRecentlyCreated ? 1 : 0;
        }

        return $hitung;
    }
}
