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
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PersetujuanPinjamanService
{
    /**
     * Tolak pemrosesan pengajuan sendiri (defense-in-depth: mirror API tolakBilaSelfApproval).
     * Wajib dipanggil tiap aksi approve/reject/cair web sebelum cek status.
     */
    public function tolakBilaSelfApproval(Pinjaman $pinjaman, ?int $aktorId = null): void
    {
        $saya = $aktorId ?? auth()->id();
        $pengaju = $pinjaman->pengaju_user_id;
        $pemilik = $pinjaman->anggota?->user_id;

        if (($pengaju && $pengaju === $saya) || ($pemilik && $pemilik === $saya)) {
            throw new RuntimeException('Anda tidak dapat memproses pengajuan Anda sendiri.');
        }
    }

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
            throw new RuntimeException('Nominal tidak sesuai dengan ketentuan tenor yang berlaku.');
        }

        $acuan = $tenorDiminta ?? $tenorAcuan ?? $pinjaman->tenor_bulan;

        if ($tenorDiminta !== null && $tenorDiminta > $tenorMaksimal) {
            throw new RuntimeException("Tenor maksimal untuk nominal ini adalah {$tenorMaksimal} bulan.");
        }

        return min($acuan, $tenorMaksimal);
    }

    /**
     * Validasi nominal usulan: min 1 + kunci ≤ limit tersedia (recalculated).
     */
    private function validasiNominal(Pinjaman $pinjaman, float $nominal): void
    {
        if ($nominal < 1) {
            throw new RuntimeException('Nominal yang disetujui minimal Rp 1.');
        }

        $limitTersedia = $this->eligibilitas->limitTersedia($pinjaman->anggota);

        if ($nominal > $limitTersedia) {
            throw new RuntimeException(
                'Nominal melebihi limit tersedia anggota: Rp '.number_format($limitTersedia, 0, ',', '.')
            );
        }
    }

    public function approveAdmin(Pinjaman $pinjaman, string $catatan): void
    {
        $this->tolakBilaSelfApproval($pinjaman);

        DB::transaction(function () use ($pinjaman, $catatan) {
            $locked = Pinjaman::with('anggota')->lockForUpdate()->findOrFail($pinjaman->id);
            if ($locked->status !== 'diajukan') {
                throw new RuntimeException('Hanya pinjaman berstatus Diajukan yang dapat diteruskan Sekretaris.');
            }

            $mandiriBendahara = $locked->catatan_bendahara === 'Diajukan mandiri oleh Bendahara';

            $locked->update([
                'status' => $mandiriBendahara ? 'approved_bendahara' : 'approved_admin',
                'catatan_admin' => $catatan,
            ]);

            AuditLog::catat(
                aksi: 'pinjaman_teruskan_admin',
                keterangan: "Pinjaman #{$locked->id} ({$locked->anggota->nama}) diteruskan oleh Sekretaris. Diminta: ".WaPesan::rupiah($locked->nominal_diminta ?? $locked->nominal)
                    .($mandiriBendahara ? ' (pengajuan mandiri Bendahara: tahap Bendahara dilewati agar tak self-approve).' : ''),
                dataLama: ['status' => 'diajukan'],
                dataBaru: ['status' => $mandiriBendahara ? 'approved_bendahara' : 'approved_admin', 'catatan_admin' => $catatan]
            );
        });

        $pinjaman->refresh();
    }

    public function rejectAdmin(Pinjaman $pinjaman, string $catatan): void
    {
        $this->tolakBilaSelfApproval($pinjaman);

        DB::transaction(function () use ($pinjaman, $catatan) {
            $locked = Pinjaman::with('anggota')->lockForUpdate()->findOrFail($pinjaman->id);
            if ($locked->status !== 'diajukan') {
                throw new RuntimeException('Hanya pinjaman berstatus Diajukan yang dapat ditolak Sekretaris.');
            }

            $locked->update([
                'status' => 'ditolak',
                'catatan_admin' => $catatan,
            ]);

            app(PengajuanPinjamanService::class)->pulihkanPrivilegeReloanBilaPerlu($locked->anggota, $locked->id);

            AuditLog::catat(
                aksi: 'pinjaman_tolak_admin',
                keterangan: "Pinjaman #{$locked->id} ({$locked->anggota->nama}) ditolak oleh Sekretaris. Nominal: ".WaPesan::rupiah($locked->nominal_diminta ?? $locked->nominal),
                dataLama: ['status' => 'diajukan'],
                dataBaru: ['status' => 'ditolak', 'catatan_admin' => $catatan]
            );
        });

        $pinjaman->refresh();

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_ditolak',
            $this->pesanDitolak('Sekretaris', $pinjaman, $catatan)
        );
    }

    public function approveBendahara(Pinjaman $pinjaman, string $catatan, ?float $nominal = null, ?int $tenorBulan = null): void
    {
        $this->tolakBilaSelfApproval($pinjaman);

        $mandiriKetua = DB::transaction(function () use ($pinjaman, $catatan, $nominal, $tenorBulan) {
            $locked = Pinjaman::with('anggota')->lockForUpdate()->findOrFail($pinjaman->id);
            if ($locked->status !== 'approved_admin') {
                throw new RuntimeException('Hanya pinjaman berstatus Diteruskan Sekretaris yang dapat ditolak atau disetujui Bendahara.');
            }

            $nominalFinal = $nominal ?? (float) $locked->nominal;
            $this->validasiNominal($locked, $nominalFinal);
            $tenorFinal = $this->resolveTenor($locked, $nominalFinal, $tenorBulan);
            $kasSaldo = $this->jurnalKas->saldoOperasional();
            $mandiriKetua = (bool) $locked->cair_oleh_bendahara;

            $payload = [
                'status' => $mandiriKetua ? 'approved_ketua' : 'approved_bendahara',
                'nominal_disetujui_bendahara' => $nominalFinal,
                'tenor_disetujui_bendahara' => $tenorFinal,
                'kas_saldo_bendahara' => $kasSaldo,
                'catatan_bendahara' => $catatan,
            ];

            if ($mandiriKetua) {
                $payload = array_merge($payload, [
                    'nominal' => $nominalFinal,
                    'tenor_bulan' => $tenorFinal,
                    'nominal_disetujui' => $nominalFinal,
                    'tenor_disetujui' => $tenorFinal,
                    'disetujui_ketua_pada' => now(),
                ]);
            }

            $locked->update($payload);

            AuditLog::catat(
                aksi: 'pinjaman_setujui_bendahara',
                keterangan: "Pinjaman #{$locked->id} ({$locked->anggota->nama}) disetujui oleh Bendahara. Diminta: ".WaPesan::rupiah($locked->nominal_diminta ?? $locked->nominal).', usulan: '.WaPesan::rupiah($nominalFinal)." ({$tenorFinal} bln). Kas operasional saat itu: ".WaPesan::rupiah($kasSaldo).', proyeksi sisa: '.WaPesan::rupiah($kasSaldo - $nominalFinal)
                    .($mandiriKetua ? ' (pengajuan mandiri Ketua: diteruskan ke daftar Pencairan Bendahara).' : ''),
                dataLama: ['status' => 'approved_admin'],
                dataBaru: array_merge(['status' => $payload['status'], 'nominal_disetujui_bendahara' => $nominalFinal, 'tenor_disetujui_bendahara' => $tenorFinal, 'kas_saldo_bendahara' => $kasSaldo, 'catatan_bendahara' => $catatan])
            );

            return $mandiriKetua;
        });

        if ($mandiriKetua) {
            $this->kirimWaPersetujuanAkhirBulan($pinjaman->refresh());
        } else {
            $pinjaman->refresh();
        }
    }

    public function rejectBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $this->tolakBilaSelfApproval($pinjaman);

        DB::transaction(function () use ($pinjaman, $catatan) {
            $locked = Pinjaman::with('anggota')->lockForUpdate()->findOrFail($pinjaman->id);
            if ($locked->status !== 'approved_admin') {
                throw new RuntimeException('Hanya pinjaman berstatus Diteruskan Sekretaris yang dapat ditolak Bendahara.');
            }

            $statusLama = $locked->status;
            $locked->update([
                'status' => 'ditolak',
                'catatan_bendahara' => $catatan,
            ]);

            app(PengajuanPinjamanService::class)->pulihkanPrivilegeReloanBilaPerlu($locked->anggota, $locked->id);

            AuditLog::catat(
                aksi: 'pinjaman_tolak_bendahara',
                keterangan: "Pinjaman #{$locked->id} ({$locked->anggota->nama}) ditolak oleh Bendahara. Nominal: ".WaPesan::rupiah($locked->nominal_diminta ?? $locked->nominal),
                dataLama: ['status' => $statusLama],
                dataBaru: ['status' => 'ditolak', 'catatan_bendahara' => $catatan]
            );
        });

        $pinjaman->refresh();

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_ditolak',
            $this->pesanDitolak('Bendahara', $pinjaman, $catatan)
        );
    }

    public function approveKetua(Pinjaman $pinjaman, string $catatan, ?float $nominal = null, ?int $tenorBulan = null): void
    {
        $this->tolakBilaSelfApproval($pinjaman);

        DB::transaction(function () use ($pinjaman, $catatan, $nominal, $tenorBulan) {
            $locked = Pinjaman::with('anggota')->lockForUpdate()->findOrFail($pinjaman->id);
            if ($locked->status !== 'approved_bendahara' || $locked->cair_oleh_bendahara) {
                throw new RuntimeException('Hanya pinjaman berstatus Disetujui Bendahara yang dapat disetujui Ketua.');
            }

            $nominalFinal = $nominal ?? (float) ($locked->nominal_disetujui_bendahara ?? $locked->nominal);
            $this->validasiNominal($locked, $nominalFinal);
            $tenorFinal = $this->resolveTenor(
                $locked,
                $nominalFinal,
                $tenorBulan,
                $locked->tenor_disetujui_bendahara ?? $locked->tenor_bulan
            );

            $kasSebelum = $this->jurnalKas->saldoOperasional();

            $locked->update([
                'status' => 'approved_ketua',
                'nominal' => $nominalFinal,
                'tenor_bulan' => $tenorFinal,
                'nominal_disetujui' => $nominalFinal,
                'tenor_disetujui' => $tenorFinal,
                'kas_saldo_ketua' => $kasSebelum,
                'catatan_ketua' => $catatan,
                'disetujui_ketua_pada' => now(),
            ]);

            $locked->refresh();

            $trail = 'Diminta: '.WaPesan::rupiah($locked->nominal_diminta ?? $nominalFinal)
                .($locked->nominal_disetujui_bendahara ? ', Bendahara: '.WaPesan::rupiah($locked->nominal_disetujui_bendahara) : '')
                .', Disetujui Ketua: '.WaPesan::rupiah($nominalFinal);

            AuditLog::catat(
                aksi: 'pinjaman_setujui_ketua',
                keterangan: "Pinjaman #{$locked->id} ({$locked->anggota->nama}) disetujui Ketua (menunggu pencairan Bendahara di akhir bulan). {$trail}.",
                dataLama: ['status' => 'approved_bendahara'],
                dataBaru: [
                    'status' => 'approved_ketua',
                    'nominal' => $nominalFinal,
                    'tenor_bulan' => $tenorFinal,
                    'kas_saldo_ketua' => $kasSebelum,
                    'catatan_ketua' => $catatan,
                    'disetujui_ketua_pada' => $locked->disetujui_ketua_pada?->toDateTimeString(),
                ]
            );
        });

        $pinjaman->refresh();

        $this->kirimWaPersetujuanAkhirBulan($pinjaman);
    }

    public function cairkanBendahara(
        Pinjaman $pinjaman,
        ?UploadedFile $buktiTransfer,
        string $tanggalPencairan,
        ?string $catatan,
        ?int $aktorId = null,
    ): void {
        $pinjaman->loadMissing('anggota');
        $aktorId ??= auth()->id();
        $milikSendiri = $aktorId !== null && (
            ((int) ($pinjaman->pengaju_user_id ?? 0) === $aktorId)
            || ((int) ($pinjaman->anggota?->user_id ?? 0) === $aktorId)
        );

        if ($milikSendiri) {
            if ($pinjaman->status !== 'approved_ketua' || $pinjaman->cair_oleh_bendahara || $pinjaman->disetujui_ketua_pada === null) {
                $this->tolakBilaSelfApproval($pinjaman, $aktorId);
            }
            if ($buktiTransfer === null) {
                throw new RuntimeException('Pencairan pinjaman milik Bendahara wajib melampirkan bukti transfer bank.');
            }
        } else {
            $this->tolakBilaSelfApproval($pinjaman, $aktorId);
        }

        if ($pinjaman->status !== 'approved_ketua') {
            throw new RuntimeException('Hanya pinjaman berstatus Disetujui Ketua yang dapat dicairkan oleh Bendahara.');
        }

        $tglCair = Carbon::parse($tanggalPencairan)->startOfDay();
        if ($tglCair->greaterThan(now()->startOfDay())) {
            throw new RuntimeException('Tanggal pencairan tidak boleh melebihi hari ini.');
        }

        $buktiBytes = $buktiTransfer ? $buktiTransfer->get() : null;
        $fileMeta = $buktiTransfer ? $this->simpanBuktiPrivat($buktiTransfer, $tglCair) : null;

        $nominalFinal = (float) ($pinjaman->nominal_disetujui ?? $pinjaman->nominal);
        $tenorFinal = (int) ($pinjaman->tenor_disetujui ?? $pinjaman->tenor_bulan);
        $kasSebelum = 0.0;
        $saldoBank = 0.0;
        $nomorDokumen = $pinjaman->nomor_dokumen
            ?? app(PenomoranDokumenService::class)->berikutnya(
                PenomoranDokumenService::JENIS_PINJAMAN,
                $tglCair
            );

        DB::transaction(function () use ($pinjaman, $catatan, $nomorDokumen, $nominalFinal, $tenorFinal, $tglCair, $fileMeta, $aktorId, &$kasSebelum, &$saldoBank) {
            $pinjamanLocked = Pinjaman::lockForUpdate()->findOrFail($pinjaman->id);
            if ($pinjamanLocked->status !== 'approved_ketua') {
                throw new RuntimeException('Status pinjaman sudah berubah. Muat ulang daftar pencairan.');
            }

            $kas = KasKoperasi::lockForUpdate()->firstOrFail();
            $kasSebelum = $this->jurnalKas->saldoOperasional($kas);
            $saldoBank = (float) $kas->saldo_bank;

            if ($nominalFinal > $saldoBank) {
                throw new RuntimeException(
                    'Melebihi saldo bank. Saldo bank saat ini: '.WaPesan::rupiah($saldoBank)
                    .', nominal dicairkan: '.WaPesan::rupiah($nominalFinal).'.'
                );
            }

            $update = [
                'status' => 'aktif',
                'nominal' => $nominalFinal,
                'tenor_bulan' => $tenorFinal,
                'nominal_disetujui' => $nominalFinal,
                'tenor_disetujui' => $tenorFinal,
                'kas_saldo_ketua' => $kasSebelum,
                'kas_sisa_ketua' => $kasSebelum - $nominalFinal,
                'tanggal_pencairan' => $tglCair->toDateString(),
                'dicairkan_oleh' => $aktorId,
                'catatan_pencairan' => $catatan ? trim($catatan) : null,
                'nomor_dokumen' => $nomorDokumen,
            ];

            if ($fileMeta) {
                $update['bukti_pencairan_path'] = $fileMeta['path'];
                $update['bukti_pencairan_nama_asli'] = $fileMeta['nama_asli'];
                $update['bukti_pencairan_mime'] = $fileMeta['mime'];
            }

            $pinjamanLocked->update($update);

            $this->bunga->simpanJadwal($pinjamanLocked);

            $this->jurnalKas->catat(
                tipe: 'keluar',
                kategori: 'pencairan_pinjaman',
                kantong: 'pinjaman',
                jumlah: $nominalFinal,
                keterangan: "Pencairan pinjaman - {$pinjamanLocked->anggota->nama}",
                referensiId: $pinjamanLocked->id,
                tanggal: $tglCair->toDateString(),
                userId: $aktorId,
            );
        });

        $pinjaman->refresh();

        $trail = 'Diminta: '.WaPesan::rupiah($pinjaman->nominal_diminta ?? $nominalFinal)
            .($pinjaman->nominal_disetujui_bendahara ? ', Bendahara: '.WaPesan::rupiah($pinjaman->nominal_disetujui_bendahara) : '')
            .', Final Cair: '.WaPesan::rupiah($nominalFinal);

        AuditLog::catat(
            aksi: 'pinjaman_dicairkan',
            keterangan: "Pinjaman #{$pinjaman->id} ({$pinjaman->anggota->nama}) ditransfer dan dicairkan Bendahara. {$trail}. Kas operasional sebelum: ".WaPesan::rupiah($kasSebelum).', sisa: '.WaPesan::rupiah($kasSebelum - $nominalFinal).', saldo bank: '.WaPesan::rupiah($saldoBank),
            dataLama: ['status' => 'approved_ketua'],
            dataBaru: [
                'status' => 'aktif',
                'nominal' => $nominalFinal,
                'tenor_bulan' => $tenorFinal,
                'kas_saldo_ketua' => $kasSebelum,
                'kas_sisa_ketua' => $kasSebelum - $nominalFinal,
                'saldo_bank' => $saldoBank,
                'dicairkan_oleh' => $aktorId,
                'catatan_pencairan' => $catatan,
                'tanggal_pencairan' => $tglCair->toDateString(),
                'ada_bukti_pencairan' => (bool) $fileMeta,
            ],
            userId: $aktorId,
        );

        $cicilanPertama = $pinjaman->angsuran()->orderBy('cicilan_ke')->first();
        $jatuhTempoPertamaLabel = $cicilanPertama?->tanggal_jatuh_tempo?->locale('id')->translatedFormat('d F Y') ?? '-';

        $isiTransfer = "Dengan hormat,\n\nDana pinjaman Anda telah *DICAIRKAN / DITRANSFER* oleh Bendahara Koperasi pada "
            .$tglCair->copy()->locale('id')->translatedFormat('d F Y').".\n\nRincian pencairan:\n"
            ."- Nomor Referensi: #{$pinjaman->id}\n"
            ."- Nomor Dokumen: {$pinjaman->nomor_dokumen}\n"
            .'- Nominal ditransfer: '.WaPesan::rupiah($nominalFinal).' ('.TerbilangHelper::angkaKeTerbilang($nominalFinal)." rupiah)\n"
            ."- Tenor: {$tenorFinal} bulan\n"
            .'- Bunga: '.$pinjaman->persentase_bunga."% per bulan (metode menurun)\n"
            .'- Rekening tujuan: '.$pinjaman->snapshot_bank.' '.$pinjaman->snapshot_no_rekening.' a.n. '.$pinjaman->snapshot_atas_nama."\n"
            ."- Jatuh tempo angsuran ke-1: *{$jatuhTempoPertamaLabel}*\n\n"
            .($fileMeta
                ? "Terlampir *Bukti Transfer Pencairan* pada pesan ini. Selanjutnya dokumen *Bukti Peminjaman & Jadwal Angsuran* akan dikirimkan pada pesan berikutnya.\n\n"
                : "Anda juga dapat memantau rincian jadwal angsuran di Portal Anggota.\n\n")
            .'Terima kasih atas kepercayaan Anda.';

        $pesanTransfer = WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_karyawan, $isiTransfer);
        if ($fileMeta && $buktiBytes !== null) {
            WaService::keAnggotaDokumen(
                $pinjaman->anggota,
                'pinjaman_dicairkan',
                $pesanTransfer,
                $buktiBytes,
                $fileMeta['nama_asli'],
                $fileMeta['mime'],
            );
        } else {
            WaService::keAnggota($pinjaman->anggota, 'pinjaman_dicairkan', $pesanTransfer);
        }

        $isiDokumen = "Berikut terlampir dokumen resmi *Bukti Peminjaman & Jadwal Angsuran* untuk Pinjaman #{$pinjaman->id} ({$pinjaman->nomor_dokumen}). Mohon lakukan pembayaran angsuran sesuai tanggal jatuh tempo yang tercantum.";
        WaService::keAnggotaDokumen(
            $pinjaman->anggota,
            'pinjaman_bukti_akad',
            WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_karyawan, $isiDokumen),
            Pdf::loadView('wa.bukti-pinjaman', $pinjaman->dataBukti())->output(),
            "Bukti-Peminjaman-{$pinjaman->id}.pdf",
            'application/pdf',
        );
    }

    private function kirimWaPersetujuanAkhirBulan(Pinjaman $pinjaman): void
    {
        $pinjaman->loadMissing('anggota');
        $nominalFinal = (float) ($pinjaman->nominal_disetujui ?? $pinjaman->nominal);
        $tenorFinal = (int) ($pinjaman->tenor_disetujui ?? $pinjaman->tenor_bulan);

        $isi = "Dengan hormat,\n\nSelamat! Pengajuan pinjaman Anda telah *DISETUJUI* oleh Ketua Koperasi pada "
            .now()->locale('id')->translatedFormat('d F Y').".\n\nRincian persetujuan:\n"
            ."- Nomor Referensi: #{$pinjaman->id}\n"
            .'- Nominal diminta: '.WaPesan::rupiah($pinjaman->nominal_diminta ?? $nominalFinal)."\n"
            .($pinjaman->nominal_disetujui_bendahara ? '- Nominal usulan Bendahara: '.WaPesan::rupiah($pinjaman->nominal_disetujui_bendahara)."\n" : '')
            .'- Nominal disetujui: '.WaPesan::rupiah($nominalFinal).' ('.TerbilangHelper::angkaKeTerbilang($nominalFinal)." rupiah)\n"
            ."- Tenor: {$tenorFinal} bulan\n"
            .'- Bunga: '.$pinjaman->persentase_bunga."% per bulan (metode menurun)\n"
            .'- Rekening tujuan: '.$pinjaman->snapshot_bank.' '.$pinjaman->snapshot_no_rekening.' a.n. '.$pinjaman->snapshot_atas_nama."\n\n"
            ."*Informasi Pencairan:*\n"
            .'Dana pinjaman akan *dicairkan pada akhir bulan* oleh Bendahara Koperasi. '
            ."Pemberitahuan lanjutan beserta *bukti transfer* akan kami kirimkan kembali melalui WhatsApp setelah dana ditransfer.\n\n"
            .'Terima kasih atas kepercayaan Anda.';

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_disetujui_ketua',
            WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_karyawan, $isi),
        );
    }

    /**
     * @return array{path: string, nama_asli: string, mime: string}
     */
    private function simpanBuktiPrivat(UploadedFile $file, Carbon $tanggal): array
    {
        $folder = sprintf('pinjaman/bukti-pencairan/%04d/%02d', $tanggal->year, $tanggal->month);
        $path = Storage::disk('local')->putFile($folder, $file);
        if (! $path) {
            throw new RuntimeException('Gagal menyimpan file bukti transfer ke storage.');
        }

        return [
            'path' => $path,
            'nama_asli' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType() ?: 'application/octet-stream',
        ];
    }

    public function rejectKetua(Pinjaman $pinjaman, string $catatan): void
    {
        $this->tolakBilaSelfApproval($pinjaman);

        DB::transaction(function () use ($pinjaman, $catatan) {
            $locked = Pinjaman::with('anggota')->lockForUpdate()->findOrFail($pinjaman->id);
            if ($locked->status !== 'approved_bendahara') {
                throw new RuntimeException('Hanya pinjaman berstatus Disetujui Bendahara yang dapat ditolak Ketua.');
            }

            $statusLama = $locked->status;
            $locked->update([
                'status' => 'ditolak',
                'catatan_ketua' => $catatan,
            ]);

            app(PengajuanPinjamanService::class)->pulihkanPrivilegeReloanBilaPerlu($locked->anggota, $locked->id);

            AuditLog::catat(
                aksi: 'pinjaman_tolak_ketua',
                keterangan: "Pinjaman #{$locked->id} ({$locked->anggota->nama}) ditolak oleh Ketua. Nominal: ".WaPesan::rupiah($locked->nominal_diminta ?? $locked->nominal),
                dataLama: ['status' => $statusLama],
                dataBaru: ['status' => 'ditolak', 'catatan_ketua' => $catatan]
            );
        });

        $pinjaman->refresh();

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_ditolak',
            $this->pesanDitolak('Ketua', $pinjaman, $catatan)
        );
    }

    /**
     * Batalkan pencairan pinjaman oleh Bendahara pada tahap approved_ketua (sebelum uang ditransfer).
     */
    public function batalkanPencairanBendahara(Pinjaman $pinjaman, string $alasan, ?int $aktorId = null): void
    {
        $pinjaman->loadMissing('anggota');
        $aktorId ??= auth()->id();
        $milikSendiri = $aktorId !== null && (
            ((int) ($pinjaman->pengaju_user_id ?? 0) === $aktorId)
            || ((int) ($pinjaman->anggota?->user_id ?? 0) === $aktorId)
        );

        if ($milikSendiri) {
            if ($pinjaman->status !== 'approved_ketua' || $pinjaman->cair_oleh_bendahara || $pinjaman->disetujui_ketua_pada === null) {
                $this->tolakBilaSelfApproval($pinjaman, $aktorId);
            }
        } else {
            $this->tolakBilaSelfApproval($pinjaman, $aktorId);
        }

        $alasanBersih = trim($alasan);

        DB::transaction(function () use ($pinjaman, $alasanBersih, $aktorId) {
            $locked = Pinjaman::with('anggota')->lockForUpdate()->findOrFail($pinjaman->id);
            if ($locked->status !== 'approved_ketua') {
                throw new RuntimeException('Hanya pinjaman berstatus Disetujui Ketua (belum dicairkan) yang pencairannya dapat dibatalkan.');
            }

            $locked->update([
                'status' => 'dibatalkan',
                'dibatalkan_pada' => now(),
                'dibatalkan_oleh' => $aktorId,
                'alasan_batal' => $alasanBersih,
                'catatan_pencairan' => 'Pencairan dibatalkan: '.$alasanBersih,
            ]);

            app(PengajuanPinjamanService::class)->pulihkanPrivilegeReloanBilaPerlu($locked->anggota, $locked->id);

            AuditLog::catat(
                aksi: 'pinjaman_pencairan_dibatalkan',
                keterangan: "Pencairan pinjaman #{$locked->id} ({$locked->anggota->nama}) dibatalkan Bendahara sebelum transfer. Nominal: ".WaPesan::rupiah((float) ($locked->nominal_disetujui ?? $locked->nominal)).". Alasan: {$alasanBersih}",
                dataLama: ['status' => 'approved_ketua'],
                dataBaru: [
                    'status' => 'dibatalkan',
                    'dibatalkan_oleh' => $aktorId,
                    'alasan_batal' => $alasanBersih,
                ],
                userId: $aktorId,
            );
        });

        $pinjaman->refresh();

        $isi = "Dengan hormat,\n\nPencairan pengajuan pinjaman Anda dengan rincian berikut:\n\n"
            ."- Nomor Referensi: #{$pinjaman->id}\n"
            .'- Nominal disetujui: '.WaPesan::rupiah((float) ($pinjaman->nominal_disetujui ?? $pinjaman->nominal))."\n"
            ."- Tenor: {$pinjaman->tenor_bulan} bulan\n\n"
            ."telah *DIBATALKAN* oleh Bendahara Koperasi sebelum dana ditransfer.\n"
            ."Alasan: {$alasanBersih}\n\n"
            .'Apabila terdapat pertanyaan atau Anda ingin mengajukan ulang, silakan hubungi Bendahara atau ajukan kembali melalui Portal Anggota.';

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_dibatalkan',
            WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_karyawan, $isi)
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
