{{-- Template PDF generik untuk semua laporan (dompdf) --}}
@php
    $rupiah = fn ($n) => is_numeric($n) ? 'Rp '.number_format((float) $n, 0, ',', '.') : $n;
    $landscape = count($hasil['kolom']) > 6;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1e293b; font-size: {{ $landscape ? '9px' : '11px' }}; }
        .kop { text-align: center; border-bottom: 1px solid #cbd5e1; padding-bottom: 10px; margin-bottom: 14px; }
        .kop h2 { margin: 0; font-size: 17px; }
        .kop p { margin: 3px 0 0; color: #475569; font-weight: bold; }
        .kop small { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 4px 6px; font-size: 0.92em; text-align: left; }
        td { border: 1px solid #e2e8f0; padding: 3px 6px; vertical-align: top; }
        .kanan { text-align: right; white-space: nowrap; }
        tfoot td { border-top: 2px solid #cbd5e1; font-weight: bold; background: #f8fafc; }
        .ringkasan { margin-top: 12px; width: 60%; }
        .ringkasan td { border: none; padding: 2px 0; }
        .ringkasan td:first-child { color: #475569; }
        .ringkasan td:last-child { text-align: right; font-weight: bold; border-top: 1px solid #e2e8f0; }
        .catatan { font-size: 0.88em; font-style: italic; color: #64748b; margin-top: 10px; }
        footer { text-align: center; font-size: 8.5px; color: #94a3b8; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="kop">
        <h2>KOPERASI KARYAWAN</h2>
        <p>{{ $judul }}</p>
        <small>Periode: {{ $periodeLabel }}</small>
    </div>

    @if (count($hasil['rows']) === 0)
        <p style="text-align:center; color:#64748b; margin-top:24px;">Tidak ada data pada periode ini.</p>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($hasil['kolom'] as $i => $label)
                        <th class="{{ in_array($i, $hasil['rataKanan']) ? 'kanan' : '' }}">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($hasil['rows'] as $row)
                    <tr>
                        @foreach ($row as $i => $cell)
                            <td class="{{ in_array($i, $hasil['rataKanan']) ? 'kanan' : '' }}">{{ is_numeric($cell) && in_array($i, $hasil['rataKanan']) ? $rupiah($cell) : $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            @if ($hasil['totals'])
                <tfoot>
                    <tr>
                        @foreach ($hasil['totals'] as $i => $cell)
                            <td class="{{ in_array($i, $hasil['rataKanan']) ? 'kanan' : '' }}">
                                {{ $cell === null ? '' : (is_numeric($cell) && in_array($i, $hasil['rataKanan']) ? $rupiah($cell) : $cell) }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif

    @if (! empty($hasil['ringkasan']))
        <table class="ringkasan">
            @foreach ($hasil['ringkasan'] as [$label, $nilai])
                <tr><td>{{ $label }}</td><td>{{ $nilai }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($hasil['catatan'])
        <p class="catatan">{{ $hasil['catatan'] }}</p>
    @endif

    <footer>Dokumen ini dicetak otomatis oleh sistem koperasi pada {{ now()->translatedFormat('d F Y H:i') }}</footer>
</body>
</html>
