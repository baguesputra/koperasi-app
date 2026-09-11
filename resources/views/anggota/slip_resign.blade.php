<?php
function formatRupiah($n) {
    return 'Rp ' . number_format(floatval($n ?? 0), 0, ',', '.');
}

$logoPath = public_path('images/logo.png');
$logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

$docNo = $doc_no ?? ('SLIP-RESIGN/' . ($anggota['no_anggota'] ?? '-'));
$tglCetak = date('d F Y');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Slip Resign - {{ $anggota['nama'] ?? '-' }}</title>
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
            font-size: 13pt;
            line-height: 1.6;
        }
        .container {
            max-width: 100%;
        }
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
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            line-height: 72px;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .coop-name {
            font-size: 18pt;
            font-weight: 700;
            color: #000;
            margin: 0 0 2px 0;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }
        .coop-tagline {
            font-size: 9pt;
            color: #4b5563;
            margin: 0 0 8px 0;
            font-style: italic;
            letter-spacing: 0.4px;
        }
        .doc-title {
            font-size: 14pt;
            font-weight: 700;
            color: #000;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            text-decoration: underline;
        }
        .doc-number {
            font-size: 10pt;
            color: #000;
            margin: 0;
            font-style: italic;
        }
        .section {
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 12pt;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            border-bottom: 2px solid #000;
            padding: 5px 0;
            margin-bottom: 12px;
        }
        .info-row {
            display: table;
            width: 100%;
            border-collapse: collapse;
            font-size: 13pt;
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
            width: 32%;
            color: #000;
        }
        .info-row .val {
            width: 68%;
            color: #000;
            font-weight: 700;
            white-space: normal;
        }
        .info-row .val-normal {
            width: 68%;
            color: #000;
            white-space: normal;
        }
        .total-row .val {
            font-size: 14pt;
        }
        .notes {
            border-left: 3px solid #000;
            padding: 8px 0 8px 16px;
            margin-left: 4px;
            font-size: 11pt;
            color: #000;
            line-height: 1.6;
            font-style: italic;
        }
        .sig-section {
            margin-top: 35px;
            display: table;
            width: 100%;
        }
        .sig-row {
            display: table-row;
        }
        .sig-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
        }
        .sig-place {
            font-size: 10pt;
            color: #000;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 75px;
        }
        .sig-name {
            font-size: 11pt;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            display: block;
            border-top: 1.5px solid #000;
            padding-top: 8px;
            margin-left: 30px;
            margin-right: 30px;
        }
        .footer-note {
            text-align: center;
            margin-top: 22px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 10pt;
            color: #000;
            letter-spacing: 0.4px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">

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
                    <p class="doc-title">Slip Pengembalian Simpanan</p>
                    <p class="doc-number">Nomor: {{ $docNo }}</p>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">I. Data Anggota</div>
            <div class="info-row">
                <div>
                    <span class="lbl">No. Anggota</span>
                    <span class="val">{{ $anggota['no_anggota'] ?? '-' }}</span>
                </div>
                <div>
                    <span class="lbl">No. Karyawan</span>
                    <span class="val">{{ $anggota['no_karyawan'] ?? '-' }}</span>
                </div>
                <div>
                    <span class="lbl">Nama Lengkap</span>
                    <span class="val">{{ $anggota['nama'] ?? '-' }}</span>
                </div>
                <div>
                    <span class="lbl">Cabang</span>
                    <span class="val-normal">{{ $anggota['cabang'] ?? '-' }}</span>
                </div>
                <div>
                    <span class="lbl">Unit Bisnis</span>
                    <span class="val-normal">{{ $anggota['unit_bisnis'] ?? '-' }}</span>
                </div>
                <div>
                    <span class="lbl">Jabatan</span>
                    <span class="val-normal">{{ $anggota['jabatan'] ?? '-' }}</span>
                </div>
                <div>
                    <span class="lbl">Tanggal Jadi Anggota</span>
                    <span class="val-normal">{{ $anggota['tanggal_jadi_anggota'] ?? '-' }}</span>
                </div>
                <div>
                    <span class="lbl">Tanggal Resign</span>
                    <span class="val">{{ $anggota['tanggal_resign'] ?? '-' }}</span>
                </div>
                <div>
                    <span class="lbl">Alasan</span>
                    <span class="val-normal">{{ $anggota['alasan_resign'] ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">II. Ringkasan Simpanan</div>
            <div class="info-row">
                <div>
                    <span class="lbl">Total Simpanan Pokok</span>
                    <span class="val-normal">{{ formatRupiah($settlement['simpanan_pokok_total'] ?? 0) }}</span>
                </div>
                <div>
                    <span class="lbl">Total Simpanan Wajib</span>
                    <span class="val-normal">{{ formatRupiah($settlement['simpanan_wajib_total'] ?? 0) }}</span>
                </div>
                <div>
                    <span class="lbl">Dana Sosial (hangus)</span>
                    <span class="val-normal">{{ formatRupiah($settlement['dana_sosial_hangus'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">III. Pelunasan Pinjaman</div>
            <div class="info-row">
                <div>
                    <span class="lbl">Total Tagihan Pelunasan</span>
                    <span class="val">{{ formatRupiah($settlement['tagihan_pelunasan'] ?? 0) }}</span>
                </div>
                <div>
                    <span class="lbl">Alokasi dari Simpanan Pokok</span>
                    <span class="val-normal">{{ formatRupiah($settlement['alokasi_dari_pokok'] ?? 0) }}</span>
                </div>
                <div>
                    <span class="lbl">Alokasi dari Simpanan Wajib</span>
                    <span class="val-normal">{{ formatRupiah($settlement['alokasi_dari_wajib'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">IV. Pengembalian ke Anggota</div>
            <div class="info-row">
                <div>
                    <span class="lbl">Kembali Simpanan Pokok</span>
                    <span class="val-normal">{{ formatRupiah($settlement['kembali_pokok'] ?? 0) }}</span>
                </div>
                <div>
                    <span class="lbl">Kembali Simpanan Wajib</span>
                    <span class="val-normal">{{ formatRupiah($settlement['kembali_wajib'] ?? 0) }}</span>
                </div>
                <div class="total-row">
                    <span class="lbl">Total Dikembalikan</span>
                    <span class="val">{{ formatRupiah($settlement['total_dikembalikan'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="notes">
                <strong>Catatan:</strong> Dana sosial bersifat hangus dan tidak dikembalikan.
                Pengembalian telah dicatat sebagai jurnal keluar kas koperasi.
                Akun login anggota telah dinonaktifkan per tanggal resign.
            </div>
        </div>

        <div class="sig-section">
            <div class="sig-row">
                <div class="sig-cell">
                    <div class="sig-place">Anggota</div>
                    <span class="sig-name">{{ $anggota['nama'] ?? '-' }}</span>
                </div>
                <div class="sig-cell">
                    <div class="sig-place">Bendahara</div>
                    <span class="sig-name">{{ $settlement['aktor'] ?? '________________' }}</span>
                </div>
            </div>
        </div>

        <div class="footer-note">
            Dokumen ini dicetak otomatis oleh sistem Koperasi Karyawan pada {{ $tglCetak }} &bull; {{ $docNo }}
        </div>
    </div>
</body>
</html>
