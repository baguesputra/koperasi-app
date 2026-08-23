<?php

namespace App\Notifications;

class LoanDisbursed extends BaseLoanNotification
{
    public function toBaileys($notifiable): string
    {
        $p = $this->pinjaman;
        $anggota = $p->anggota;

        $tanggalCair = $p->tanggal_cair ? $p->tanggal_cair->format('d M Y') : now()->format('d M Y');

        return implode("\n", [
            "Halo {$anggota->nama} ({$anggota->no_anggota}),",
            '',
            '💰 *Pinjaman Telah Dicairkan*',
            '',
            "💰 Nominal: {$this->formatNominal($p->nominal)}",
            "📅 Tenor: {$p->tenor_bulan} bulan",
            "📝 Keperluan: {$p->keperluan}",
            "📅 Tanggal Pengajuan: {$p->tanggal_pengajuan->format('d M Y')}",
            "💵 Tanggal Cair: {$tanggalCair}",
            '',
            '📋 *Status: Aktif - Dana Telah Dikirim*',
            '',
            'Dana pinjaman telah berhasil dicairkan ke rekening Anda. Mohon periksa rekening tujuan.',
            'Jadwal angsuran akan mulai berlaku sesuai ketentuan.',
            '',
            "🔗 Detail & Jadwal: {$this->getDashboardUrl()}",
            '',
            '— Koperasi',
        ]);
    }
}
