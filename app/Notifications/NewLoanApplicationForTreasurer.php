<?php

namespace App\Notifications;

class NewLoanApplicationForTreasurer extends BaseLoanNotification
{
    public function toBaileys($notifiable): string
    {
        $p = $this->pinjaman;
        $anggota = $p->anggota;

        return implode("\n", [
            "Halo {$notifiable->name},",
            '',
            '🔔 *Pengajuan Pinjaman Baru*',
            '',
            "👤 Anggota: {$anggota->nama} ({$anggota->no_anggota})",
            "🏢 Cabang: {$anggota->cabang}",
            "💰 Nominal: {$this->formatNominal($p->nominal)}",
            "📅 Tenor: {$p->tenor_bulan} bulan",
            "📝 Keperluan: {$p->keperluan}",
            "📅 Tanggal: {$p->tanggal_pengajuan->format('d M Y H:i')}",
            '',
            '⏳ *Mohon ditinjau segera*',
            '',
            "🔗 Review: {$this->getDashboardUrl()}",
            '',
            '— Sistem Koperasi',
        ]);
    }
}
