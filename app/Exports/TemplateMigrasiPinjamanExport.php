<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TemplateMigrasiPinjamanExport implements FromArray, WithColumnFormatting, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'TOP-100001', '2024-01-10', '2024-01-15',
                10000000, 10, 1, 4, '2024-05-15',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'No Karyawan', 'Tanggal Pengajuan', 'Tanggal Pencairan',
            'Nominal', 'Tenor Bulan', 'Bunga Persen', 'Sudah Bayar Cicilan Ke', 'Tanggal Bayar Terakhir',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
