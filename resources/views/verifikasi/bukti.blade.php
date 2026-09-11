<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Verifikasi Bukti Peminjaman - {{ $pinjaman->anggota->nama }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #FAFAFA;
            --card-bg: #FFFFFF;
            --border: #E5E7EB;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
            --brand: #1E3A5F;
            --success: #16A34A;
            --info: #2563EB;
            --warning: #D97706;
            --radius: 16px;
            --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --space-1: 4px;
            --space-2: 8px;
            --space-3: 12px;
            --space-4: 16px;
            --space-5: 20px;
            --space-6: 24px;
            --space-8: 32px;
        }

        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            margin: 0;
            min-height: 100vh;
            line-height: 1.6;
            font-size: clamp(13px, 1.6vw, 15px);
            -webkit-font-smoothing: antialiased;
        }

        /* Page watermark */
        body::before {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: min(400px, 80vw);
            height: min(400px, 80vw);
            background: url('/images/logo.png') center / contain no-repeat;
            opacity: 0.08;
            pointer-events: none;
            z-index: -1;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: clamp(16px, 3vw, 32px) clamp(12px, 2.5vw, 20px);
            position: relative;
            z-index: 1;
        }

        /* Hero */
        .hero {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: clamp(20px, 3vw, 32px);
            margin-bottom: var(--space-6);
            box-shadow: var(--shadow);
        }
        .hero-inner {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: clamp(12px, 2vw, 20px);
        }
        @media (min-width: 560px) {
            .hero-inner {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
            }
        }
        .hero-brand {
            display: flex;
            align-items: center;
            gap: clamp(12px, 2vw, 16px);
            flex: 1;
            min-width: 0;
        }
        .hero-logo {
            width: clamp(48px, 6vw, 56px);
            height: clamp(48px, 6vw, 56px);
            border-radius: clamp(10px, 1.5vw, 14px);
            object-fit: cover;
            background: var(--bg-base);
            padding: 4px;
            flex-shrink: 0;
        }
        .hero-text h1 {
            margin: 0;
            font-size: clamp(20px, 2.8vw, 24px);
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }
        .hero-text p {
            margin: clamp(4px, 0.8vw, 6px) 0 0;
            font-size: clamp(13px, 1.5vw, 14px);
            color: var(--text-secondary);
        }
        .hero-meta {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }
        @media (max-width: 559px) {
            .hero-meta { align-items: stretch; text-align: left; }
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: clamp(6px, 1vw, 8px) clamp(12px, 2vw, 16px);
            font-size: clamp(11px, 1.3vw, 12px);
            font-weight: 700;
            border-radius: 999px;
            background: #DCFCE7;
            color: var(--success);
            white-space: nowrap;
        }
        .hero-ref {
            font-size: clamp(11px, 1.3vw, 12px);
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
            word-break: break-all;
        }
        .hero-verified {
            font-size: clamp(11px, 1.3vw, 12px);
            color: var(--text-muted);
        }

        /* Card */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: var(--space-6);
        }
        .card-header {
            padding: clamp(12px, 2vw, 16px) clamp(16px, 2.5vw, 24px);
            border-bottom: 1px solid var(--border);
            background: #FAFAFA;
        }
        .card-title {
            margin: 0;
            font-size: clamp(12px, 1.4vw, 13px);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-secondary);
        }
        .card-body {
            padding: clamp(16px, 2.5vw, 24px);
        }

        /* Grid */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-5);
        }
        @media (min-width: 700px) {
            .grid-2 { grid-template-columns: 1fr 1fr; gap: var(--space-6); }
        }

        /* Definition List - responsive stacked */
        .dl {
            display: grid;
            grid-template-columns: 1fr;
            gap: clamp(6px, 1vw, 8px) 0;
        }
        @media (min-width: 520px) {
            .dl {
                grid-template-columns: clamp(120px, 20vw, 150px) 1fr;
                gap: clamp(8px, 1.2vw, 12px) clamp(12px, 2vw, 16px);
            }
        }
        .dl dt {
            font-size: clamp(12px, 1.4vw, 13px);
            color: var(--text-secondary);
            font-weight: 500;
        }
        .dl dd {
            margin: 0;
            font-size: clamp(13px, 1.5vw, 14px);
            font-weight: 600;
            color: var(--text-primary);
            word-break: break-word;
        }
        .dl dd.mono { font-family: 'JetBrains Mono', monospace; font-size: clamp(12px, 1.4vw, 13px); }
        .dl dd.text-lg {
            font-size: clamp(18px, 2.5vw, 22px);
            font-weight: 700;
            line-height: 1.3;
        }

        /* Timeline Horizontal - Flex-based with precise connectors */
        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 8px 0 clamp(16px, 2.5vw, 24px);
        }
        @media (max-width: 699px) {
            .timeline {
                flex-direction: column;
                align-items: flex-start;
                gap: 0;
                padding: 8px 0 clamp(20px, 3vw, 32px);
            }
        }
        /* Base line - single continuous line */
        .timeline::before {
            content: '';
            position: absolute;
            top: 28px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }
        @media (max-width: 699px) {
            .timeline::before {
                top: 0;
                left: 26px;
                width: 2px;
                height: 100%;
                right: auto;
            }
        }
        .timeline-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 0 6px;
        }
        @media (max-width: 699px) {
            .timeline-step {
                flex: none;
                width: 100%;
                text-align: left;
                padding-left: 58px;
                padding-bottom: clamp(16px, 3vw, 24px);
            }
            .timeline-step:last-child { padding-bottom: 0; }
        }
        /* Colored connector segments - each step colors the line TO the next step */
        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 28px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }
        @media (max-width: 699px) {
            .timeline-step:not(:last-child)::after {
                top: 26px;
                left: 26px;
                right: auto;
                width: calc(100% - 52px);
                height: 2px;
            }
        }
        .timeline-step.done:not(:last-child)::after { background: var(--success); }
        .timeline-step.current:not(:last-child)::after { background: var(--info); }
        .step-dot {
            width: clamp(36px, 4.5vw, 40px);
            height: clamp(36px, 4.5vw, 40px);
            border-radius: 50%;
            border: 3px solid;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--card-bg);
            margin-bottom: 8px;
            transition: all 0.2s ease;
            position: relative;
            z-index: 2;
        }
        @media (max-width: 699px) { .step-dot { position: absolute; left: 8px; top: 6px; margin-bottom: 0; } }
        .step-dot.done {
            border-color: var(--success);
            background: var(--success);
            width: clamp(40px, 5vw, 44px);
            height: clamp(40px, 5vw, 44px);
            margin-top: -2px;
        }
        .step-dot.current { border-color: var(--info); background: var(--info); animation: pulse 2s infinite; }
        .step-dot.pending { border-color: var(--border); background: var(--card-bg); }
        .step-dot svg { width: clamp(16px, 2vw, 18px); height: clamp(16px, 2vw, 18px); }
        .step-dot.done svg { stroke: white; }
        .step-dot.current svg { stroke: white; }
        .step-dot.pending svg { stroke: var(--text-muted); }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); } 50% { box-shadow: 0 0 0 8px rgba(37, 99, 235, 0); } }
        .step-label {
            font-size: clamp(12px, 1.4vw, 13px);
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
            line-height: 1.3;
        }
        @media (max-width: 699px) { .step-label { white-space: normal; max-width: 100%; } }
        .timeline-step.done .step-label { color: var(--success); }
        .timeline-step.current .step-label { color: var(--info); }
        .step-status {
            display: inline-block;
            padding: 2px clamp(6px, 1vw, 8px);
            font-size: clamp(10px, 1.2vw, 11px);
            font-weight: 600;
            border-radius: 999px;
            margin-bottom: 4px;
            white-space: nowrap;
        }
        .step-status.done { background: #DCFCE7; color: var(--success); }
        .step-status.current { background: #DBEAFE; color: var(--info); }
        .step-status.pending { background: #F3F4F6; color: var(--text-muted); }
        .step-date {
            font-size: clamp(11px, 1.3vw, 12px);
            color: var(--text-secondary);
            margin-bottom: 2px;
            white-space: nowrap;
        }
        .step-user {
            font-size: clamp(11px, 1.3vw, 12px);
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Rekening Card */
        .rekening-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-3);
        }
        @media (min-width: 560px) {
            .rekening-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: var(--space-4);
            }
        }
        .rekening-item {
            padding: clamp(12px, 2vw, 16px);
            background: #FAFAFA;
            border: 1px solid var(--border);
            border-radius: 10px;
        }
        .rekening-label {
            font-size: clamp(10px, 1.2vw, 11px);
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
        }
        .rekening-value {
            font-size: clamp(13px, 1.5vw, 14px);
            font-weight: 600;
            color: var(--text-primary);
            word-break: break-all;
        }

        /* Table - responsive with horizontal scroll */
        .table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: calc(-1 * var(--space-4)) calc(-1 * var(--space-4)) 0;
            padding: 0 var(--space-4) var(--space-4);
        }
        .table {
            width: 100%;
            min-width: 580px;
            border-collapse: collapse;
            font-size: clamp(12px, 1.4vw, 13px);
        }
        .table th {
            text-align: left;
            padding: clamp(10px, 1.5vw, 12px) clamp(12px, 1.8vw, 16px);
            font-weight: 600;
            font-size: clamp(11px, 1.3vw, 12px);
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 2px solid var(--border);
            background: var(--card-bg);
            position: sticky;
            left: 0;
            z-index: 2;
            white-space: nowrap;
        }
        .table th:first-child { position: sticky; left: 0; z-index: 3; background: var(--card-bg); }
        .table th.num, .table td.num { text-align: right; }
        .table th.center, .table td.center { text-align: center; }
        .table td {
            padding: clamp(10px, 1.5vw, 12px) clamp(12px, 1.8vw, 16px);
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            white-space: nowrap;
        }
        .table tbody tr { transition: background 0.1s; }
        .table tbody tr:hover { background: #FAFAFA; }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tfoot td {
            padding: clamp(10px, 1.5vw, 12px) clamp(12px, 1.8vw, 16px);
            border-top: 2px solid var(--border);
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
        }
        .table .mono { font-family: 'JetBrains Mono', monospace; }

        /* Footer */
        .footer {
            text-align: center;
            padding: clamp(16px, 2.5vw, 24px);
            color: var(--text-muted);
            font-size: clamp(11px, 1.3vw, 12px);
            border-top: 1px solid var(--border);
            margin-top: var(--space-2);
        }
        .footer a { color: var(--info); text-decoration: none; font-family: 'JetBrains Mono', monospace; }
        .footer a:hover { text-decoration: underline; }

        /* Touch targets */
        @media (hover: none) and (pointer: coarse) {
            .step-dot { min-width: 44px; min-height: 44px; }
        }

        /* Print */
        @media print {
            body::before { display: none; }
            body { background: white; font-size: 12pt; }
            .container { padding: 0; max-width: none; }
            .hero { border: none; border-bottom: 2px solid var(--text-primary); border-radius: 0; box-shadow: none; padding: 24px; }
            .card { box-shadow: none; border-color: var(--border); page-break-inside: avoid; border-radius: 0; }
            .table-wrap { margin: 0; padding: 0; overflow: visible; }
            .table { min-width: 0; }
            .table th { background: white; position: static; }
            .timeline::before, .timeline-step:not(:last-child)::after { background: var(--text-primary) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .step-dot { border-color: var(--text-primary) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .step-dot.done { background: var(--text-primary) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .step-dot.current { background: var(--text-primary) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .footer { border-top: 1px solid var(--text-primary); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Hero -->
        <section class="hero">
            <div class="hero-inner">
                <div class="hero-brand">
                    <img src="/images/logo.png" alt="" class="hero-logo" onerror="this.style.display='none'">
                    <div class="hero-text">
                        <h1>{{ config('koperasi.nama', 'KOPERASI KARYAWAN') }}</h1>
                        <p>{{ config('koperasi.unit', 'KARYA MANDIRI DUTA MALL BANJARMASIN') }}</p>
                        <p>Verifikasi Resmi Bukti Peminjaman Dana</p>
                    </div>
                </div>
                <div class="hero-meta">
                    <span class="badge">VALID</span>
                    <div class="hero-ref">No. Referensi: {{ $pinjaman->nomor_dokumen ?? '-' }}</div>
                    <div class="hero-verified">Diverifikasi: {{ now()->format('d M Y H:i') }}</div>
                </div>
            </div>
        </section>

        <!-- Timeline -->
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Timeline Persetujuan</h2>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @foreach ($timeline as $index => $step)
                        <div class="timeline-step {{ $step['status'] }}">
                            <div class="step-dot {{ $step['status'] }}">
                                @if ($step['status'] === 'done')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @elseif ($step['status'] === 'current')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </div>
                            <div class="step-label">{{ $step['label'] }}</div>
                            <span class="step-status {{ $step['status'] }}">
                                {{ $step['status'] === 'done' ? 'Selesai' : ($step['status'] === 'current' ? 'Sedang Proses' : 'Menunggu') }}
                            </span>
                            <div class="step-date">{{ $step['date'] }}</div>
                            <div class="step-user">Oleh: {{ $step['user'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Data Anggota & Detail Pinjaman -->
        <div class="grid-2">
            <section class="card">
                <div class="card-header"><h3 class="card-title">Data Anggota</h3></div>
                <div class="card-body">
                    <dl class="dl">
                        <dt>No. Karyawan</dt>
                        <dd>{{ $pinjaman->anggota->no_karyawan }}</dd>
                        <dt>Nama Lengkap</dt>
                        <dd>{{ $pinjaman->anggota->nama }}</dd>
                        <dt>Cabang</dt>
                        <dd>{{ $pinjaman->anggota->cabang }}</dd>
                        <dt>Unit Bisnis</dt>
                        <dd>{{ $pinjaman->anggota->unit_bisnis }}</dd>
                        <dt>Jabatan</dt>
                        <dd>{{ $pinjaman->anggota->jabatan }}</dd>
                    </dl>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h3 class="card-title">Detail Pinjaman</h3></div>
                <div class="card-body">
                    <dl class="dl">
                        <dt>Nominal Pinjaman</dt>
                        <dd class="mono text-lg">{{ 'Rp ' . number_format($pinjaman->nominal, 0, ',', '.') }}</dd>
                        <dt>Terbilang</dt>
                        <dd>{{ $pinjaman->terbilang ?? \App\Helpers\TerbilangHelper::angkaKeTerbilang($pinjaman->nominal) }}</dd>
                        <dt>Jangka Waktu</dt>
                        <dd>{{ $pinjaman->tenor_bulan }} Bulan</dd>
                        <dt>Suku Bunga</dt>
                        <dd>{{ $pinjaman->persentase_bunga }}% per Bulan (Menurun)</dd>
                        <dt>Tanggal Pengajuan</dt>
                        <dd>{{ $pinjaman->tanggal_pengajuan?->format('d M Y') }}</dd>
                        <dt>Tanggal Pencairan</dt>
                        <dd>{{ $pinjaman->tanggal_pencairan?->format('d M Y') ?? '-' }}</dd>
                        <dt>Keperluan</dt>
                        <dd>{{ $pinjaman->keperluan ?? '-' }}</dd>
                    </dl>
                </div>
            </section>
        </div>

        <!-- Rekening Tujuan -->
        <section class="card">
            <div class="card-header"><h3 class="card-title">Rekening Tujuan Pencairan</h3></div>
            <div class="card-body">
                <div class="rekening-grid">
                    <div class="rekening-item">
                        <div class="rekening-label">Bank</div>
                        <div class="rekening-value">{{ $pinjaman->snapshot_bank ?? '-' }}</div>
                    </div>
                    <div class="rekening-item">
                        <div class="rekening-label">No. Rekening</div>
                        <div class="rekening-value">{{ $pinjaman->snapshot_no_rekening ?? '-' }}</div>
                    </div>
                    <div class="rekening-item">
                        <div class="rekening-label">Atas Nama</div>
                        <div class="rekening-value">{{ $pinjaman->snapshot_atas_nama ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Jadwal Angsuran -->
        @php
            $totalPokok = $pinjaman->angsuran->sum('nominal_pokok');
            $totalBunga = $pinjaman->angsuran->sum('nominal_bunga');
            $totalAngsuran = $pinjaman->angsuran->sum('total_bayar');
        @endphp
        <section class="card">
            <div class="card-header"><h3 class="card-title">Jadwal Angsuran</h3></div>
            <div class="card-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th style="width: 160px;">Jatuh Tempo</th>
                                <th class="num" style="width: 160px;">Pokok</th>
                                <th class="num" style="width: 160px;">Bunga</th>
                                <th class="num" style="width: 180px;">Total Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pinjaman->angsuran as $a)
                                <tr>
                                    <td>{{ $a->cicilan_ke }}</td>
                                    <td>{{ $a->tanggal_jatuh_tempo?->format('d M Y') }}</td>
                                    <td class="num mono">{{ 'Rp ' . number_format($a->nominal_pokok, 0, ',', '.') }}</td>
                                    <td class="num mono">{{ 'Rp ' . number_format($a->nominal_bunga, 0, ',', '.') }}</td>
                                    <td class="num mono">{{ 'Rp ' . number_format($a->total_bayar, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">TOTAL</td>
                                <td class="num mono">{{ 'Rp ' . number_format($totalPokok, 0, ',', '.') }}</td>
                                <td class="num mono">{{ 'Rp ' . number_format($totalBunga, 0, ',', '.') }}</td>
                                <td class="num mono">{{ 'Rp ' . number_format($totalAngsuran, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <p>Halaman verifikasi resmi Koperasi Karyawan &bull;
            <a href="{{ $verificationUrl }}">{{ parse_url($verificationUrl, PHP_URL_HOST) }}</a>
            &bull; Dicetak pada {{ now()->format('d M Y H:i') }}</p>
        </footer>
    </div>
</body>
</html>