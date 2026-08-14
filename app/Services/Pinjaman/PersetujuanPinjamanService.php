<?php

namespace App\Services\Pinjaman;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PersetujuanPinjamanService
{
    public function __construct(private PerhitunganBungaService $bunga) {}

    public function approveBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'approved_bendahara',
            'catatan_bendahara' => $catatan,
        ]);
    }

    public function rejectBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_bendahara' => $catatan,
        ]);
    }

    /**
     * Ketua approve final -> pinjaman langsung aktif, jadwal angsuran ter-generate,
     * saldo kas berkurang, tercatat di jurnal.
     */
    public function approveKetua(Pinjaman $pinjaman, string $catatan): void
    {
        $this->cairkan($pinjaman, [
            'status' => 'aktif',
            'catatan_ketua' => $catatan,
        ]);
    }

    /**
     * Bendahara mencairkan pinjaman yang diajukan sendiri oleh Ketua
     * (cair_oleh_bendahara = true). Status jadi aktif, jadwal angsuran
     * ter-generate, saldo kas berkurang, tercatat di jurnal atas nama bendahara.
     */
    public function cairBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $catatanBendahara = $pinjaman->catatan_bendahara
            ? $pinjaman->catatan_bendahara.' | '.$catatan
            : $catatan;

        $this->cairkan($pinjaman, [
            'status' => 'aktif',
            'catatan_bendahara' => $catatanBendahara,
        ]);
    }

    /**
     * Logika pencairan bersama: validasi saldo, set status aktif + tanggal
     * pencairan, generate jadwal angsuran, kurangi saldo kas, catat jurnal.
     */
    private function cairkan(Pinjaman $pinjaman, array $update): void
    {
        $kas = KasKoperasi::firstOrFail();

        if ($kas->saldo_saat_ini < $pinjaman->nominal) {
            throw new RuntimeException(
                'Saldo kas koperasi tidak mencukupi untuk pencairan pinjaman ini. Saldo saat ini: Rp '.
                number_format($kas->saldo_saat_ini, 0, ',', '.')
            );
        }

        DB::transaction(function () use ($pinjaman, $update, $kas) {
            $pinjaman->update(array_merge($update, [
                'tanggal_pencairan' => now(),
            ]));

            $this->bunga->simpanJadwal($pinjaman);

            $kas->decrement('saldo_saat_ini', $pinjaman->nominal);

            JurnalKas::create([
                'tipe' => 'keluar',
                'kategori' => 'pencairan_pinjaman',
                'jumlah' => $pinjaman->nominal,
                'keterangan' => "Pencairan pinjaman - {$pinjaman->anggota->nama}",
                'referensi_id' => $pinjaman->id,
                'tanggal' => now(),
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function rejectKetua(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);
    }
}
