{{-- Template PDF khusus Neraca Skontro 2 sisi bersebelahan (dompdf landscape) --}}
@php
    $rupiah = fn ($n) => is_numeric($n) ? 'Rp '.number_format((float) $n, 0, ',', '.') : $n;
    $kopNama = config('koperasi.nama', 'KOPERASI KARYAWAN');
    $tglCetak = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y H:i');

    $skontro = $hasil['skontro'];
    $ratakanSisi = function (array $sisi): array {
        $rows = [];
        foreach ($sisi['kelompok'] ?? [] as $k) {
            $rows[] = ['type' => 'section', 'label' => $k['judul'], 'nilai' => null];
            foreach ($k['baris'] ?? [] as $b) {
                $rows[] = ['type' => 'data', 'label' => $b['label'], 'nilai' => $b['nilai']];
            }
            if (! empty($k['subtotal'])) {
                $rows[] = ['type' => 'subtotal', 'label' => $k['subtotal']['label'], 'nilai' => $k['subtotal']['nilai']];
            }
        }

        return $rows;
    };

    $kiriRows = $ratakanSisi($skontro['kiri']);
    $kananRows = $ratakanSisi($skontro['kanan']);
    $maxRows = max(count($kiriRows), count($kananRows));

    $kosong = ['type' => 'empty', 'label' => '', 'nilai' => null];
    while (count($kiriRows) < $maxRows) {
        $kiriRows[] = $kosong;
    }
    while (count($kananRows) < $maxRows) {
        $kananRows[] = $kosong;
    }

    $seimbang = (bool) ($skontro['seimbang'] ?? false);
    $selisih = (float) ($skontro['selisih'] ?? 0);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $judul }} - {{ $periodeLabel }}</title>
    <style>
        @page {
            margin: 1.8cm 1.8cm;
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
            font-size: 10pt;
            line-height: 1.5;
        }
        .kop-row { display: table; width: 100%; }
        .kop-logo { display: table-cell; vertical-align: middle; width: 80px; padding-right: 16px; }
        .kop-logo img, .kop-logo .logo-fallback { width: 72px; height: 72px; }
        .kop-logo .logo-fallback { background: #000; color: #fff; text-align: center; font-weight: bold; font-size: 13pt; line-height: 72px; }
        .kop-text { display: table-cell; vertical-align: middle; text-align: center; }
        .kop-nama { font-size: 17pt; font-weight: 700; margin: 0; letter-spacing: 1px; text-transform: uppercase; }
        .kop-unit { font-size: 12pt; font-weight: 700; margin: 2px 0; letter-spacing: 0.6px; text-transform: uppercase; }
        .kop-alamat { font-size: 9pt; color: #333; margin: 0; }
        .kop-garis { border-top: 2px solid #000; margin: 10px 0 14px 0; }
        .kop-judul { text-align: center; margin-bottom: 14px; }
        .dok-title { font-size: 14pt; font-weight: 700; margin: 0 0 3px 0; text-transform: uppercase; letter-spacing: 1.6px; text-decoration: underline; }
        .dok-number { font-size: 10pt; margin: 0; font-style: italic; }

        table.skontro { width: 100%; border-collapse: collapse; font-size: 9.5pt; table-layout: fixed; }
        table.skontro th, table.skontro td { border: 1px solid #000; padding: 5px 8px; }
        table.skontro th { text-transform: uppercase; font-size: 9pt; letter-spacing: 0.5px; background: #f0f0f0; }
        table.skontro th.text-right, table.skontro td.text-right { text-align: right; }
        table.skontro td.section { background: #e5e7eb; font-weight: 700; text-transform: uppercase; font-size: 8.5pt; letter-spacing: 0.4px; }
        table.skontro td.subtotal { background: #f3f4f6; font-weight: 700; }
        table.skontro td.empty { color: transparent; }
        table.skontro th.skontro-spacer,
        table.skontro td.skontro-spacer { border: none !important; background: transparent !important; padding: 0 !important; }
        table.skontro tfoot td:not(.skontro-spacer) {
            font-weight: 700;
            background: #e5e7eb;
            border-top: 2px solid #000;
            border-bottom: 3px double #000;
        }

        .status-box {
            margin-top: 12px;
            border: 1px solid #000;
            padding: 7px 10px;
            font-size: 9.5pt;
        }
        .status-box table { width: 100%; border-collapse: collapse; }
        .status-box td { border: none; padding: 0; }
        .status-right { text-align: right; font-weight: 700; }
        .notes { border-top: 1px solid #d1d5db; padding-top: 8px; font-size: 9pt; color: #374151; font-style: italic; line-height: 1.5; margin-top: 10px; }
        .footer-note { text-align: center; margin-top: 16px; padding-top: 8px; border-top: 1px solid #000; font-size: 8.5pt; font-style: italic; }
    </style>
</head>
<body>
    @include('partials.kop')

    <div class="kop-judul">
        <p class="dok-title">{{ $judul }}</p>
        <p class="dok-number">Per Tanggal: {{ $periodeLabel }}</p>
    </div>

    <table class="skontro">
        <colgroup>
            <col style="width: 31%;">
            <col style="width: 17.5%;">
            <col style="width: 3%;">
            <col style="width: 31%;">
            <col style="width: 17.5%;">
        </colgroup>
        <thead>
            <tr>
                <th>{{ $skontro['kiri']['judul'] }}</th>
                <th class="text-right">Nilai</th>
                <th class="skontro-spacer">&nbsp;</th>
                <th>{{ $skontro['kanan']['judul'] }}</th>
                <th class="text-right">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $maxRows; $i++)
                @php
                    $l = $kiriRows[$i];
                    $r = $kananRows[$i];
                    $renderSisi = function (array $item) use ($rupiah): array {
                        if ($item['type'] === 'section') {
                            return ['section', $item['label'], null];
                        }
                        if ($item['type'] === 'empty') {
                            return ['empty', "\u{00A0}", "\u{00A0}"];
                        }
                        $isSubtotal = $item['type'] === 'subtotal';
                        $nilaiText = (! $isSubtotal && (float) $item['nilai'] == 0.0) ? '-' : $rupiah($item['nilai']);

                        return [$isSubtotal ? 'subtotal' : '', $item['label'], $nilaiText];
                    };
                    [$lCls, $lLbl, $lVal] = $renderSisi($l);
                    [$rCls, $rLbl, $rVal] = $renderSisi($r);
                @endphp
                <tr>
                    @if ($l['type'] === 'section')
                        <td colspan="2" class="section">{{ $lLbl }}</td>
                    @else
                        <td class="{{ $lCls }}">{{ $lLbl }}</td>
                        <td class="text-right {{ $lCls }}">{{ $lVal }}</td>
                    @endif

                    <td class="skontro-spacer">&nbsp;</td>

                    @if ($r['type'] === 'section')
                        <td colspan="2" class="section">{{ $rLbl }}</td>
                    @else
                        <td class="{{ $rCls }}">{{ $rLbl }}</td>
                        <td class="text-right {{ $rCls }}">{{ $rVal }}</td>
                    @endif
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td>{{ $skontro['kiri']['total']['label'] }}</td>
                <td class="text-right">{{ $rupiah($skontro['kiri']['total']['nilai']) }}</td>
                <td class="skontro-spacer">&nbsp;</td>
                <td>{{ $skontro['kanan']['total']['label'] }}</td>
                <td class="text-right">{{ $rupiah($skontro['kanan']['total']['nilai']) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="status-box">
        <table>
            <tr>
                <td>
                    Total Aktiva: <strong>{{ $rupiah($skontro['kiri']['total']['nilai']) }}</strong>
                    &nbsp;&bull;&nbsp;
                    Total Kewajiban &amp; Modal: <strong>{{ $rupiah($skontro['kanan']['total']['nilai']) }}</strong>
                </td>
                <td class="status-right">
                    Selisih: {{ $rupiah($selisih) }} — {{ $seimbang ? 'SEIMBANG' : 'TIDAK SEIMBANG' }}
                </td>
            </tr>
        </table>
    </div>

    @if (! empty($hasil['catatan']))
        <div class="notes">{{ $hasil['catatan'] }}</div>
    @endif

    <div class="footer-note">
        Dokumen ini diterbitkan oleh sistem {{ $kopNama }} pada {{ $tglCetak }}
    </div>
</body>
</html>
