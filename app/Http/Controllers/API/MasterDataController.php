<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    /**
     * Get list of cabang
     */
    public function cabang(Request $request)
    {
        $cabang = \App\Models\Anggota::distinct()
            ->pluck('cabang')
            ->sort()
            ->values()
            ->all();

        return response()->json($cabang);
    }

    /**
     * Get list of unit bisnis
     */
    public function unitBisnis(Request $request)
    {
        $unitBisnis = \App\Models\Anggota::distinct()
            ->pluck('unit_bisnis')
            ->sort()
            ->values()
            ->all();

        return response()->json($unitBisnis);
    }

    /**
     * Get list of jabatan
     */
    public function jabatan(Request $request)
    {
        $jabatan = \App\Models\Anggota::distinct()
            ->pluck('jabatan')
            ->sort()
            ->values()
            ->all();

        return response()->json($jabatan);
    }

    /**
     * Get list of divisi
     */
    public function divisi(Request $request)
    {
        $divisi = \App\Models\Anggota::distinct()
            ->pluck('divisi')
            ->sort()
            ->values()
            ->all();

        return response()->json($divisi);
    }

    /**
     * Get list of department
     */
    public function department(Request $request)
    {
        $department = \App\Models\Anggota::distinct()
            ->pluck('department')
            ->sort()
            ->values()
            ->all();

        return response()->json($department);
    }

    /**
     * Get tabel tenor
     */
    public function tabelTenor(Request $request)
    {
        $tabelTenor = \App\Models\TabelTenor::orderBy('nominal_min')
            ->get(['nominal_min', 'nominal_max', 'tenor_maksimal_bulan']);

        return response()->json($tabelTenor);
    }

    /**
     * Get setting simpanan
     */
    public function settingSimpanan(Request $request)
    {
        $settingSimpanan = \App\Models\SettingSimpanan::orderBy('id')
            ->get(['jenis', 'label', 'nominal']);

        return response()->json($settingSimpanan);
    }
}