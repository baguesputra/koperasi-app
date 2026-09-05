<?php
function formatRupiah($n) {
    return 'Rp ' . number_format(floatval($n ?? 0), 0, ',', '.');
}

// Encode logo as base64 for reliable PDF rendering
$logoPath = public_path('images/logo.png');
$logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

// Generate simple barcode data (code39-like representation for dates)
// In production, use a proper barcode library. Here we create a visual representation.
function generateBarcodeData($text) {
    // Return a string that can be rendered as a barcode pattern
    // For simplicity, we'll create a visual barcode using CSS
    return $text;
}

function renderBarcode($data) {
    // Simple visual barcode - in production use: https://github.com/picqer/php-barcode-generator
    // This creates a basic code39-like pattern
    $pattern = '';
    $chars = str_split(strtoupper($data));
    foreach ($chars as $c) {
        $pattern .= $c . '|';
    }
    return $pattern;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bukti Peminjaman - {{ $pinjaman['anggota']['nama'] }}</title>
    <style>
        @page {
            margin: 2cm 2.5cm;
            @bottom-center {
                content: "Halaman " counter(page) " dari " counter(pages);
                font-size: 8px;
                color: #6b7280;
                font-family: 'DejaVu Sans', sans-serif;
            }
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: white;
            color: #1a1a1a;
            font-size: 11px;
            line-height: 1.5;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 10px;
        }
        /* Header - centered title with logo on left */
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .logo {
            width: 65px;
            height: 65px;
            flex-shrink: 0;
        }
        .header-text {
            text-align: center;
        }
        .coop-name {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 4px 0;
            letter-spacing: 1px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 3px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .doc-number {
            font-size: 10px;
            color: #4a4a4a;
            margin: 0;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #1a1a1a;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 5px 15px;
            font-size: 10px;
        }
        .info-label {
            color: #4a4a4a;
            font-weight: 500;
        }
        .info-value {
            color: #1a1a1a;
            font-weight: 600;
        }
        .info-value-normal {
            color: #1a1a1a;
            font-weight: 400;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9px;
        }
        th {
            background-color: #1a1a1a;
            color: white;
            font-weight: 600;
            padding: 7px 4px;
            text-align: center;
            border: 1px solid #1a1a1a;
        }
        td {
            padding: 6px 4px;
            border: 1px solid #d1d5db;
            text-align: center;
        }
        td:first-child, td:nth-child(2) {
            text-align: left;
        }
        td.text-right, th.text-right {
            text-align: right;
            padding-right: 8px;
        }
        tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        .tfoot-row {
            background-color: #e5e5e5 !important;
            font-weight: 700;
        }
        .tfoot-row td {
            border-top: 2px solid #1a1a1a;
        }
        .bank-card {
            background-color: #f5f5f5;
            border: 1px solid #9ca3af;
            border-radius: 4px;
            padding: 12px;
            margin-top: 10px;
        }
        .bank-name {
            font-size: 12px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 4px 0;
        }
        .bank-detail {
            font-size: 10px;
            color: #374151;
            margin: 1px 0;
        }
        .notes {
            background-color: #fafafa;
            border: 1px solid #9ca3af;
            border-radius: 4px;
            padding: 10px;
            font-size: 9px;
            color: #374151;
            line-height: 1.6;
        }
        /* Three signature blocks: left (anggota), center (bendahara), right (ketua) */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #1a1a1a;
        }
        .sig-block {
            text-align: center;
        }
        .sig-label {
            font-size: 10px;
            color: #4a4a4a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 35px;
        }
        .sig-line {
            border-top: 1px solid #1a1a1a;
            padding-top: 8px;
            font-weight: 600;
            color: #1a1a1a;
            font-size: 10px;
        }
        .sig-sub {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
        }
        /* Barcode styling */
        .barcode-section {
            margin-top: 25px;
            padding: 15px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background-color: #fafafa;
        }
        .barcode-title {
            font-size: 9px;
            font-weight: 600;
            color: #4a4a4a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .barcode-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }
        .barcode-item {
            text-align: center;
        }
        .barcode-label {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .barcode-visual {
            font-family: 'Libre Barcode 39', 'DejaVu Sans', monospace;
            font-size: 18px;
            letter-spacing: 0;
            color: #1a1a1a;
            background: white;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            display: inline-block;
        }
        .barcode-text {
            font-size: 8px;
            color: #4a4a4a;
            margin-top: 4px;
            font-family: 'DejaVu Sans', monospace;
        }
        .footer-note {
            text-align: center;
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px dashed #9ca3af;
            font-size: 8px;
            color: #9ca3af;
        }
        .amount-large {
            font-size: 13px;
            font-weight: 700;
            color: #1a1a1a;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header - Logo left, Title centered -->
        <div class="header">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo Koperasi" class="logo" />
            @else
                <div class="logo" style="background:#1a1a1a;border-radius:4px;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:12px;">KOPERASI</div>
            @endif
            <div class="header-text">
                <h1 class="coop-name">KOPERASI KARYAWAN</h1>
                <p class="doc-title">Bukti Peminjaman</p>
                <p class="doc-number">Nomor: BUKTI-PJM/{{ $pinjaman['anggota']['no_anggota'] }}/{{ $pinjaman['id'] }}</p>
            </div>
        </div>

        <!-- Data Anggota -->
        <div class="section">
            <div class="section-title">Data Anggota</div>
            <div class="info-grid">
                <span class="info-label">Nomor Anggota</span>
                <span class="info-value">{{ $pinjaman['anggota']['no_anggota'] }}</span>
                <span class="info-label">Nomor Karyawan</span>
                <span class="info-value">{{ $pinjaman['anggota']['no_karyawan'] }}</span>
                <span class="info-label">Nama Lengkap</span>
                <span class="info-value">{{ $pinjaman['anggota']['nama'] }}</span>
                <span class="info-label">Cabang</span>
                <span class="info-value">{{ $pinjaman['anggota']['cabang'] }}</span>
                <span class="info-label">Unit Bisnis</span>
                <span class="info-value-normal">{{ $pinjaman['anggota']['unit_bisnis'] }}</span>
                <span class="info-label">Jabatan</span>
                <span class="info-value-normal">{{ $pinjaman['anggota']['jabatan'] }}</span>
            </div>
        </div>

        <!-- Detail Pinjaman -->
        <div class="section">
            <div class="section-title">Detail Pinjaman</div>
            <div class="info-grid">
                <span class="info-label">Nominal Pinjaman</span>
                <span class="info-value amount-large">{{ formatRupiah($pinjaman['nominal']) }}</span>
                <span class="info-label">Terbilang</span>
                <span class="info-value-normal" style="grid-column: span 1;">{{ $pinjaman['terbilang'] }}</span>
                <span class="info-label">Tenor</span>
                <span class="info-value">{{ $pinjaman['tenor_bulan'] }} Bulan</span>
                <span class="info-label">Bunga</span>
                <span class="info-value">{{ $pinjaman['persentase_bunga'] }}% / Bulan (Menurun)</span>
                <span class="info-label">Tanggal Pengajuan</span>
                <span class="info-value">{{ $pinjaman['tanggal_pengajuan'] }}</span>
                <span class="info-label">Tanggal Cair</span>
                <span class="info-value">{{ $pinjaman['tanggal_cair'] ?? '-' }}</span>
                <span class="info-label">Keperluan</span>
                <span class="info-value-normal" style="grid-column: span 1; white-space: pre-line;">{{ $pinjaman['keperluan'] ?? '-' }}</span>
            </div>
        </div>

        <!-- Rekening Tujuan -->
        <div class="section">
            <div class="section-title">Rekening Tujuan Pencairan</div>
            <div class="bank-card">
                <p class="bank-name">{{ $pinjaman['rekening']['bank'] }}</p>
                <p class="bank-detail"><strong>No. Rekening:</strong> {{ $pinjaman['rekening']['no_rekening'] }}</p>
                <p class="bank-detail"><strong>Atas Nama:</strong> {{ $pinjaman['rekening']['atas_nama'] }}</p>
            </div>
        </div>

        <!-- Jadwal Angsuran -->
        <div class="section">
            <div class="section-title">Jadwal Angsuran</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">No</th>
                        <th style="width: 22%;">Jatuh Tempo</th>
                        <th class="text-right" style="width: 20%;">Pokok</th>
                        <th class="text-right" style="width: 20%;">Bunga</th>
                        <th class="text-right" style="width: 20%;">Total Bayar</th>
                        <th style="width: 10%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($angsuran as $a)
                        <tr>
                            <td>{{ $a['cicilan_ke'] }}</td>
                            <td>{{ $a['tanggal_jatuh_tempo'] }}</td>
                            <td class="text-right">{{ formatRupiah($a['nominal_pokok']) }}</td>
                            <td class="text-right">{{ formatRupiah($a['nominal_bunga']) }}</td>
                            <td class="text-right">{{ formatRupiah($a['total_bayar']) }}</td>
                            <td>
                                @php
                                    $statusClass = $a['status'] === 'lunas' ? 'background:#e5e5e5;color:#1a1a1a;border:1px solid #1a1a1a;' : 
                                                   ($a['status'] === 'belum_bayar' ? 'background:#f5f5f5;color:#4a4a4a;border:1px solid #9ca3af;' : 'background:#f5f5f5;color:#4a4a4a;border:1px solid #9ca3af;');
                                @endphp
                                <span style="padding:2px 6px;border-radius:3px;font-size:8px;font-weight:600;{{ $statusClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $a['status'])) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="tfoot-row">
                        <td colspan="2">TOTAL</td>
                        <td class="text-right">{{ formatRupiah($totals['pokok']) }}</td>
                        <td class="text-right">{{ formatRupiah($totals['bunga']) }}</td>
                        <td class="text-right">{{ formatRupiah($totals['angsuran']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Catatan -->
        <div class="section">
            <div class="notes">
                <strong>Catatan Penting:</strong><br>
                1. Angsuran wajib dibayar sesuai tanggal jatuh tempo setiap bulannya.<br>
                2. Keterlambatan pembayaran akan dikenai denda sesuai ketentuan koperasi.<br>
                3. Peminjaman ini dilindungi oleh aturan simpan pinjam koperasi yang berlaku.<br>
                4. Setiap perubahan data wajib dilaporkan kepada bendahara koperasi.<br>
                5. Bukti ini sah sebagai dokumen resmi tanpa perlu tanda tangan basah jika dicetak melalui sistem.
            </div>
        </div>

        <!-- Barcode Section -->
        <div class="barcode-section">
            <div class="barcode-title">Verifikasi Digital (Barcode)</div>
            <div class="barcode-grid">
                <!-- Barcode 1: Pengaju / Tanggal Pengajuan -->
                <div class="barcode-item">
                    <div class="barcode-label">Pengaju / Tanggal Pengajuan</div>
                    <div class="barcode-visual">*{{ strtoupper($pinjaman['anggota']['no_anggota']) . '-' . str_replace([' ', ':'], ['', ''], $pinjaman['tanggal_pengajuan']) }}*</div>
                    <div class="barcode-text">{{ $pinjaman['tanggal_pengajuan'] }} | {{ $pinjaman['anggota']['nama'] }}</div>
                </div>
                <!-- Barcode 2: Bendahara Approve -->
                <div class="barcode-item">
                    <div class="barcode-label">Bendahara / Tanggal Approve</div>
                    <div class="barcode-visual">*BND-{{ strtoupper($pinjaman['anggota']['no_anggota']) . '-' . ($pinjaman['tanggal_cair'] ?? 'BELUM') }}*</div>
                    <div class="barcode-text">{{ $pinjaman['tanggal_cair'] ?? 'Belum diapprove' }}</div>
                </div>
                <!-- Barcode 3: Ketua Approve -->
                <div class="barcode-item">
                    <div class="barcode-label">Ketua / Tanggal Approve</div>
                    <div class="barcode-visual">*KTA-{{ strtoupper($pinjaman['anggota']['no_anggota']) . '-' . ($pinjaman['tanggal_cair'] ?? 'BELUM') }}*</div>
                    <div class="barcode-text">{{ $pinjaman['tanggal_cair'] ?? 'Belum diapprove' }}</div>
                </div>
            </div>
        </div>

        <!-- Tanda Tangan: Kiri=Anggota, Tengah=Bendahara, Kanan=Ketua -->
        <div class="signatures">
            <div class="sig-block">
                <div class="sig-label">Anggota / Peminjam</div>
                <div class="sig-line">{{ $pinjaman['anggota']['nama'] }}</div>
                <div class="sig-sub">{{ $pinjaman['anggota']['no_anggota'] }}</div>
            </div>
            <div class="sig-block">
                <div class="sig-label">Bendahara Koperasi</div>
                <div class="sig-line">________________________</div>
                <div class="sig-sub">Nama & Tanda Tangan</div>
            </div>
            <div class="sig-block">
                <div class="sig-label">Ketua Koperasi</div>
                <div class="sig-line">________________________</div>
                <div class="sig-sub">Nama & Tanda Tangan</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-note">
            Dokumen ini dicetak otomatis oleh sistem Koperasi Karyawan pada {{ date('d F Y') }} | Halaman ini merupakan bukti resmi peminjaman
        </div>
    </div>
</body>
</html>