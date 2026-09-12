{{-- Template PDF generik untuk semua laporan (dompdf), gaya disamakan dengan bukti pinjam --}}
@php
    $rupiah = fn ($n) => is_numeric($n) ? 'Rp '.number_format((float) $n, 0, ',', '.') : $n;
    $landscape = count($hasil['kolom']) > 6;
    $kopNama = config('koperasi.nama', 'KOPERASI KARYAWAN');
    $tglCetak = \Carbon\Carbon::now()->translatedFormat('d F Y H:i');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $judul }} - {{ $periodeLabel }}</title>
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
            font-size: {{ $landscape ? '10pt' : '12pt' }};
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
        .kop-garis { border-top: 2px solid #000; margin: 10px 0 16px 0; }
        .kop-judul { text-align: center; margin-bottom: 18px; }
        .dok-title { font-size: 14pt; font-weight: 700; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 1.6px; text-decoration: underline; }
        .dok-number { font-size: 10pt; margin: 0; font-style: italic; }
        table.laporan { width: 100%; border-collapse: collapse; font-size: {{ $landscape ? '9.5pt' : '11pt' }}; }
        table.laporan th, table.laporan td { border: 1px solid #000; padding: 6px 8px; }
        table.laporan th { text-transform: uppercase; font-size: 9.5pt; letter-spacing: 0.5px; background: #f0f0f0; }
        table.laporan td.text-right, table.laporan th.text-right { text-align: right; }
        table.laporan td.text-center { text-align: center; }
        table.laporan tfoot td { font-weight: 700; border-top: 2px solid #000; }
        table.laporan tr.section td { background: #e5e7eb; font-weight: 700; text-transform: uppercase; font-size: 9.5pt; letter-spacing: 0.5px; }
        table.laporan tr.subtotal td { background: #f0f0f0; font-weight: 700; }
        .info { display: table; width: 100%; table-layout: fixed; margin-top: 10px; }
        .info > div { display: table-row; }
        .info > div > span { display: table-cell; padding: 5px 12px 5px 0; vertical-align: top; }
        .lbl { width: 60%; color: #333; }
        .titik { width: 3%; }
        .val { width: 37%; font-weight: 700; text-align: right; }
        .notes { border-left: 3px solid #000; padding: 8px 0 8px 14px; font-size: 10.5pt; font-style: italic; line-height: 1.6; margin-top: 12px; }
        .kosong { text-align: center; font-style: italic; margin-top: 24px; }
        .footer-note { text-align: center; margin-top: 20px; padding-top: 8px; border-top: 1px solid #000; font-size: 9pt; font-style: italic; }
    </style>
</head>
<body>
    @include('partials.kop')

    <div class="kop-judul">
        <p class="dok-title">{{ $judul }}</p>
        <p class="dok-number">Periode: {{ $periodeLabel }}</p>
    </div>

    @if (count($hasil['rows']) === 0)
        <p class="kosong">Tidak ada data pada periode ini.</p>
    @else
        <table class="laporan">
            <thead>
                <tr>
                    @foreach ($hasil['kolom'] as $i => $label)
                        <th class="{{ in_array($i, $hasil['rataKanan']) ? 'text-right' : '' }}">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($hasil['rows'] as $ri => $row)
                    @if (($hasil['gayaBaris'][$ri] ?? null) === 'section')
                        <tr class="section">
                            <td colspan="{{ count($hasil['kolom']) }}">{{ $row[0] }}</td>
                        </tr>
                    @else
                        <tr class="{{ ($hasil['gayaBaris'][$ri] ?? null) === 'subtotal' ? 'subtotal' : '' }}">
                            @foreach ($row as $i => $cell)
                                <td class="{{ in_array($i, $hasil['rataKanan']) ? 'text-right' : '' }}">{{ $cell === null ? '' : (is_numeric($cell) && in_array($i, $hasil['rataKanan']) ? $rupiah($cell) : $cell) }}</td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
            @if ($hasil['totals'])
                <tfoot>
                    <tr>
                        @foreach ($hasil['totals'] as $i => $cell)
                            <td class="{{ in_array($i, $hasil['rataKanan']) ? 'text-right' : '' }}">
                                {{ $cell === null ? '' : (is_numeric($cell) && in_array($i, $hasil['rataKanan']) ? $rupiah($cell) : $cell) }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif

    @if (! empty($hasil['ringkasan']))
        <div class="info">
            @foreach ($hasil['ringkasan'] as [$label, $nilai])
                <div><span class="lbl">{{ $label }}</span><span class="titik">:</span><span class="val">{{ $nilai }}</span></div>
            @endforeach
        </div>
    @endif

    @if ($hasil['catatan'])
        <div class="notes">{{ $hasil['catatan'] }}</div>
    @endif

    <div class="footer-note">
        Dokumen ini diterbitkan oleh sistem {{ $kopNama }} pada {{ $tglCetak }}
    </div>
</body>
</html>
