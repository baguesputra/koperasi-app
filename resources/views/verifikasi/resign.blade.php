<?php
function rp($n) { return 'Rp '.number_format((float) ($n ?? 0), 0, ',', '.'); }
function tgl($t) { try { return \Carbon\Carbon::parse($t)->translatedFormat('d F Y'); } catch (\Throwable $e) { return $t ?? '-'; } }
$s = $settlement ?? [];
$mode = $s['mode'] ?? 'selesai';
$menunggu = ($anggota->status ?? '') === 'resign_menunggu' || $mode === 'menunggu_pelunasan_akhir';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Rincian Penyelesaian Resign - {{ $anggota->nama ?? '-' }}</title>
    <style>
        :root {
            --bg-base: #FAFAFA; --card-bg: #FFFFFF; --border: #E5E7EB;
            --text-primary: #111827; --text-secondary: #6B7280; --text-muted: #9CA3AF;
            --brand: #1E3A5F; --success: #16A34A; --warning: #D97706;
            --radius: 16px; --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        }
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: var(--bg-base); color: var(--text-primary); margin: 0; min-height: 100vh; line-height: 1.6; font-size: 15px; }
        .container { max-width: 760px; margin: 0 auto; padding: 20px 14px; }
        .hero { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 20px; box-shadow: var(--shadow); text-align: center; }
        .hero h1 { margin: 0; font-size: 22px; }
        .hero p { margin: 6px 0 0; color: var(--text-secondary); font-size: 14px; }
        .badge { display: inline-block; padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 999px; margin-top: 12px; }
        .badge.menunggu { background: #FEF3C7; color: var(--warning); }
        .badge.selesai { background: #DCFCE7; color: var(--success); }
        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
        .card-header { padding: 12px 20px; border-bottom: 1px solid var(--border); background: #FAFAFA; }
        .card-title { margin: 0; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-secondary); }
        .card-body { padding: 20px; }
        .dl { display: grid; grid-template-columns: 1fr; gap: 8px 0; }
        @media (min-width: 520px) { .dl { grid-template-columns: 170px 1fr; gap: 10px 14px; } }
        .dl dt { font-size: 13px; color: var(--text-secondary); }
        .dl dd { margin: 0; font-size: 14px; font-weight: 600; }
        .dl dd.big { font-size: 20px; }
        .note { background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 10px; padding: 12px 14px; font-size: 13px; color: #92400E; margin-top: 14px; }
        .footer { text-align: center; padding: 20px; color: var(--text-muted); font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <section class="hero">
            <h1>Rincian Penyelesaian Keanggotaan</h1>
            <p>{{ $anggota->nama ?? '-' }} &bull; {{ $anggota->no_karyawan ?? '-' }}</p>
            @if($menunggu)
                <span class="badge menunggu">MENUNGGU PELUNASAN AKHIR</span>
            @else
                <span class="badge selesai">SELESAI</span>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Data Anggota</h2></div>
            <div class="card-body">
                <dl class="dl">
                    <dt>Nama</dt><dd>{{ $anggota->nama ?? '-' }}</dd>
                    <dt>No. Karyawan</dt><dd>{{ $anggota->no_karyawan ?? '-' }}</dd>
                    <dt>Cabang</dt><dd>{{ $anggota->cabang ?? '-' }}</dd>
                    <dt>Tanggal Resign</dt><dd>{{ tgl($anggota->tanggal_resign ?? ($s['tanggal_proses'] ?? null)) }}</dd>
                    <dt>Alasan</dt><dd>{{ $anggota->alasan_resign ?? '-' }}</dd>
                </dl>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Rincian Simpanan &amp; Pelunasan</h2></div>
            <div class="card-body">
                <dl class="dl">
                    <dt>Simpanan Pokok</dt><dd>{{ rp($s['simpanan_pokok_total'] ?? 0) }}</dd>
                    <dt>Simpanan Wajib</dt><dd>{{ rp($s['simpanan_wajib_total'] ?? 0) }}</dd>
                    <dt>Dana Sosial (hangus)</dt><dd>{{ rp($s['dana_sosial_hangus'] ?? 0) }}</dd>
                    <dt>Total Tagihan</dt><dd>{{ rp($s['tagihan_pelunasan'] ?? 0) }}</dd>
                    <dt>Terpakai Pelunasan</dt><dd>{{ rp($s['terpakai_pelunasan'] ?? (($s['alokasi_dari_pokok'] ?? 0) + ($s['alokasi_dari_wajib'] ?? 0))) }}</dd>
                    @if($menunggu)
                        <dt>Sisa Cicilan Akhir</dt><dd class="big">{{ rp($s['shortfall'] ?? 0) }}</dd>
                        <dt>Jatuh Tempo</dt><dd>{{ tgl($s['cicilan_akhir_jatuh_tempo'] ?? null) }}</dd>
                    @else
                        <dt>Total Dikembalikan</dt><dd class="big">{{ rp($s['total_dikembalikan'] ?? 0) }}</dd>
                    @endif
                    <dt>Dokumen</dt><dd>{{ $s['doc_no'] ?? '-' }}</dd>
                </dl>
                @if($menunggu)
                    <div class="note">Mohon selesaikan sisa cicilan akhir sebelum tanggal jatuh tempo melalui Bendahara Koperasi. Halaman ini berlaku sampai {{ tgl($kedaluwarsa ?? null) }}.</div>
                @else
                    <div class="note">Terhitung mulai tanggal resign di atas, yang bersangkutan bukan lagi anggota koperasi. Halaman ini berlaku sampai {{ tgl($kedaluwarsa ?? null) }}.</div>
                @endif
            </div>
        </section>

        <div class="footer">Dokumen diterbitkan oleh sistem koperasi &bull; {{ now()->format('d M Y H:i') }}</div>
    </div>
</body>
</html>
