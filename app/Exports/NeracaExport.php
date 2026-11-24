<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class NeracaExport implements FromArray, WithEvents, WithStrictNullComparison, WithTitle
{
    /** @var array<int, array{type: string, label: string, nilai: float|null}> */
    private array $kiriRows = [];

    /** @var array<int, array{type: string, label: string, nilai: float|null}> */
    private array $kananRows = [];

    private int $maxRows = 0;

    public function __construct(
        private string $judul,
        private string $periodeLabel,
        private array $skontro,
        private ?string $catatan = null,
    ) {
        $this->kiriRows = $this->ratakanSisi($this->skontro['kiri']);
        $this->kananRows = $this->ratakanSisi($this->skontro['kanan']);
        $this->maxRows = max(count($this->kiriRows), count($this->kananRows));

        $kosong = ['type' => 'empty', 'label' => '', 'nilai' => null];
        while (count($this->kiriRows) < $this->maxRows) {
            $this->kiriRows[] = $kosong;
        }
        while (count($this->kananRows) < $this->maxRows) {
            $this->kananRows[] = $kosong;
        }
    }

    public function title(): string
    {
        return 'Neraca';
    }

    public function array(): array
    {
        $baris = [
            [strtoupper((string) config('koperasi.nama', 'KOPERASI KARYAWAN'))],
            [strtoupper($this->judul)],
            ["Per Tanggal: {$this->periodeLabel}"],
            [],
            [
                $this->skontro['kiri']['judul'],
                'Nilai (Rp)',
                '',
                $this->skontro['kanan']['judul'],
                'Nilai (Rp)',
            ],
        ];

        for ($i = 0; $i < $this->maxRows; $i++) {
            $l = $this->kiriRows[$i];
            $r = $this->kananRows[$i];
            $baris[] = [
                $l['type'] === 'empty' ? null : $l['label'],
                in_array($l['type'], ['data', 'subtotal'], true) ? (float) $l['nilai'] : null,
                '',
                $r['type'] === 'empty' ? null : $r['label'],
                in_array($r['type'], ['data', 'subtotal'], true) ? (float) $r['nilai'] : null,
            ];
        }

        $baris[] = [
            $this->skontro['kiri']['total']['label'],
            (float) $this->skontro['kiri']['total']['nilai'],
            '',
            $this->skontro['kanan']['total']['label'],
            (float) $this->skontro['kanan']['total']['nilai'],
        ];

        $seimbang = (bool) ($this->skontro['seimbang'] ?? false);
        $selisih = (float) ($this->skontro['selisih'] ?? 0);

        $baris[] = [];
        $baris[] = [
            'Status Neraca',
            $seimbang
                ? 'Seimbang (Selisih Rp 0)'
                : 'Tidak Seimbang (Selisih Rp '.number_format($selisih, 0, ',', '.').')',
        ];

        if ($this->catatan) {
            $baris[] = [$this->catatan];
        }

        return $baris;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (['A' => 30, 'B' => 20, 'C' => 4, 'D' => 32, 'E' => 20] as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // Judul kop
                $sheet->mergeCells('A1:E1');
                $sheet->mergeCells('A2:E2');
                $sheet->mergeCells('A3:E3');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
                $sheet->getStyle('A1:E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Header kolom kiri & kanan (row 5)
                foreach (['A5:B5', 'D5:E5'] as $range) {
                    $sheet->getStyle($range)->getFont()->setBold(true)->setSize(10);
                    $sheet->getStyle($range)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E2E8F0');
                    $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
                $sheet->getStyle('B5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Isi baris kiri & kanan secara independen
                for ($i = 0; $i < $this->maxRows; $i++) {
                    $row = 6 + $i;
                    $this->gayaSel($sheet, $row, 'A', 'B', $this->kiriRows[$i]);
                    $this->gayaSel($sheet, $row, 'D', 'E', $this->kananRows[$i]);
                }

                // Baris grand total sejajar
                $totalRow = 6 + $this->maxRows;
                foreach (['A'.$totalRow.':B'.$totalRow, 'D'.$totalRow.':E'.$totalRow] as $range) {
                    $sheet->getStyle($range)->getFont()->setBold(true)->setSize(11);
                    $sheet->getStyle($range)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E2E8F0');
                    $borders = $sheet->getStyle($range)->getBorders();
                    $borders->getTop()->setBorderStyle(Border::BORDER_THIN);
                    $borders->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
                    $borders->getLeft()->setBorderStyle(Border::BORDER_THIN);
                    $borders->getRight()->setBorderStyle(Border::BORDER_THIN);
                    $borders->getVertical()->setBorderStyle(Border::BORDER_THIN);
                }
                foreach (['B'.$totalRow, 'E'.$totalRow] as $cell) {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Status & catatan
                $statusRow = $totalRow + 2;
                $sheet->mergeCells("B{$statusRow}:E{$statusRow}");
                $sheet->getStyle("A{$statusRow}:B{$statusRow}")->getFont()->setBold(true)->setSize(10);

                if ($this->catatan) {
                    $noteRow = $statusRow + 1;
                    $sheet->mergeCells("A{$noteRow}:E{$noteRow}");
                    $sheet->getStyle("A{$noteRow}")->getFont()->setItalic(true)->setSize(9);
                    $sheet->getStyle("A{$noteRow}")->getAlignment()->setWrapText(true);
                }
            },
        ];
    }

    /**
     * @return array<int, array{type: string, label: string, nilai: float|null}>
     */
    private function ratakanSisi(array $sisi): array
    {
        $rows = [];
        foreach ($sisi['kelompok'] ?? [] as $k) {
            $rows[] = ['type' => 'section', 'label' => $k['judul'], 'nilai' => null];
            foreach ($k['baris'] ?? [] as $b) {
                $rows[] = ['type' => 'data', 'label' => $b['label'], 'nilai' => (float) $b['nilai']];
            }
            if (! empty($k['subtotal'])) {
                $rows[] = ['type' => 'subtotal', 'label' => $k['subtotal']['label'], 'nilai' => (float) $k['subtotal']['nilai']];
            }
        }

        return $rows;
    }

    private function gayaSel($sheet, int $row, string $colLabel, string $colNilai, array $item): void
    {
        $range = "{$colLabel}{$row}:{$colNilai}{$row}";
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        if ($item['type'] === 'section') {
            $sheet->mergeCells($range);
            $sheet->getStyle($range)->getFont()->setBold(true)->setSize(9.5);
            $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F1F5F9');

            return;
        }

        if ($item['type'] === 'subtotal') {
            $sheet->getStyle($range)->getFont()->setBold(true);
            $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F8FAFC');
        }

        if ($item['type'] !== 'empty') {
            $sheet->getStyle("{$colNilai}{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("{$colNilai}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }
}
