<?php

namespace App\Console\Commands;

use App\Models\GateSyncLog;
use App\Models\GateSyncSetting;
use App\Services\Gate\SinkronisasiAnggotaService;
use Illuminate\Console\Command;

class SinkronAnggotaGate extends Command
{
    protected $signature = 'gate:sinkron-anggota
        {--company= : Filter company_id GATE}
        {--department= : Filter department_id GATE}
        {--level= : Filter level jabatan GATE}
        {--limit= : Batas jumlah baris}
        {--dry-run : Pratinjau tanpa menyimpan}';

    protected $description = 'Sinkron karyawan GATE menjadi user + anggota koperasi';

    public function handle(SinkronisasiAnggotaService $sinkron): int
    {
        $hasil = $sinkron->sinkron(
            [
                'company_id' => $this->option('company'),
                'department_id' => $this->option('department'),
                'level' => $this->option('level'),
            ],
            (bool) $this->option('dry-run'),
            null,
            $this->option('limit') ? (int) $this->option('limit') : null,
            GateSyncSetting::current()->grace_miss_count ?? 2
        );

        GateSyncLog::create([
            'kind' => 'karyawan', 'source' => 'jadwal', 'is_dry_run' => (bool) $this->option('dry-run'),
            'count_baru' => count($hasil['baru']), 'count_diperbarui' => count($hasil['diperbarui']),
            'count_gagal' => count($hasil['gagal']), 'count_dilewati' => $hasil['dilewati'],
            'count_nonaktif' => $hasil['nonaktif'] ?? 0,
        ]);

        $this->info('Baru: '.count($hasil['baru']).', diperbarui: '.count($hasil['diperbarui']).', gagal: '.count($hasil['gagal']).', dilewati: '.$hasil['dilewati']);

        foreach ($hasil['gagal'] as $gagal) {
            $this->error($gagal);
        }

        return self::SUCCESS;
    }
}
