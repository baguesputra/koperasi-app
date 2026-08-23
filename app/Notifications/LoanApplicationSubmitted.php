<?php

namespace App\Notifications;

class LoanApplicationSubmitted extends BaseLoanNotification
{
    public function toBaileys($notifiable): string
    {
        $p = $this->pinjaman;
        $anggota = $p->anggota;

        return implode("\n", [
            "Halo {$anggota->nama} ({$anggota->no_anggota}),",
            '',
            '✅ *Pengajuan Pinjaman Diterima*',
            '',
            "💰 Nominal: {$this->formatNominal($p->nominal)}",
            "📅 Tenor: {$p->tenor_bulan} bulan",
            "📝 Keperluan: {$p->keperluan}",
            "📅 Tanggal: {$p->tanggal_pengajuan->format('d M Y')}",
            '',
            '📋 *Status: Sedang ditinjau oleh Bendahara*',
            '',
            'Silakan menunggu proses review. Anda akan mendapat notifikasi lagi setelah ada keputusan.',
            '',
            "🔗 Detail: {$this->getDashboardUrl()}",
            '',
            '— Koperasi',
        ]);
    }
}
