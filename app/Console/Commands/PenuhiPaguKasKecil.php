<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\SettingKas;
use App\Models\User;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Console\Command;

class PenuhiPaguKasKecil extends Command
{
    protected $signature = 'kas:penuhi-pagu';

    protected $description = 'Isi ulang kas kecil dari bank hingga pagu kas kecil (target minus sisa). Idempoten per bulan.';

    public function handle(JurnalKasService $jurnal): int
    {
        $bulan = now()->format('Y-m');
        $target = SettingKas::nilai(SettingKas::CADANGAN, 5_000_000);
        $kas = KasKoperasi::firstOrFail();
        $sisa = (float) $kas->saldo_kas_kecil;
        $kurang = $target - $sisa;

        $penanda = "Penuhi pagu kas kecil {$bulan}";
        $sudah = JurnalKas::where('kategori', 'terima_sisih_kas_kecil')
            ->where('keterangan', 'like', "{$penanda}%")
            ->exists();

        if ($sudah) {
            $this->info("Pagu kas kecil {$bulan} sudah dipenuhi bulan ini. Lewati.");

            return self::SUCCESS;
        }

        if ($kurang <= 0) {
            $this->info('Kas kecil sudah mencapai pagu ('.$this->rupiah($sisa).' >= '.$this->rupiah($target).'). Tidak ada yang disisih.');

            return self::SUCCESS;
        }

        $bank = (float) $kas->saldo_bank;
        $pindah = min($kurang, $bank);

        if ($pindah <= 0) {
            $this->error('Saldo bank kosong, pemenuhan pagu kas kecil dibatalkan.');

            return self::FAILURE;
        }

        $adminId = User::where('no_karyawan', 'ADM-000001')->value('id') ?? User::first()?->id;

        $jurnal->sisihKasKecil(
            $pindah,
            "{$penanda} (target {$this->rupiah($target)})",
            now()->format('Y-m-d'),
            (int) $adminId,
        );

        AuditLog::catat(
            'penuhi_pagu_kas_kecil',
            "Pagu kas kecil {$bulan} dipenuhi: sisih {$this->rupiah($pindah)} dari bank (sisa kas {$this->rupiah($sisa)}, target {$this->rupiah($target)}).",
            ['saldo_kas_kecil' => $sisa, 'saldo_bank' => $bank],
            ['saldo_kas_kecil' => $sisa + $pindah, 'saldo_bank' => $bank - $pindah, 'target' => $target],
            $adminId ? (int) $adminId : null,
        );

        if ($pindah < $kurang) {
            $this->warn('Bank tidak mencukupi: terpindah '.$this->rupiah($pindah).' dari kurang '.$this->rupiah($kurang).'.');

            return self::SUCCESS;
        }

        $this->info('Pagu kas kecil dipenuhi: '.$this->rupiah($pindah).'.');

        return self::SUCCESS;
    }

    private function rupiah(float $n): string
    {
        return 'Rp '.number_format($n, 0, ',', '.');
    }
}
