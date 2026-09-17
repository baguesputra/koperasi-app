<?php

namespace App\Imports;

use App\Models\Anggota;
use App\Services\Migrasi\MigrasiPinjamanService;
use App\Services\Migrasi\PencocokanNamaService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PinjamanMigrasiImport implements ToCollection, WithHeadingRow
{
    public array $berhasil = [];

    public array $gagal = [];

    public function __construct(
        private MigrasiPinjamanService $migrasi,
        private PencocokanNamaService $pencocokan = new PencocokanNamaService,
    ) {}

    public function collection(Collection $rows): void
    {
        $userId = auth()->id();
        $this->pencocokan->lupakanCache();

        foreach ($rows as $index => $row) {
            $baris = $index + 2;

            $validasi = $this->validasiBaris($row, $baris);
            if ($validasi !== true) {
                $this->gagal[] = $validasi;

                continue;
            }

            $noKaryawan = trim((string) ($row['no_karyawan'] ?? ''));
            $anggota = $noKaryawan !== ''
                ? Anggota::where('no_karyawan', $noKaryawan)->first()
                : $this->pencocokan->cocokkan(trim((string) ($row['nama'] ?? '')));

            try {
                $pinjaman = $this->migrasi->proses(
                    anggota: $anggota,
                    tanggalPengajuan: $this->parseTanggal($row['tanggal_pengajuan']),
                    tanggalPencairan: $this->parseTanggal($row['tanggal_pencairan']),
                    nominal: (float) $row['nominal'],
                    tenorBulan: (int) $row['tenor_bulan'],
                    persentaseBunga: ($row['bunga_persen'] === null || trim((string) $row['bunga_persen']) === '')
                        ? 0.0
                        : (float) $row['bunga_persen'],
                    sudahBayar: (int) ($row['sudah_bayar_cicilan_ke'] ?? 0),
                    tanggalBayarTerakhir: empty($row['tanggal_bayar_terakhir'])
                        ? null
                        : $this->parseTanggal($row['tanggal_bayar_terakhir']),
                    userId: $userId,
                );

                $this->berhasil[] = "{$anggota->nama} ({$anggota->no_karyawan}) - Rp ".number_format($pinjaman->nominal, 0, ',', '.');
            } catch (\Throwable $e) {
                $this->gagal[] = "Baris {$baris}: {$e->getMessage()}";
            }
        }
    }

    private function validasiBaris($row, int $baris): true|string
    {
        $noKaryawan = trim((string) ($row['no_karyawan'] ?? ''));
        $nama = trim((string) ($row['nama'] ?? ''));

        if ($noKaryawan === '' && $nama === '') {
            return "Baris {$baris}: No Karyawan atau Nama wajib diisi.";
        }

        $anggota = $noKaryawan !== ''
            ? Anggota::where('no_karyawan', $noKaryawan)->where('status', 'aktif')->first()
            : $this->pencocokan->cocokkan($nama);

        if (! $anggota) {
            return "Baris {$baris}: Anggota '".($noKaryawan !== '' ? $noKaryawan : $nama)."' tidak ditemukan atau tidak aktif.".$this->teksSaran($nama, $noKaryawan);
        }

        if (! $this->parseTanggal($row['tanggal_pengajuan'] ?? null)) {
            return "Baris {$baris}: Tanggal Pengajuan tidak valid.";
        }

        if (! $this->parseTanggal($row['tanggal_pencairan'] ?? null)) {
            return "Baris {$baris}: Tanggal Pencairan tidak valid.";
        }

        if (! is_numeric($row['nominal'] ?? null) || (float) $row['nominal'] <= 0) {
            return "Baris {$baris}: Nominal harus angka lebih dari 0.";
        }

        if (! is_numeric($row['tenor_bulan'] ?? null) || (int) $row['tenor_bulan'] < 1) {
            return "Baris {$baris}: Tenor Bulan minimal 1.";
        }

        $bunga = $row['bunga_persen'] ?? null;

        if ($bunga !== null && trim((string) $bunga) !== '' && (! is_numeric($bunga) || (float) $bunga < 0)) {
            return "Baris {$baris}: Bunga Persen harus angka 0 atau lebih.";
        }

        $sudahBayar = (int) ($row['sudah_bayar_cicilan_ke'] ?? 0);

        if ($sudahBayar < 0 || $sudahBayar > (int) $row['tenor_bulan']) {
            return "Baris {$baris}: Sudah Bayar Cicilan Ke harus 0 sampai tenor.";
        }

        if ($sudahBayar > 0 && ! empty($row['tanggal_bayar_terakhir']) && ! $this->parseTanggal($row['tanggal_bayar_terakhir'])) {
            return "Baris {$baris}: Tanggal Bayar Terakhir tidak valid.";
        }

        return true;
    }

    private function teksSaran(string $nama, string $noKaryawan): string
    {
        if ($noKaryawan !== '' || trim($nama) === '') {
            return '';
        }

        $saran = $this->pencocokan->saran($nama);

        if ($saran === []) {
            return '';
        }

        $teks = array_map(
            fn ($item) => "{$item['anggota']->nama} ({$item['anggota']->no_karyawan})",
            $saran
        );

        return ' Maksud: '.implode(' / ', $teks).'? Isi No Karyawan biar pasti.';
    }

    private function parseTanggal($value): ?string
    {
        try {
            if ($value === null || trim((string) $value) === '') {
                return null;
            }

            $teks = trim((string) $value);

            if (is_numeric($teks)) {
                return Date::excelToDateTimeObject((float) $teks)->format('Y-m-d');
            }

            // Format Indonesia (d/m/Y) dulu — Carbon::parse default m/d/Y membalik tanggal.
            foreach (['j/n/Y', 'j-n-Y', 'j.n.Y', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'Y/m/d', 'j M Y', 'd M Y'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $teks)->format('Y-m-d');
                } catch (\Throwable $e) {
                    continue;
                }
            }

            return Carbon::parse($teks)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
