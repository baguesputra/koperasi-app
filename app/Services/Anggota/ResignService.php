<?php

namespace App\Services\Anggota;

use App\Helpers\TerbilangHelper;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\AuditLog;
use App\Models\KasKoperasi;
use App\Models\RekeningTabungan;
use App\Models\TabunganTransaksi;
use App\Models\User;
use App\Services\Dokumen\PenomoranDokumenService;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Tabungan\PerhitunganBungaTabunganService;
use App\Services\Wa\WaPesan;
use App\Services\Wa\WaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class ResignService
{
    public function __construct(
        private JurnalKasService $jurnalKas,
        private PerhitunganBungaTabunganService $bungaTabungan,
    ) {}

    /**
     * Ringkasan data untuk preview modal konfirmasi.
     */
    public function ringkasan(Anggota $anggota): array
    {
        return $anggota->ringkasanResign();
    }

    /**
     * Eksekusi proses resign Tahap 1 (Sekretaris) dalam 1 DB transaction.
     *
     * Kas fisik TIDAK langsung dikeluarkan pada tahap ini agar pencairan aktual,
     * pemilihan sumber kas, dan unggah bukti transfer dilakukan oleh Bendahara
     * di Pusat Persetujuan > Pencairan Dana (selama total_dikembalikan > 0).
     *
     * Dua jalur:
     *   A. Simpanan cukup → status=resign, user nonaktif.
     *   B. Simpanan kurang → status=resign_menunggu: seluruh simpanan dialokasikan ke
     *      angsuran terawal, sisa tagihan menjadi SATU cicilan akhir terjadwal.
     */
    public function proses(
        Anggota $anggota,
        string $alasan,
        string $tanggalResign,
        User $aktor,
        ?array $rekeningTujuan = null,
    ): void {
        $pascaTransaksi = [
            'jenis' => null,
            'settlement' => [],
            'jatuh_tempo' => null,
        ];

        DB::transaction(function () use ($anggota, $alasan, $tanggalResign, $aktor, $rekeningTujuan, &$pascaTransaksi) {
            $anggotaLocked = Anggota::lockForUpdate()->findOrFail($anggota->id);

            if ($anggotaLocked->status !== 'aktif') {
                throw new RuntimeException(
                    "Anggota {$anggotaLocked->nama} berstatus '{$anggotaLocked->status}', tidak bisa di-resign."
                );
            }

            $rekSnapshot = $this->resolveRekeningTujuan($anggotaLocked, $rekeningTujuan);

            // Snapshot simpanan & hitung total tagihan (1 query agregat).
            $simpananAgg = $anggotaLocked->simpanan()
                ->selectRaw("SUM(CASE WHEN jenis = 'pokok' THEN jumlah ELSE 0 END) as pokok")
                ->selectRaw("SUM(CASE WHEN jenis = 'wajib' THEN jumlah ELSE 0 END) as wajib")
                ->selectRaw("SUM(CASE WHEN jenis = 'dana_sosial' THEN jumlah ELSE 0 END) as sosial")
                ->first();
            $simpananPokok = (float) ($simpananAgg->pokok ?? 0);
            $simpananWajib = (float) ($simpananAgg->wajib ?? 0);
            $danaSosial = (float) ($simpananAgg->sosial ?? 0);
            $totalSimpananKembali = $simpananPokok + $simpananWajib;

            $pinjamanAktif = $anggotaLocked->pinjaman()->where('status', 'aktif')->get();
            $totalTagihan = 0.0;
            foreach ($pinjamanAktif as $p) {
                $totalTagihan += $p->agregatJadwalAktif()['sisa_bayar'];
            }

            $tabunganResign = $this->prosesTutupTabunganResign($anggotaLocked, $tanggalResign, $aktor);

            if ($totalSimpananKembali < $totalTagihan) {
                [$settlementMenunggu, $jatuhTempo] = $this->prosesMenunggu(
                    $anggotaLocked,
                    $alasan,
                    $tanggalResign,
                    $aktor,
                    [
                        'simpanan_pokok' => $simpananPokok,
                        'simpanan_wajib' => $simpananWajib,
                        'dana_sosial' => $danaSosial,
                        'total_simpanan' => $totalSimpananKembali,
                        'total_tagihan' => $totalTagihan,
                        'shortfall' => $totalTagihan - $totalSimpananKembali,
                        'tabungan' => $tabunganResign,
                    ],
                    $rekSnapshot
                );

                $pascaTransaksi = [
                    'jenis' => 'menunggu_shortfall',
                    'settlement' => $settlementMenunggu,
                    'jatuh_tempo' => $jatuhTempo,
                ];

                return;
            }

            $dataLama = [
                'status' => $anggotaLocked->status,
                'simpanan_pokok' => $simpananPokok,
                'simpanan_wajib' => $simpananWajib,
                'dana_sosial' => $danaSosial,
                'sisa_tagihan_pinjaman' => $totalTagihan,
            ];

            // Pelunasan angsuran dari offset simpanan dicatat masuk Bank;
            // sisa pengembalian ke anggota menunggu transfer & bukti Bendahara.
            $totalPelunasan = 0.0;
            $rincianPinjaman = [];
            foreach ($pinjamanAktif as $pinjaman) {
                $nominalAwal = (float) $pinjaman->nominal;
                $sisaCicilan = $pinjaman->agregatJadwalAktif()['sisa'];
                $pelunasanPinjaman = 0.0;
                $angsuranList = Angsuran::where('pinjaman_id', $pinjaman->id)
                    ->where('status', 'belum_bayar')
                    ->lockForUpdate()
                    ->orderBy('cicilan_ke')
                    ->get();

                foreach ($angsuranList as $angsuran) {
                    $angsuran->update([
                        'status' => 'lunas',
                        'tanggal_konfirmasi_bayar' => now(),
                        'confirmed_by' => $aktor->id,
                    ]);

                    $this->catatPelunasanResign(
                        jumlah: (float) $angsuran->total_bayar,
                        cicilan: $angsuran->cicilan_ke,
                        referensiId: $angsuran->id,
                        tanggalResign: $tanggalResign,
                        aktor: $aktor,
                        anggota: $anggotaLocked,
                    );

                    $totalPelunasan += (float) $angsuran->total_bayar;
                    $pelunasanPinjaman += (float) $angsuran->total_bayar;
                }

                // Angsuran percepatan dari pengajuan aktif
                $pengajuanAktif = $pinjaman->pengajuanPercepatan()->where('status', 'aktif')->latest()->first();
                if ($pengajuanAktif) {
                    $percepatanList = AngsuranPercepatan::where('pengajuan_percepatan_id', $pengajuanAktif->id)
                        ->where('status', 'belum_bayar')
                        ->lockForUpdate()
                        ->orderBy('cicilan_ke')
                        ->get();

                    foreach ($percepatanList as $ap) {
                        $ap->update([
                            'status' => 'lunas',
                            'tanggal_konfirmasi_bayar' => now(),
                            'confirmed_by' => $aktor->id,
                        ]);

                        $this->catatPelunasanResign(
                            jumlah: (float) $ap->total_bayar,
                            cicilan: $ap->cicilan_ke,
                            referensiId: $ap->id,
                            tanggalResign: $tanggalResign,
                            aktor: $aktor,
                            anggota: $anggotaLocked,
                            percepatan: true,
                        );

                        $totalPelunasan += (float) $ap->total_bayar;
                        $pelunasanPinjaman += (float) $ap->total_bayar;
                    }
                }

                $rincianPinjaman[] = [
                    'nominal_awal' => $nominalAwal,
                    'sisa_tagihan' => $pelunasanPinjaman,
                    'sisa_cicilan' => $sisaCicilan,
                ];

                // Tandai pinjaman lunas kalau semua jadwal sudah tidak ada belum_bayar.
                $sisaLama = Angsuran::where('pinjaman_id', $pinjaman->id)->where('status', 'belum_bayar')->count();
                $sisaBaru = $pengajuanAktif
                    ? AngsuranPercepatan::where('pengajuan_percepatan_id', $pengajuanAktif->id)->where('status', 'belum_bayar')->count()
                    : 0;

                if ($sisaLama === 0 && $sisaBaru === 0) {
                    $pinjaman->update(['status' => 'lunas']);
                }
            }

            $alokasiPokok = min($simpananPokok, $totalPelunasan);
            $alokasiWajib = max(0, $totalPelunasan - $simpananPokok);
            $kembaliPokok = round(max(0, $simpananPokok - $alokasiPokok), 2);
            $kembaliWajib = round(max(0, $simpananWajib - $alokasiWajib), 2);
            $totalDikembalikan = round($kembaliPokok + $kembaliWajib + $tabunganResign['kembali'], 2);
            $statusPencairan = $totalDikembalikan > 0 ? 'menunggu' : 'tidak_perlu';

            // Snapshot settlement untuk audit, pencairan Bendahara & reprint PDF.
            $settlement = [
                'doc_no' => app(PenomoranDokumenService::class)->berikutnya(
                    PenomoranDokumenService::JENIS_RESIGN,
                    Carbon::parse($tanggalResign)
                ),
                'tagihan_pelunasan' => $totalPelunasan,
                'simpanan_pokok_total' => $simpananPokok,
                'simpanan_wajib_total' => $simpananWajib,
                'dana_sosial_hangus' => $danaSosial,
                'alokasi_dari_pokok' => $alokasiPokok,
                'alokasi_dari_wajib' => $alokasiWajib,
                'kembali_pokok' => $kembaliPokok,
                'kembali_wajib' => $kembaliWajib,
                'tabungan_rekening_id' => $tabunganResign['rekening_id'],
                'tabungan_transaksi_id' => $tabunganResign['transaksi_id'],
                'tabungan_no_rekening' => $tabunganResign['no_rekening'],
                'tabungan_bunga_prorata' => $tabunganResign['bunga_prorata'],
                'kembali_tabungan' => $tabunganResign['kembali'],
                'total_dikembalikan' => $totalDikembalikan,
                'rekening_tujuan' => $rekSnapshot,
                'status_pencairan' => $statusPencairan,
                'tanggal_proses' => $tanggalResign,
                'aktor' => $aktor->no_karyawan ?? $aktor->name,
                'pinjaman_rincian' => $rincianPinjaman,
            ];

            // Update anggota + user.
            $anggotaLocked->update([
                'status' => 'resign',
                'tanggal_resign' => $tanggalResign,
                'alasan_resign' => $alasan,
                'resigned_by' => $aktor->id,
                'resigned_settlement_json' => $settlement,
                'status_pencairan_resign' => $statusPencairan,
                'resign_snapshot_bank' => $rekSnapshot['nama_bank'] ?? null,
                'resign_snapshot_no_rekening' => $rekSnapshot['no_rekening'] ?? null,
                'resign_snapshot_atas_nama' => $rekSnapshot['atas_nama'] ?? null,
                'resign_akun_kas_pencairan' => null,
                'tanggal_pencairan_resign' => null,
                'dicairkan_resign_oleh' => null,
                'catatan_pencairan_resign' => null,
                'bukti_pencairan_resign_path' => null,
                'bukti_pencairan_resign_nama_asli' => null,
                'bukti_pencairan_resign_mime' => null,
            ]);

            if ($anggotaLocked->user_id) {
                User::where('id', $anggotaLocked->user_id)->update(['status' => 'nonaktif']);
            }

            AuditLog::catat(
                aksi: 'anggota_resign',
                keterangan: "Resign anggota {$anggotaLocked->nama} ({$anggotaLocked->no_karyawan}). ".
                    'Pelunasan: Rp '.number_format($totalPelunasan, 0, ',', '.').
                    ', pengembalian ke anggota: Rp '.number_format($totalDikembalikan, 0, ',', '.').
                    ($totalDikembalikan > 0 ? ' (menunggu pencairan Bendahara)' : ' (tanpa sisa pengembalian)').
                    ', dana_sosial hangus: Rp '.number_format($danaSosial, 0, ',', '.').
                    ". Alasan: {$alasan}",
                dataLama: $dataLama,
                dataBaru: [
                    'status' => 'resign',
                    'status_pencairan_resign' => $statusPencairan,
                    'tanggal_resign' => $tanggalResign,
                    'alasan_resign' => $alasan,
                    'resigned_by' => $aktor->no_karyawan ?? $aktor->name,
                    'settlement' => $settlement,
                ]
            );

            $pascaTransaksi = [
                'jenis' => $totalDikembalikan <= 0 ? 'tanpa_pengembalian' : null,
                'settlement' => $settlement,
                'jatuh_tempo' => null,
            ];
        });

        if ($pascaTransaksi['jenis'] === 'menunggu_shortfall') {
            $this->kirimWaMenunggu(
                $anggota->fresh(),
                $pascaTransaksi['settlement'],
                (string) $pascaTransaksi['jatuh_tempo']
            );
        } elseif ($pascaTransaksi['jenis'] === 'tanpa_pengembalian') {
            $this->kirimWaResignTanpaPengembalian($anggota->fresh(), $pascaTransaksi['settlement']);
        }
    }

    /**
     * Tahap 2 (Bendahara): eksekusi pencairan dana pengembalian resign + simpan bukti transfer
     * + catat jurnal kas keluar di tanggal transfer + kirim WA 1x dengan bukti & slip PDF.
     */
    public function cairkanBendahara(
        Anggota $anggota,
        ?UploadedFile $buktiTransfer,
        string $tanggalPencairan,
        string $akunKas,
        ?string $catatan,
        ?int $aktorId = null,
    ): void {
        if (! in_array($akunKas, ['bank', 'kas_kecil'], true)) {
            throw new RuntimeException('Sumber kas harus Bank atau Kas Kecil.');
        }

        $tglCair = Carbon::parse($tanggalPencairan)->startOfDay();
        if ($tglCair->greaterThan(now()->startOfDay())) {
            throw new RuntimeException('Tanggal pencairan tidak boleh melebihi hari ini.');
        }

        $aktorId ??= auth()->id();
        $buktiBytes = $buktiTransfer ? $buktiTransfer->get() : null;
        $fileMeta = $buktiTransfer ? $this->simpanBuktiPrivat($buktiTransfer, $tglCair) : null;
        $settlementFinal = [];

        DB::transaction(function () use ($anggota, $tglCair, $akunKas, $catatan, $aktorId, $fileMeta, &$settlementFinal) {
            $anggotaLocked = Anggota::lockForUpdate()->findOrFail($anggota->id);

            if ($anggotaLocked->status_pencairan_resign !== 'menunggu') {
                throw new RuntimeException('Pengembalian resign anggota ini tidak sedang menunggu pencairan.');
            }

            $settlement = $anggotaLocked->resigned_settlement_json ?? [];
            $kembaliPokok = round((float) ($settlement['kembali_pokok'] ?? 0), 2);
            $kembaliWajib = round((float) ($settlement['kembali_wajib'] ?? 0), 2);
            $kembaliTabungan = round((float) ($settlement['kembali_tabungan'] ?? 0), 2);
            $totalDikembalikan = round($kembaliPokok + $kembaliWajib + $kembaliTabungan, 2);

            if ($totalDikembalikan <= 0) {
                throw new RuntimeException('Tidak ada saldo pengembalian resign untuk dicairkan.');
            }

            $kas = KasKoperasi::lockForUpdate()->firstOrFail();
            $kolomFisik = $akunKas === 'kas_kecil' ? 'saldo_kas_kecil' : 'saldo_bank';
            $saldoSebelum = (float) $kas->{$kolomFisik};
            if ($totalDikembalikan > $saldoSebelum) {
                $labelAkun = $akunKas === 'kas_kecil' ? 'Kas Kecil' : 'Bank';
                throw new RuntimeException(
                    "Melebihi saldo {$labelAkun}. Saldo {$labelAkun} saat ini: ".WaPesan::rupiah($saldoSebelum)
                    .', nominal pengembalian: '.WaPesan::rupiah($totalDikembalikan).'.'
                );
            }

            $tglStr = $tglCair->toDateString();

            if ($kembaliPokok > 0) {
                $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'return_simpanan_pokok',
                    kantong: $akunKas === 'kas_kecil' ? 'kas_kecil' : 'pengembalian_simpanan',
                    jumlah: $kembaliPokok,
                    keterangan: "Pengembalian simpanan pokok resign - {$anggotaLocked->nama}",
                    referensiId: $anggotaLocked->id,
                    tanggal: $tglStr,
                    userId: (int) $aktorId,
                    subJudul: 'Pencairan pengembalian resign ke anggota',
                );
            }

            if ($kembaliWajib > 0) {
                $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'return_simpanan_wajib',
                    kantong: $akunKas === 'kas_kecil' ? 'kas_kecil' : 'pengembalian_simpanan',
                    jumlah: $kembaliWajib,
                    keterangan: "Pengembalian simpanan wajib resign - {$anggotaLocked->nama}",
                    referensiId: $anggotaLocked->id,
                    tanggal: $tglStr,
                    userId: (int) $aktorId,
                    subJudul: 'Pencairan pengembalian resign ke anggota',
                );
            }

            if ($kembaliTabungan > 0) {
                $noRekTabungan = $settlement['tabungan_no_rekening'] ?? null;
                $jurnalTab = $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'return_tabungan_resign',
                    kantong: $akunKas === 'kas_kecil' ? 'kas_kecil' : 'tabungan',
                    jumlah: $kembaliTabungan,
                    keterangan: "Pengembalian tabungan resign - {$anggotaLocked->nama}"
                        .($noRekTabungan ? " ({$noRekTabungan})" : ''),
                    referensiId: (int) ($settlement['tabungan_rekening_id'] ?? $anggotaLocked->id),
                    tanggal: $tglStr,
                    userId: (int) $aktorId,
                    subJudul: 'Pencairan tabungan anggota resign',
                );

                if (! empty($settlement['tabungan_transaksi_id'])) {
                    TabunganTransaksi::where('id', $settlement['tabungan_transaksi_id'])->update([
                        'jurnal_kas_id' => $jurnalTab->id,
                        'no_bukti' => $jurnalTab->no_bukti,
                        'kanal' => $akunKas === 'kas_kecil' ? 'tunai' : 'transfer',
                        'tanggal' => $tglStr,
                    ]);
                }
            }

            $aktorUser = $aktorId ? User::find($aktorId) : null;
            $catatanBersih = $catatan ? trim($catatan) : null;

            $settlement['status_pencairan'] = 'dicairkan';
            $settlement['tanggal_pencairan'] = $tglStr;
            $settlement['akun_kas_pencairan'] = $akunKas;
            $settlement['dicairkan_oleh'] = $aktorUser?->name;
            $settlement['catatan_pencairan'] = $catatanBersih;
            $settlementFinal = $settlement;

            $anggotaLocked->update([
                'status_pencairan_resign' => 'dicairkan',
                'resign_akun_kas_pencairan' => $akunKas,
                'tanggal_pencairan_resign' => $tglStr,
                'dicairkan_resign_oleh' => $aktorId,
                'catatan_pencairan_resign' => $catatanBersih,
                'bukti_pencairan_resign_path' => $fileMeta['path'] ?? null,
                'bukti_pencairan_resign_nama_asli' => $fileMeta['nama_asli'] ?? null,
                'bukti_pencairan_resign_mime' => $fileMeta['mime'] ?? null,
                'resigned_settlement_json' => $settlement,
            ]);

            AuditLog::catat(
                aksi: 'resign_dicairkan',
                keterangan: "Pencairan pengembalian resign {$anggotaLocked->nama} ({$anggotaLocked->no_karyawan}) sebesar "
                    .WaPesan::rupiah($totalDikembalikan)." via {$akunKas}.",
                dataLama: ['status_pencairan_resign' => 'menunggu'],
                dataBaru: [
                    'status_pencairan_resign' => 'dicairkan',
                    'akun_kas' => $akunKas,
                    'tanggal_pencairan' => $tglStr,
                    'total_dikembalikan' => $totalDikembalikan,
                    'ada_bukti_pencairan' => (bool) $fileMeta,
                ],
                userId: $aktorId,
            );
        });

        $anggotaFresh = $anggota->fresh(['dicairkanResignOleh']);
        $this->kirimWaDicairkan($anggotaFresh, $settlementFinal, $tglCair, $akunKas, $buktiBytes, $fileMeta);
    }

    /**
     * @return array{nama_bank: string|null, no_rekening: string|null, atas_nama: string|null}|null
     */
    private function resolveRekeningTujuan(Anggota $anggota, ?array $input): ?array
    {
        $bank = trim((string) ($input['nama_bank'] ?? $input['bank'] ?? ''));
        $nomor = trim((string) ($input['no_rekening'] ?? $input['nomor'] ?? ''));
        $atasNama = trim((string) ($input['atas_nama'] ?? ''));

        if ($bank !== '' || $nomor !== '') {
            return [
                'nama_bank' => $bank !== '' ? $bank : null,
                'no_rekening' => $nomor !== '' ? $nomor : null,
                'atas_nama' => $atasNama !== '' ? $atasNama : $anggota->nama,
            ];
        }

        $default = $anggota->rekening()->orderByDesc('is_default')->orderBy('id')->first();
        if (! $default) {
            return null;
        }

        return [
            'nama_bank' => $default->nama_bank,
            'no_rekening' => $default->no_rekening,
            'atas_nama' => $default->atas_nama,
        ];
    }

    /**
     * @return array{path: string, nama_asli: string, mime: string}
     */
    private function simpanBuktiPrivat(UploadedFile $file, Carbon $tanggal): array
    {
        $folder = sprintf('resign/bukti-pencairan/%04d/%02d', $tanggal->year, $tanggal->month);
        $path = Storage::disk('local')->putFile($folder, $file);
        if (! $path) {
            throw new RuntimeException('Gagal menyimpan file bukti transfer pencairan resign.');
        }

        return [
            'path' => $path,
            'nama_asli' => $file->getClientOriginalName() ?: basename($path),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
        ];
    }

    /**
     * Jalur B: simpanan tidak cukup. Seluruh simpanan dialokasikan ke
     * angsuran terawal (tertua), sisa tagihan menjadi SATU cicilan akhir
     * terjadwal jatuh tempo akhir bulan tanggal_resign. Status anggota
     * menjadi resign_menunggu; user tetap aktif agar portal + WA jalan.
     *
     * @param  array{nama_bank: string|null, no_rekening: string|null, atas_nama: string|null}|null  $rekSnapshot
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function prosesMenunggu(
        Anggota $anggotaLocked,
        string $alasan,
        string $tanggalResign,
        User $aktor,
        array $snap,
        ?array $rekSnapshot = null,
    ): array {
        $jatuhTempo = Carbon::parse($tanggalResign)->endOfMonth()->format('Y-m-d');
        $sisaAlokasi = $snap['total_simpanan'];
        $totalTerpakai = 0.0;
        $totalAlokasiPokok = min($snap['simpanan_pokok'], $snap['total_tagihan']);
        $alokasiPokokTerpakai = 0.0;

        // Alokasikan simpanan ke angsuran terawal (biasa lalu percepatan).
        $semuaBelum = collect();
        foreach ($anggotaLocked->pinjaman()->where('status', 'aktif')->get() as $pinjaman) {
            $list = Angsuran::where('pinjaman_id', $pinjaman->id)
                ->where('status', 'belum_bayar')
                ->lockForUpdate()
                ->orderBy('tanggal_jatuh_tempo')
                ->orderBy('cicilan_ke')
                ->get()
                ->map(fn ($a) => ['model' => $a, 'percepatan' => false, 'pinjaman' => $pinjaman]);
            $semuaBelum = $semuaBelum->concat($list);

            $pengajuanAktif = $pinjaman->pengajuanPercepatan()->where('status', 'aktif')->latest()->first();
            if ($pengajuanAktif) {
                $listP = AngsuranPercepatan::where('pengajuan_percepatan_id', $pengajuanAktif->id)
                    ->where('status', 'belum_bayar')
                    ->lockForUpdate()
                    ->orderBy('tanggal_jatuh_tempo')
                    ->orderBy('cicilan_ke')
                    ->get()
                    ->map(fn ($a) => ['model' => $a, 'percepatan' => true, 'pinjaman' => $pinjaman]);
                $semuaBelum = $semuaBelum->concat($listP);
            }
        }
        $semuaBelum = $semuaBelum->sortBy([
            fn ($a, $b) => strcmp((string) $a['model']->tanggal_jatuh_tempo, (string) $b['model']->tanggal_jatuh_tempo),
            fn ($a, $b) => $a['model']->cicilan_ke <=> $b['model']->cicilan_ke,
        ])->values();

        foreach ($semuaBelum as $item) {
            $a = $item['model'];
            $nilai = (float) $a->total_bayar;
            if ($sisaAlokasi > 0 && $nilai <= $sisaAlokasi) {
                $a->update([
                    'status' => 'lunas',
                    'tanggal_konfirmasi_bayar' => now(),
                    'confirmed_by' => $aktor->id,
                ]);
                $this->catatPelunasanResign(
                    jumlah: $nilai,
                    cicilan: $a->cicilan_ke,
                    referensiId: $a->id,
                    tanggalResign: $tanggalResign,
                    aktor: $aktor,
                    anggota: $anggotaLocked,
                    percepatan: $item['percepatan'],
                );
                $sisaAlokasi -= $nilai;
                $totalTerpakai += $nilai;
                $alokasiPokokTerpakai = min($totalAlokasiPokok, $alokasiPokokTerpakai + $nilai);

                continue;
            }
            // Sisa tak teralokasi digantikan cicilan akhir. Angsuran biasa
            // → 'digantikan' (dikecualikan dari jadwal aktif). Percepatan
            // tak punya status itu → lunasi administratif.
            if ($item['percepatan']) {
                $a->update([
                    'status' => 'lunas',
                    'tanggal_konfirmasi_bayar' => now(),
                    'confirmed_by' => $aktor->id,
                ]);
            } else {
                $a->update(['status' => 'digantikan']);
            }
        }

        // Sisa alokasi yang tak muat satu angsuran penuh dicatat agregat
        // supaya seluruh simpanan terpakai (kembali = 0 di jalur menunggu).
        $sisaAgregat = round($snap['total_simpanan'] - $totalTerpakai, 2);
        if ($sisaAgregat > 0) {
            $this->catatPelunasanAgregat(
                jumlah: $sisaAgregat,
                tanggalResign: $tanggalResign,
                aktor: $aktor,
                anggota: $anggotaLocked,
            );
            $totalTerpakai += $sisaAgregat;
            $alokasiPokokTerpakai = min($totalAlokasiPokok, $alokasiPokokTerpakai + $sisaAgregat);
        }

        $shortfall = round($snap['total_tagihan'] - $totalTerpakai, 2);

        if ($shortfall <= 0) {
            throw new RuntimeException('Perhitungan shortfall tidak valid. Hubungi Sekretaris.');
        }

        // Buat SATU cicilan akhir pada pinjaman dengan sisa terbesar.
        $target = $anggotaLocked->pinjaman()->where('status', 'aktif')->get()
            ->sortByDesc(fn ($p) => $p->agregatJadwalAktif()['sisa_bayar'])
            ->firstOrFail();
        $cicilanTerakhir = (int) Angsuran::where('pinjaman_id', $target->id)->max('cicilan_ke');

        $cicilanAkhir = Angsuran::create([
            'pinjaman_id' => $target->id,
            'cicilan_ke' => $cicilanTerakhir + 1,
            'nominal_pokok' => $shortfall,
            'nominal_bunga' => 0,
            'total_bayar' => $shortfall,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => $jatuhTempo,
        ]);

        $totalDikembalikan = round((float) ($snap['tabungan']['kembali'] ?? 0), 2);
        $statusPencairan = $totalDikembalikan > 0 ? 'menunggu' : 'tidak_perlu';

        $settlement = [
            'doc_no' => app(PenomoranDokumenService::class)->berikutnya(
                PenomoranDokumenService::JENIS_RESIGN,
                Carbon::parse($tanggalResign)
            ),
            'mode' => 'menunggu_pelunasan_akhir',
            'tagihan_pelunasan' => $snap['total_tagihan'],
            'simpanan_pokok_total' => $snap['simpanan_pokok'],
            'simpanan_wajib_total' => $snap['simpanan_wajib'],
            'dana_sosial_hangus' => $snap['dana_sosial'],
            'alokasi_dari_pokok' => round($alokasiPokokTerpakai, 2),
            'alokasi_dari_wajib' => round($totalTerpakai - $alokasiPokokTerpakai, 2),
            'terpakai_pelunasan' => round($totalTerpakai, 2),
            'kembali_pokok' => 0,
            'kembali_wajib' => 0,
            'tabungan_rekening_id' => $snap['tabungan']['rekening_id'] ?? null,
            'tabungan_transaksi_id' => $snap['tabungan']['transaksi_id'] ?? null,
            'tabungan_no_rekening' => $snap['tabungan']['no_rekening'] ?? null,
            'tabungan_bunga_prorata' => $snap['tabungan']['bunga_prorata'] ?? 0,
            'kembali_tabungan' => $totalDikembalikan,
            'total_dikembalikan' => $totalDikembalikan,
            'rekening_tujuan' => $rekSnapshot,
            'status_pencairan' => $statusPencairan,
            'shortfall' => $shortfall,
            'cicilan_akhir_id' => $cicilanAkhir->id,
            'cicilan_akhir_pinjaman_id' => $target->id,
            'cicilan_akhir_jatuh_tempo' => $jatuhTempo,
            'tanggal_proses' => $tanggalResign,
            'aktor' => $aktor->no_karyawan ?? $aktor->name,
            'pinjaman_rincian' => [],
        ];

        $anggotaLocked->update([
            'status' => 'resign_menunggu',
            'tanggal_resign' => $tanggalResign,
            'alasan_resign' => $alasan,
            'resigned_by' => $aktor->id,
            'resigned_settlement_json' => $settlement,
            'status_pencairan_resign' => $statusPencairan,
            'resign_snapshot_bank' => $rekSnapshot['nama_bank'] ?? null,
            'resign_snapshot_no_rekening' => $rekSnapshot['no_rekening'] ?? null,
            'resign_snapshot_atas_nama' => $rekSnapshot['atas_nama'] ?? null,
            'resign_akun_kas_pencairan' => null,
            'tanggal_pencairan_resign' => null,
            'dicairkan_resign_oleh' => null,
            'catatan_pencairan_resign' => null,
            'bukti_pencairan_resign_path' => null,
            'bukti_pencairan_resign_nama_asli' => null,
            'bukti_pencairan_resign_mime' => null,
        ]);

        AuditLog::catat(
            aksi: 'anggota_resign_menunggu',
            keterangan: "Resign menunggu pelunasan akhir {$anggotaLocked->nama} ({$anggotaLocked->no_karyawan}). ".
                'Simpanan terpakai: Rp '.number_format($totalTerpakai, 0, ',', '.').
                ', sisa cicilan akhir: Rp '.number_format($shortfall, 0, ',', '.').
                " jatuh tempo {$jatuhTempo}. Alasan: {$alasan}",
            dataLama: ['status' => 'aktif'],
            dataBaru: [
                'status' => 'resign_menunggu',
                'status_pencairan_resign' => $statusPencairan,
                'tanggal_resign' => $tanggalResign,
                'settlement' => $settlement,
            ]
        );

        return [$settlement, $jatuhTempo];
    }

    /**
     * Finalisasi resign_menunggu setelah cicilan akhir lunas via Konfirmasi
     * Angsuran. Dipanggil dari KonfirmasiAngsuranService (dalam transaksi
     * yang sama) agar status berubah atomik dengan pelunasan.
     */
    public function finalisasiJikaMenunggu(Angsuran $angsuran, int $aktorId): bool
    {
        $pinjaman = $angsuran->pinjaman;
        $anggota = Anggota::lockForUpdate()->find($pinjaman->anggota_id);

        if (! $anggota || $anggota->status !== 'resign_menunggu') {
            return false;
        }

        $settlement = $anggota->resigned_settlement_json ?? [];

        if (($settlement['cicilan_akhir_id'] ?? null) !== $angsuran->id) {
            return false;
        }

        $masihAda = Angsuran::where('pinjaman_id', $pinjaman->id)
            ->where('status', 'belum_bayar')
            ->exists()
            || AngsuranPercepatan::whereHas('pengajuan', fn ($q) => $q
                ->where('pinjaman_id', $pinjaman->id)
                ->where('status', 'aktif'))
                ->where('status', 'belum_bayar')
                ->exists();

        if ($masihAda) {
            return false;
        }

        $anggota->update(['status' => 'resign']);
        $pinjaman->update(['status' => 'lunas']);

        if ($anggota->user_id) {
            User::where('id', $anggota->user_id)->update(['status' => 'nonaktif']);
        }

        $settlement['mode'] = 'selesai';
        $settlement['tanggal_final'] = now()->format('Y-m-d');
        $anggota->update(['resigned_settlement_json' => $settlement]);

        AuditLog::catat(
            aksi: 'anggota_resign',
            keterangan: "Resign final {$anggota->nama} ({$anggota->no_karyawan}) setelah cicilan akhir Rp ".
                number_format((float) $angsuran->total_bayar, 0, ',', '.').' lunas.',
            dataLama: ['status' => 'resign_menunggu'],
            dataBaru: ['status' => 'resign', 'tanggal_final' => now()->format('Y-m-d')]
        );

        $this->kirimWaFinal($anggota->refresh(), $settlement);

        return true;
    }

    /**
     * Tautan rincian publik bertanda tangan. Kedaluwarsa mengikuti
     * tanggal_resign; bila sudah lewat, fallback +30 hari dari sekarang.
     */
    public function tautanRincian(Anggota $anggota): string
    {
        $batas = $anggota->tanggal_resign
            ? Carbon::parse($anggota->tanggal_resign)->endOfDay()
            : now()->addDays(30);

        if ($batas->isPast()) {
            $batas = now()->addDays(30);
        }

        return URL::temporarySignedRoute('verifikasi.resign', $batas, ['anggota' => $anggota->id]);
    }

    private function kirimWaMenunggu(Anggota $anggota, array $settlement, string $jatuhTempo): void
    {
        $tautan = $this->tautanRincian($anggota);
        $batasTampil = Carbon::parse($anggota->tanggal_resign)->translatedFormat('d F Y');

        $isi = "Dengan hormat,\n\nProses pengunduran diri Bapak/Ibu dari keanggotaan koperasi telah kami catat dengan rincian:\n\n"
            .'- Simpanan pokok + wajib: '.WaPesan::rupiah($settlement['simpanan_pokok_total'] + $settlement['simpanan_wajib_total'])."\n"
            .'- Dialokasikan untuk pelunasan: '.WaPesan::rupiah($settlement['terpakai_pelunasan'])."\n"
            .'- Sisa cicilan akhir: '.WaPesan::rupiah($settlement['shortfall'])."\n"
            .'- Jatuh tempo cicilan akhir: '.Carbon::parse($jatuhTempo)->translatedFormat('d F Y')."\n\n"
            ."Mohon selesaikan sisa cicilan akhir sebelum tanggal jatuh tempo. Rincian lengkap dapat dilihat pada tautan berikut (berlaku sampai {$batasTampil}, atau 30 hari bila sudah lewat):\n{$tautan}\n\n"
            .'Terima kasih.';

        WaService::keAnggota($anggota, 'resign_menunggu', WaPesan::susun($anggota->nama, $anggota->no_karyawan, $isi));
    }

    private function kirimWaFinal(Anggota $anggota, array $settlement): void
    {
        $tautan = $this->tautanRincian($anggota);
        $batasTampil = Carbon::parse($anggota->tanggal_resign)->translatedFormat('d F Y');

        $isi = "Dengan hormat,\n\nSisa cicilan akhir Bapak/Ibu telah lunas. Pengunduran diri dari keanggotaan koperasi kini *SELESAI*.\n\n"
            ."Rincian penyelesaian dapat dilihat pada tautan berikut (berlaku sampai {$batasTampil}, atau 30 hari bila sudah lewat):\n{$tautan}\n\n"
            .'Terima kasih atas kepercayaan Bapak/Ibu selama menjadi anggota.';

        WaService::keAnggota($anggota, 'resign_selesai', WaPesan::susun($anggota->nama, $anggota->no_karyawan, $isi));
    }

    private function kirimWaResignTanpaPengembalian(Anggota $anggota, array $settlement): void
    {
        $tautan = $this->tautanRincian($anggota);
        $docNo = $settlement['doc_no'] ?? '-';
        $tglResign = $anggota->tanggal_resign
            ? Carbon::parse($anggota->tanggal_resign)->locale('id')->translatedFormat('d F Y')
            : '-';

        $isi = "Dengan hormat,\n\nProses pengunduran diri (resign) keanggotaan koperasi Bapak/Ibu telah *SELESAI* diproses pada {$tglResign}.\n\n"
            ."Rincian penyelesaian:\n"
            ."- Nomor Dokumen: {$docNo}\n"
            .'- Simpanan Pokok + Wajib: '.WaPesan::rupiah(($settlement['simpanan_pokok_total'] ?? 0) + ($settlement['simpanan_wajib_total'] ?? 0))."\n"
            .'- Dialokasikan untuk pelunasan pinjaman: '.WaPesan::rupiah($settlement['tagihan_pelunasan'] ?? 0)."\n"
            ."- Sisa dikembalikan: Rp 0\n\n"
            ."Terlampir dokumen resmi *Slip Pengembalian Simpanan*. Rincian daring juga dapat dibuka pada tautan berikut:\n{$tautan}\n\n"
            .'Terima kasih atas kebersamaan Anda bersama koperasi.';

        WaService::keAnggotaDokumen(
            $anggota,
            'resign_selesai_tanpa_transfer',
            WaPesan::susun($anggota->nama, $anggota->no_karyawan, $isi),
            Pdf::loadView('anggota.slip_resign', $anggota->dataSlipResign())->output(),
            "Slip-Resign-{$anggota->no_karyawan}.pdf",
            'application/pdf',
        );
    }

    private function kirimWaDicairkan(
        Anggota $anggota,
        array $settlement,
        Carbon $tglCair,
        string $akunKas,
        ?string $buktiBytes,
        ?array $fileMeta,
    ): void {
        $tautan = $this->tautanRincian($anggota);
        $docNo = $settlement['doc_no'] ?? '-';
        $total = (float) ($settlement['total_dikembalikan'] ?? 0);
        $metode = $akunKas === 'kas_kecil' ? 'Tunai (Kas Kecil)' : 'Transfer Bank';
        $rek = $settlement['rekening_tujuan'] ?? null;
        $rekTampil = ($rek && ! empty($rek['no_rekening']))
            ? trim(($rek['nama_bank'] ? $rek['nama_bank'].' ' : '').$rek['no_rekening'].(! empty($rek['atas_nama']) ? " a.n. {$rek['atas_nama']}" : ''))
            : '-';

        $rincianBaris = [];
        if (($settlement['kembali_pokok'] ?? 0) > 0) {
            $rincianBaris[] = '- Kembali Simpanan Pokok: '.WaPesan::rupiah($settlement['kembali_pokok']);
        }
        if (($settlement['kembali_wajib'] ?? 0) > 0) {
            $rincianBaris[] = '- Kembali Simpanan Wajib: '.WaPesan::rupiah($settlement['kembali_wajib']);
        }
        if (($settlement['kembali_tabungan'] ?? 0) > 0) {
            $rincianBaris[] = '- Kembali Tabungan (+ Bunga Pro-Rata): '.WaPesan::rupiah($settlement['kembali_tabungan']);
        }

        $isi = "Dengan hormat,\n\nPengembalian hak simpanan/tabungan resign Anda telah *DICAIRKAN / DITRANSFER* oleh Bendahara Koperasi pada "
            .$tglCair->copy()->locale('id')->translatedFormat('d F Y').".\n\n"
            ."Rincian penyelesaian:\n"
            ."- Nomor Dokumen: {$docNo}\n"
            .(($settlement['tagihan_pelunasan'] ?? 0) > 0 ? '- Dipotong pelunasan pinjaman: '.WaPesan::rupiah($settlement['tagihan_pelunasan'])."\n" : '')
            .(implode("\n", $rincianBaris) !== '' ? implode("\n", $rincianBaris)."\n" : '')
            .'- *Total Diterima*: '.WaPesan::rupiah($total).' ('.TerbilangHelper::angkaKeTerbilang($total)." rupiah)\n"
            ."- Metode: {$metode}\n"
            .($akunKas === 'bank' ? "- Rekening Tujuan: {$rekTampil}\n" : '')
            ."\nRincian lengkap dapat dibuka di:\n{$tautan}\n\n"
            .($fileMeta
                ? "Terlampir *Bukti Transfer Pencairan* pada pesan ini. Dokumen resmi *Slip Pengembalian Simpanan (PDF)* dikirimkan pada pesan berikutnya.\n\n"
                : '')
            .'Terima kasih atas kebersamaan dan kepercayaan Anda selama menjadi anggota koperasi.';

        $pesan = WaPesan::susun($anggota->nama, $anggota->no_karyawan, $isi);
        if ($fileMeta && $buktiBytes !== null) {
            WaService::keAnggotaDokumen(
                $anggota,
                'resign_dicairkan',
                $pesan,
                $buktiBytes,
                $fileMeta['nama_asli'],
                $fileMeta['mime'],
            );
        } else {
            WaService::keAnggota($anggota, 'resign_dicairkan', $pesan);
        }

        $isiSlip = "Berikut terlampir dokumen resmi *Slip Pengembalian Simpanan Resign* ({$docNo}) atas nama {$anggota->nama}.";
        WaService::keAnggotaDokumen(
            $anggota,
            'resign_slip_pdf',
            WaPesan::susun($anggota->nama, $anggota->no_karyawan, $isiSlip),
            Pdf::loadView('anggota.slip_resign', $anggota->dataSlipResign())->output(),
            "Slip-Resign-{$anggota->no_karyawan}.pdf",
            'application/pdf',
        );
    }

    /**
     * Sisa alokasi yang tak muat satu angsuran penuh: catat agregat
     * (tanpa menandai angsuran tertentu lunas). Dipakai hanya di jalur
     * resign_menunggu agar seluruh simpanan terpakai.
     */
    private function catatPelunasanAgregat(
        float $jumlah,
        string $tanggalResign,
        User $aktor,
        Anggota $anggota,
    ): void {
        $this->jurnalKas->catat(
            tipe: 'masuk',
            kategori: 'pelunasan_resign_pinjaman',
            kantong: 'pinjaman',
            jumlah: $jumlah,
            keterangan: "Pelunasan resign agregat (sisa alokasi) - {$anggota->nama}",
            referensiId: $anggota->id,
            tanggal: $tanggalResign,
            userId: $aktor->id,
            subJudul: 'Pelunasan dari uang simpanan anggota',
        );
    }

    /**
     * Catat 1 transaksi pelunasan angsuran saat resign sebagai jurnal masuk
     * Bank: MASUK kantong:pinjaman, sub: "Pelunasan dari uang simpanan
     * anggota". Dananya di-offset dari simpanan.
     */
    private function catatPelunasanResign(
        float $jumlah,
        int $cicilan,
        int $referensiId,
        string $tanggalResign,
        User $aktor,
        Anggota $anggota,
        bool $percepatan = false,
    ): void {
        $jenis = $percepatan ? 'percepatan' : 'angsuran';
        $keterangan = "Pelunasan resign {$jenis} ke-{$cicilan} - {$anggota->nama}";

        $this->jurnalKas->catat(
            tipe: 'masuk',
            kategori: 'pelunasan_resign_pinjaman',
            kantong: 'pinjaman',
            jumlah: $jumlah,
            keterangan: $keterangan,
            referensiId: $referensiId,
            tanggal: $tanggalResign,
            userId: $aktor->id,
            subJudul: 'Pelunasan dari uang simpanan anggota',
        );
    }

    /**
     * Hitung bunga pro-rata s/d tanggal resign, tutup rekening tabungan aktif,
     * dan catat transaksi penutupan tabungan (jurnal kas menunggu pencairan Bendahara).
     *
     * @return array{rekening_id: int|null, transaksi_id: int|null, no_rekening: string|null, bunga_prorata: float, kembali: float}
     */
    private function prosesTutupTabunganResign(Anggota $anggotaLocked, string $tanggalResign, User $aktor): array
    {
        $rekening = RekeningTabungan::where('anggota_id', $anggotaLocked->id)
            ->where('status', 'aktif')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if (! $rekening) {
            return ['rekening_id' => null, 'transaksi_id' => null, 'no_rekening' => null, 'bunga_prorata' => 0.0, 'kembali' => 0.0];
        }

        $tglResignCarbon = Carbon::parse($tanggalResign);
        $bungaProrata = $this->bungaTabungan->kreditkanBungaPenutupan($rekening, $tglResignCarbon, $aktor->id);
        $rekening->refresh();

        $saldoAkhir = round((float) $rekening->saldo, 2);
        $trx = null;

        if ($saldoAkhir > 0) {
            $trx = TabunganTransaksi::create([
                'rekening_tabungan_id' => $rekening->id,
                'anggota_id' => $anggotaLocked->id,
                'no_bukti' => 'RSGN-TAB-'.$rekening->id,
                'jenis' => 'tutup_rekening',
                'kanal' => 'transfer',
                'nominal' => $saldoAkhir,
                'saldo_sebelum' => $saldoAkhir,
                'saldo_sesudah' => 0,
                'tanggal' => $tanggalResign,
                'keterangan' => "Penutupan & pengembalian tabungan resign ({$rekening->no_rekening})",
                'jurnal_kas_id' => null,
                'created_by' => $aktor->id,
            ]);
        }

        $rekening->update([
            'saldo' => 0,
            'status' => 'ditutup',
            'tanggal_tutup' => $tanggalResign,
            'diproses_oleh' => $aktor->id,
            'catatan_pengurus' => 'Ditutup otomatis karena anggota resign.',
        ]);

        return [
            'rekening_id' => $rekening->id,
            'transaksi_id' => $trx?->id,
            'no_rekening' => $rekening->no_rekening,
            'bunga_prorata' => round($bungaProrata, 2),
            'kembali' => $saldoAkhir,
        ];
    }
}
