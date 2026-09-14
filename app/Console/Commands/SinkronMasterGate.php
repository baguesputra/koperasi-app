<?php

namespace App\Console\Commands;

use App\Services\Gate\SinkronisasiMasterService;
use Illuminate\Console\Command;

class SinkronMasterGate extends Command
{
    protected $signature = 'gate:sinkron-master
        {--company= : ID perusahaan GATE}
        {--dry-run : Pratinjau tanpa menyimpan}';

    protected $description = 'Sinkron master perusahaan, departemen, divisi, jabatan dari GATE';

    public function handle(SinkronisasiMasterService $sinkron): int
    {
        $hasil = $sinkron->sinkron($this->option('company'), (bool) $this->option('dry-run'));

        $this->info('Perusahaan: '.$hasil['perusahaan'].', departemen: '.$hasil['departemen'].', divisi: '.$hasil['divisi'].', jabatan: '.$hasil['jabatan'].', gagal: '.count($hasil['gagal']));

        foreach ($hasil['gagal'] as $gagal) {
            $this->error($gagal);
        }

        return self::SUCCESS;
    }
}
