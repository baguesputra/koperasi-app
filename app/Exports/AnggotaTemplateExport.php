<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AnggotaTemplateExport implements FromArray, WithColumnFormatting, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'Budi Santoso', 'TOP-100099', '3573000000000001', 'Banjarmasin',
                'Operasional', 'Operasional', 'Lapangan', 'staff',
                '2024-01-15', '2024-06-15',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Nama', 'No Karyawan', 'No KTP', 'Cabang',
            'Unit Bisnis', 'Department', 'Divisi', 'Jabatan',
            'Tanggal Mulai Kerja', 'Tanggal Jadi Anggota',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT, // No KTP - paksa jadi teks, jangan angka
            'B' => NumberFormat::FORMAT_TEXT, // No Karyawan - jaga-jaga juga
        ];
    }
}
