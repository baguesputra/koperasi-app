<?php

namespace App\Services\Pinjaman;

use App\Helpers\TerbilangHelper;
use App\Models\AuditLog;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Services\Dokumen\PenomoranDokumenService;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Wa\WaPesan;
use App\Services\Wa\WaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PersetujuanPinjamanService
{
    public function __construct(
        private PerhitunganBungaService $bunga,
        private JurnalKasService $jurnalKas,
        private EligibilitasPinjamanService $eligibilitas,
    ) {}

    /**
     * Resolve tenor final: kosong → auto-clamp, isi → validasi lawan tenor_maksimal.
     */
    public function resolveTenor(Pinjaman $pinjaman, float $nominal, ?int $tenorDiminta, ?int $tenorAcuan = null): int
    {
        $tenorMaksimal = $this->eligibilitas->tenorMaksimal($nominal);

        if (! $tenorMaksimal) {
            throw new \RuntimeException('Nominal tidak sesuai dengan ketentuan tenor yang berlaku.');
        }

        $acuan = $tenorDiminta ?? $tenorAcuan ?? $pinjaman->tenor_bulan;

        if ($tenorDiminta !== null && $tenorDiminta > $tenorMaksimal) {
            throw new \RuntimeException("Tenor maksimal untuk nominal ini adalah {$tenorMaksimal} bulan.");
        }

        return min($acuan, $tenorMaksimal);
    }

    /**
     * Validasi nominal usulan: min 1 + kunci ≤ limit tersedia (recalculated).
     */
    private function validasiNominal(Pinjaman $pinjaman, float $nominal): void
    {
        if ($nominal < 1) {
            throw new \RuntimeException('Nominal yang disetujui minimal Rp 1.');
        }

        $limitTersedia = $this->eligibilitas->limitTersedia($pinjaman->anggota);

        if ($nominal > $limitTersedia) {
            throw new \RuntimeException(
                'Nominal melebihi limit tersedia anggota: Rp '.number_format($limitTersedia, 0, ',', '.')
            );
        }
    }

    public function approveBendahara(Pinjaman $pinjaman, string $catatan, ?float $nominal = null, ?int $tenorBulan = null): void
    {
        if ($pinjaman->status !== 'diajukan') {
            throw new \RuntimeException('Hanya pinjaman berstatus Diajukan yang dapat disetujui Bendahara.');
        }

        $nominalFinal = $nominal ?? (float) $pinjaman->nominal;
        $this->validasiNominal($pinjaman, $nominalFinal);
        $tenorFinal = $this->resolveTenor($pinjaman, $nominalFinal, $tenorBulan);
        $kasSaldo = $this->jurnalKas->saldoOperasional();

        $pinjaman->update([
            'status' => 'approved_bendahara',
            'nominal_disetujui_bendahara' => $nominalFinal,
            'tenor_disetujui_bendahara' => $tenorFinal,
            'kas_saldo_bendahara' => $kasSaldo,
            'catatan_bendahara' => $catatan,
        ]);

        AuditLog::catat(
            aksi: 'pinjaman_setujui_bendahara',
            keterangan: "Pinjaman #{$pinjaman->id} ({$pinjaman->anggota->nama}) disetujui oleh Bendahara. Diminta: ".WaPesan::rupiah($pinjaman->nominal_diminta ?? $pinjaman->nominal).', usulan: '.WaPesan::rupiah($nominalFinal)." ({$tenorFinal} bln). Kas operasional saat itu: ".WaPesan::rupiah($kasSaldo).', proyeksi sisa: '.WaPesan::rupiah($kasSaldo - $nominalFinal),
            dataLama: ['status' => 'diajukan'],
            dataBaru: ['status' => 'approved_bendahara', 'nominal_disetujui_bendahara' => $nominalFinal, 'tenor_disetujui_bendahara' => $tenorFinal, 'kas_saldo_bendahara' => $kasSaldo, 'catatan_bendahara' => $catatan]
        );

        $isi = "Pengajuan pinjaman Anda telah kami informasikan dengan rincian:\n\n"
            ."- Nomor Referensi: #{$pinjaman->id}\n"
            .'- Nominal diminta: '.WaPesan::rupiah($pinjaman->nominal_diminta ?? $pinjaman->nominal)."\n"
            .'- Nominal usulan Bendahara: '.WaPesan::rupiah($nominalFinal)."\n"
            ."- Tenor usulan: {$tenorFinal} bulan\n\n"
            .'Status saat ini: *Disetujui Bendahara* dan sedang menunggu persetujuan Ketua.'
            .' Pemberitahuan selanjutnya akan kami sampaikan melalui WhatsApp ini.';

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_disetujui_bendahara',
            WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_karyawan, $isi)
        );
    }

    public function rejectBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $statusLama = $pinjaman->status;
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_bendahara' => $catatan,
        ]);

        AuditLog::catat(
            aksi: 'pinjaman_tolak_bendahara',
            keterangan: "Pinjaman #{$pinjaman->id} ({$pinjaman->anggota->nama}) ditolak oleh Bendahara. Nominal: ".WaPesan::rupiah($pinjaman->nominal_diminta ?? $pinjaman->nominal),
            dataLama: ['status' => $statusLama],
            dataBaru: ['status' => 'ditolak', 'catatan_bendahara' => $catatan]
        );

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_ditolak',
            $this->pesanDitolak('Bendahara', $pinjaman, $catatan)
        );
    }

    /**
     * Pencairan pinjaman pengajuan mandiri Ketua oleh Bendahara
     * (status approved_bendahara + cair_oleh_bendahara). Prosesnya identik
     * dengan approveKetua: aktifkan, bentuk jadwal, catat jurnal keluar,
     * kirim WA formal + lampiran Bukti Peminjaman.
     */
    public function cairBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        if ($pinjaman->status !== 'approved_bendahara' || ! $pinjaman->cair_oleh_bendahara) {
            throw new \RuntimeException('Pinjaman ini tidak dalam status menunggu pencairan Bendahara.');
        }

        // Jalur mandiri Ketua: usulan Bendahara = final (tanpa edit Ketua).
        $this->cairkan(
            $pinjaman,
            $catatan,
            'pinjaman_cair_bendahara',
            'dicairkan oleh Bendahara (pengajuan mandiri Ketua)',
            $pinjaman->nominal_disetujui_bendahara !== null ? (float) $pinjaman->nominal_disetujui_bendahara : null,
            $pinjaman->tenor_disetujui_bendahara,
        );
    }

    public function approveKetua(Pinjaman $pinjaman, string $catatan, ?float $nominal = null, ?int $tenorBulan = null): void
    {
        if ($pinjaman->status !== 'approved_bendahara') {
            throw new \RuntimeException('Hanya pinjaman berstatus Disetujui Bendahara yang dapat disetujui Ketua.');
        }

        $nominalFinal = $nominal ?? (float) ($pinjaman->nominal_disetujui_bendahara ?? $pinjaman->nominal);
        $this->validasiNominal($pinjaman, $nominalFinal);
        $tenorFinal = $this->resolveTenor(
            $pinjaman,
            $nominalFinal,
            $tenorBulan,
            $pinjaman->tenor_disetujui_bendahara ?? $pinjaman->tenor_bulan
        );

        $this->cairkan($pinjaman, $catatan, 'pinjaman_setujui_ketua', 'disetujui & dicairkan oleh Ketua', $nominalFinal, $tenorFinal);
    }

    private function cairkan(Pinjaman $pinjaman, string $catatan, string $aksi = 'pinjaman_cair', string $deskripsi = 'dicairkan', ?float $nominalFinal = null, ?int $tenorFinal = null): void
    {
        $statusLama = $pinjaman->status;
        $nominalFinal ??= (float) ($pinjaman->nominal_disetujui_bendahara ?? $pinjaman->nominal);
        $tenorFinal ??= $pinjaman->tenor_disetujui_bendahara ?? $pinjaman->tenor_bulan;
        $kasSebelum = 0.0;
        $nomorDokumen = $pinjaman->nomor_dokumen
            ?? app(PenomoranDokumenService::class)->berikutnya(
                PenomoranDokumenService::JENIS_PINJAMAN,
                now()
            );
        $infoPagu = [];
        $talangan = [];
        DB::transaction(function () use ($pinjaman, $catatan, $nomorDokumen, $nominalFinal, $tenorFinal, &$kasSebelum, &$infoPagu, &$talangan) {
            $kas = KasKoperasi::lockForUpdate()->firstOrFail();
            $kasSebelum = $this->jurnalKas->saldoOperasional($kas);
            $infoPagu = $this->jurnalKas->sisaPaguBulan($kas);

            if ($nominalFinal > $infoPagu['layak']) {
                throw new \RuntimeException(
                    'Melebihi pagu pinjaman bulan '.now()->translatedFormat('F Y').'. '
                    .'Pagu: '.WaPesan::rupiah($infoPagu['pagu'])
                    .', sudah cair: '.WaPesan::rupiah($infoPagu['sudah_cair'])
                    .', cadangan sosial: '.WaPesan::rupiah($infoPagu['cadangan'])
                    .', layak: '.WaPesan::rupiah($infoPagu['layak']).'.'
                );
            }

            if ($kasSebelum - $nominalFinal < $infoPagu['cadangan']) {
                throw new \RuntimeException(
                    'Pencairan menyisakan kas di bawah cadangan sosial ('.WaPesan::rupiah($infoPagu['cadangan']).'). '
                    .'Sisa bila cair: '.WaPesan::rupiah($kasSebelum - $nominalFinal).'.'
                );
            }

            // P1-4: kantong pinjaman boleh ditalangi sosial lalu simpanan.
            // Guard global di atas sudah jamin sisa ≥ cadangan; di sini hanya
            // cover defisit fisik kantong pinjaman (hutang, kembali dari angsuran).
            $defisit = $nominalFinal - (float) $kas->saldo_pinjaman;

            if ($defisit > 0) {
                $talangan = $this->jurnalKas->talangiPinjaman(
                    $defisit,
                    "Talangan pencairan pinjaman - {$pinjaman->anggota->nama}",
                    $pinjaman->id,
                    now()->format('Y-m-d'),
                    auth()->id(),
                );
            }

            $pinjaman->update([
                'status' => 'aktif',
                'nominal' => $nominalFinal,
                'tenor_bulan' => $tenorFinal,
                'nominal_disetujui' => $nominalFinal,
                'tenor_disetujui' => $tenorFinal,
                'kas_saldo_ketua' => $kasSebelum,
                'catatan_ketua' => $catatan,
                'tanggal_pencairan' => now(),
                'nomor_dokumen' => $nomorDokumen,
            ]);

            $this->bunga->simpanJadwal($pinjaman);

            // Validasi saldo cukup otomatis ditangani JurnalKasService (lempar exception kalau kurang)
            $this->jurnalKas->catat(
                tipe: 'keluar',
                kategori: 'pencairan_pinjaman',
                kantong: 'pinjaman',
                jumlah: $nominalFinal,
                keterangan: "Pencairan pinjaman - {$pinjaman->anggota->nama}",
                referensiId: $pinjaman->id,
                tanggal: now()->format('Y-m-d'),
                userId: auth()->id(),
            );

            $pinjaman->update(['kas_sisa_ketua' => $kasSebelum - $nominalFinal]);
        });

        $pinjaman->refresh();

        $trail = 'Diminta: '.WaPesan::rupiah($pinjaman->nominal_diminta ?? $nominalFinal)
            .($pinjaman->nominal_disetujui_bendahara ? ', Bendahara: '.WaPesan::rupiah($pinjaman->nominal_disetujui_bendahara) : '')
            .', Final: '.WaPesan::rupiah($nominalFinal);

        if ($talangan) {
            $trail .= ', Talangan: '.implode(' + ', array_map(
                fn ($t) => (JurnalKasService::KANTONG_LABEL[$t['kantong']] ?? $t['kantong']).' '.WaPesan::rupiah($t['jumlah']),
                $talangan
            ));
        }

        AuditLog::catat(
            aksi: $aksi,
            keterangan: "Pinjaman #{$pinjaman->id} ({$pinjaman->anggota->nama}) {$deskripsi}. {$trail}. Kas operasional sebelum: ".WaPesan::rupiah($kasSebelum).', sisa: '.WaPesan::rupiah($kasSebelum - $nominalFinal).', pagu bulan: '.WaPesan::rupiah($infoPagu['pagu'] ?? 0),
            dataLama: ['status' => $statusLama],
            dataBaru: ['status' => 'aktif', 'nominal' => $nominalFinal, 'tenor_bulan' => $tenorFinal, 'kas_saldo_ketua' => $kasSebelum, 'kas_sisa_ketua' => $kasSebelum - $nominalFinal, 'pagu' => $infoPagu, 'catatan_ketua' => $catatan, 'tanggal_pencairan' => now()->toDateTimeString()]
        );

        $isi = "Dengan hormat,\n\nSelamat! Pengajuan pinjaman Anda telah *DISETUJUI* oleh Ketua dan dana telah dicairkan pada "
            .now()->translatedFormat('d F Y').".\n\nRincian pencairan:\n"
            ."- Nomor Referensi: #{$pinjaman->id}\n"
            .'- Nominal diminta: '.WaPesan::rupiah($pinjaman->nominal_diminta ?? $nominalFinal)."\n"
            .($pinjaman->nominal_disetujui_bendahara ? '- Nominal usulan Bendahara: '.WaPesan::rupiah($pinjaman->nominal_disetujui_bendahara)."\n" : '')
            .'- Nominal dicairkan: '.WaPesan::rupiah($nominalFinal).' ('.TerbilangHelper::angkaKeTerbilang($nominalFinal).")\n"
            ."- Tenor: {$tenorFinal} bulan\n"
            .'- Bunga: '.$pinjaman->persentase_bunga."% per bulan (metode menurun)\n"
            .'- Rekening tujuan: '.$pinjaman->snapshot_bank.' '.$pinjaman->snapshot_no_rekening.' a.n. '.$pinjaman->snapshot_atas_nama."\n\n"
            ."Dokumen *Bukti Peminjaman* terlampir pada pesan ini, memuat rincian jadwal angsuran Anda. Mohon lakukan pembayaran angsuran sesuai tanggal jatuh tempo yang tercantum.\n\n"
            .'Terima kasih atas kepercayaan Anda.';

        WaService::keAnggotaDokumen(
            $pinjaman->anggota,
            'pinjaman_disetujui_ketua',
            WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_karyawan, $isi),
            Pdf::loadView('wa.bukti-pinjaman', $pinjaman->dataBukti())->output(),
            "Bukti-Peminjaman-{$pinjaman->id}.pdf",
        );
    }

    public function rejectKetua(Pinjaman $pinjaman, string $catatan): void
    {
        $statusLama = $pinjaman->status;
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);

        AuditLog::catat(
            aksi: 'pinjaman_tolak_ketua',
            keterangan: "Pinjaman #{$pinjaman->id} ({$pinjaman->anggota->nama}) ditolak oleh Ketua. Nominal: ".WaPesan::rupiah($pinjaman->nominal_diminta ?? $pinjaman->nominal),
            dataLama: ['status' => $statusLama],
            dataBaru: ['status' => 'ditolak', 'catatan_ketua' => $catatan]
        );

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_ditolak',
            $this->pesanDitolak('Ketua', $pinjaman, $catatan)
        );
    }

    private function pesanDitolak(string $oleh, Pinjaman $pinjaman, string $catatan): string
    {
        $isi = "Dengan hormat,\n\nMohon maaf, pengajuan pinjaman Anda dengan rincian berikut:\n\n"
            ."- Nomor Referensi: #{$pinjaman->id}\n"
            .'- Nominal diminta: '.WaPesan::rupiah($pinjaman->nominal_diminta ?? $pinjaman->nominal)."\n"
            .'- Tenor diminta: '.($pinjaman->tenor_diminta ?? $pinjaman->tenor_bulan)." bulan\n\n"
            ."telah *DITOLAK* oleh {$oleh}."
            .($catatan ? "\n\nCatatan: {$catatan}" : '')
            ."\n\nApabila terdapat pertanyaan lebih lanjut, silakan menghubungi pengurus atau Bendahara Koperasi.";

        return WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_karyawan, $isi);
    }
}
