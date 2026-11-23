<?php

namespace App\Services\Tabungan;

use App\Models\AuditLog;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\RekeningAnggota;
use App\Models\RekeningTabungan;
use App\Models\SettingTabungan;
use App\Models\TabunganTransaksi;
use App\Services\Keuangan\JurnalKasService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TabunganTransaksiService
{
    public function __construct(
        private JurnalKasService $jurnalKas,
        private PerhitunganBungaTabunganService $bungaService,
    ) {}

    public function ajukanSetor(
        RekeningTabungan $rekening,
        float $nominal,
        string $tanggalTransaksi,
        UploadedFile $bukti,
        ?string $catatan,
        int $aktorId,
    ): PengajuanTransaksiTabungan {
        $setting = SettingTabungan::current();
        $minSetor = (float) $setting->min_setor;

        if ($rekening->status !== 'aktif') {
            throw new RuntimeException('Rekening tabungan harus berstatus aktif untuk melakukan setoran.');
        }

        if ($nominal < $minSetor) {
            throw new RuntimeException('Minimal setoran tabungan adalah Rp '.number_format($minSetor, 0, ',', '.').'.');
        }

        $tgl = Carbon::parse($tanggalTransaksi)->startOfDay();
        if ($tgl->greaterThan(now()->startOfDay())) {
            throw new RuntimeException('Tanggal transfer tidak boleh di masa depan.');
        }

        $fileMeta = $this->simpanBuktiPrivat($bukti, 'setor', $tgl);

        return DB::transaction(function () use ($rekening, $nominal, $tgl, $fileMeta, $catatan, $aktorId) {
            $rekLocked = RekeningTabungan::lockForUpdate()->findOrFail($rekening->id);
            $rekLocked->loadMissing('anggota');

            if ($rekLocked->status !== 'aktif') {
                throw new RuntimeException('Rekening tabungan tidak aktif.');
            }

            $pengajuan = PengajuanTransaksiTabungan::create([
                'rekening_tabungan_id' => $rekLocked->id,
                'anggota_id' => $rekLocked->anggota_id,
                'jenis' => 'setor',
                'kanal' => 'transfer',
                'nominal' => $nominal,
                'tanggal_transaksi' => $tgl->toDateString(),
                'bukti_path' => $fileMeta['path'],
                'bukti_nama_asli' => $fileMeta['nama_asli'],
                'bukti_mime' => $fileMeta['mime'],
                'catatan_anggota' => $catatan ? trim($catatan) : null,
                'status' => 'menunggu',
            ]);

            AuditLog::catat(
                aksi: 'tabungan_setor_diajukan',
                keterangan: "Pengajuan setoran tabungan {$rekLocked->anggota?->nama} ({$rekLocked->no_rekening}) sebesar Rp ".number_format($nominal, 0, ',', '.'),
                dataBaru: [
                    'pengajuan_id' => $pengajuan->id,
                    'rekening_tabungan_id' => $rekLocked->id,
                    'nominal' => $nominal,
                    'tanggal_transaksi' => $tgl->toDateString(),
                ],
                userId: $aktorId,
            );

            return $pengajuan;
        });
    }

    public function setujuiSetor(PengajuanTransaksiTabungan $pengajuan, ?string $catatan, int $aktorId): TabunganTransaksi
    {
        return DB::transaction(function () use ($pengajuan, $catatan, $aktorId) {
            $pengLocked = PengajuanTransaksiTabungan::lockForUpdate()->findOrFail($pengajuan->id);
            if ($pengLocked->status !== 'menunggu' || $pengLocked->jenis !== 'setor') {
                throw new RuntimeException('Pengajuan setoran ini sudah diproses atau tidak valid.');
            }

            $rekening = RekeningTabungan::lockForUpdate()->findOrFail($pengLocked->rekening_tabungan_id);
            $rekening->loadMissing('anggota');
            if ($rekening->status !== 'aktif') {
                throw new RuntimeException('Rekening tabungan tidak aktif.');
            }

            $nominal = (float) $pengLocked->nominal;
            $tanggalEfektif = now()->toDateString();
            $tanggalTransferLabel = $pengLocked->tanggal_transaksi->format('d M Y');
            $nama = $rekening->anggota?->nama ?? '-';

            $jurnal = $this->jurnalKas->catat(
                tipe: 'masuk',
                kategori: 'setor_tabungan',
                kantong: 'tabungan',
                jumlah: $nominal,
                keterangan: "Setoran tabungan {$nama} ({$rekening->no_rekening})",
                referensiId: $rekening->id,
                tanggal: $tanggalEfektif,
                userId: $aktorId,
                subJudul: "Setoran transfer tgl {$tanggalTransferLabel}",
            );

            $saldoSebelum = (float) $rekening->saldo;
            $saldoSesudah = round($saldoSebelum + $nominal, 2);

            $trx = TabunganTransaksi::create([
                'rekening_tabungan_id' => $rekening->id,
                'anggota_id' => $rekening->anggota_id,
                'no_bukti' => $jurnal->no_bukti,
                'jenis' => 'setor',
                'kanal' => $pengLocked->kanal,
                'nominal' => $nominal,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'tanggal' => $tanggalEfektif,
                'keterangan' => $pengLocked->catatan_anggota ?: "Setoran tabungan transfer ({$rekening->no_rekening})",
                'bukti_path' => $pengLocked->bukti_path,
                'bukti_nama_asli' => $pengLocked->bukti_nama_asli,
                'bukti_mime' => $pengLocked->bukti_mime,
                'jurnal_kas_id' => $jurnal->id,
                'pengajuan_id' => $pengLocked->id,
                'created_by' => $aktorId,
            ]);

            $rekening->update(['saldo' => $saldoSesudah]);

            $pengLocked->update([
                'status' => 'disetujui',
                'diproses_oleh' => $aktorId,
                'diproses_pada' => now(),
                'catatan_pengurus' => $catatan ? trim($catatan) : null,
                'transaksi_id' => $trx->id,
            ]);

            AuditLog::catat(
                aksi: 'tabungan_setor_disetujui',
                keterangan: "Setoran tabungan {$nama} ({$rekening->no_rekening}) disetujui: Rp ".number_format($nominal, 0, ',', '.')." ({$jurnal->no_bukti})",
                dataLama: ['status' => 'menunggu', 'saldo_sebelum' => $saldoSebelum],
                dataBaru: [
                    'status' => 'disetujui',
                    'pengajuan_id' => $pengLocked->id,
                    'transaksi_id' => $trx->id,
                    'jurnal_kas_id' => $jurnal->id,
                    'saldo_sesudah' => $saldoSesudah,
                ],
                userId: $aktorId,
            );

            return $trx;
        });
    }

    public function catatSetorTunai(
        RekeningTabungan $rekening,
        float $nominal,
        string $akunKas,
        string $tanggal,
        ?string $keterangan,
        ?UploadedFile $bukti,
        int $aktorId,
    ): TabunganTransaksi {
        $setting = SettingTabungan::current();
        $minSetor = (float) $setting->min_setor;

        if ($rekening->status !== 'aktif') {
            throw new RuntimeException('Rekening tabungan harus berstatus aktif untuk mencatat setoran.');
        }

        if ($nominal < $minSetor) {
            throw new RuntimeException('Minimal setoran tabungan adalah Rp '.number_format($minSetor, 0, ',', '.').'.');
        }

        if (! in_array($akunKas, ['bank', 'kas_kecil'], true)) {
            throw new RuntimeException("Akun kas '{$akunKas}' tidak valid.");
        }

        $tgl = Carbon::parse($tanggal)->startOfDay();
        $fileMeta = $bukti ? $this->simpanBuktiPrivat($bukti, 'setor', $tgl) : null;

        return DB::transaction(function () use ($rekening, $nominal, $akunKas, $tgl, $keterangan, $fileMeta, $aktorId) {
            $rekLocked = RekeningTabungan::lockForUpdate()->findOrFail($rekening->id);
            $rekLocked->loadMissing('anggota');

            if ($rekLocked->status !== 'aktif') {
                throw new RuntimeException('Rekening tabungan tidak aktif.');
            }

            $kantong = $akunKas === 'kas_kecil' ? 'kas_kecil' : 'tabungan';
            $nama = $rekLocked->anggota?->nama ?? '-';
            $ket = $keterangan ? trim($keterangan) : "Setoran tunai tabungan ({$rekLocked->no_rekening})";

            $jurnal = $this->jurnalKas->catat(
                tipe: 'masuk',
                kategori: 'setor_tabungan',
                kantong: $kantong,
                jumlah: $nominal,
                keterangan: "Setoran tunai tabungan {$nama} ({$rekLocked->no_rekening})",
                referensiId: $rekLocked->id,
                tanggal: $tgl->toDateString(),
                userId: $aktorId,
                subJudul: 'Setoran tunai oleh Bendahara',
            );

            $saldoSebelum = (float) $rekLocked->saldo;
            $saldoSesudah = round($saldoSebelum + $nominal, 2);

            $trx = TabunganTransaksi::create([
                'rekening_tabungan_id' => $rekLocked->id,
                'anggota_id' => $rekLocked->anggota_id,
                'no_bukti' => $jurnal->no_bukti,
                'jenis' => 'setor',
                'kanal' => 'tunai',
                'nominal' => $nominal,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'tanggal' => $tgl->toDateString(),
                'keterangan' => $ket,
                'bukti_path' => $fileMeta['path'] ?? null,
                'bukti_nama_asli' => $fileMeta['nama_asli'] ?? null,
                'bukti_mime' => $fileMeta['mime'] ?? null,
                'jurnal_kas_id' => $jurnal->id,
                'created_by' => $aktorId,
            ]);

            $rekLocked->update(['saldo' => $saldoSesudah]);

            AuditLog::catat(
                aksi: 'tabungan_setor_tunai',
                keterangan: "Setoran tunai tabungan {$nama} ({$rekLocked->no_rekening}) sebesar Rp ".number_format($nominal, 0, ',', '.')." via {$akunKas}",
                dataLama: ['saldo_sebelum' => $saldoSebelum],
                dataBaru: [
                    'transaksi_id' => $trx->id,
                    'jurnal_kas_id' => $jurnal->id,
                    'akun_kas' => $akunKas,
                    'saldo_sesudah' => $saldoSesudah,
                ],
                userId: $aktorId,
            );

            return $trx;
        });
    }

    public function ajukanTarik(
        RekeningTabungan $rekening,
        float $nominal,
        string $kanal,
        ?int $rekeningAnggotaId,
        bool $tutupRekening,
        ?string $catatan,
        int $aktorId,
    ): PengajuanTransaksiTabungan {
        if ($rekening->status !== 'aktif') {
            throw new RuntimeException('Rekening tabungan tidak aktif.');
        }

        if (! in_array($kanal, ['transfer', 'tunai'], true)) {
            throw new RuntimeException('Kanal pencairan harus transfer atau tunai.');
        }

        $setting = SettingTabungan::current();

        return DB::transaction(function () use ($rekening, $nominal, $kanal, $rekeningAnggotaId, $tutupRekening, $catatan, $aktorId, $setting) {
            $rekLocked = RekeningTabungan::lockForUpdate()->findOrFail($rekening->id);
            $rekLocked->loadMissing('anggota');

            if ($rekLocked->status !== 'aktif') {
                throw new RuntimeException('Rekening tabungan tidak aktif.');
            }

            $masihMenunggu = PengajuanTransaksiTabungan::where('rekening_tabungan_id', $rekLocked->id)
                ->whereIn('jenis', ['tarik', 'tutup_rekening'])
                ->where('status', 'menunggu')
                ->exists();

            if ($masihMenunggu) {
                throw new RuntimeException('Anda masih memiliki pengajuan penarikan atau penutupan rekening yang menunggu verifikasi.');
            }

            $saldoSekarang = (float) $rekLocked->saldo;
            if ($tutupRekening) {
                $jenis = 'tutup_rekening';
                $nominalFinal = $saldoSekarang;
            } else {
                $jenis = 'tarik';
                $minTarik = (float) $setting->min_tarik;
                $saldoMinimal = (float) $setting->saldo_minimal;

                if ($nominal < $minTarik) {
                    throw new RuntimeException('Minimal penarikan tabungan adalah Rp '.number_format($minTarik, 0, ',', '.').'.');
                }
                if ($nominal > $saldoSekarang) {
                    throw new RuntimeException('Saldo tabungan tidak mencukupi untuk penarikan sebesar itu.');
                }
                if (($saldoSekarang - $nominal) < $saldoMinimal) {
                    throw new RuntimeException(
                        'Sisa saldo setelah penarikan minimal Rp '.number_format($saldoMinimal, 0, ',', '.').' (atau pilih Tutup Rekening untuk mencairkan seluruh saldo).'
                    );
                }
                $nominalFinal = $nominal;
            }

            $rekeningSnapshot = null;
            if ($kanal === 'transfer') {
                $rekAnggota = RekeningAnggota::where('id', $rekeningAnggotaId)
                    ->where('anggota_id', $rekLocked->anggota_id)
                    ->first();

                if (! $rekAnggota) {
                    throw new RuntimeException('Silakan pilih rekening bank tujuan milik Anda di Profil.');
                }

                $rekeningSnapshot = [
                    'id' => $rekAnggota->id,
                    'nama_bank' => $rekAnggota->nama_bank,
                    'no_rekening' => $rekAnggota->no_rekening,
                    'atas_nama' => $rekAnggota->atas_nama,
                ];
            }

            $pengajuan = PengajuanTransaksiTabungan::create([
                'rekening_tabungan_id' => $rekLocked->id,
                'anggota_id' => $rekLocked->anggota_id,
                'jenis' => $jenis,
                'kanal' => $kanal,
                'nominal' => $nominalFinal,
                'tanggal_transaksi' => now()->toDateString(),
                'rekening_tujuan_snapshot' => $rekeningSnapshot,
                'catatan_anggota' => $catatan ? trim($catatan) : null,
                'status' => 'menunggu',
            ]);

            AuditLog::catat(
                aksi: $tutupRekening ? 'tabungan_tutup_diajukan' : 'tabungan_tarik_diajukan',
                keterangan: ($tutupRekening ? 'Pengajuan tutup rekening tabungan ' : 'Pengajuan penarikan tabungan ')
                    ."{$rekLocked->anggota?->nama} ({$rekLocked->no_rekening}) sebesar Rp ".number_format($nominalFinal, 0, ',', '.'),
                dataBaru: [
                    'pengajuan_id' => $pengajuan->id,
                    'jenis' => $jenis,
                    'kanal' => $kanal,
                    'nominal' => $nominalFinal,
                ],
                userId: $aktorId,
            );

            return $pengajuan;
        });
    }

    public function setujuiTarik(
        PengajuanTransaksiTabungan $pengajuan,
        string $akunKas,
        ?UploadedFile $buktiPencairan,
        ?string $catatan,
        int $aktorId,
    ): ?TabunganTransaksi {
        if (! in_array($akunKas, ['bank', 'kas_kecil'], true)) {
            throw new RuntimeException("Akun kas '{$akunKas}' tidak valid.");
        }

        $sekarang = now();
        $fileMeta = $buktiPencairan ? $this->simpanBuktiPrivat($buktiPencairan, 'pencairan', $sekarang) : null;

        return DB::transaction(function () use ($pengajuan, $akunKas, $fileMeta, $catatan, $aktorId, $sekarang) {
            $pengLocked = PengajuanTransaksiTabungan::lockForUpdate()->findOrFail($pengajuan->id);
            if ($pengLocked->status !== 'menunggu' || ! in_array($pengLocked->jenis, ['tarik', 'tutup_rekening'], true)) {
                throw new RuntimeException('Pengajuan penarikan ini sudah diproses atau tidak valid.');
            }

            $rekening = RekeningTabungan::lockForUpdate()->findOrFail($pengLocked->rekening_tabungan_id);
            $rekening->loadMissing('anggota');

            if ($rekening->status !== 'aktif') {
                throw new RuntimeException('Rekening tabungan tidak aktif.');
            }

            $isTutup = $pengLocked->jenis === 'tutup_rekening';
            $tanggalCair = $sekarang->toDateString();
            $nama = $rekening->anggota?->nama ?? '-';

            if ($isTutup) {
                $this->bungaService->kreditkanBungaPenutupan($rekening, $sekarang, $aktorId);
                $rekening->refresh();
                $nominalCair = (float) $rekening->saldo;
            } else {
                $nominalCair = (float) $pengLocked->nominal;
                $setting = SettingTabungan::current();
                $saldoSekarang = (float) $rekening->saldo;
                if ($nominalCair > $saldoSekarang) {
                    throw new RuntimeException('Saldo tabungan anggota tidak lagi mencukupi.');
                }
                if (($saldoSekarang - $nominalCair) < (float) $setting->saldo_minimal) {
                    throw new RuntimeException('Sisa saldo tidak boleh di bawah saldo minimal mengendap.');
                }
            }

            $trx = null;
            $saldoSebelum = (float) $rekening->saldo;
            $saldoSesudah = round($saldoSebelum - $nominalCair, 2);

            if ($nominalCair > 0) {
                $kantong = $akunKas === 'kas_kecil' ? 'kas_kecil' : 'tabungan';
                $labelAksi = $isTutup ? 'Penutupan rekening tabungan' : 'Penarikan tabungan';

                $jurnal = $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'tarik_tabungan',
                    kantong: $kantong,
                    jumlah: $nominalCair,
                    keterangan: "{$labelAksi} {$nama} ({$rekening->no_rekening})",
                    referensiId: $rekening->id,
                    tanggal: $tanggalCair,
                    userId: $aktorId,
                    subJudul: $pengLocked->kanal === 'transfer' ? 'Transfer ke rekening anggota' : 'Pencairan tunai',
                );

                $trx = TabunganTransaksi::create([
                    'rekening_tabungan_id' => $rekening->id,
                    'anggota_id' => $rekening->anggota_id,
                    'no_bukti' => $jurnal->no_bukti,
                    'jenis' => $isTutup ? 'tutup_rekening' : 'tarik',
                    'kanal' => $pengLocked->kanal,
                    'nominal' => $nominalCair,
                    'saldo_sebelum' => $saldoSebelum,
                    'saldo_sesudah' => $saldoSesudah,
                    'tanggal' => $tanggalCair,
                    'keterangan' => $pengLocked->catatan_anggota ?: "{$labelAksi} ({$rekening->no_rekening})",
                    'bukti_path' => $fileMeta['path'] ?? null,
                    'bukti_nama_asli' => $fileMeta['nama_asli'] ?? null,
                    'bukti_mime' => $fileMeta['mime'] ?? null,
                    'jurnal_kas_id' => $jurnal->id,
                    'pengajuan_id' => $pengLocked->id,
                    'created_by' => $aktorId,
                ]);
            }

            $updateRek = ['saldo' => $saldoSesudah];
            if ($isTutup) {
                $updateRek['status'] = 'ditutup';
                $updateRek['tanggal_tutup'] = $tanggalCair;
            }
            $rekening->update($updateRek);

            $pengLocked->update([
                'status' => 'disetujui',
                'nominal' => $nominalCair,
                'diproses_oleh' => $aktorId,
                'diproses_pada' => now(),
                'catatan_pengurus' => $catatan ? trim($catatan) : null,
                'bukti_pencairan_path' => $fileMeta['path'] ?? null,
                'bukti_pencairan_nama_asli' => $fileMeta['nama_asli'] ?? null,
                'bukti_pencairan_mime' => $fileMeta['mime'] ?? null,
                'transaksi_id' => $trx?->id,
            ]);

            AuditLog::catat(
                aksi: $isTutup ? 'tabungan_rekening_ditutup' : 'tabungan_tarik_disetujui',
                keterangan: ($isTutup ? 'Penutupan rekening tabungan ' : 'Penarikan tabungan ')
                    ."{$nama} ({$rekening->no_rekening}) sebesar Rp ".number_format($nominalCair, 0, ',', '.')." via {$akunKas}",
                dataLama: ['status' => 'menunggu', 'saldo_sebelum' => $saldoSebelum],
                dataBaru: [
                    'status' => 'disetujui',
                    'pengajuan_id' => $pengLocked->id,
                    'transaksi_id' => $trx?->id,
                    'nominal_cair' => $nominalCair,
                    'saldo_sesudah' => $saldoSesudah,
                ],
                userId: $aktorId,
            );

            return $trx;
        });
    }

    public function tolakPengajuan(PengajuanTransaksiTabungan $pengajuan, string $catatan, int $aktorId): PengajuanTransaksiTabungan
    {
        $catatan = trim($catatan);
        if (mb_strlen($catatan) < 3) {
            throw new RuntimeException('Alasan penolakan minimal 3 karakter.');
        }

        return DB::transaction(function () use ($pengajuan, $catatan, $aktorId) {
            $pengLocked = PengajuanTransaksiTabungan::lockForUpdate()->findOrFail($pengajuan->id);
            $pengLocked->loadMissing('anggota', 'rekening');

            if ($pengLocked->status !== 'menunggu') {
                throw new RuntimeException('Pengajuan ini sudah diproses.');
            }

            $pengLocked->update([
                'status' => 'ditolak',
                'diproses_oleh' => $aktorId,
                'diproses_pada' => now(),
                'catatan_pengurus' => $catatan,
            ]);

            AuditLog::catat(
                aksi: 'tabungan_transaksi_ditolak',
                keterangan: "Pengajuan {$pengLocked->jenis} tabungan {$pengLocked->anggota?->nama} ({$pengLocked->rekening?->no_rekening}) ditolak: {$catatan}",
                dataLama: ['status' => 'menunggu'],
                dataBaru: [
                    'status' => 'ditolak',
                    'pengajuan_id' => $pengLocked->id,
                    'catatan_pengurus' => $catatan,
                ],
                userId: $aktorId,
            );

            return $pengLocked->refresh();
        });
    }

    public function batalkanTransaksi(TabunganTransaksi $transaksi, string $alasan, int $aktorId): TabunganTransaksi
    {
        $alasan = trim($alasan);
        if (mb_strlen($alasan) < 3) {
            throw new RuntimeException('Alasan pembatalan minimal 3 karakter.');
        }

        return DB::transaction(function () use ($transaksi, $alasan, $aktorId) {
            $trx = TabunganTransaksi::lockForUpdate()->findOrFail($transaksi->id);
            $rekening = RekeningTabungan::lockForUpdate()->findOrFail($trx->rekening_tabungan_id);
            $rekening->loadMissing('anggota');

            if ($trx->dibatalkan_pada !== null) {
                throw new RuntimeException('Transaksi tabungan ini sudah pernah dibatalkan.');
            }

            if ($trx->koreksi_dari_id !== null) {
                throw new RuntimeException('Transaksi pembalik (storno) tidak dapat dibatalkan lagi.');
            }

            if (! in_array($trx->jenis, ['setor', 'tarik'], true)) {
                throw new RuntimeException('Hanya transaksi setor atau tarik yang dapat dibatalkan dari menu ini.');
            }

            if ($rekening->status !== 'aktif') {
                throw new RuntimeException('Transaksi pada rekening yang sudah ditutup tidak dapat dibatalkan.');
            }

            $nominal = (float) $trx->nominal;
            $saldoSebelum = (float) $rekening->saldo;

            if ($trx->jenis === 'setor') {
                if ($saldoSebelum < $nominal) {
                    throw new RuntimeException('Saldo tabungan anggota tidak mencukupi untuk membatalkan setoran ini.');
                }
                $jenisPembalik = 'koreksi_keluar';
                $saldoSesudah = round($saldoSebelum - $nominal, 2);
            } else {
                $jenisPembalik = 'koreksi_masuk';
                $saldoSesudah = round($saldoSebelum + $nominal, 2);
            }

            $jurnalPembalik = null;
            if ($trx->jurnal_kas_id) {
                $jurnalAsal = $trx->jurnalKas()->lockForUpdate()->first();
                if ($jurnalAsal) {
                    $jurnalPembalik = $this->jurnalKas->batalkanBarisInternal($jurnalAsal, $alasan, $aktorId);
                }
            }

            $pembalik = TabunganTransaksi::create([
                'rekening_tabungan_id' => $rekening->id,
                'anggota_id' => $rekening->anggota_id,
                'no_bukti' => $jurnalPembalik?->no_bukti ?? ($trx->no_bukti.'-REV'),
                'jenis' => $jenisPembalik,
                'kanal' => $trx->kanal,
                'nominal' => $nominal,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'tanggal' => $trx->tanggal->toDateString(),
                'keterangan' => "[PEMBALIK {$trx->no_bukti}] {$alasan}",
                'jurnal_kas_id' => $jurnalPembalik?->id,
                'koreksi_dari_id' => $trx->id,
                'created_by' => $aktorId,
            ]);

            $trx->update([
                'dibatalkan_pada' => now(),
                'dibatalkan_oleh' => $aktorId,
                'alasan_batal' => $alasan,
            ]);

            $rekening->update(['saldo' => $saldoSesudah]);

            AuditLog::catat(
                aksi: 'tabungan_transaksi_dibatalkan',
                keterangan: "Pembatalan transaksi tabungan {$trx->no_bukti} ({$rekening->anggota?->nama}): {$alasan}",
                dataLama: [
                    'transaksi_id' => $trx->id,
                    'no_bukti' => $trx->no_bukti,
                    'saldo_sebelum' => $saldoSebelum,
                ],
                dataBaru: [
                    'pembalik_id' => $pembalik->id,
                    'no_bukti_pembalik' => $pembalik->no_bukti,
                    'saldo_sesudah' => $saldoSesudah,
                    'alasan' => $alasan,
                ],
                userId: $aktorId,
            );

            return $pembalik;
        });
    }

    /**
     * @return array{path: string, nama_asli: string, mime: string}
     */
    private function simpanBuktiPrivat(UploadedFile $file, string $sub, Carbon $tanggal): array
    {
        $folder = sprintf('tabungan/bukti-%s/%04d/%02d', $sub, $tanggal->year, $tanggal->month);
        $path = Storage::disk('local')->putFile($folder, $file);
        if (! $path) {
            throw new RuntimeException('Gagal menyimpan file bukti ke storage.');
        }

        return [
            'path' => $path,
            'nama_asli' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType() ?: 'application/octet-stream',
        ];
    }
}
