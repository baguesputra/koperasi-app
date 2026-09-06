<?php
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

function formatRupiah($n) {
    return 'Rp ' . number_format(floatval($n ?? 0), 0, ',', '.');
}

$logoPath = public_path('images/logo.png');
$logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

$anggota = $pinjaman['anggota'];
$rek = $pinjaman['rekening'] ?? [];

$ketuaNama = $pinjaman['disetujui_oleh_ketua_nama'] ?? 'Ketua Koperasi';

$docNo = 'BUKTI-PJM/' . $anggota['no_anggota'] . '/' . $pinjaman['id'];
$tglCetak = date('d F Y');

// Generate QR Code
$verificationUrl = $pinjaman['verification_url'] ?? route('verifikasi.bukti', ['pinjaman' => $pinjaman['id']]);
try {
    $qrCode = new QrCode(
        $verificationUrl,
        new Encoding('UTF-8'),
        ErrorCorrectionLevel::Medium,
        60,  // size 60 directly
        0
    );
    $writer = new SvgWriter();
    $qrSvg = $writer->write($qrCode)->getString();
    // Ensure clean width/height for Dompdf (replace any existing)
    $qrSvg = preg_replace('/width\s*=\s*"[^"]*"/', 'width="60"', $qrSvg);
    $qrSvg = preg_replace('/height\s*=\s*"[^"]*"/', 'height="60"', $qrSvg);
    // Remove any px units
    $qrSvg = preg_replace('/width\s*=\s*"(\d+)px"/', 'width="$1"', $qrSvg);
    $qrSvg = preg_replace('/height\s*=\s*"(\d+)px"/', 'height="$1"', $qrSvg);
} catch (\Throwable $e) {
    $qrSvg = '<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><text x="5" y="20" font-size="8">QR</text></svg>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bukti Peminjaman - {{ $pinjaman['anggota']['nama'] }}</title>
    <style>
        @page {
            margin: 2cm 2cm;
            @bottom-center {
                content: "Halaman " counter(page) " dari " counter(pages);
                font-size: 8pt;
                color: #4b5563;
                font-family: 'Times New Roman', Times, serif;
            }
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            background: white;
            color: #000;
            font-size: 12pt;
            line-height: 1.5;
        }
        .container {
            max-width: 100%;
        }
        /* ====== HEADER ====== */
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .header-row {
            display: table-row;
        }
        .header-logo {
            display: table-cell;
            vertical-align: middle;
            width: 80px;
            padding-right: 18px;
        }
        .header-logo img,
        .header-logo .logo-fallback {
            width: 72px;
            height: 72px;
        }
        .header-logo .logo-fallback {
            background: #000;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 13pt;
            font-family: 'Times New Roman', Times, serif;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .coop-name {
            font-family: 'Times New Roman', Times, serif;
            font-size: 18pt;
            font-weight: 700;
            color: #000;
            margin: 0 0 2px 0;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }
        .coop-tagline {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            color: #4b5563;
            margin: 0 0 8px 0;
            font-style: italic;
            letter-spacing: 0.4px;
        }
        .doc-title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14pt;
            font-weight: 700;
            color: #000;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            text-decoration: underline;
        }
        .doc-number {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            color: #000;
            margin: 0;
            font-weight: 400;
            font-style: italic;
        }
        /* ====== SECTION ====== */
        .section {
            margin-bottom: 18px;
        }
        .section-title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            background: #f0f0f0;
            border-left: 4px solid #000;
            border-right: 1px solid #000;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 10px;
            margin-bottom: 10px;
        }
        /* ====== INFO GRID 2 KOLOM ====== */
        .info-row {
            display: table;
            width: 100%;
            border-collapse: collapse;
            font-size: 12pt;
            table-layout: fixed;
        }
        .info-row > div {
            display: table-row;
        }
        .info-row > div > span {
            display: table-cell;
            padding: 7px 16px 7px 0;
            vertical-align: top;
            white-space: nowrap;
        }
        .info-row .lbl {
            width: 28%;
            color: #000;
            font-weight: 400;
            padding-right: 16px;
        }
        .info-row .val {
            width: 22%;
            color: #000;
            font-weight: 700;
        }
        .info-row .val-normal {
            width: 22%;
            color: #000;
            font-weight: 400;
        }
        .info-row .sep {
            width: 4%;
        }
        /* ====== BANK CARD ====== */
        .bank-card {
            border: 1px solid #000;
            border-radius: 0;
            padding: 12px 16px;
            background: #fafafa;
        }
        .bank-name {
            font-size: 13pt;
            font-weight: 700;
            color: #000;
            margin: 0 0 6px 0;
            font-family: 'Times New Roman', Times, serif;
        }
        .bank-detail {
            font-size: 12pt;
            color: #000;
            margin: 2px 0;
        }
        /* ====== TABEL ANGSURAN ====== */
        .angsuran-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
            border: 1.5px solid #000;
        }
        .angsuran-table thead tr {
            background: #000;
        }
        .angsuran-table th {
            color: white;
            font-weight: 700;
            padding: 8px 8px;
            text-align: center;
            border: 1px solid #000;
            font-size: 10pt;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .angsuran-table th.text-right,
        .angsuran-table td.text-right {
            text-align: right;
        }
        .angsuran-table td {
            padding: 7px 8px;
            border: 0.75px solid #000;
            color: #000;
        }
        .angsuran-table td.text-center {
            text-align: center;
        }
        .angsuran-table tfoot tr {
            background: #f0f0f0;
        }
        .angsuran-table tfoot td {
            border-top: 2px solid #000;
            font-weight: 700;
            color: #000;
            font-size: 11.5pt;
        }
        /* ====== NOTES ====== */
        .notes {
            border: 1px solid #000;
            border-radius: 0;
            padding: 10px 14px;
            font-size: 10pt;
            color: #000;
            line-height: 1.6;
            background: #fafafa;
            font-style: italic;
        }
        /* ====== TANDA TANGAN - SINGLE KETUA KANAN ====== */
        .sig-section {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .sig-left {
            width: 50%;
            text-align: left;
        }
        .sig-right {
            width: 50%;
            text-align: center;
            padding-right: 20px;
        }
        .sig-place {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            color: #000;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding-bottom: 75px;
        }
        .sig-name {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            font-weight: 700;
            color: #000;
            border-top: 1.5px solid #000;
            padding-top: 8px;
            display: block;
            text-transform: uppercase;
        }
        .sig-id {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            color: #000;
            margin-top: 3px;
            display: block;
            font-style: italic;
        }
        /* ====== QR CODE ====== */
        .qr-section {
            width: 100%;
            text-align: left;
            padding-bottom: 10px;
        }
        .qr-label {
            font-family: 'Times New Roman', Times, serif;
            font-size: 7pt;
            color: #666;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .qr-url {
            font-family: 'Times New Roman', Times, serif;
            font-size: 5.5pt;
            color: #999;
            margin-top: 2px;
        }
        /* ====== FOOTER ====== */
        .footer-note {
            text-align: center;
            margin-top: 22px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 9pt;
            color: #000;
            letter-spacing: 0.4px;
            font-style: italic;
        }
        .amount-large {
            font-size: 14pt;
            font-weight: 700;
            color: #000;
            font-family: 'Times New Roman', Times, serif;
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- ============ HEADER ============ -->
        <div class="header">
            <div class="header-row">
                <div class="header-logo">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo Koperasi" />
                    @else
                        <div class="logo-fallback">KOP</div>
                    @endif
                </div>
                <div class="header-text">
                    <h1 class="coop-name">KOPERASI KARYAWAN</h1>
                    <p class="coop-tagline">Simpan Pinjam &mdash; Anggota Koperasi</p>
                    <p class="doc-title">Bukti Peminjaman Dana</p>
                    <p class="doc-number">Nomor: {{ $docNo }}</p>
                </div>
            </div>
        </div>

        <!-- ============ DATA ANGGOTA & DETAIL PINJAMAN ============ -->
        <div class="section">
            <div class="section-title">I. Data Anggota & Detail Pinjaman</div>
            <div class="info-row">
                <div>
                    <span class="lbl">No. Anggota</span>
                    <span class="val">{{ $anggota['no_anggota'] }}</span>
                    <span class="sep"></span>
                    <span class="lbl">Nominal Pinjaman</span>
                    <span class="val amount-large">{{ formatRupiah($pinjaman['nominal']) }}</span>
                </div>
                <div>
                    <span class="lbl">No. Karyawan</span>
                    <span class="val">{{ $anggota['no_karyawan'] }}</span>
                    <span class="sep"></span>
                    <span class="lbl">Terbilang</span>
                    <span class="val-normal">{{ $pinjaman['terbilang'] }}</span>
                </div>
                <div>
                    <span class="lbl">Nama Lengkap</span>
                    <span class="val">{{ $anggota['nama'] }}</span>
                    <span class="sep"></span>
                    <span class="lbl">Jangka Waktu</span>
                    <span class="val">{{ $pinjaman['tenor_bulan'] }} Bulan</span>
                </div>
                <div>
                    <span class="lbl">Cabang</span>
                    <span class="val">{{ $anggota['cabang'] }}</span>
                    <span class="sep"></span>
                    <span class="lbl">Suku Bunga</span>
                    <span class="val">{{ $pinjaman['persentase_bunga'] }}% per Bulan (Menurun)</span>
                </div>
                <div>
                    <span class="lbl">Jabatan</span>
                    <span class="val-normal">{{ $anggota['jabatan'] }}</span>
                    <span class="sep"></span>
                    <span class="lbl">Tanggal Pengajuan</span>
                    <span class="val">{{ $pinjaman['tanggal_pengajuan'] }}</span>
                </div>
                <div>
                    <span class="lbl">Unit Bisnis</span>
                    <span class="val-normal">{{ $anggota['unit_bisnis'] }}</span>
                    <span class="sep"></span>
                    <span class="lbl">Tanggal Pencairan</span>
                    <span class="val">{{ $pinjaman['tanggal_cair'] ?? '-' }}</span>
                </div>
                <div>
                    <span class="lbl">Keperluan</span>
                    <span class="val-normal" colspan="3">{{ $pinjaman['keperluan'] ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- ============ REKENING TUJUAN ============ -->
        <div class="section">
            <div class="section-title">II. Rekening Tujuan Pencairan</div>
            <div class="bank-card">
                <p class="bank-name">{{ $rek['bank'] ?? '-' }}</p>
                <p class="bank-detail"><strong>No. Rekening:</strong> {{ $rek['no_rekening'] ?? '-' }}</p>
                <p class="bank-detail"><strong>Atas Nama:</strong> {{ $rek['atas_nama'] ?? '-' }}</p>
            </div>
        </div>

        <!-- ============ JADWAL ANGSURAN ============ -->
        <div class="section">
            <div class="section-title">III. Jadwal Angsuran</div>
            <table class="angsuran-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">No</th>
                        <th style="width: 24%;">Tanggal Jatuh Tempo</th>
                        <th class="text-right" style="width: 22%;">Pokok</th>
                        <th class="text-right" style="width: 22%;">Bunga</th>
                        <th class="text-right" style="width: 24%;">Total Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($angsuran as $a)
                        <tr>
                            <td class="text-center">{{ $a['cicilan_ke'] }}</td>
                            <td class="text-center">{{ $a['tanggal_jatuh_tempo'] }}</td>
                            <td class="text-right">{{ formatRupiah($a['nominal_pokok']) }}</td>
                            <td class="text-right">{{ formatRupiah($a['nominal_bunga']) }}</td>
                            <td class="text-right">{{ formatRupiah($a['total_bayar']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-center">TOTAL</td>
                        <td class="text-right">{{ formatRupiah($totals['pokok']) }}</td>
                        <td class="text-right">{{ formatRupiah($totals['bunga']) }}</td>
                        <td class="text-right">{{ formatRupiah($totals['angsuran']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- ============ CATATAN ============ -->
        <div class="section">
            <div class="notes">
                <strong>Catatan:</strong> Angsuran wajib dibayar paling lambat pada tanggal jatuh tempo setiap bulannya. Keterlambatan pembayaran akan dikenai denda sesuai ketentuan Anggaran Dasar dan Anggaran Rumah Tangga Koperasi. Dokumen ini dicetak melalui sistem dan merupakan bukti resmi peminjaman.
            </div>
        </div>

        <!-- ============ TANDA TANGAN KETUA + QR CODE ============ -->
        <div class="sig-section">
            <div class="sig-left">
                <!-- QR Code di kiri -->
                <div class="qr-section">
                    <div class="qr-label">Scan untuk Verifikasi</div>
                    <div style="width: 60px; height: 60px;">{!! $qrSvg !!}</div>
                    <div class="qr-url">{{ parse_url($verificationUrl, PHP_URL_HOST) }}/verifikasi/bukti/{{ $pinjaman['id'] }}</div>
                </div>
            </div>
            <div class="sig-right">
                <div class="sig-place">Ketua Koperasi</div>
                <span class="sig-name">{{ $ketuaNama }}</span>
                <span class="sig-id">Ketua</span>
            </div>
        </div>

        <!-- ============ FOOTER ============ -->
        <div class="footer-note">
            Dokumen ini dicetak otomatis oleh sistem Koperasi Karyawan pada {{ $tglCetak }} &bull; {{ $docNo }}
        </div>
    </div>
</body>
</html>