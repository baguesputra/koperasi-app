<?php

namespace App\Notifications;

class LoanRejected extends BaseLoanNotification
{
    public function toBaileys($notifiable): string
    {
        $p = $this->pinjaman;
        $anggota = $p->anggota;

        $catatan = $this->catatan ? "\n📝 Alasan: {$this->catatan}" : '';

        return implode("\n", [
            "Halo {$anggota->nama} ({$anggota->no_anggota}),",
            '',
            '❌ *Pengajuan Pinjaman Ditolak*',
            '',
            "💰 Nominal: {$this->formatNominal($p->nominal)}",
            "📅 Tenor: {$p->tenor_bulan} bulan",
            "📝 Keperluan: {$p->keperluan}",
            "📅 Tanggal: {$p->tanggal_pengajuan->format('d M Y')}",
            $catatan,
            '',
            '📋 *Status: Ditolak*',
            '',
            'Mohon maaf, pengajuan pinjaman Anda tidak dapat diproses saat ini. Silakan hubungi Bendahara untuk informasi lebih lanjut.',
            '',
            "🔗 Detail: {$this->getDashboardUrl()}",
            '',
            '— Koperasi',
        ]);
    }
}
