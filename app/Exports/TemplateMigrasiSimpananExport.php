<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TemplateMigrasiSimpananExport implements FromArray, WithColumnFormatting, WithHeadings
{
    public function array(): array
    {
        return [
            ['TOP-100001', 'wajib', '2024-01', 50000, '2024-01-15'],
        ];
    }

    public function headings(): array
    {
        return [
            'No Karyawan', 'Jenis', 'Bulan Periode', 'Jumlah', 'Tanggal Input',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
