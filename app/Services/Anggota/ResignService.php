<?php

namespace App\Services\Anggota;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Dokumen\PenomoranDokumenService;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Wa\WaPesan;
use App\Services\Wa\WaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class ResignService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    /**
     * Ringkasan data untuk preview modal konfirmasi.
     */
    public function ringkasan(Anggota $anggota): array
    {
        return $anggota->ringkasanResign();
    }

    /**
     * Eksekusi proses resign dalam 1 DB transaction.
     *
     * Dua jalur:
     *   A. Simpanan cukup → resign final langsung (status=resign, user nonaktif).
     *   B. Simpanan kurang → resign_menunggu: seluruh simpanan dialokasikan ke
     *      angsuran terawal, sisa tagihan menjadi SATU cicilan akhir terjadwal
     *      (jatuh tempo = akhir bulan tanggal_resign). Status final diproses
     *      saat cicilan akhir dilunasi via Konfirmasi Angsuran
     *      (lihat finalisasiMenunggu()).
     */
    public function proses(Anggota $anggota, string $alasan, string $tanggalResign, User $aktor): void
    {
        DB::transaction(function () use ($anggota, $alasan, $tanggalResign, $aktor) {
            $anggotaLocked = Anggota::lockForUpdate()->findOrFail($anggota->id);

            if ($anggotaLocked->status !== 'aktif') {
                throw new RuntimeException(
                    "Anggota {$anggotaLocked->nama} berstatus '{$anggotaLocked->status}', tidak bisa di-resign."
                );
            }

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

            if ($totalSimpananKembali < $totalTagihan) {
                $this->prosesMenunggu($anggotaLocked, $alasan, $tanggalResign, $aktor, [
                    'simpanan_pokok' => $simpananPokok,
                    'simpanan_wajib' => $simpananWajib,
                    'dana_sosial' => $danaSosial,
                    'total_simpanan' => $totalSimpananKembali,
                    'total_tagihan' => $totalTagihan,
                    'shortfall' => $totalTagihan - $totalSimpananKembali,
                ]);

                return;
            }

            $dataLama = [
                'status' => $anggotaLocked->status,
                'simpanan_pokok' => $simpananPokok,
                'simpanan_wajib' => $simpananWajib,
                'dana_sosial' => $danaSosial,
                'sisa_tagihan_pinjaman' => $totalTagihan,
            ];

            // Pelunasan angsuran dari offset simpanan: tiap cicilan lunas dicatat
            // 1 jurnal audit (NON_FISIK, tanpa gerak uang — pinjaman di-offset
            // simpanan anggota). Uang fisik hanya bergerak saat sisa simpanan
            // dikembalikan ke anggota (bank berkurang).
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

            // Step 6: Kembalikan sisa simpanan ke anggota (keluar dari bank).
            $alokasiPokok = min($simpananPokok, $totalPelunasan);
            $alokasiWajib = max(0, $totalPelunasan - $simpananPokok);
            $kembaliPokok = max(0, $simpananPokok - $alokasiPokok);
            $kembaliWajib = max(0, $simpananWajib - $alokasiWajib);

            if ($kembaliPokok > 0) {
                $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'return_simpanan_pokok',
                    kantong: 'pengembalian_simpanan',
                    jumlah: $kembaliPokok,
                    keterangan: "Pengembalian simpanan pokok resign - {$anggotaLocked->nama}",
                    referensiId: $anggotaLocked->id,
                    tanggal: $tanggalResign,
                    userId: $aktor->id,
                    subJudul: 'Pengembalian ke anggota',
                );
            }

            if ($kembaliWajib > 0) {
                $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'return_simpanan_wajib',
                    kantong: 'pengembalian_simpanan',
                    jumlah: $kembaliWajib,
                    keterangan: "Pengembalian simpanan wajib resign - {$anggotaLocked->nama}",
                    referensiId: $anggotaLocked->id,
                    tanggal: $tanggalResign,
                    userId: $aktor->id,
                    subJudul: 'Pengembalian ke anggota',
                );
            }

            // Snapshot settlement untuk audit & reprint PDF.
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
                'total_dikembalikan' => $kembaliPokok + $kembaliWajib,
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
            ]);

            if ($anggotaLocked->user_id) {
                User::where('id', $anggotaLocked->user_id)->update(['status' => 'nonaktif']);
            }

            AuditLog::catat(
                aksi: 'anggota_resign',
                keterangan: "Resign anggota {$anggotaLocked->nama} ({$anggotaLocked->no_karyawan}). ".
                    'Pelunasan: Rp '.number_format($totalPelunasan, 0, ',', '.').
                    ', pengembalian simpanan: Rp '.number_format($kembaliPokok + $kembaliWajib, 0, ',', '.').
                    ', dana_sosial hangus: Rp '.number_format($danaSosial, 0, ',', '.').
                    ". Alasan: {$alasan}",
                dataLama: $dataLama,
                dataBaru: [
                    'status' => 'resign',
                    'tanggal_resign' => $tanggalResign,
                    'alasan_resign' => $alasan,
                    'resigned_by' => $aktor->no_karyawan ?? $aktor->name,
                    'settlement' => $settlement,
                ]
            );
        });
    }

    /**
     * Jalur B: simpanan tidak cukup. Seluruh simpanan dialokasikan ke
     * angsuran terawal (tertua), sisa tagihan menjadi SATU cicilan akhir
     * terjadwal jatuh tempo akhir bulan tanggal_resign. Status anggota
     * menjadi resign_menunggu; user tetap aktif agar portal + WA jalan.
     */
    private function prosesMenunggu(
        Anggota $anggotaLocked,
        string $alasan,
        string $tanggalResign,
        User $aktor,
        array $snap
    ): void {
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
            throw new RuntimeException('Perhitungan shortfall tidak valid. Hubungi admin.');
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
            'total_dikembalikan' => 0,
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
                'tanggal_resign' => $tanggalResign,
                'settlement' => $settlement,
            ]
        );

        $this->kirimWaMenunggu($anggotaLocked->refresh(), $settlement, $jatuhTempo);
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
            subJudul: 'Pelunasan dari uang simpanan anggota (offset, tanpa gerak kas)',
        );
    }

    /**
     * Catat 1 transaksi pelunasan angsuran saat resign sebagai 1 jurnal audit
     * (NON_FISIK): MASUK kantong:pinjaman, sub: "Pelunasan dari uang simpanan
     * anggota". Tidak menggerakkan kas — dananya di-offset dari simpanan.
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
            subJudul: 'Pelunasan dari uang simpanan anggota (offset, tanpa gerak kas)',
        );
    }
}
