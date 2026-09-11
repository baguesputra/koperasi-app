<?php
function formatRupiah($n) {
    return 'Rp ' . number_format(floatval($n ?? 0), 0, ',', '.');
}

function tanggalIndonesia($tgl) {
    if (! $tgl) return '-';
    try {
        return \Carbon\Carbon::parse($tgl)->translatedFormat('d F Y');
    } catch (\Throwable $e) {
        return $tgl;
    }
}

$kopNama = config('koperasi.nama', 'KOPERASI KARYAWAN');
$kopUnit = config('koperasi.unit', 'KARYA MANDIRI DUTA MALL BANJARMASIN');
$kopAlamat = config('koperasi.alamat', 'Jalan Jenderal Ahmad Yani KM 2 No. 98, Melayu');
$kopKontak = trim(collect([config('koperasi.telepon'), config('koperasi.email')])->filter()->join(' | '));

$logoPath = public_path('images/logo.png');
$logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

$docNo = $doc_no ?? '-';
$tglCetak = \Carbon\Carbon::now()->translatedFormat('d F Y');
$kotaTtd = $kota_ttd ?? config('koperasi.kota_ttd', 'Banjarmasin');
$tglTtd = tanggalIndonesia($settlement['tanggal_proses'] ?? $anggota['tanggal_resign'] ?? null);
$rincian = $settlement['pinjaman_rincian'] ?? [];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Slip Pengembalian Simpanan - {{ $anggota['nama'] ?? '-' }}</title>
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
        .kop { width: 100%; }
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
        .lbl { width: 34%; }
        .titik { width: 3%; }
        .val { width: 63%; font-weight: 700; }
        .val-normal { width: 63%; }
        table.rincian { width: 100%; border-collapse: collapse; font-size: 11pt; }
        table.rincian th, table.rincian td { border: 1px solid #000; padding: 6px 8px; }
        table.rincian th { text-transform: uppercase; font-size: 9.5pt; letter-spacing: 0.5px; background: #f0f0f0; }
        table.rincian td.text-right, table.rincian th.text-right { text-align: right; }
        table.rincian td.text-center, table.rincian th.text-center { text-align: center; }
        table.rincian tfoot td { font-weight: 700; border-top: 2px solid #000; }
        .terbilang { border: 1px solid #000; background: #fafafa; padding: 8px 12px; font-style: italic; font-size: 11pt; margin-top: 10px; }
        .notes { border-left: 3px solid #000; padding: 8px 0 8px 14px; font-size: 10.5pt; font-style: italic; line-height: 1.6; }
        .penutup { text-align: justify; margin: 16px 0 8px 0; }
        .sig { display: table; width: 100%; margin-top: 24px; }
        .sig-row { display: table-row; }
        .sig-cell { display: table-cell; width: 50%; text-align: center; }
        .sig-place { font-size: 10pt; margin-bottom: 4px; }
        .sig-space { height: 80px; }
        .sig-name { font-size: 11pt; font-weight: 700; text-transform: uppercase; border-top: 1.5px solid #000; display: inline-block; padding-top: 6px; min-width: 200px; }
        .sig-role { font-size: 9pt; font-style: italic; display: block; margin-top: 2px; }
        .footer-note { text-align: center; margin-top: 20px; padding-top: 8px; border-top: 1px solid #000; font-size: 9pt; font-style: italic; }
    </style>
</head>
<body>
    <div class="kop">
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
    </div>
    <div class="kop-garis"></div>
    <div class="kop-judul">
        <p class="dok-title">Slip Pengembalian Simpanan</p>
        <p class="dok-number">Nomor: {{ $docNo }}</p>
    </div>

    <p class="pembuka">
        Dengan hormat,<br>
        Sehubungan dengan pengunduran diri (resign) anggota di bawah ini dari keanggotaan koperasi,
        bersama ini kami sampaikan rincian penyelesaian simpanan dan pelunasan pinjaman sebagai berikut:
    </p>

    <div class="section">
        <div class="section-title">I. Data Anggota</div>
        <div class="info">
            <div><span class="lbl">No. Karyawan</span><span class="titik">:</span><span class="val">{{ $anggota['no_karyawan'] ?? '-' }}</span></div>
            <div><span class="lbl">Nama Lengkap</span><span class="titik">:</span><span class="val">{{ $anggota['nama'] ?? '-' }}</span></div>
            <div><span class="lbl">Cabang / Unit Bisnis</span><span class="titik">:</span><span class="val-normal">{{ ($anggota['cabang'] ?? '-') . ' / ' . ($anggota['unit_bisnis'] ?? '-') }}</span></div>
            <div><span class="lbl">Jabatan</span><span class="titik">:</span><span class="val-normal">{{ $anggota['jabatan'] ?? '-' }}</span></div>
            <div><span class="lbl">Tanggal Jadi Anggota</span><span class="titik">:</span><span class="val-normal">{{ $anggota['tanggal_jadi_anggota'] ?? '-' }}</span></div>
            <div><span class="lbl">Tanggal Resign</span><span class="titik">:</span><span class="val-normal">{{ $anggota['tanggal_resign'] ?? '-' }}</span></div>
            <div><span class="lbl">Alasan Resign</span><span class="titik">:</span><span class="val-normal">{{ $anggota['alasan_resign'] ?? '-' }}</span></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">II. Ringkasan Simpanan</div>
        <div class="info">
            <div><span class="lbl">Simpanan Pokok</span><span class="titik">:</span><span class="val-normal">{{ formatRupiah($settlement['simpanan_pokok_total'] ?? 0) }}</span></div>
            <div><span class="lbl">Simpanan Wajib</span><span class="titik">:</span><span class="val-normal">{{ formatRupiah($settlement['simpanan_wajib_total'] ?? 0) }}</span></div>
            <div><span class="lbl">Dana Sosial (hangus)</span><span class="titik">:</span><span class="val-normal">{{ formatRupiah($settlement['dana_sosial_hangus'] ?? 0) }}</span></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">III. Rincian Pelunasan Pinjaman</div>
        @if(count($rincian) > 0)
            <table class="rincian">
                <thead>
                    <tr>
                        <th class="text-center" style="width:7%;">No</th>
                        <th style="width:33%;">Pinjaman</th>
                        <th class="text-right" style="width:20%;">Nominal Awal</th>
                        <th class="text-center" style="width:15%;">Sisa Cicilan</th>
                        <th class="text-right" style="width:25%;">Dipotong / Dilunasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rincian as $i => $r)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>Pinjaman {{ $i + 1 }}</td>
                            <td class="text-right">{{ formatRupiah($r['nominal_awal'] ?? 0) }}</td>
                            <td class="text-center">{{ $r['sisa_cicilan'] ?? 0 }}x</td>
                            <td class="text-right">{{ formatRupiah($r['sisa_tagihan'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-center">TOTAL PELUNASAN</td>
                        <td class="text-right">{{ formatRupiah($settlement['tagihan_pelunasan'] ?? 0) }}</td>
                    </tr>
                </tfoot>
            </table>
            <div class="info" style="margin-top:8px;">
                <div><span class="lbl">Alokasi dari Simpanan Pokok</span><span class="titik">:</span><span class="val-normal">{{ formatRupiah($settlement['alokasi_dari_pokok'] ?? 0) }}</span></div>
                <div><span class="lbl">Alokasi dari Simpanan Wajib</span><span class="titik">:</span><span class="val-normal">{{ formatRupiah($settlement['alokasi_dari_wajib'] ?? 0) }}</span></div>
            </div>
        @else
            <div class="info">
                <div><span class="lbl">Total Tagihan Pelunasan</span><span class="titik">:</span><span class="val">{{ formatRupiah($settlement['tagihan_pelunasan'] ?? 0) }}</span></div>
                <div><span class="lbl">Alokasi dari Simpanan Pokok</span><span class="titik">:</span><span class="val-normal">{{ formatRupiah($settlement['alokasi_dari_pokok'] ?? 0) }}</span></div>
                <div><span class="lbl">Alokasi dari Simpanan Wajib</span><span class="titik">:</span><span class="val-normal">{{ formatRupiah($settlement['alokasi_dari_wajib'] ?? 0) }}</span></div>
            </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">IV. Pengembalian Kepada Anggota</div>
        <div class="info">
            <div><span class="lbl">Kembali Simpanan Pokok</span><span class="titik">:</span><span class="val-normal">{{ formatRupiah($settlement['kembali_pokok'] ?? 0) }}</span></div>
            <div><span class="lbl">Kembali Simpanan Wajib</span><span class="titik">:</span><span class="val-normal">{{ formatRupiah($settlement['kembali_wajib'] ?? 0) }}</span></div>
            <div><span class="lbl"><strong>Total Dikembalikan</strong></span><span class="titik">:</span><span class="val">{{ formatRupiah($settlement['total_dikembalikan'] ?? 0) }}</span></div>
        </div>
        <div class="terbilang">Terbilang: {{ $settlement['terbilang_total'] ?? '-' }}</div>
    </div>

    <div class="section">
        <div class="notes">
            <strong>Catatan:</strong> Dana sosial bersifat hangus dan tidak dikembalikan.
            Pengembalian telah dicatat dalam pembukuan kas koperasi.
            Terhitung mulai tanggal resign di atas, yang bersangkutan bukan lagi anggota koperasi
            dan akun loginnya dinonaktifkan.
        </div>
    </div>

    <p class="penutup">
        Demikian slip penyelesaian keanggotaan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
    </p>

    <div class="sig">
        <div class="sig-row">
            <div class="sig-cell">
                <div class="sig-place">{{ $kotaTtd }}, {{ $tglTtd }}<br>Penerima / Anggota</div>
                <div class="sig-space"></div>
                <span class="sig-name">{{ $anggota['nama'] ?? '-' }}</span>
                <span class="sig-role">No. Karyawan: {{ $anggota['no_karyawan'] ?? '-' }}</span>
            </div>
            <div class="sig-cell">
                <div class="sig-place">{{ $kotaTtd }}, {{ $tglTtd }}<br>Bendahara Koperasi</div>
                <div class="sig-space"></div>
                <span class="sig-name">{{ $settlement['aktor'] ?? '________________' }}</span>
                <span class="sig-role">Bendahara</span>
            </div>
        </div>
    </div>

    <div class="footer-note">
        Dokumen ini diterbitkan oleh sistem {{ $kopNama }} pada {{ $tglCetak }} &bull; {{ $docNo }}
    </div>
</body>
</html>
