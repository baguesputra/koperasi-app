<?php
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

function formatRupiah($n) {
    return 'Rp ' . number_format(floatval($n ?? 0), 0, ',', '.');
}

$kopNama = config('koperasi.nama', 'KOPERASI KARYAWAN');
$kopUnit = config('koperasi.unit', 'KARYA MANDIRI DUTA MALL BANJARMASIN');
$kopAlamat = config('koperasi.alamat', 'Jalan Jenderal Ahmad Yani KM 2 No. 98, Melayu');
$kopKontak = trim(collect([config('koperasi.telepon'), config('koperasi.email')])->filter()->join(' | '));

$logoPath = public_path('images/logo.png');
$logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

$anggota = $pinjaman['anggota'];
$rek = $pinjaman['rekening'] ?? [];

$ketuaNama = $pinjaman['disetujui_oleh_ketua_nama'] ?? 'Ketua Koperasi';

$docNo = $pinjaman['nomor_dokumen'] ?? '-';
$tglCetak = \Carbon\Carbon::now()->translatedFormat('d F Y');
$kotaTtd = $kota_ttd ?? config('koperasi.kota_ttd', 'Banjarmasin');
$tglTtd = $pinjaman['tanggal_cair'] ?? $tglCetak;

// Generate QR Code
$verificationUrl = $pinjaman['verification_url'] ?? route('verifikasi.bukti', ['pinjaman' => $pinjaman['id']]);
try {
    $qrCode = new QrCode(
        $verificationUrl,
        new Encoding('UTF-8'),
        ErrorCorrectionLevel::Medium,
        200,
        0
    );
    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    $qrPngBase64 = base64_encode($result->getString());
} catch (\Throwable $e) {
    try {
        $fallbackQr = new QrCode($verificationUrl, new Encoding('UTF-8'), ErrorCorrectionLevel::Medium, 200, 0);
        $fallbackWriter = new PngWriter();
        $fallbackResult = $fallbackWriter->write($fallbackQr);
        $qrPngBase64 = base64_encode($fallbackResult->getString());
    } catch (\Throwable $e) {
        $qrPngBase64 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    }
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
                content: "Halaman " counter(page) " dari " counter(pages) "  |  {{ $docNo }}";
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
            line-height: 1.6;
        }
        .kop-row { display: table; width: 100%; }
        .kop-logo { display: table-cell; vertical-align: middle; width: 80px; padding-right: 16px; }
        .kop-logo img, .kop-logo .logo-fallback { width: 72px; height: 72px; }
        .kop-logo .logo-fallback { background: #000; color: #fff; text-align: center; font-weight: bold; font-size: 13pt; line-height: 72px; }
        .kop-text { display: table-cell; vertical-align: middle; text-align: center; }
        .kop-nama { font-size: 17pt; font-weight: 700; margin: 0; letter-spacing: 1px; text-transform: uppercase; }
        .kop-unit { font-size: 12pt; font-weight: 700; margin: 2px 0; letter-spacing: 0.6px; text-transform: uppercase; }
        .kop-alamat { font-size: 9pt; color: #333; margin: 0; }
        .kop-garis { border-top: 3px double #000; margin: 10px 0 16px 0; }
        .kop-judul { text-align: center; margin-bottom: 18px; }
        .dok-title { font-size: 14pt; font-weight: 700; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 1.6px; text-decoration: underline; }
        .dok-number { font-size: 10pt; margin: 0; font-style: italic; }
        .pembuka { text-align: justify; margin-bottom: 16px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 11.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #000; padding: 4px 0; margin-bottom: 10px; }
        .info { display: table; width: 100%; table-layout: fixed; }
        .info > div { display: table-row; }
        .info > div > span { display: table-cell; padding: 5px 12px 5px 0; vertical-align: top; }
        .lbl { width: 30%; }
        .titik { width: 3%; }
        .val { width: 67%; font-weight: 700; }
        .val-normal { width: 67%; }
        .bank-card { border: 1px solid #000; padding: 10px 14px; background: #fafafa; }
        .bank-name { font-size: 12.5pt; font-weight: 700; margin: 0 0 4px 0; }
        .bank-detail { font-size: 11pt; margin: 2px 0; }
        table.angsuran { width: 100%; border-collapse: collapse; font-size: 11pt; }
        table.angsuran th, table.angsuran td { border: 1px solid #000; padding: 6px 8px; }
        table.angsuran th { text-transform: uppercase; font-size: 9.5pt; letter-spacing: 0.5px; background: #f0f0f0; }
        table.angsuran td.text-right, table.angsuran th.text-right { text-align: right; }
        table.angsuran td.text-center { text-align: center; }
        table.angsuran tfoot td { font-weight: 700; border-top: 2px solid #000; }
        .terbilang { border: 1px solid #000; background: #fafafa; padding: 8px 12px; font-style: italic; font-size: 11pt; margin-top: 10px; }
        .notes { border-left: 3px solid #000; padding: 8px 0 8px 14px; font-size: 10.5pt; font-style: italic; line-height: 1.6; }
        .penutup { text-align: justify; margin: 16px 0 8px 0; }
        .sig { display: table; width: 100%; margin-top: 24px; }
        .sig-row { display: table-row; }
        .sig-cell { display: table-cell; width: 50%; text-align: center; vertical-align: top; }
        .sig-place { font-size: 10pt; margin-bottom: 4px; }
        .qr-block { margin: 6px auto; }
        .sig-space { height: 24px; }
        .sig-name { font-size: 11pt; font-weight: 700; text-transform: uppercase; border-top: 1.5px solid #000; display: inline-block; padding-top: 6px; min-width: 200px; }
        .sig-role { font-size: 9pt; font-style: italic; display: block; margin-top: 2px; }
        .qr-caption { font-size: 8pt; color: #333; margin-top: 4px; }
        .footer-note { text-align: center; margin-top: 20px; padding-top: 8px; border-top: 1px solid #000; font-size: 9pt; font-style: italic; }
    </style>
</head>
<body>
    <div class="kop-row">
        <div class="kop-logo">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo Koperasi" />
            @else
                <div class="logo-fallback">KOP</div>
            @endif
        </div>
        <div class="kop-text">
            <h1 class="kop-nama">{{ $kopNama }}</h1>
            <p class="kop-unit">{{ $kopUnit }}</p>
            <p class="kop-alamat">{{ $kopAlamat }}</p>
            @if($kopKontak)
                <p class="kop-alamat">{{ $kopKontak }}</p>
            @endif
        </div>
    </div>
    <div class="kop-garis"></div>
    <div class="kop-judul">
        <p class="dok-title">Bukti Peminjaman Dana</p>
        <p class="dok-number">Nomor: {{ $docNo }}</p>
    </div>

    <p class="pembuka">
        Dengan hormat,<br>
        Berdasarkan pengajuan yang telah disetujui, bersama ini kami sampaikan bukti pencairan pinjaman dana
        kepada anggota di bawah ini:
    </p>

    <div class="section">
        <div class="section-title">I. Data Anggota &amp; Detail Pinjaman</div>
        <div class="info">
            <div><span class="lbl">No. Karyawan</span><span class="titik">:</span><span class="val">{{ $anggota['no_karyawan'] }}</span></div>
            <div><span class="lbl">Nama Lengkap</span><span class="titik">:</span><span class="val">{{ $anggota['nama'] }}</span></div>
            <div><span class="lbl">Cabang / Unit Bisnis</span><span class="titik">:</span><span class="val-normal">{{ ($anggota['cabang'] ?? '-') . ' / ' . ($anggota['unit_bisnis'] ?? '-') }}</span></div>
            <div><span class="lbl">Jabatan</span><span class="titik">:</span><span class="val-normal">{{ $anggota['jabatan'] ?? '-' }}</span></div>
            <div><span class="lbl">Nominal Pinjaman</span><span class="titik">:</span><span class="val">{{ formatRupiah($pinjaman['nominal']) }}</span></div>
            <div><span class="lbl">Jangka Waktu</span><span class="titik">:</span><span class="val-normal">{{ $pinjaman['tenor_bulan'] }} Bulan</span></div>
            <div><span class="lbl">Suku Bunga</span><span class="titik">:</span><span class="val-normal">{{ $pinjaman['persentase_bunga'] }}% per Bulan (Menurun)</span></div>
            <div><span class="lbl">Tanggal Pengajuan</span><span class="titik">:</span><span class="val-normal">{{ $pinjaman['tanggal_pengajuan'] }}</span></div>
            <div><span class="lbl">Tanggal Pencairan</span><span class="titik">:</span><span class="val-normal">{{ $pinjaman['tanggal_cair'] ?? '-' }}</span></div>
            <div><span class="lbl">Keperluan</span><span class="titik">:</span><span class="val-normal">{{ $pinjaman['keperluan'] ?? '-' }}</span></div>
        </div>
        <div class="terbilang">Terbilang: {{ $pinjaman['terbilang'] }}</div>
    </div>

    <div class="section">
        <div class="section-title">II. Rekening Tujuan Pencairan</div>
        <div class="bank-card">
            <p class="bank-name">{{ $rek['bank'] ?? '-' }}</p>
            <p class="bank-detail"><strong>No. Rekening:</strong> {{ $rek['no_rekening'] ?? '-' }}</p>
            <p class="bank-detail"><strong>Atas Nama:</strong> {{ $rek['atas_nama'] ?? '-' }}</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">III. Jadwal Angsuran</div>
        <table class="angsuran">
            <thead>
                <tr>
                    <th class="text-center" style="width:8%;">No</th>
                    <th class="text-center" style="width:24%;">Tanggal Jatuh Tempo</th>
                    <th class="text-right" style="width:22%;">Pokok</th>
                    <th class="text-right" style="width:22%;">Bunga</th>
                    <th class="text-right" style="width:24%;">Total Bayar</th>
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

    <div class="section">
        <div class="notes">
            <strong>Catatan:</strong> Angsuran wajib dibayar paling lambat pada tanggal jatuh tempo setiap bulannya.
            Keterlambatan pembayaran mengikuti ketentuan Anggaran Dasar dan Anggaran Rumah Tangga koperasi.
            Dokumen ini merupakan bukti resmi pencairan pinjaman.
        </div>
    </div>

    <p class="penutup">
        Demikian bukti peminjaman ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
    </p>

    <div class="sig">
        <div class="sig-row">
            <div class="sig-cell">
                <div class="sig-place">{{ $kotaTtd }}, {{ $tglTtd }}<br>Penerima</div>
                <div class="sig-space"></div>
                <span class="sig-name">{{ $anggota['nama'] }}</span>
                <span class="sig-role">No. Karyawan: {{ $anggota['no_karyawan'] }}</span>
            </div>
            <div class="sig-cell">
                <div class="sig-place">{{ $kotaTtd }}, {{ $tglTtd }}<br>Ketua Koperasi</div>
                <div class="qr-block">
                    <img src="data:image/png;base64,{{ $qrPngBase64 }}" width="110" height="110" />
                </div>
                <div class="sig-space"></div>
                <span class="sig-name">{{ $ketuaNama }}</span>
                <span class="sig-role">Ketua</span>
                <div class="qr-caption">Pindai untuk verifikasi keaslian dokumen</div>
            </div>
        </div>
    </div>

    <div class="footer-note">
        Dokumen ini diterbitkan oleh sistem {{ $kopNama }} pada {{ $tglCetak }} &bull; {{ $docNo }}
    </div>
</body>
</html>
