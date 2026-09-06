<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Bukti Peminjaman - {{ $pinjaman->pinjaman['anggota']['nama'] ?? $pinjaman->anggota->nama }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-break { page-break-before: always; }
        }
        .timeline-step::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 40px;
            bottom: -40px;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-step:last-child::before { display: none; }
        .timeline-step.done::before { background: #22c55e; }
        .timeline-step.current::before { background: #3b82f6; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="no-print bg-blue-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h1 class="text-lg font-semibold text-gray-800">Verifikasi Bukti Peminjaman</h1>
                <div class="flex gap-2">
                    <a href="{{ route('pinjaman.cetak-bukti', ['pinjaman' => $pinjaman->id, 'download' => 1]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition no-print">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download PDF
                    </a>
                    <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition no-print">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Cetak
                    </button>
                </div>
            </div>

            <div class="p-6 md:p-8">
                <div class="flex items-start gap-4 mb-8 border-b border-gray-200 pb-6">
                    <div class="w-20 h-20 flex-shrink-0 bg-gray-100 rounded-lg flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900">KOPERASI KARYAWAN</h2>
                        <p class="text-sm text-gray-500 mt-1">Verifikasi Resmi Bukti Peminjaman Dana</p>
                        <p class="text-sm text-gray-400 mt-2 font-mono">No. Referensi: BUKTI-PJM/{{ $pinjaman->anggota->no_anggota }}/{{ $pinjaman->id }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">VALID</span>
                        <p class="text-xs text-gray-400 mt-1">Diverifikasi: {{ now()->format('d M Y H:i') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <section>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Data Anggota</h3>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">No. Anggota</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->anggota->no_anggota }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">No. Karyawan</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->anggota->no_karyawan }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Nama Lengkap</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->anggota->nama }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Cabang</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->anggota->cabang }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Unit Bisnis</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->anggota->unit_bisnis }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Jabatan</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->anggota->jabatan }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Detail Pinjaman</h3>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Nominal Pinjaman</dt>
                                <dd class="font-bold text-gray-900 text-lg">{{ 'Rp ' . number_format($pinjaman->pinjaman['nominal'] ?? $pinjaman->nominal, 0, ',', '.') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Terbilang</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->pinjaman['terbilang'] ?? $pinjaman->terbilang }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Jangka Waktu</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->pinjaman['tenor_bulan'] ?? $pinjaman->tenor_bulan }} Bulan</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Suku Bunga</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->pinjaman['persentase_bunga'] ?? $pinjaman->persentase_bunga }}% per Bulan (Menurun)</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Tanggal Pengajuan</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->pinjaman['tanggal_pengajuan'] ?? $pinjaman->tanggal_pengajuan }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Tanggal Pencairan</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->pinjaman['tanggal_cair'] ?? $pinjaman->tanggal_pencairan ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Keperluan</dt>
                                <dd class="font-semibold text-gray-900">{{ $pinjaman->pinjaman['keperluan'] ?? $pinjaman->keperluan ?? '-' }}</dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <section class="mb-8">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Timeline Persetujuan</h3>
                    <div class="relative pl-10">
                        @foreach ($timeline as $index => $step)
                            <div class="timeline-step {{ $step['status'] }} relative pb-8 {{ $index === array_key_last($timeline) ? 'last' : '' }}">
                                <div class="absolute left-0 top-0 w-8 h-8 rounded-full border-3 flex items-center justify-center {{ $step['status'] === 'done' ? 'bg-green-500 border-green-500' : ($step['status'] === 'current' ? 'bg-blue-500 border-blue-500' : 'bg-white border-gray-300') }}">
                                    @if ($step['status'] === 'done')
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @elseif ($step['status'] === 'current')
                                        <svg class="w-4 h-4 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @else
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold text-gray-900">{{ $step['label'] }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $step['status'] === 'done' ? 'bg-green-100 text-green-800' : ($step['status'] === 'current' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600') }}">
                                            {{ $step['status'] === 'done' ? 'Selesai' : ($step['status'] === 'current' ? 'Sedang Proses' : 'Menunggu') }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500">{{ $step['date'] }}</p>
                                    <p class="text-sm text-gray-400">Oleh: {{ $step['user'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="mb-8">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Jadwal Angsuran</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 border-b border-gray-200">No</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 border-b border-gray-200">Jatuh Tempo</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600 border-b border-gray-200">Pokok</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600 border-b border-gray-200">Bunga</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600 border-b border-gray-200">Total Bayar</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-600 border-b border-gray-200">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($pinjaman->angsuran as $a)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-700">{{ $a->cicilan_ke }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $a->tanggal_jatuh_tempo }}</td>
                                        <td class="px-4 py-3 text-right text-gray-700 font-mono">{{ 'Rp ' . number_format($a->nominal_pokok, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-gray-700 font-mono">{{ 'Rp ' . number_format($a->nominal_bunga, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-gray-900 font-semibold font-mono">{{ 'Rp ' . number_format($a->total_bayar, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $a->status === 'lunas' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst(str_replace('_', ' ', $a->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 font-bold text-gray-900 border-t-2 border-gray-300">TOTAL</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900 border-t-2 border-gray-300 font-mono">{{ 'Rp ' . number_format($pinjaman->totals['pokok'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900 border-t-2 border-gray-300 font-mono">{{ 'Rp ' . number_format($pinjaman->totals['bunga'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900 border-t-2 border-gray-300 font-mono">{{ 'Rp ' . number_format($pinjaman->totals['angsuran'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 border-t-2 border-gray-300"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <section class="mb-8 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Rekening Tujuan Pencairan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Bank</p>
                            <p class="font-semibold text-gray-900">{{ $pinjaman->pinjaman['rekening']['bank'] ?? $pinjaman->rekening['bank'] ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">No. Rekening</p>
                            <p class="font-semibold text-gray-900">{{ $pinjaman->pinjaman['rekening']['no_rekening'] ?? $pinjaman->rekening['no_rekening'] ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Atas Nama</p>
                            <p class="font-semibold text-gray-900">{{ $pinjaman->pinjaman['rekening']['atas_nama'] ?? $pinjaman->rekening['atas_nama'] ?? '-' }}</p>
                        </div>
                    </div>
                </section>

                <div class="border-t border-gray-200 pt-6">
                    <p class="text-center text-sm text-gray-400">
                        Halaman verifikasi resmi Koperasi Karyawan &bull;
                        <a href="{{ $verificationUrl }}" class="text-blue-600 hover:underline font-mono text-xs">{{ parse_url($verificationUrl, PHP_URL_HOST) }}</a>
                        &bull; Dicetak pada {{ now()->format('d M Y H:i') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="no-print mt-6 text-center text-sm text-gray-400">
            <p>Scan QR code di PDF bukti peminjaman untuk membuka halaman ini</p>
        </div>
    </div>
</body>
</html>