<?php

namespace App\Exports;

use App\Models\ShuPeriode;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\HasReferencesToOtherSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KomponenShuSheetExport implements Export, WithTitle, WithEvents, HasReferencesToOtherSheets
{
    public function __construct(private ShuPeriode $shu) {}

    public function title(): string
    {
        return 'Komponen SHU';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $tahun = $this->shu->tahun_buku;

                // Column Widths
                $widths = [
                    'A' => 5, 'B' => 36, 'C' => 20, 'D' => 16, 'E' => 4,
                    'F' => 28, 'G' => 4, 'H' => 26, 'I' => 3, 'J' => 18,
                    'K' => 16, 'L' => 3, 'M' => 26, 'N' => 18, 'O' => 16,
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // Header Titles
                $sheet->mergeCells('A1:D1')->setCellValue('A1', 'Komponen SHU');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:D2')->setCellValue('A2', 'Koperasi Karya Mandiri Duta Mall ');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A3:D3')->setCellValue('A3', "Tahun {$tahun}");
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Title Pengurus & Pengawas
                $sheet->mergeCells('H3:K3')->setCellValue('H3', "SHU Pengurus Koperasi  Tahun {$tahun}");
                $sheet->getStyle('H3')->getFont()->setBold(true);
                $sheet->getStyle('H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('M3:O3')->setCellValue('M3', "SHU Pengawas Tahun {$tahun}");
                $sheet->getStyle('M3')->getFont()->setBold(true);
                $sheet->getStyle('M3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Table Header Komponen SHU (Row 5)
                $headers1 = ['A5' => 'No', 'B5' => 'Komponen', 'C5' => 'Besaran Prosentase', 'D5' => 'Nilainya '];
                foreach ($headers1 as $cell => $text) {
                    $sheet->setCellValue($cell, $text);
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
                    $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }

                // 8 Komponen bawaan
                $komponenList = [
                    ['no' => 1, 'nama' => 'Cadangan ', 'persen' => $this->shu->pct_cadangan, 'fill' => '7CEB99'],
                    ['no' => 2, 'nama' => 'Jasa partisipasi Atas Simpanan', 'persen' => $this->shu->pct_jasa_simpanan, 'fill' => 'A9D18E'],
                    ['no' => 3, 'nama' => 'Jasa Partisipasi atas Transaksi', 'persen' => $this->shu->pct_jasa_transaksi, 'fill' => 'A9D18E'],
                    ['no' => 4, 'nama' => 'SHU Bagian Pengurus', 'persen' => $this->shu->pct_pengurus, 'fill' => 'FFFF00'],
                    ['no' => 5, 'nama' => 'SHU Bagian Pengawas', 'persen' => $this->shu->pct_pengawas, 'fill' => 'FFFF00'],
                    ['no' => 6, 'nama' => 'SHU Dana Pendidikan ', 'persen' => $this->shu->pct_pendidikan, 'fill' => '7CEB99'],
                    ['no' => 7, 'nama' => 'SHU dana Sosial ', 'persen' => $this->shu->pct_sosial, 'fill' => null],
                    ['no' => 8, 'nama' => 'SHU Pengembangan Wilayah Kerja ', 'persen' => $this->shu->pct_wilker, 'fill' => '7CEB99'],
                ];

                // Tambahkan custom komponen jika ada
                $this->shu->loadMissing('customKomponen');
                $noKomp = 9;
                foreach ($this->shu->customKomponen ?? [] as $custom) {
                    $komponenList[] = [
                        'no' => $noKomp++,
                        'nama' => $custom->label,
                        'persen' => (float) $custom->persen,
                        'fill' => '7CEB99',
                    ];
                }

                $fmtAccounting = '_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)';

                $startRowKomp = 6;
                $currRow = $startRowKomp;
                $jumlahRow = $startRowKomp + count($komponenList);

                foreach ($komponenList as $k) {
                    $sheet->setCellValue("A{$currRow}", $k['no']);
                    $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->setCellValue("B{$currRow}", $k['nama']);

                    $pctVal = (float) $k['persen'];
                    if ($pctVal > 1) {
                        $pctVal = $pctVal / 100;
                    }

                    $sheet->setCellValue("C{$currRow}", $pctVal);
                    $sheet->getStyle("C{$currRow}")->getNumberFormat()->setFormatCode('0%');
                    $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->setCellValue("D{$currRow}", "=D{$jumlahRow}*C{$currRow}");
                    $sheet->getStyle("D{$currRow}")->getNumberFormat()->setFormatCode($fmtAccounting);

                    foreach (['A', 'B', 'C', 'D'] as $col) {
                        $sheet->getStyle("{$col}{$currRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                        if ($k['fill']) {
                            $sheet->getStyle("{$col}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($k['fill']);
                        }
                    }
                    $currRow++;
                }

                // Baris Jumlah
                $sheet->mergeCells("A{$jumlahRow}:B{$jumlahRow}")->setCellValue("A{$jumlahRow}", 'Jumlah');
                $sheet->getStyle("A{$jumlahRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$jumlahRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue("C{$jumlahRow}", "=SUM(C{$startRowKomp}:C" . ($jumlahRow - 1) . ")");
                $sheet->getStyle("C{$jumlahRow}")->getNumberFormat()->setFormatCode('0%');
                $sheet->getStyle("C{$jumlahRow}")->getFont()->setBold(true);
                $sheet->getStyle("C{$jumlahRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue("D{$jumlahRow}", (float) $this->shu->total_shu);
                $sheet->getStyle("D{$jumlahRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("D{$jumlahRow}")->getFont()->setBold(true);

                foreach (['A', 'B', 'C', 'D'] as $col) {
                    $sheet->getStyle("{$col}{$jumlahRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("{$col}{$jumlahRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C5E0B4');
                }

                // Baris SUM D
                $sheet->setCellValue("D" . ($jumlahRow + 1), "=SUM(D{$startRowKomp}:D" . ($jumlahRow - 1) . ")");
                $sheet->getStyle("D" . ($jumlahRow + 1))->getNumberFormat()->setFormatCode($fmtAccounting);

                // Rekap Kolom F
                $sheet->mergeCells('F5:F6')->setCellValue('F5', "Simpanan + Transaksi untuk anggota");
                $sheet->getStyle('F5')->getFont()->setBold(true);
                $sheet->getStyle('F5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                $sheet->getStyle('F5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('76E3FF');
                $sheet->getStyle('F5:F6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $sheet->setCellValue('F7', "=SUM(D7+D8)");
                $sheet->getStyle('F7')->getNumberFormat()->setFormatCode($fmtAccounting);
                $sheet->getStyle('F7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('76E3FF');
                $sheet->getStyle('F7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $sheet->setCellValue('F10', "Jasa Pengurus ");
                $sheet->getStyle('F10')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

                $sheet->setCellValue('F11', "=K15");
                $sheet->getStyle('F11')->getNumberFormat()->setFormatCode($fmtAccounting);
                $sheet->getStyle('F11')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

                $sheet->setCellValue('F13', "Jasa Pengawas");
                $sheet->getStyle('F13')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

                $sheet->setCellValue('F14', "=O9");
                $sheet->getStyle('F14')->getNumberFormat()->setFormatCode($fmtAccounting);
                $sheet->getStyle('F14')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

                $sheet->setCellValue('F16', "Pengurus n pengawas");
                $sheet->getStyle('F16')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

                $sheet->setCellValue('F17', "=SUM(F11+F14)");
                $sheet->getStyle('F17')->getNumberFormat()->setFormatCode($fmtAccounting);
                $sheet->getStyle('F17')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

                $sheet->setCellValue('F19', "dana yg di bagikan ");
                $sheet->getStyle('F19')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C5E0B4');

                $sheet->setCellValue('F20', "=F7+F17");
                $sheet->getStyle('F20')->getNumberFormat()->setFormatCode($fmtAccounting);
                $sheet->getStyle('F20')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C5E0B4');

                $sheet->setCellValue('F23', "Dana yg tidak di bagi");
                $sheet->getStyle('F23')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('7CEB99');

                // Dana tak dibagi = Cadangan (D6) + Pendidikan (D11) + Sosial (D12) + Wilker (D13) + custom
                $selTakBagi = ['D6', 'D11', 'D12', 'D13'];
                for ($ci = 14; $ci < $jumlahRow; $ci++) {
                    $selTakBagi[] = "D{$ci}";
                }
                $sheet->setCellValue('F24', '=' . implode('+', $selTakBagi));
                $sheet->getStyle('F24')->getNumberFormat()->setFormatCode($fmtAccounting);
                $sheet->getStyle('F24')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('7CEB99');

                // Tabel Pengurus (H - K)
                $pengurusList = $this->shu->pengurus_rincian ?? [];
                $rPengurus = 5;
                foreach ($pengurusList as $p) {
                    $sheet->setCellValue("H{$rPengurus}", $p['jabatan'] ?? '');
                    $sheet->setCellValue("J{$rPengurus}", $p['nama'] ?? '');
                    $sheet->setCellValue("K{$rPengurus}", (float) ($p['nominal'] ?? 0));
                    $sheet->getStyle("K{$rPengurus}")->getNumberFormat()->setFormatCode($fmtAccounting);
                    $rPengurus++;
                }
                $lastRPengurus = max(5, $rPengurus - 1);

                $sheet->mergeCells('H15:J15')->setCellValue('H15', 'saldo yg di alokasikan ');
                $sheet->getStyle('H15')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('H15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('K15', '=D9');
                $sheet->getStyle('K15')->getFont()->setBold(true);
                $sheet->getStyle('K15')->getNumberFormat()->setFormatCode($fmtAccounting);

                $sheet->mergeCells('H16:J16')->setCellValue('H16', 'saldo yg di bagikan ');
                $sheet->getStyle('H16')->getFont()->setBold(true);
                $sheet->getStyle('H16')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('K16', "=SUM(K5:K{$lastRPengurus})");
                $sheet->getStyle('K16')->getFont()->setBold(true);
                $sheet->getStyle('K16')->getNumberFormat()->setFormatCode($fmtAccounting);

                $sheet->mergeCells('H17:J17')->setCellValue('H17', 'sisa dana ');
                $sheet->getStyle('H17')->getFont()->setBold(true);
                $sheet->getStyle('H17')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('K17', '=K15-K16');
                $sheet->getStyle('K17')->getFont()->setBold(true);
                $sheet->getStyle('K17')->getNumberFormat()->setFormatCode($fmtAccounting);

                // Tabel Pengawas (M - O)
                $pengawasList = $this->shu->pengawas_rincian ?? [];
                $rPengawas = 5;
                foreach ($pengawasList as $pw) {
                    $sheet->setCellValue("M{$rPengawas}", $pw['jabatan'] ?? 'pengawas');
                    $sheet->setCellValue("N{$rPengawas}", $pw['nama'] ?? '');
                    $sheet->setCellValue("O{$rPengawas}", (float) ($pw['nominal'] ?? 0));
                    $sheet->getStyle("O{$rPengawas}")->getNumberFormat()->setFormatCode($fmtAccounting);
                    $rPengawas++;
                }
                $lastRPengawas = max(5, $rPengawas - 1);

                $sheet->mergeCells('M9:N9')->setCellValue('M9', 'saldo yg di alokasikan ');
                $sheet->getStyle('M9')->getFont()->setBold(true);
                $sheet->getStyle('M9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('O9', '=D10');
                $sheet->getStyle('O9')->getFont()->setBold(true);
                $sheet->getStyle('O9')->getNumberFormat()->setFormatCode($fmtAccounting);

                $sheet->mergeCells('M10:N10')->setCellValue('M10', 'saldo yg di bagikan ');
                $sheet->getStyle('M10')->getFont()->setBold(true);
                $sheet->getStyle('M10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('O10', "=SUM(O5:O{$lastRPengawas})");
                $sheet->getStyle('O10')->getFont()->setBold(true);
                $sheet->getStyle('O10')->getNumberFormat()->setFormatCode($fmtAccounting);

                $sheet->mergeCells('M11:N11')->setCellValue('M11', 'sisa dana ');
                $sheet->getStyle('M11')->getFont()->setBold(true);
                $sheet->getStyle('M11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('O11', '=O9-O10');
                $sheet->getStyle('O11')->getFont()->setBold(true);
                $sheet->getStyle('O11')->getNumberFormat()->setFormatCode($fmtAccounting);

                $sheet->mergeCells('M14:O14')->setCellValue('M14', 'SHU Anggota, SHU Pengurus, SHU Pengawas');
                $sheet->getStyle('M14')->getFont()->setBold(true);
                $sheet->getStyle('M14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('M15', 'Jumlah sisa dana yg tidak di serahkan ');
                $sheet->getStyle('M15')->getFont()->setBold(true);

                // Rumus O15 mengambil selisih pembulatan anggota dari sheet Distribusi
                $totalMembers = $this->shu->detail()->count();
                $totalRowDist = 7 + max(1, $totalMembers);
                $rekapValRow = $totalRowDist + 6;
                $distTitle = "Distribusi SHU {$tahun}";
                $sheet->setCellValue('O15', "=O11+K17+'{$distTitle}'!J{$rekapValRow}");
                $sheet->getStyle('O15')->getFont()->setBold(true);
                $sheet->getStyle('O15')->getNumberFormat()->setFormatCode($fmtAccounting);
            },
        ];
    }
}
