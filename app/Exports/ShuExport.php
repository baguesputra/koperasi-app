<?php

namespace App\Exports;

use App\Models\ShuPeriode;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ShuExport implements Export, WithMultipleSheets
{
    use Exportable;

    public function __construct(private ShuPeriode $shu) {}

    /**
     * @return array<int, Export>
     */
    public function sheets(): array
    {
        return [
            new KomponenShuSheetExport($this->shu),
            new DistribusiShuSheetExport($this->shu),
        ];
    }
}
