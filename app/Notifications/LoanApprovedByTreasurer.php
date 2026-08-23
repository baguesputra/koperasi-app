<?php

namespace App\Notifications;

class LoanApprovedByTreasurer extends BaseLoanNotification
{
    public function toBaileys($notifiable): string
    {
        $p = $this->pinjaman;
        $anggota = $p->anggota;

        $catatan = $this->catatan ? "\n📝 Catatan Bendahara: {$this->catatan}" : '';

        return implode("\n", [
            "Halo {$anggota->nama} ({$anggota->no_anggota}),",
            '',
            '✅ *Pengajuan Pinjaman Disetujui Bendahara*',
            '',
            "💰 Nominal: {$this->formatNominal($p->nominal)}",
            "📅 Tenor: {$p->tenor_bulan} bulan",
            "📝 Keperluan: {$p->keperluan}",
            "📅 Tanggal: {$p->tanggal_pengajuan->format('d M Y')}",
            $catatan,
            '',
            '📋 *Status: Menunggu Approval Ketua Koperasi*',
            '',
            'Pengajuan Anda telah disetujui oleh Bendahara dan kini menunggu persetujuan Ketua Koperasi.',
            '',
            "🔗 Detail: {$this->getDashboardUrl()}",
            '',
            '— Koperasi',
        ]);
    }
}
