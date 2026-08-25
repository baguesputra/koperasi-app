{{-- Bukti Peminjaman untuk lampiran WhatsApp (dompdf) --}}
@php
    $rupiah = fn ($n) => 'Rp '.number_format((float) ($n ?? 0), 0, ',', '.');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1e293b; font-size: 11px; }
        .kop { text-align: center; border-bottom: 1px solid #cbd5e1; padding-bottom: 12px; margin-bottom: 16px; }
        .kop h2 { margin: 0; font-size: 18px; }
        .kop p { margin: 3px 0 0; color: #64748b; }
        .kop small { color: #94a3b8; }
        h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #334155; margin: 14px 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        table.data td { padding: 2px 0; vertical-align: top; }
        table.data td:first-child { width: 45%; color: #475569; }
        table.data td:last-child { text-align: right; font-weight: bold; }
        .rekening { background: #f8fafc; padding: 8px 10px; border-radius: 6px; }
        .rekening p { margin: 0; }
        table.jadwal th, table.jadwal td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; }
        table.jadwal th { text-align: left; font-size: 10px; color: #475569; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; }
        table.jadwal .kanan { text-align: right; }
        table.jadwal tfoot td { border-top: 2px solid #cbd5e1; font-weight: bold; }
        .catatan { font-size: 10px; font-style: italic; color: #64748b; margin-top: 14px; }
        .ttd { width: 100%; margin-top: 24px; }
        .ttd td { text-align: center; width: 50%; }
        .ttd .garis { margin-top: 56px; border-top: 1px solid #cbd5e1; padding-top: 4px; font-weight: bold; display: inline-block; min-width: 160px; }
        footer { text-align: center; font-size: 9px; color: #94a3b8; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="kop">
        <h2>KOPERASI KARYAWAN</h2>
        <p>Bukti Peminjaman</p>
        <small>Nomor: BUKTI-PJM/{{ $pinjaman['anggota']['no_anggota'] }}/{{ $pinjaman['id'] }}</small>
    </div>

    <h3>Data Anggota</h3>
    <table class="data">
        <tr><td>No. Anggota</td><td>{{ $pinjaman['anggota']['no_anggota'] }}</td></tr>
        <tr><td>No. Karyawan</td><td>{{ $pinjaman['anggota']['no_karyawan'] }}</td></tr>
        <tr><td>Nama</td><td>{{ $pinjaman['anggota']['nama'] }}</td></tr>
        <tr><td>Cabang</td><td>{{ $pinjaman['anggota']['cabang'] }}</td></tr>
        <tr><td>Unit Bisnis</td><td>{{ $pinjaman['anggota']['unit_bisnis'] }}</td></tr>
        <tr><td>Jabatan</td><td>{{ $pinjaman['anggota']['jabatan'] }}</td></tr>
    </table>

    <h3>Detail Pinjaman</h3>
    <table class="data">
        <tr><td>Nominal Pinjaman</td><td>{{ $rupiah($pinjaman['nominal']) }}</td></tr>
        <tr><td>Terbilang</td><td style="font-style: italic">{{ $pinjaman['terbilang'] }}</td></tr>
        <tr><td>Tenor</td><td>{{ $pinjaman['tenor_bulan'] }} bulan</td></tr>
        <tr><td>Bunga</td><td>{{ $pinjaman['persentase_bunga'] }}% / bulan (menurun)</td></tr>
        <tr><td>Tanggal Pengajuan</td><td>{{ $pinjaman['tanggal_pengajuan'] }}</td></tr>
        <tr><td>Tanggal Cair</td><td>{{ $pinjaman['tanggal_cair'] }}</td></tr>
        <tr><td>Keperluan</td><td>{{ $pinjaman['keperluan'] ?: '-' }}</td></tr>
    </table>

    <h3>Rekening Tujuan Pencairan</h3>
    <div class="rekening">
        <p><strong>{{ $pinjaman['rekening']['bank'] }}</strong></p>
        <p>{{ $pinjaman['rekening']['no_rekening'] }}</p>
        <p style="color: #64748b">a.n. {{ $pinjaman['rekening']['atas_nama'] }}</p>
    </div>

    <h3>Jadwal Angsuran</h3>
    <table class="jadwal">
        <thead>
            <tr>
                <th>#</th>
                <th>Jatuh Tempo</th>
                <th class="kanan">Pokok</th>
                <th class="kanan">Bunga</th>
                <th class="kanan">Total Bayar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($angsuran as $a)
                <tr>
                    <td>{{ $a['cicilan_ke'] }}</td>
                    <td>{{ $a['tanggal_jatuh_tempo'] }}</td>
                    <td class="kanan">{{ $rupiah($a['nominal_pokok']) }}</td>
                    <td class="kanan">{{ $rupiah($a['nominal_bunga']) }}</td>
                    <td class="kanan"><strong>{{ $rupiah($a['total_bayar']) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>TOTAL</strong></td>
                <td class="kanan">{{ $rupiah($totals['pokok']) }}</td>
                <td class="kanan">{{ $rupiah($totals['bunga']) }}</td>
                <td class="kanan">{{ $rupiah($totals['angsuran']) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="catatan">
        Catatan: Angsuran dibayar setiap tanggal jatuh tempo. Keterlambatan akan dikenai kebijakan internal koperasi.
        Peminjaman ini dilindungi oleh aturan simpan &amp; pinjam koperasi yang berlaku.
    </p>

    <table class="ttd">
        <tr>
            <td>Anggota<br><span class="garis">{{ $pinjaman['anggota']['nama'] }}</span></td>
            <td>Bendahara<br><span class="garis">&nbsp;</span></td>
        </tr>
    </table>

    <footer>Dokumen ini dicetak otomatis oleh sistem pada {{ now()->translatedFormat('d F Y') }}</footer>
</body>
</html>
