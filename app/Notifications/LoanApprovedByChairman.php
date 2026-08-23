<?php

namespace App\Notifications;

class LoanApprovedByChairman extends BaseLoanNotification
{
    public function toBaileys($notifiable): string
    {
        $p = $this->pinjaman;
        $anggota = $p->anggota;

        $catatan = $this->catatan ? "\n📝 Catatan Ketua: {$this->catatan}" : '';

        return implode("\n", [
            "Halo {$anggota->nama} ({$anggota->no_anggota}),",
            '',
            '🎉 *Pengajuan Pinjaman Disetujui Ketua Koperasi*',
            '',
            "💰 Nominal: {$this->formatNominal($p->nominal)}",
            "📅 Tenor: {$p->tenor_bulan} bulan",
            "📝 Keperluan: {$p->keperluan}",
            "📅 Tanggal: {$p->tanggal_pengajuan->format('d M Y')}",
            $catatan,
            '',
            '📋 *Status: Disetujui - Menunggu Pencairan Dana*',
            '',
            'Pengajuan Anda telah disetujui sepenuhnya. Dana akan dicairkan oleh Bendahara sesuai proses.',
            '',
            "🔗 Detail: {$this->getDashboardUrl()}",
            '',
            '— Koperasi',
        ]);
    }
}
