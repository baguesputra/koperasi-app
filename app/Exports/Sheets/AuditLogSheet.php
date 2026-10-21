<?php

namespace App\Exports\Sheets;

use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogSheet implements Export, FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    private int $rowNumber = 0;

    public function __construct(
        private string $title,
        private Collection $logs
    ) {}

    public function collection(): Collection
    {
        return $this->logs;
    }

    public function title(): string
    {
        // Sheet name di Excel maksimal 31 karakter
        return mb_substr($this->title, 0, 31);
    }

    public function headings(): array
    {
        return [
            'No',
            'Waktu (WITA)',
            'Nama Aktor',
            'No. Karyawan',
            'IP Address',
            'Modul / Kategori',
            'Aksi',
            'Keterangan',
            'Perubahan Data (Sebelum -> Sesudah)',
            'User Agent / Perangkat',
        ];
    }

    /**
     * @param  AuditLog  $row
     */
    public function map($row): array
    {
        $this->rowNumber++;

        $aktorNama = $row->user ? $row->user->name : 'Sistem Otomatis';
        $noKaryawan = $row->user?->no_karyawan ?? '-';
        $kategori = AuditLog::labelKategori($row->aksi);

        // Format perubahan diff menjadi teks terstruktur yang mudah dibaca di Excel
        $perubahan = [];
        $dataLama = (array) ($row->data_lama ?? []);
        $dataBaru = (array) ($row->data_baru ?? []);
        $keys = array_unique(array_merge(array_keys($dataLama), array_keys($dataBaru)));

        foreach ($keys as $key) {
            $valLama = $dataLama[$key] ?? null;
            $valBaru = $dataBaru[$key] ?? null;

            if ($valLama !== $valBaru) {
                $strLama = is_array($valLama) ? json_encode($valLama, JSON_UNESCAPED_SLASHES) : ($valLama === null ? 'null' : (string) $valLama);
                $strBaru = is_array($valBaru) ? json_encode($valBaru, JSON_UNESCAPED_SLASHES) : ($valBaru === null ? 'null' : (string) $valBaru);
                $perubahan[] = "[{$key}] {$strLama} -> {$strBaru}";
            }
        }

        $teksPerubahan = empty($perubahan) ? '-' : implode("\n", $perubahan);

        return [
            $this->rowNumber,
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-',
            $aktorNama,
            $noKaryawan,
            $row->ip_address ?? '-',
            $kategori,
            $row->aksi,
            $row->keterangan,
            $teksPerubahan,
            $row->user_agent ?? '-',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 20,  // Waktu
            'C' => 24,  // Nama Aktor
            'D' => 16,  // No Karyawan
            'E' => 18,  // IP Address
            'F' => 18,  // Modul
            'G' => 28,  // Aksi
            'H' => 45,  // Keterangan
            'I' => 40,  // Perubahan
            'J' => 30,  // User Agent
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $this->logs->count() + 1);

        // Header Style (Baris 1)
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'], // Teal Dark Koperasi
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        // Data Row Style
        if ($lastRow > 1) {
            $sheet->getStyle("A2:J{$lastRow}")->applyFromArray([
                'font' => ['size' => 9],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);

            // Alignment spesifik
            $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
