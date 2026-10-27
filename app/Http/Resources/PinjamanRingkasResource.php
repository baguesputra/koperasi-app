<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pinjaman ringkas untuk list dashboard anggota.
 *
 * Mirror: koperasi-mobile src/api/types.ts (PinjamanRingkas).
 */
class PinjamanRingkasResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $agg = $this->agregatJadwalAktif();
        $terdekat = $this->angsuranBelumBayar()->orderBy('cicilan_ke')->first();

        return [
            'id' => $this->id,
            'nominal' => (float) $this->nominal,
            'tenor_bulan' => (int) $this->tenor_bulan,
            'status' => $this->status,
            'tanggal_pengajuan' => $this->tanggal_pengajuan?->format('Y-m-d'),
            'tanggal_pencairan' => $this->tanggal_pencairan?->format('Y-m-d'),
            'sisa_angsuran' => $agg['sisa'],
            'total_angsuran' => $agg['total'],
            'sisa_total_bayar' => (float) $agg['sisa_bayar'],
            'angsuran_berikutnya' => $terdekat ? [
                'cicilan_ke' => $terdekat->cicilan_ke,
                'total_bayar' => (float) $terdekat->total_bayar,
                'tanggal_jatuh_tempo' => $terdekat->tanggal_jatuh_tempo?->format('Y-m-d'),
            ] : null,
            'sudah_pakai_percepatan' => (bool) $this->sudah_pakai_percepatan,
        ];
    }
}
