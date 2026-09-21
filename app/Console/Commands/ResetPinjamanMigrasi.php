<?php

namespace App\Console\Commands;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PenomoranDokumen;
use App\Models\Pinjaman;
use App\Services\Dokumen\PenomoranDokumenService;
use App\Services\Pinjaman\PerhitunganBungaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ResetPinjamanMigrasi extends Command
{
    protected $signature = 'migrasi:reset-pinjaman
        {--dry-run : Pratinjau tanpa menghapus}
        {--force : Eksekusi penghapusan}
        {--file=docs/data-koperasi/migrasi-pinjaman-final-20260917-v3-1persen.xlsx : Excel acuan simulasi kas}';

    protected $description = 'Hapus semua pinjaman + jurnal terkait, nomor dokumen diulang dari awal.';

    public function handle(PerhitunganBungaService $bunga): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force')) {
            $this->error('Tanpa --dry-run wajib --force.');

            return self::FAILURE;
        }

        $pinjamanIds = Pinjaman::pluck('id');
        $angsuranIds = $pinjamanIds->isEmpty()
            ? collect()
            : Angsuran::whereIn('pinjaman_id', $pinjamanIds)->pluck('id');

        $jurnalPinjaman = JurnalKas::whereIn('kategori', ['pencairan_pinjaman', 'pembayaran_angsuran'])->get();
        $jurnalResign = $angsuranIds->isEmpty() ? collect() : JurnalKas::whereIn('kategori', ['pelunasan_resign_pinjaman', 'pelunasan_resign_simpanan'])
            ->whereIn('referensi_id', $angsuranIds)->get();

        $pengajuanPercepatan = $pinjamanIds->isEmpty() ? 0 : DB::table('pengajuan_percepatan')->whereIn('pinjaman_id', $pinjamanIds)->count();
        $auditMigrasi = AuditLog::where('aksi', 'migrasi_pinjaman')->count();
        $nomorPjm = PenomoranDokumen::where('jenis', PenomoranDokumenService::JENIS_PINJAMAN)->count();

        $anggotaTerdampak = $pinjamanIds->isEmpty() ? collect() : Anggota::whereIn('id', Pinjaman::whereIn('id', $pinjamanIds)->select('anggota_id'))
            ->whereIn('id', Pinjaman::whereIn('id', $pinjamanIds)->whereIn('id', DB::table('pengajuan_percepatan')->select('pinjaman_id'))->select('anggota_id'))
            ->pluck('nama');

        $hapusIds = $jurnalPinjaman->pluck('id')->merge($jurnalResign->pluck('id'));

        $net = fn (string $kantong, $kecualikan) => (float) JurnalKas::where('kantong', $kantong)
            ->whereNotIn('id', $kecualikan->all() ?: [0])
            ->selectRaw("SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE -jumlah END) as total")->value('total');
        $saldoPinjamanBaru = max(0, $net('pinjaman', $hapusIds));
        $saldoTransitBaru = max(0, $net('pengembalian_simpanan', $hapusIds));

        $this->table(['Cakupan', 'Jumlah'], [
            ['Pinjaman', $pinjamanIds->count()],
            ['Angsuran', $angsuranIds->count()],
            ['Jurnal pencairan/angsuran', $jurnalPinjaman->count()],
            ['Jurnal pelunasan resign terkait', $jurnalResign->count()],
            ['Pengajuan percepatan', $pengajuanPercepatan],
            ['Audit migrasi_pinjaman', $auditMigrasi],
            ['Counter nomor KOP-PJM', $nomorPjm],
            ['Saldo pinjaman setelah reset', 'Rp '.number_format($saldoPinjamanBaru, 0, ',', '.')],
            ['Saldo transit setelah reset', 'Rp '.number_format($saldoTransitBaru, 0, ',', '.')],
        ]);

        if ($anggotaTerdampak->isNotEmpty()) {
            $this->warn('Anggota percepatan terdampak: '.$anggotaTerdampak->join(', '));
        }

        $simulasi = $this->simulasiKas($bunga, (string) $this->option('file'), $saldoPinjamanBaru);
        if ($simulasi === null) {
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('Dry-run selesai, tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        $arsip = 'docs/data-koperasi/backup/reset-pinjaman-'.now()->format('Ymd-His').'.json';
        file_put_contents(base_path($arsip), json_encode([
            'pinjaman' => Pinjaman::all()->toArray(),
            'angsuran' => Angsuran::whereIn('pinjaman_id', $pinjamanIds)->get()->toArray(),
            'jurnal_terhapus' => JurnalKas::whereIn('id', $hapusIds->all() ?: [0])->get()->toArray(),
            'audit_migrasi' => AuditLog::where('aksi', 'migrasi_pinjaman')->get()->toArray(),
            'penomoran' => PenomoranDokumen::where('jenis', PenomoranDokumenService::JENIS_PINJAMAN)->get()->toArray(),
        ], JSON_UNESCAPED_UNICODE));
        $this->info('Arsip: '.$arsip);

        DB::transaction(function () use ($hapusIds, $saldoPinjamanBaru, $saldoTransitBaru, $pinjamanIds) {
            JurnalKas::whereIn('id', $hapusIds->all() ?: [0])->delete();
            AuditLog::where('aksi', 'migrasi_pinjaman')->delete();
            PenomoranDokumen::where('jenis', PenomoranDokumenService::JENIS_PINJAMAN)->delete();
            DB::table('pinjaman')->whereIn('id', $pinjamanIds->all() ?: [0])->delete();

            foreach (['pinjaman', 'pengembalian_simpanan'] as $kantong) {
                $berjalan = 0.0;
                JurnalKas::where('kantong', $kantong)->orderBy('tanggal')->orderBy('id')
                    ->each(function (JurnalKas $j) use (&$berjalan) {
                        $berjalan += $j->tipe === 'masuk' ? (float) $j->jumlah : -(float) $j->jumlah;
                        $j->update(['saldo_setelah' => $berjalan]);
                    });
            }

            KasKoperasi::firstOrFail()->update([
                'saldo_pinjaman' => $saldoPinjamanBaru,
                'saldo_pengembalian_simpanan' => $saldoTransitBaru,
            ]);

            AuditLog::catat('reset_pinjaman_migrasi', "Reset pinjaman: {$pinjamanIds->count()} pinjaman dihapus, nomor KOP-PJM diulang dari awal.");
        });

        $this->info("Reset selesai: {$pinjamanIds->count()} pinjaman dihapus.");

        return self::SUCCESS;
    }

    private function simulasiKas(PerhitunganBungaService $bunga, string $file, float $saldoAwal): ?array
    {
        if (! file_exists(base_path($file))) {
            $this->error("File tidak ada: {$file}");

            return null;
        }

        $rows = IOFactory::load(base_path($file))->getActiveSheet()->toArray(null, true, true, false);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), array_shift($rows));
        $idx = fn (string $kunci) => array_search($kunci, array_map(fn ($h) => str_contains($h, $kunci) ? $kunci : $h, $header));

        $iCair = $idx('pencairan');
        $iNominal = array_search('nominal', $header);
        $iTenor = array_search('tenor bulan', $header);
        $iSudah = $idx('sudah bayar');
        $iTglBayar = $idx('bayar terakhir');
        $iNoKaryawan = $idx('karyawan');

        $gagal = 0;
        $contohGagal = [];
        $event = [];

        foreach ($rows as $n => $r) {
            $baris = $n + 2;
            $anggota = $iNoKaryawan !== false ? Anggota::where('no_karyawan', trim((string) $r[$iNoKaryawan]))->where('status', 'aktif')->first() : null;

            if (! $anggota || ! $this->tanggal($r[$iCair]) || ! is_numeric($r[$iNominal]) || (float) $r[$iNominal] <= 0
                || ! is_numeric($r[$iTenor]) || (int) $r[$iTenor] < 1 || (int) ($r[$iSudah] ?? 0) > (int) $r[$iTenor]) {
                $gagal++;
                if (count($contohGagal) < 10) {
                    $contohGagal[] = "Baris {$baris}: ".($r[$iNoKaryawan] ?? '?');
                }

                continue;
            }

            $nominal = (float) $r[$iNominal];
            $tenor = (int) $r[$iTenor];
            $cair = Carbon::parse($this->tanggal($r[$iCair]));
            $batas = $this->tanggal($r[$iTglBayar] ?? null) ? Carbon::parse($this->tanggal($r[$iTglBayar])) : null;

            $event[] = ['tgl' => $cair->format('Y-m-d'), 'keluar_dulu' => true, 'jumlah' => -$nominal];

            foreach ($bunga->buatJadwal($nominal, $tenor, 1.0) as $j) {
                if ($j['cicilan_ke'] > (int) ($r[$iSudah] ?? 0)) {
                    break;
                }
                $tempo = $cair->copy()->addMonths($j['cicilan_ke'] - 1)->endOfMonth();
                if ($batas && $tempo->greaterThan($batas)) {
                    $tempo = $batas;
                }
                $event[] = ['tgl' => $tempo->format('Y-m-d'), 'keluar_dulu' => false, 'jumlah' => $j['total_bayar']];
            }
        }

        usort($event, fn ($a, $b) => [$a['tgl'], $a['keluar_dulu'] ? 0 : 1] <=> [$b['tgl'], $b['keluar_dulu'] ? 0 : 1]);

        $berjalan = $saldoAwal;
        $minimum = $berjalan;
        foreach ($event as $e) {
            $berjalan += $e['jumlah'];
            $minimum = min($minimum, $berjalan);
        }

        $this->table(['Simulasi import ulang', 'Nilai'], [
            ['Baris valid / gagal', (count($rows) - $gagal).' / '.$gagal],
            ['Saldo minimum simulasi', 'Rp '.number_format($minimum, 0, ',', '.')],
            ['Topup dibutuhkan', $minimum < 0 ? 'Rp '.number_format(-$minimum, 0, ',', '.') : '-'],
        ]);

        foreach ($contohGagal as $c) {
            $this->warn($c);
        }

        return ['gagal' => $gagal, 'minimum' => $minimum];
    }

    private function tanggal($nilai): ?string
    {
        if ($nilai === null || trim((string) $nilai) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim((string) $nilai))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
