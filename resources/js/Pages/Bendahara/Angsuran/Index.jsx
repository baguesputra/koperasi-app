import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { TrendingUp, Wallet, Calendar, Check, ChevronLeft, ChevronRight, ChevronDown, Search, X, RefreshCw, MapPin, AlarmClock } from 'lucide-react';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

export default function Index({ bulan, daftarAngsuran, cabangAktif, daftarCabang, tagihanPerCabang, totalTagihanBulanIni, totalKeuntunganBulanIni, totalKeuntunganKeseluruhan }) {
    const [terpilih, setTerpilih] = useState([]);
    const [processing, setProcessing] = useState(false);
    const [cari, setCari] = useState('');
    const [rincianTerbuka, setRincianTerbuka] = useState(false);

    useEffect(() => {
        setCari('');
        setTerpilih([]);
    }, [bulan, cabangAktif]);

    function ubahBulan(nilaiBaru) {
        router.get(route('bendahara.angsuran.index'), { bulan: nilaiBaru, cabang: cabangAktif }, { preserveState: true });
    }

    function geserBulan(delta) {
        const [tahun, bulanAngka] = bulan.split('-').map(Number);
        const d = new Date(tahun, bulanAngka - 1 + delta, 1);
        ubahBulan(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`);
    }

    const labelBulan = useMemo(() => {
        const [tahun, bulanAngka] = bulan.split('-').map(Number);
        return new Date(tahun, bulanAngka - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    }, [bulan]);

    const bulanIni = new Date();
    const kunciBulanIni = `${bulanIni.getFullYear()}-${String(bulanIni.getMonth() + 1).padStart(2, '0')}`;

    function pindahTab(cabang) {
        router.get(route('bendahara.angsuran.index'), { bulan, cabang }, { preserveState: true });
    }

    function resetFilter() {
        setCari('');
        router.get(route('bendahara.angsuran.index'), { bulan: kunciBulanIni, cabang: '' }, { preserveState: true });
    }

    const kataCari = cari.trim().toLowerCase();
    const tampil = useMemo(
        () => daftarAngsuran.filter((a) =>
            !kataCari || a.nama.toLowerCase().includes(kataCari) || (a.no_karyawan ?? '').toLowerCase().includes(kataCari)
        ),
        [daftarAngsuran, kataCari]
    );

    const semuaTampilTerpilih = tampil.length > 0 && tampil.every((a) => terpilih.includes(a.id));

    function toggleSatu(id) {
        setTerpilih((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    }

    function toggleSemua() {
        setTerpilih((prev) => semuaTampilTerpilih
            ? prev.filter((id) => !tampil.some((a) => a.id === id))
            : [...new Set([...prev, ...tampil.map((a) => a.id)])]);
    }

    function konfirmasi() {
        setProcessing(true);
        router.post(
            route('bendahara.angsuran.konfirmasi'),
            { angsuran_ids: terpilih },
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => setTerpilih([]),
                onFinish: () => setProcessing(false),
            })
        );
    }

    const totalTerpilih = daftarAngsuran
        .filter((a) => terpilih.includes(a.id))
        .reduce((sum, a) => sum + a.total_bayar, 0);

    const jumlahTerlambat = daftarAngsuran.filter((a) => a.terlambat).length;
    const filterAktif = Boolean(kataCari || bulan !== kunciBulanIni || cabangAktif);

    const tab = [
        { key: '', label: 'Semua Cabang', nominal: totalTagihanBulanIni },
        ...daftarCabang.map((c) => ({
            key: c,
            label: c,
            nominal: tagihanPerCabang[c] ?? 0,
        })),
    ];

    return (
        <AppLayout>
            <Head title="Konfirmasi Angsuran" />

            <PageHeader title="Konfirmasi Angsuran" subtitle="Tandai angsuran yang sudah dipotong dari gaji anggota" />

            <div className="rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-5 py-4 mb-3 shadow-md shadow-brand-navy/20">
                <div className="flex items-center gap-3">
                    <span className="w-11 h-11 rounded-2xl bg-white/15 inline-flex items-center justify-center shrink-0">
                        <Calendar size={22} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-white/70">Total Tagihan {labelBulan}</p>
                        <p className="text-2xl font-bold tabular-nums leading-tight">{formatRupiah(totalTagihanBulanIni)}</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setRincianTerbuka((v) => !v)}
                        aria-expanded={rincianTerbuka}
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full px-3 py-1.5 transition-colors shrink-0"
                    >
                        Rincian
                        <ChevronDown size={14} className={`transition-transform ${rincianTerbuka ? 'rotate-180' : ''}`} />
                    </button>
                </div>
                {rincianTerbuka && (
                    <dl className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-white/15 text-sm">
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Untung bulan ini</dt>
                            <dd className="font-bold tabular-nums">{formatRupiah(totalKeuntunganBulanIni)}</dd>
                        </div>
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Untung keseluruhan</dt>
                            <dd className="font-bold tabular-nums">{formatRupiah(totalKeuntunganKeseluruhan)}</dd>
                        </div>
                        {Object.entries(tagihanPerCabang ?? {}).map(([c, nominal]) => (
                            <div key={c} className="rounded-xl bg-white/10 px-3 py-2">
                                <dt className="text-xs text-white/70">{c}</dt>
                                <dd className="font-bold tabular-nums">{formatRupiah(nominal)}</dd>
                            </div>
                        ))}
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Jumlah angsuran</dt>
                            <dd className="font-bold tabular-nums">{daftarAngsuran.length} tagihan</dd>
                        </div>
                    </dl>
                )}
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Terlambat" value={jumlahTerlambat} icon={AlarmClock} tone="rose" />
                </div>
                <div className="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Menunggu" value={daftarAngsuran.length} icon={Wallet} tone="amber" />
                </div>
                <div className="col-span-2 lg:col-span-1 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md rounded-xl">
                    <StatWidget compact label="Untung Bulan Ini" value={formatRupiah(totalKeuntunganBulanIni)} icon={TrendingUp} tone="green" />
                </div>
            </div>

            <div className="flex items-center gap-1.5 bg-slate-100 p-1 rounded-full w-fit max-w-full overflow-x-auto mb-4">
                {tab.map((t) => {
                    const aktif = cabangAktif === t.key;
                    return (
                        <button
                            key={t.key}
                            onClick={() => pindahTab(t.key)}
                            aria-pressed={aktif}
                            className={`inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-full whitespace-nowrap transition-all shrink-0 ${
                                aktif ? 'bg-white text-slate-800 shadow-md' : 'text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            <MapPin size={15} />
                            {t.label}
                            <span className="text-xs tabular-nums text-slate-400">
                                {formatRupiahSingkat(t.nominal)}
                            </span>
                        </button>
                    );
                })}
            </div>

            <Card padding="sm" className="shadow-md border-slate-200/70 mb-4">
                <div className="pb-3 mb-1 border-b border-slate-100">
                    <div className="flex flex-col md:flex-row md:items-center gap-2">
                        <div className="flex items-center gap-1.5 min-w-0">
                            <p className="text-sm text-slate-500 truncate">
                                Jatuh tempo <span className="font-bold text-slate-800">{labelBulan}</span>
                                <span className="text-slate-400"> • {tampil.length}{kataCari && daftarAngsuran.length > 0 ? ` dari ${daftarAngsuran.length}` : ''} belum dikonfirmasi</span>
                            </p>
                            {bulan !== kunciBulanIni && (
                                <button
                                    onClick={() => ubahBulan(kunciBulanIni)}
                                    className="text-xs font-bold text-brand-green-dark bg-brand-green-light rounded-full px-2.5 py-1 hover:bg-brand-green/20 transition-colors shrink-0"
                                >
                                    Bulan ini
                                </button>
                            )}
                        </div>
                        <div className="flex items-center gap-2 md:ml-auto">
                            <div className="flex items-center rounded-full border border-slate-200 bg-slate-50/60 overflow-hidden">
                                <button
                                    onClick={() => geserBulan(-1)}
                                    aria-label={`Bulan sebelum ${labelBulan}`}
                                    className={`px-2.5 py-2 text-slate-500 hover:bg-white hover:text-slate-700 transition-colors ${fokusRing}`}
                                >
                                    <ChevronLeft size={16} />
                                </button>
                                <input
                                    type="month"
                                    value={bulan}
                                    onChange={(e) => e.target.value && ubahBulan(e.target.value)}
                                    aria-label="Pilih bulan"
                                    className="w-[8.5rem] px-1 py-2 text-sm font-semibold text-slate-700 border-x border-slate-200 bg-transparent focus:outline-none"
                                />
                                <button
                                    onClick={() => geserBulan(1)}
                                    aria-label={`Bulan setelah ${labelBulan}`}
                                    className={`px-2.5 py-2 text-slate-500 hover:bg-white hover:text-slate-700 transition-colors ${fokusRing}`}
                                >
                                    <ChevronRight size={16} />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-col sm:flex-row sm:items-center gap-2 mt-2">
                        <div className="flex-1 relative group">
                            <Search size={16} aria-hidden="true" className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-green transition-colors pointer-events-none" />
                            <TextField
                                type="search"
                                size="sm"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Cari nama atau no. karyawan..."
                                aria-label="Cari nama atau nomor karyawan"
                                className="pl-10 pr-8 text-sm rounded-full border-slate-200 bg-slate-50/60 focus:bg-white shadow-inner focus:shadow-md transition-all"
                            />
                            {cari && (
                                <button
                                    type="button"
                                    onClick={() => setCari('')}
                                    aria-label="Hapus pencarian"
                                    className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 inline-flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-200/70"
                                >
                                    <X size={14} />
                                </button>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            {tampil.length > 0 && (
                                <button
                                    onClick={toggleSemua}
                                    className={`text-sm font-semibold text-brand-green hover:text-brand-green-dark ${fokusRing} rounded-md px-1 shrink-0`}
                                >
                                    {semuaTampilTerpilih ? 'Batalkan semua' : 'Pilih semua'}
                                </button>
                            )}
                            {filterAktif && (
                                <Button type="button" variant="ghost" size="sm" className="shrink-0 rounded-full" onClick={resetFilter}>
                                    <RefreshCw size={14} />
                                    Reset
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {daftarAngsuran.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <Calendar size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                        <p className="text-sm font-semibold text-slate-600">Tidak ada angsuran jatuh tempo di {labelBulan}.</p>
                        {(bulan !== kunciBulanIni || cabangAktif) && (
                            <p className="text-sm text-slate-400 mt-1">
                                Coba{' '}
                                {bulan !== kunciBulanIni && (
                                    <button onClick={() => ubahBulan(kunciBulanIni)} className={`font-semibold text-brand-green hover:text-brand-green-dark ${fokusRing} rounded`}>
                                        kembali ke bulan ini
                                    </button>
                                )}
                                {bulan !== kunciBulanIni && cabangAktif ? ' atau ' : ''}
                                {cabangAktif && (
                                    <button onClick={() => pindahTab('')} className={`font-semibold text-brand-green hover:text-brand-green-dark ${fokusRing} rounded`}>
                                        lihat semua cabang
                                    </button>
                                )}
                                .
                            </p>
                        )}
                    </div>
                ) : tampil.length === 0 ? (
                    <p className="text-sm text-slate-400 text-center py-10">
                        Tidak ada yang cocok dengan pencarian &ldquo;{cari}&rdquo;.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-50">
                        {tampil.map((a) => {
                            const dipilih = terpilih.includes(a.id);
                            return (
                                <li key={a.id}>
                                    <label
                                        className={`flex items-start gap-3 px-2 py-3 rounded-xl cursor-pointer transition-all has-[:focus-visible]:bg-slate-50 ${
                                            dipilih ? 'bg-brand-green-light/40 hover:bg-brand-green-light/60' : 'hover:bg-gradient-to-r hover:from-slate-50 hover:to-transparent'
                                        }`}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={dipilih}
                                            onChange={() => toggleSatu(a.id)}
                                            aria-label={`Pilih angsuran ${a.nama}, cicilan ke-${a.cicilan_ke}`}
                                            className="mt-1 w-5 h-5 rounded-lg border-slate-300 text-brand-green focus:ring-brand-green/30 shrink-0"
                                        />
                                        <span className="w-9 h-9 rounded-full bg-gradient-to-br from-brand-navy to-brand-navy-light text-white hidden sm:inline-flex items-center justify-center text-sm font-bold shrink-0 shadow-sm" aria-hidden="true">
                                            {a.nama.charAt(0).toUpperCase()}
                                        </span>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-1.5 flex-wrap">
                                                <p className="text-sm font-semibold text-slate-800">{a.nama}</p>
                                                {a.terlambat && (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700">
                                                        Terlambat
                                                    </span>
                                                )}
                                                {a.ada_pengajuan_percepatan && (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700">
                                                        Perubahan tenor
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-xs text-slate-400 mt-0.5 truncate">
                                                {a.no_karyawan} • {a.cabang} • Cicilan ke-{a.cicilan_ke} • Tempo {a.tanggal_jatuh_tempo}
                                            </p>
                                        </div>
                                        <div className="text-right shrink-0 pl-2">
                                            <p className="text-sm font-bold text-slate-800 whitespace-nowrap tabular-nums">{formatRupiah(a.total_bayar)}</p>
                                            <p className="text-xs text-brand-green-dark whitespace-nowrap tabular-nums">+{formatRupiah(a.nominal_bunga)}</p>
                                        </div>
                                    </label>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </Card>

            {terpilih.length > 0 && (
                <div className="sticky bottom-0 z-30 mt-4 bg-white border border-slate-200 shadow-xl rounded-t-2xl px-4 py-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))]">
                    <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <p className="text-sm font-semibold text-slate-700 text-center sm:text-left tabular-nums">
                            {terpilih.length} dipilih •{' '}
                            <span className="text-brand-green-dark font-bold">{formatRupiah(totalTerpilih)}</span>
                        </p>
                        <Button variant="primary" size="sm" onClick={konfirmasi} disabled={processing} className="w-full sm:w-auto rounded-full shadow-md shadow-brand-green/25">
                            <Check size={16} aria-hidden="true" />
                            {processing ? 'Memproses...' : 'Konfirmasi Terpilih'}
                        </Button>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
