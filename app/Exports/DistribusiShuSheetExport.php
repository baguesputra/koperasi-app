<?php

namespace App\Exports;

use App\Models\ShuDetail;
use App\Models\ShuPeriode;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\HasReferencesToOtherSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DistribusiShuSheetExport implements Export, WithTitle, WithEvents, HasReferencesToOtherSheets
{
    public function __construct(private ShuPeriode $shu) {}

    public function title(): string
    {
        return "Distribusi SHU {$this->shu->tahun_buku}";
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $tahun = $this->shu->tahun_buku;

                // Set column widths matching original template
                $widths = [
                    'A' => 5,
                    'B' => 41,
                    'C' => 18,
                    'D' => 16,
                    'E' => 22,
                    'F' => 22,
                    'G' => 21,
                    'H' => 21,
                    'I' => 22,
                    'J' => 22,
                    'K' => 19,
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // Titles
                $sheet->mergeCells('A1:K1')->setCellValue('A1', 'DISTRIBUSI SHU PERANGGOTA ');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:K2')->setCellValue('A2', 'KOPERASI KARYA MANDIRI DUTA MALL BANJARMASIN ');
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A3:K3')->setCellValue('A3', "TAHUN BUKU {$tahun}");
                $sheet->getStyle('A3')->getFont()->setBold(true);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Two-level Table Headers (Rows 5 & 6)
                $sheet->mergeCells('A5:A6')->setCellValue('A5', 'No');
                $sheet->mergeCells('B5:B6')->setCellValue('B5', 'Nama Anggota');
                $sheet->mergeCells('C5:F5')->setCellValue('C5', 'Partisipasi Anggota');
                $sheet->setCellValue('C6', 'Simp Wajib');
                $sheet->setCellValue('D6', 'Simp Pokok');
                $sheet->setCellValue('E6', 'Modal ( SP + SW )');
                $sheet->setCellValue('F6', 'Transaksi');

                $sheet->mergeCells('G5:H5')->setCellValue('G5', 'SHU Anggota ');
                $sheet->setCellValue('G6', 'Atas Modal');
                $sheet->setCellValue('H6', 'Atas Transaksi');

                $sheet->mergeCells('I5:I6')->setCellValue('I5', 'Jumlah SHU ');
                $sheet->mergeCells('J5:J6')->setCellValue('J5', 'SHU di Serahkan');
                $sheet->mergeCells('K5:K6')->setCellValue('K5', 'Tanda Tangan ');

                // Style Headers
                $headerRange = 'A5:K6';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($headerRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D9D9D9');
                $sheet->getStyle($headerRange)->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $fmtRupiah = '_-[$Rp-421]* #,##0_-;\-[$Rp-421]* #,##0_-;_-[$Rp-421]* "-"??_-;_-@_-';

                // Fetch members sorted by anggota.id
                $details = ShuDetail::with('anggota:id,nama')
                    ->where('shu_periode_id', $this->shu->id)
                    ->join('anggota', 'anggota.id', '=', 'shu_detail.anggota_id')
                    ->orderBy('anggota.id')
                    ->select('shu_detail.*')
                    ->get();

                $startDataRow = 7;
                $count = $details->count();
                $totalRow = $startDataRow + max(1, $count);
                $lastDataRow = $details->isNotEmpty() ? ($startDataRow + $count - 1) : $startDataRow;

                $r = $startDataRow;
                foreach ($details as $idx => $d) {
                    $sheet->setCellValue("A{$r}", $idx + 1);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->setCellValue("B{$r}", $d->anggota->nama ?? '');
                    $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    $sheet->setCellValue("C{$r}", (float) $d->simp_wajib);
                    $sheet->getStyle("C{$r}")->getNumberFormat()->setFormatCode($fmtRupiah);

                    $sheet->setCellValue("D{$r}", (float) $d->simp_pokok);
                    $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode($fmtRupiah);

                    $sheet->setCellValue("E{$r}", "=SUM(C{$r}:D{$r})");
                    $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode($fmtRupiah);

                    $sheet->setCellValue("F{$r}", (float) $d->transaksi);
                    $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode($fmtRupiah);

                    // Cross-sheet formula: proporsi modal * komponen SHU Simpanan (D7 di sheet Komponen SHU)
                    $sheet->setCellValue("G{$r}", "=IF(\$E\${$totalRow}>0, E{$r}/\$E\${$totalRow}*'Komponen SHU'!\$D\$7, 0)");
                    $sheet->getStyle("G{$r}")->getNumberFormat()->setFormatCode($fmtRupiah);

                    // Cross-sheet formula: proporsi transaksi * komponen SHU Transaksi (D8 di sheet Komponen SHU)
                    $sheet->setCellValue("H{$r}", "=IF(\$F\${$totalRow}>0, F{$r}/\$F\${$totalRow}*'Komponen SHU'!\$D\$8, 0)");
                    $sheet->getStyle("H{$r}")->getNumberFormat()->setFormatCode($fmtRupiah);

                    // Jumlah SHU = G + H
                    $sheet->setCellValue("I{$r}", "=G{$r}+H{$r}");
                    $sheet->getStyle("I{$r}")->getNumberFormat()->setFormatCode($fmtRupiah);

                    // SHU diserahkan = pembulatan ke bawah kelipatan 1000
                    $sheet->setCellValue("J{$r}", "=FLOOR(I{$r},1000)");
                    $sheet->getStyle("J{$r}")->getNumberFormat()->setFormatCode($fmtRupiah);

                    $sheet->getStyle("A{$r}:K{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $r++;
                }

                if ($details->isEmpty()) {
                    // Empty state fallback border
                    $sheet->getStyle("A{$startDataRow}:K{$startDataRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }

                // Baris Total SUM
                $sheet->setCellValue("C{$totalRow}", "=SUM(C{$startDataRow}:C{$lastDataRow})");
                $sheet->setCellValue("D{$totalRow}", "=SUM(D{$startDataRow}:D{$lastDataRow})");
                $sheet->setCellValue("E{$totalRow}", "=SUM(E{$startDataRow}:E{$lastDataRow})");
                $sheet->setCellValue("F{$totalRow}", "=SUM(F{$startDataRow}:F{$lastDataRow})");
                $sheet->setCellValue("G{$totalRow}", "=IF(\$E\${$totalRow}>0, E{$totalRow}/\$E\${$totalRow}*'Komponen SHU'!\$D\$7, 0)");
                $sheet->setCellValue("H{$totalRow}", "=SUM(H{$startDataRow}:H{$lastDataRow})");
                $sheet->setCellValue("I{$totalRow}", "=SUM(I{$startDataRow}:I{$lastDataRow})");
                $sheet->setCellValue("J{$totalRow}", "=SUM(J{$startDataRow}:J{$lastDataRow})");

                foreach (['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
                    $sheet->getStyle("{$col}{$totalRow}")->getNumberFormat()->setFormatCode($fmtRupiah);
                }
                $sheet->getStyle("A{$totalRow}:K{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalRow}:K{$totalRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$totalRow}:K{$totalRow}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);

                // Kotak Rekap Bawah
                $rekapValRow = $totalRow + 6;
                $rekapLabelRow = $totalRow + 7;
                $rekapLabelRow2 = $totalRow + 8;

                $sheet->setCellValue("F{$rekapValRow}", "=F{$totalRow}");
                $sheet->getStyle("F{$rekapValRow}")->getNumberFormat()->setFormatCode($fmtRupiah);

                $sheet->setCellValue("G{$rekapValRow}", "='Komponen SHU'!D7");
                $sheet->getStyle("G{$rekapValRow}")->getNumberFormat()->setFormatCode($fmtRupiah);

                $sheet->setCellValue("H{$rekapValRow}", "='Komponen SHU'!D8");
                $sheet->getStyle("H{$rekapValRow}")->getNumberFormat()->setFormatCode($fmtRupiah);

                $sheet->setCellValue("I{$rekapValRow}", "='Komponen SHU'!F7");
                $sheet->getStyle("I{$rekapValRow}")->getNumberFormat()->setFormatCode($fmtRupiah);

                $sheet->setCellValue("J{$rekapValRow}", "=I{$totalRow}-J{$totalRow}");
                $sheet->getStyle("J{$rekapValRow}")->getNumberFormat()->setFormatCode($fmtRupiah);

                $sheet->getStyle("F{$rekapValRow}:J{$rekapValRow}")->getFont()->setBold(true);
                $sheet->getStyle("F{$rekapValRow}:J{$rekapValRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Labels rekap bawah (merged 2 baris: rekapLabelRow : rekapLabelRow2)
                $sheet->mergeCells("F{$rekapLabelRow}:F{$rekapLabelRow2}")->setCellValue("F{$rekapLabelRow}", "Jumlah Transaksi tahun {$tahun}");
                $sheet->mergeCells("G{$rekapLabelRow}:G{$rekapLabelRow2}")->setCellValue("G{$rekapLabelRow}", "Jumlah Dana Modal Dibagi");
                $sheet->mergeCells("H{$rekapLabelRow}:H{$rekapLabelRow2}")->setCellValue("H{$rekapLabelRow}", "Jumlah Dana Transaksi Dibagi");
                $sheet->mergeCells("I{$rekapLabelRow}:I{$rekapLabelRow2}")->setCellValue("I{$rekapLabelRow}", "Jumlah Dana yang di bagi ");
                $sheet->mergeCells("J{$rekapLabelRow}:J{$rekapLabelRow2}")->setCellValue("J{$rekapLabelRow}", "Sisa dana yg tidak di bagi");

                foreach (['F', 'G', 'H', 'I', 'J'] as $col) {
                    $sheet->getStyle("{$col}{$rekapLabelRow}:{$col}{$rekapLabelRow2}")->getFont()->setBold(true);
                    $sheet->getStyle("{$col}{$rekapLabelRow}:{$col}{$rekapLabelRow2}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);
                    $sheet->getStyle("{$col}{$rekapLabelRow}:{$col}{$rekapLabelRow2}")->getBorders()
                        ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
            },
        ];
    }
}

