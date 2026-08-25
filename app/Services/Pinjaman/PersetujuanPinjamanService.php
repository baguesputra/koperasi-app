<?php

namespace App\Services\Pinjaman;

use App\Helpers\TerbilangHelper;
use App\Models\Pinjaman;
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
    ) {}

    public function approveBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'approved_bendahara',
            'catatan_bendahara' => $catatan,
        ]);

        $isi = "Pengajuan pinjaman Anda telah kami informasikan dengan rincian:\n\n"
            ."- Nomor Referensi: #{$pinjaman->id}\n"
            .'- Nominal: '.WaPesan::rupiah($pinjaman->nominal)."\n"
            ."- Tenor: {$pinjaman->tenor_bulan} bulan\n\n"
            .'Status saat ini: *Disetujui Bendahara* dan sedang menunggu persetujuan Ketua.'
            .' Pemberitahuan selanjutnya akan kami sampaikan melalui WhatsApp ini.';

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_disetujui_bendahara',
            WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_anggota, $isi)
        );
    }

    public function rejectBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_bendahara' => $catatan,
        ]);

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_ditolak',
            $this->pesanDitolak('Bendahara', $pinjaman, $catatan)
        );
    }

    public function approveKetua(Pinjaman $pinjaman, string $catatan): void
    {
        DB::transaction(function () use ($pinjaman, $catatan) {
            $pinjaman->update([
                'status' => 'aktif',
                'catatan_ketua' => $catatan,
                'tanggal_pencairan' => now(),
            ]);

            $this->bunga->simpanJadwal($pinjaman);

            // Validasi saldo cukup otomatis ditangani JurnalKasService (lempar exception kalau kurang)
            $this->jurnalKas->catat(
                tipe: 'keluar',
                kategori: 'pencairan_pinjaman',
                kantong: 'pinjaman',
                jumlah: (float) $pinjaman->nominal,
                keterangan: "Pencairan pinjaman - {$pinjaman->anggota->nama}",
                referensiId: $pinjaman->id,
                tanggal: now()->format('Y-m-d'),
                userId: auth()->id(),
            );
        });

        $pinjaman->refresh();

        $isi = "Dengan hormat,\n\nSelamat! Pengajuan pinjaman Anda telah *DISETUJUI* oleh Ketua dan dana telah dicairkan pada "
            .now()->translatedFormat('d F Y').".\n\nRincian pencairan:\n"
            ."- Nomor Referensi: #{$pinjaman->id}\n"
            .'- Nominal: '.WaPesan::rupiah($pinjaman->nominal).' ('.TerbilangHelper::angkaKeTerbilang($pinjaman->nominal).")\n"
            ."- Tenor: {$pinjaman->tenor_bulan} bulan\n"
            .'- Bunga: '.$pinjaman->persentase_bunga."% per bulan (metode menurun)\n"
            .'- Rekening tujuan: '.$pinjaman->snapshot_bank.' '.$pinjaman->snapshot_no_rekening.' a.n. '.$pinjaman->snapshot_atas_nama."\n\n"
            ."Dokumen *Bukti Peminjaman* terlampir pada pesan ini, memuat rincian jadwal angsuran Anda. Mohon lakukan pembayaran angsuran sesuai tanggal jatuh tempo yang tercantum.\n\n"
            .'Terima kasih atas kepercayaan Anda.';

        WaService::keAnggotaDokumen(
            $pinjaman->anggota,
            'pinjaman_disetujui_ketua',
            WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_anggota, $isi),
            Pdf::loadView('wa.bukti-pinjaman', $pinjaman->dataBukti())->output(),
            "Bukti-Peminjaman-{$pinjaman->id}.pdf",
        );
    }

    public function rejectKetua(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);

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
            .'- Nominal: '.WaPesan::rupiah($pinjaman->nominal)."\n"
            ."- Tenor: {$pinjaman->tenor_bulan} bulan\n\n"
            ."telah *DITOLAK* oleh {$oleh}."
            .($catatan ? "\n\nCatatan: {$catatan}" : '')
            ."\n\nApabila terdapat pertanyaan lebih lanjut, silakan menghubungi pengurus atau Bendahara Koperasi.";

        return WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_anggota, $isi);
    }
}
