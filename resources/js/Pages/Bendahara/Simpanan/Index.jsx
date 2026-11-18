import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { HeartHandshake, Check, ChevronLeft, ChevronRight, ChevronDown, Search, X, RefreshCw, Users, Wallet, MapPin, Calendar, RotateCcw } from 'lucide-react';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatWidget from '@/Components/ui/StatWidget';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import Drawer from '@/Components/ui/Drawer';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

export default function Index({
    bulan,
    belumSimpanan = [],
    sudahSimpanan = [],
    totalSudahSimpanan = 0,
    cabangAktif = '',
    daftarCabang = [],
    ringkasanCabang = {},
    nominalPerAnggota = 0,
    totalDanaSosialTerkumpul = 0,
}) {
    const daftarCabangAman = Array.isArray(daftarCabang) ? daftarCabang : Object.values(daftarCabang ?? {});
    const [terpilih, setTerpilih] = useState([]);
    const [processing, setProcessing] = useState(false);
    const [cari, setCari] = useState('');
    const [rincianTerbuka, setRincianTerbuka] = useState(false);
    const [tabStatus, setTabStatus] = useState('belum');
    const [itemBatal, setItemBatal] = useState(null);

    const batalForm = useForm({
        bulan_periode: bulan,
        alasan: '',
    });

    useEffect(() => {
        setCari('');
        setTerpilih([]);
        setItemBatal(null);
        batalForm.setData('bulan_periode', bulan);
    }, [bulan, cabangAktif]);

    function ubahBulan(nilaiBaru) {
        router.get(route('bendahara.simpanan.index'), { bulan: nilaiBaru, cabang: cabangAktif }, { preserveState: true });
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
        router.get(route('bendahara.simpanan.index'), { bulan, cabang }, { preserveState: true });
    }

    function resetFilter() {
        setCari('');
        router.get(route('bendahara.simpanan.index'), { bulan: kunciBulanIni, cabang: '' }, { preserveState: true });
    }

    const kataCari = cari.trim().toLowerCase();
    const dataBelumSimpanan = Array.isArray(belumSimpanan) ? belumSimpanan : Object.values(belumSimpanan ?? {});
    const dataSudahSimpanan = Array.isArray(sudahSimpanan) ? sudahSimpanan : Object.values(sudahSimpanan ?? {});
    const tampil = useMemo(
        () => dataBelumSimpanan.filter((a) =>
            !kataCari || a.nama.toLowerCase().includes(kataCari) || (a.no_karyawan ?? '').toLowerCase().includes(kataCari)
        ),
        [dataBelumSimpanan, kataCari]
    );
    const tampilSudah = useMemo(
        () => dataSudahSimpanan.filter((a) =>
            !kataCari || a.nama.toLowerCase().includes(kataCari) || (a.no_karyawan ?? '').toLowerCase().includes(kataCari)
        ),
        [dataSudahSimpanan, kataCari]
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
            route('bendahara.simpanan.konfirmasi'),
            { anggota_ids: terpilih, bulan_periode: bulan },
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => setTerpilih([]),
                onFinish: () => setProcessing(false),
            })
        );
    }

    function bukaModalBatal(a) {
        batalForm.clearErrors();
        batalForm.setData({ bulan_periode: bulan, alasan: '' });
        setItemBatal(a);
    }

    function submitBatal(e) {
        e.preventDefault();
        if (!itemBatal) return;
        batalForm.post(
            route('bendahara.simpanan.batalkan', itemBatal.id),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    batalForm.reset('alasan');
                    setItemBatal(null);
                },
            })
        );
    }

    const totalBelumSemuaCabang = Object.values(ringkasanCabang ?? {}).reduce((total, r) => total + r.nominal, 0);
    const filterAktif = Boolean(kataCari || bulan !== kunciBulanIni || cabangAktif);

    const tab = [
        { key: '', label: 'Semua Cabang', nominal: totalBelumSemuaCabang },
        ...daftarCabangAman.map((c) => ({
            key: c,
            label: c,
            nominal: ringkasanCabang?.[c]?.nominal ?? 0,
        })),
    ];

    return (
        <AppLayout>
            <Head title="Konfirmasi Simpanan" />

            <PageHeader title="Konfirmasi Simpanan Wajib" subtitle="Tandai anggota yang simpanan wajibnya sudah dipotong bulan ini" />

            <div className="rounded-2xl bg-gradient-to-r from-brand-navy to-brand-navy-light text-white px-5 py-4 mb-3 shadow-md shadow-brand-navy/20">
                <div className="flex items-center gap-3">
                    <span className="w-11 h-11 rounded-2xl bg-white/15 inline-flex items-center justify-center shrink-0">
                        <Calendar size={22} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-white/70">Total Tagihan {labelBulan}</p>
                        <p className="text-2xl font-bold tabular-nums leading-tight privacy-blur">{formatRupiah(totalBelumSemuaCabang)}</p>
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
                            <dt className="text-xs text-white/70">Per anggota (wajib + dansos)</dt>
                            <dd className="font-bold tabular-nums privacy-blur">{formatRupiah(nominalPerAnggota)}</dd>
                        </div>
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Dana sosial terkumpul</dt>
                            <dd className="font-bold tabular-nums privacy-blur">{formatRupiah(totalDanaSosialTerkumpul)}</dd>
                        </div>
                        {Object.entries(ringkasanCabang ?? {}).map(([c, r]) => (
                            <div key={c} className="rounded-xl bg-white/10 px-3 py-2">
                                <dt className="text-xs text-white/70">{c} ({r.jumlah_anggota} org)</dt>
                                <dd className="font-bold tabular-nums privacy-blur">{formatRupiah(r.nominal)}</dd>
                            </div>
                        ))}
                        <div className="rounded-xl bg-white/10 px-3 py-2">
                            <dt className="text-xs text-white/70">Jumlah anggota</dt>
                            <dd className="font-bold tabular-nums">{belumSimpanan.length} belum konfirmasi</dd>
                        </div>
                    </dl>
                )}
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                <StatWidget compact label="Belum Konfirmasi" value={belumSimpanan.length} icon={Users} tone="amber" />
                <StatWidget compact label="Per Anggota" value={formatRupiah(nominalPerAnggota)} icon={Wallet} tone="navy" />
                <div className="col-span-2 lg:col-span-1">
                    <StatWidget compact label="Dana Sosial" value={formatRupiah(totalDanaSosialTerkumpul)} icon={HeartHandshake} tone="green" />
                </div>
            </div>

            <div className="flex items-center gap-1.5 bg-gradient-to-r from-brand-navy/[0.05] via-slate-100/80 to-brand-green-light/30 border border-slate-200/80 p-1 rounded-full w-fit max-w-full overflow-x-auto mb-4">
                {tab.map((t) => {
                    const aktif = cabangAktif === t.key;
                    return (
                        <button
                            key={t.key}
                            onClick={() => pindahTab(t.key)}
                            aria-pressed={aktif}
                            className={`inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-full whitespace-nowrap transition-all shrink-0 ${
                                aktif ? 'bg-gradient-to-b from-white to-brand-green-light/25 text-brand-navy border border-brand-green/35 shadow-sm' : 'text-slate-600 hover:bg-white/75 hover:text-brand-navy'
                            }`}
                        >
                            <MapPin size={15} />
                            {t.label}
                            <span className="text-xs tabular-nums text-slate-400 privacy-blur">
                                {formatRupiahSingkat(t.nominal)}
                            </span>
                        </button>
                    );
                })}
            </div>

            <Card padding="sm" className="shadow-md border-slate-200/70 mb-4">
                <div className="pb-3 mb-1 border-b border-slate-100">
                    {/* Segmented Status Switcher */}
                    <div className="inline-flex items-center p-1 rounded-xl bg-gradient-to-r from-brand-navy/[0.04] to-brand-green-light/20 border border-slate-200/80 mb-3">
                        <button
                            type="button"
                            onClick={() => setTabStatus('belum')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${fokusRing} ${
                                tabStatus === 'belum'
                                    ? 'bg-white text-slate-800 shadow-xs'
                                    : 'text-slate-500 hover:text-slate-800'
                            }`}
                        >
                            Belum Dikonfirmasi ({dataBelumSimpanan.length})
                        </button>
                        <button
                            type="button"
                            onClick={() => setTabStatus('sudah')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${fokusRing} ${
                                tabStatus === 'sudah'
                                    ? 'bg-white text-emerald-700 border border-emerald-200/80 shadow-xs'
                                    : 'text-slate-600 hover:text-brand-navy border border-transparent'
                            }`}
                        >
                            Sudah Dikonfirmasi ({dataSudahSimpanan.length})
                        </button>
                    </div>

                    <div className="flex flex-col md:flex-row md:items-center gap-2">
                        <div className="flex items-center gap-1.5 min-w-0">
                            <p className="text-sm text-slate-500 truncate">
                                {tabStatus === 'belum' ? 'Belum konfirmasi' : 'Sudah konfirmasi'}{' '}
                                <span className="font-bold text-slate-800">{labelBulan}</span>
                                <span className="text-slate-400">
                                    {' '}• {tabStatus === 'belum' ? tampil.length : tampilSudah.length}
                                    {kataCari && (tabStatus === 'belum' ? dataBelumSimpanan.length : dataSudahSimpanan.length) > 0
                                        ? ` dari ${tabStatus === 'belum' ? dataBelumSimpanan.length : dataSudahSimpanan.length}`
                                        : ''}{' '}
                                    anggota
                                </span>
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
                            <div className="flex items-center rounded-full border border-slate-200/80 bg-gradient-to-r from-slate-50/70 to-brand-green-light/20 overflow-hidden">
                                <button
                                    onClick={() => geserBulan(-1)}
                                    aria-label={`Bulan sebelum ${labelBulan}`}
                                    className={`px-2.5 py-2 text-slate-500 hover:bg-white hover:text-brand-navy transition-colors ${fokusRing}`}
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
                                    className={`px-2.5 py-2 text-slate-500 hover:bg-white hover:text-brand-navy transition-colors ${fokusRing}`}
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
                            {tabStatus === 'belum' && tampil.length > 0 && (
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

                {tabStatus === 'belum' ? (
                    belumSimpanan.length === 0 ? (
                        <div className="text-center py-12 px-4">
                            <Check size={28} aria-hidden="true" className="mx-auto text-brand-green mb-3" />
                            <p className="text-sm font-semibold text-slate-600">Semua anggota aktif sudah dikonfirmasi untuk {labelBulan}.</p>
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
                                            className={`flex items-center gap-3 px-2 py-3 rounded-xl cursor-pointer transition-all has-[:focus-visible]:bg-brand-green-light/40 ${
                                                dipilih ? 'bg-brand-green-light/45 hover:bg-brand-green-light/65' : 'hover:bg-brand-green-light/30'
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={dipilih}
                                                onChange={() => toggleSatu(a.id)}
                                                aria-label={`Pilih simpanan wajib ${a.nama}`}
                                                className="w-5 h-5 rounded-lg border-slate-300 text-brand-green focus:ring-brand-green/30 shrink-0"
                                            />
                                            <span className="hidden sm:inline-flex shrink-0">
                                                <FotoAnggota nama={a.nama} fotoUrl={a.foto_url} ukuran="sm" />
                                            </span>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-semibold text-slate-800 truncate">{a.nama}</p>
                                                <p className="text-xs text-slate-400 mt-0.5 truncate">{a.no_karyawan} • {a.cabang}</p>
                                            </div>
                                            <div className="text-right shrink-0 pl-2">
                                                <p className="text-sm font-bold text-slate-800 whitespace-nowrap tabular-nums privacy-blur">{formatRupiah(nominalPerAnggota)}</p>
                                                <p className="text-[11px] text-slate-400">wajib + dansos</p>
                                            </div>
                                        </label>
                                    </li>
                                );
                            })}
                        </ul>
                    )
                ) : dataSudahSimpanan.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <Users size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                        <p className="text-sm font-semibold text-slate-600">Belum ada simpanan yang dikonfirmasi untuk {labelBulan}.</p>
                    </div>
                ) : tampilSudah.length === 0 ? (
                    <p className="text-sm text-slate-400 text-center py-10">
                        Tidak ada yang cocok dengan pencarian &ldquo;{cari}&rdquo;.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-50">
                        {tampilSudah.map((a) => (
                            <li
                                key={a.id}
                                onClick={() => bukaModalBatal(a)}
                                onKeyDown={(e) => {
                                    if (e.target === e.currentTarget && (e.key === 'Enter' || e.key === ' ')) {
                                        e.preventDefault();
                                        bukaModalBatal(a);
                                    }
                                }}
                                tabIndex={0}
                                title="Klik untuk rincian atau rollback konfirmasi simpanan"
                                className="group flex items-center gap-3 px-2 py-3 rounded-xl cursor-pointer hover:bg-brand-green-light/30 transition-all focus-visible:outline-none focus-visible:bg-brand-green-light/40"
                            >
                                <span className="hidden sm:inline-flex shrink-0">
                                    <FotoAnggota nama={a.nama} fotoUrl={a.foto_url} ukuran="sm" />
                                </span>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-1.5 flex-wrap">
                                        <p className="text-sm font-semibold text-slate-800 group-hover:text-brand-navy truncate">{a.nama}</p>
                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-2xs">
                                            Terkonfirmasi
                                        </span>
                                        <ChevronRight size={14} className="text-slate-300 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all" aria-hidden="true" />
                                    </div>
                                    <p className="text-xs text-slate-400 mt-0.5 truncate">
                                        {a.no_karyawan} • {a.cabang}{a.tanggal_input ? ` • Dikonfirmasi ${a.tanggal_input}` : ''}
                                    </p>
                                </div>
                                <div className="text-right shrink-0 pl-2">
                                    <p className="text-sm font-bold text-slate-800 whitespace-nowrap tabular-nums privacy-blur">{formatRupiah(a.nominal_total)}</p>
                                    <p className="text-[11px] text-slate-400">wajib + dansos</p>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            {tabStatus === 'belum' && terpilih.length > 0 && (
                <div className="sticky bottom-0 z-30 mt-4 bg-white border border-slate-200 shadow-xl rounded-t-2xl px-4 py-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))]">
                    <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <p className="text-sm font-semibold text-slate-700 text-center sm:text-left tabular-nums">
                            {terpilih.length} dipilih •{' '}
                            <span className="text-brand-green-dark font-bold privacy-blur">{formatRupiah(terpilih.length * nominalPerAnggota)}</span>
                        </p>
                        <Button variant="primary" size="sm" onClick={konfirmasi} disabled={processing} className="w-full sm:w-auto rounded-full shadow-md shadow-brand-green/25">
                            <Check size={16} aria-hidden="true" />
                            {processing ? 'Memproses...' : 'Konfirmasi Terpilih'}
                        </Button>
                    </div>
                </div>
            )}

            <Drawer
                show={Boolean(itemBatal)}
                onClose={() => setItemBatal(null)}
                title="Rincian & Rollback Konfirmasi Simpanan"
                description="Membatalkan konfirmasi akan menghapus simpanan wajib periode ini dan menerbitkan jurnal pembalik (storno) di Buku Kas."
            >
                {itemBatal && (
                    <div className="space-y-4">
                        <div className="p-3.5 rounded-xl bg-gradient-to-br from-brand-navy/[0.03] via-slate-50/60 to-brand-green-light/20 border border-slate-200/80 shadow-2xs space-y-2 text-xs">
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-slate-500">Anggota</span>
                                <span className="font-semibold text-slate-800">{itemBatal.nama}</span>
                            </div>
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-slate-500">No. Karyawan / Cabang</span>
                                <span className="font-medium text-slate-800">{itemBatal.no_karyawan} • {itemBatal.cabang}</span>
                            </div>
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-slate-500">Periode</span>
                                <span className="font-medium text-slate-800">{labelBulan}</span>
                            </div>
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-slate-500">Simpanan Wajib</span>
                                <span className="font-medium tabular-nums privacy-blur text-slate-800">{formatRupiah(itemBatal.nominal_wajib)}</span>
                            </div>
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-slate-500">Dana Sosial</span>
                                <span className="font-medium tabular-nums privacy-blur text-slate-800">{formatRupiah(itemBatal.nominal_dana_sosial)}</span>
                            </div>
                            <div className="flex items-center justify-between gap-2 pt-2 border-t border-slate-200">
                                <span className="font-semibold text-slate-700">Total Dibalikkan</span>
                                <span className="font-bold tabular-nums privacy-blur text-rose-600">{formatRupiah(itemBatal.nominal_total)}</span>
                            </div>
                        </div>

                        {itemBatal.bisa_dibatalkan ? (
                            <form onSubmit={submitBatal} className="space-y-4">
                                <FormField
                                    label="Alasan Pembatalan / Rollback"
                                    error={batalForm.errors.alasan}
                                    required
                                    hint="Contoh: Salah contreng anggota saat konfirmasi massal"
                                >
                                    <TextField
                                        size="sm"
                                        as="textarea"
                                        rows={3}
                                        value={batalForm.data.alasan}
                                        onChange={(e) => batalForm.setData('alasan', e.target.value)}
                                        placeholder="Jelaskan alasan pembatalan konfirmasi simpanan ini..."
                                        required
                                    />
                                </FormField>

                                <div className="pt-2 flex justify-end gap-2 border-t border-slate-100">
                                    <Button type="button" variant="secondary" size="sm" onClick={() => setItemBatal(null)}>
                                        Tutup
                                    </Button>
                                    <Button type="submit" size="sm" disabled={batalForm.processing} className="bg-rose-600 hover:bg-rose-700">
                                        <RotateCcw size={14} aria-hidden="true" />
                                        {batalForm.processing ? 'Memproses...' : 'Batalkan Konfirmasi Simpanan'}
                                    </Button>
                                </div>
                            </form>
                        ) : (
                            <>
                                <div className="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800">
                                    Anggota ini berstatus <strong>{itemBatal.status_anggota}</strong> sehingga konfirmasi simpanan tidak dapat dibatalkan.
                                </div>
                                <div className="pt-2 flex justify-end border-t border-slate-100">
                                    <Button type="button" variant="secondary" size="sm" onClick={() => setItemBatal(null)}>
                                        Tutup
                                    </Button>
                                </div>
                            </>
                        )}
                    </div>
                )}
            </Drawer>
        </AppLayout>
    );
}
