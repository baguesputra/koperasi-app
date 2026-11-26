import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useId, useMemo, useState } from 'react';
import { TrendingUp, Calendar, Check, ChevronLeft, ChevronRight, Search, X, RefreshCw, MapPin, AlarmClock, RotateCcw, AlertCircle } from 'lucide-react';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import PrivacyWrapper from '@/Components/ui/PrivacyWrapper';
import { UnifiedLedgerStrip } from '@/Components/ui/SummaryLayout';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import Drawer from '@/Components/ui/Drawer';
import {
    DrawerBody,
    DrawerFooter,
    DrawerFooterActions,
    DrawerKeyValue,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

export default function Index({
    bulan,
    bulanIni: bulanIniServer,
    tabAwal = 'belum',
    daftarAngsuran = [],
    daftarTerlambat = [],
    sudahDikonfirmasi = [],
    totalSudahDikonfirmasi = 0,
    cabangAktif = '',
    daftarCabang = [],
    tagihanPerCabang = {},
    totalTagihanBulanIni = 0,
    totalTagihanTerlambat = 0,
    dipindahKeBulanIni = { jumlah: 0, nominal: 0 },
    totalPendapatanBungaBulanIni = 0,
    totalPendapatanBungaKeseluruhan = 0,
}) {
    const daftarCabangAman = Array.isArray(daftarCabang) ? daftarCabang : Object.values(daftarCabang ?? {});
    const [terpilih, setTerpilih] = useState([]);
    const [processing, setProcessing] = useState(false);
    const [cari, setCari] = useState('');
    const [tabStatus, setTabStatus] = useState(tabAwal);
    const [itemBatal, setItemBatal] = useState(null);
    const batalFormId = useId();

    const batalForm = useForm({
        alasan: '',
    });

    useEffect(() => {
        setCari('');
        setTerpilih([]);
        setItemBatal(null);
        setTabStatus(tabAwal);
    }, [bulan, cabangAktif, tabAwal]);

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

    const bulanIniDate = new Date();
    const kunciBulanIni = bulanIniServer || `${bulanIniDate.getFullYear()}-${String(bulanIniDate.getMonth() + 1).padStart(2, '0')}`;
    const isBulanLalu = bulan < kunciBulanIni;

    function gantiTab(tabBaru) {
        setTabStatus(tabBaru);
        setTerpilih([]);
    }

    function bukaTerlambatBulanIni() {
        if (bulan === kunciBulanIni) {
            gantiTab('terlambat');
            return;
        }
        router.get(
            route('bendahara.angsuran.index'),
            { bulan: kunciBulanIni, cabang: cabangAktif, tab: 'terlambat' },
            { preserveState: true }
        );
    }

    function pindahTab(cabang) {
        router.get(route('bendahara.angsuran.index'), { bulan, cabang }, { preserveState: true });
    }

    function resetFilter() {
        setCari('');
        router.get(route('bendahara.angsuran.index'), { bulan: kunciBulanIni, cabang: '' }, { preserveState: true });
    }

    const kataCari = cari.trim().toLowerCase();
    const dataTerlambat = Array.isArray(daftarTerlambat) ? daftarTerlambat : Object.values(daftarTerlambat ?? {});
    const dataSudah = Array.isArray(sudahDikonfirmasi) ? sudahDikonfirmasi : Object.values(sudahDikonfirmasi ?? {});
    const tampil = useMemo(
        () => daftarAngsuran.filter((a) =>
            !kataCari || a.nama.toLowerCase().includes(kataCari) || (a.no_karyawan ?? '').toLowerCase().includes(kataCari)
        ),
        [daftarAngsuran, kataCari]
    );
    const tampilTerlambat = useMemo(
        () => dataTerlambat.filter((a) =>
            !kataCari || a.nama.toLowerCase().includes(kataCari) || (a.no_karyawan ?? '').toLowerCase().includes(kataCari)
        ),
        [dataTerlambat, kataCari]
    );
    const tampilSudah = useMemo(
        () => dataSudah.filter((a) =>
            !kataCari || a.nama.toLowerCase().includes(kataCari) || (a.no_karyawan ?? '').toLowerCase().includes(kataCari)
        ),
        [dataSudah, kataCari]
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

    function bukaModalBatal(a) {
        batalForm.clearErrors();
        batalForm.setData('alasan', '');
        setItemBatal(a);
    }

    function submitBatal(e) {
        e.preventDefault();
        if (!itemBatal) return;
        batalForm.post(
            route('bendahara.angsuran.batalkan', itemBatal.id),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    batalForm.reset('alasan');
                    setItemBatal(null);
                },
            })
        );
    }

    const sumberAktif = tabStatus === 'terlambat' ? dataTerlambat : daftarAngsuran;
    const totalTerpilih = sumberAktif
        .filter((a) => terpilih.includes(a.id))
        .reduce((sum, a) => sum + a.total_bayar, 0);

    const totalTerlambatCabang = dataTerlambat.reduce((sum, a) => sum + (a.total_bayar || 0), 0);
    const jumlahTerlambatWidget = isBulanLalu ? (dipindahKeBulanIni?.jumlah ?? 0) : dataTerlambat.length;
    const nominalTerlambatWidget = isBulanLalu ? (dipindahKeBulanIni?.nominal ?? 0) : totalTerlambatCabang;
    const filterAktif = Boolean(kataCari || bulan !== kunciBulanIni || cabangAktif);

    const tab = [
        { key: '', label: 'Semua Cabang', nominal: totalTagihanBulanIni },
        ...daftarCabangAman.map((c) => ({
            key: c,
            label: c,
            nominal: tagihanPerCabang[c] ?? 0,
        })),
    ];

    return (
        <AppLayout>
            <Head title="Konfirmasi Angsuran" />

            <PageHeader title="Konfirmasi Angsuran" subtitle="Tandai angsuran yang sudah dipotong dari gaji anggota" />

            <UnifiedLedgerStrip
                columns={4}
                items={[
                    {
                        key: 'tagihan',
                        variant: 'anchor',
                        label: `Tagihan ${labelBulan}`,
                        icon: Calendar,
                        value: formatRupiah(totalTagihanBulanIni),
                        meta: (
                            <>
                                {daftarAngsuran.length} menunggu • Masuk{' '}
                                <PrivacyWrapper>{formatRupiah(totalSudahDikonfirmasi)}</PrivacyWrapper>
                            </>
                        ),
                    },
                    {
                        key: 'terlambat',
                        label: 'Terlambat',
                        icon: AlarmClock,
                        tone: 'rose',
                        value: jumlahTerlambatWidget,
                        privacy: false,
                        onClick: bukaTerlambatBulanIni,
                        active: tabStatus === 'terlambat',
                        valueTone: jumlahTerlambatWidget > 0 ? 'text-rose-600' : 'text-[#0F1E36]',
                        meta:
                            nominalTerlambatWidget > 0 ? (
                                <>
                                    <PrivacyWrapper>{formatRupiah(nominalTerlambatWidget)}</PrivacyWrapper> →
                                </>
                            ) : isBulanLalu ? (
                                'Dipindah ke bulan ini →'
                            ) : (
                                'Tidak ada tunggakan'
                            ),
                    },
                    {
                        key: 'sudah',
                        label: 'Sudah Konfirmasi',
                        icon: Check,
                        tone: 'green',
                        value: dataSudah.length,
                        privacy: false,
                        onClick: () => gantiTab('sudah'),
                        active: tabStatus === 'sudah',
                        meta: (
                            <>
                                <PrivacyWrapper>{formatRupiah(totalSudahDikonfirmasi)}</PrivacyWrapper> masuk
                            </>
                        ),
                    },
                    {
                        key: 'bunga',
                        label: 'Pendapatan Bunga',
                        icon: TrendingUp,
                        tone: 'navy',
                        value: formatRupiah(totalPendapatanBungaBulanIni),
                        meta: (
                            <>
                                Total:{' '}
                                <PrivacyWrapper>{formatRupiah(totalPendapatanBungaKeseluruhan)}</PrivacyWrapper>
                            </>
                        ),
                    },
                ]}
            />

            <div className="flex items-center gap-1.5 bg-white border border-slate-200/85 shadow-2xs p-1 rounded-full w-fit max-w-full overflow-x-auto mb-3">
                {tab.map((t) => {
                    const aktif = cabangAktif === t.key;
                    return (
                        <button
                            key={t.key}
                            onClick={() => pindahTab(t.key)}
                            aria-pressed={aktif}
                            className={`inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold rounded-full whitespace-nowrap transition-all shrink-0 ${
                                aktif
                                    ? 'bg-[#0F1E36] text-white shadow-xs'
                                    : 'text-slate-600 hover:bg-slate-100/80 hover:text-[#0F1E36]'
                            }`}
                        >
                            <MapPin size={15} />
                            {t.label}
                            <span className={`text-xs tabular-nums ${aktif ? 'text-white/75' : 'text-slate-400'}`}>
                                <PrivacyWrapper>{formatRupiahSingkat(t.nominal)}</PrivacyWrapper>
                            </span>
                        </button>
                    );
                })}
            </div>

            <Card padding="sm" className="mb-4">
                <div className="pb-3 mb-1 border-b border-slate-100">
                    {/* Segmented Status Switcher */}
                    <div className="inline-flex flex-wrap items-center p-1 rounded-xl bg-slate-100/80 border border-slate-200/80 mb-3 gap-0.5">
                        <button
                            type="button"
                            onClick={() => gantiTab('belum')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${fokusRing} ${
                                tabStatus === 'belum'
                                    ? 'bg-white text-[#0F1E36] shadow-2xs'
                                    : 'text-slate-500 hover:text-[#0F1E36]'
                            }`}
                        >
                            Belum Dikonfirmasi ({daftarAngsuran.length})
                        </button>
                        <button
                            type="button"
                            onClick={() => gantiTab('terlambat')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${fokusRing} ${
                                tabStatus === 'terlambat'
                                    ? 'bg-white text-rose-700 border border-rose-200/80 shadow-2xs'
                                    : 'text-slate-600 hover:text-rose-700 border border-transparent'
                            }`}
                        >
                            Terlambat ({dataTerlambat.length})
                        </button>
                        <button
                            type="button"
                            onClick={() => gantiTab('sudah')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${fokusRing} ${
                                tabStatus === 'sudah'
                                    ? 'bg-white text-emerald-700 border border-emerald-200/80 shadow-2xs'
                                    : 'text-slate-600 hover:text-[#0F1E36] border border-transparent'
                            }`}
                        >
                            Sudah Dikonfirmasi ({dataSudah.length})
                        </button>
                    </div>

                    <div className="flex flex-col md:flex-row md:items-center gap-2">
                        <div className="flex items-center gap-1.5 min-w-0">
                            <p className="text-sm text-slate-500 truncate">
                                {tabStatus === 'terlambat'
                                    ? 'Tunggakan sebelum'
                                    : tabStatus === 'sudah'
                                    ? 'Konfirmasi & jatuh tempo'
                                    : 'Jatuh tempo'}{' '}
                                <span className="font-bold text-slate-800">{labelBulan}</span>
                                <span className="text-slate-500">
                                    {' '}•{' '}
                                    {tabStatus === 'belum'
                                        ? tampil.length
                                        : tabStatus === 'terlambat'
                                        ? tampilTerlambat.length
                                        : tampilSudah.length}
                                    {kataCari &&
                                    (tabStatus === 'belum'
                                        ? daftarAngsuran.length
                                        : tabStatus === 'terlambat'
                                        ? dataTerlambat.length
                                        : dataSudah.length) > 0
                                        ? ` dari ${
                                              tabStatus === 'belum'
                                                  ? daftarAngsuran.length
                                                  : tabStatus === 'terlambat'
                                                  ? dataTerlambat.length
                                                  : dataSudah.length
                                          }`
                                        : ''}{' '}
                                    {tabStatus === 'belum'
                                        ? 'belum dikonfirmasi'
                                        : tabStatus === 'terlambat'
                                        ? 'terlambat'
                                        : 'sudah dikonfirmasi'}
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
                            <div className="flex items-center rounded-full border border-slate-200/85 bg-white hover:border-slate-300 transition-colors overflow-hidden">
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
                                className="pl-10 pr-8 text-sm rounded-full border-slate-200 bg-slate-50/80 hover:bg-white focus:bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)] transition-all"
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

                {(tabStatus === 'belum' || tabStatus === 'terlambat') && isBulanLalu ? (
                    <div className="text-center py-10 px-4">
                        <div className="max-w-lg mx-auto rounded-xl border border-slate-200/80 bg-slate-50/70 p-5">
                            <AlarmClock size={26} aria-hidden="true" className="mx-auto text-slate-400 mb-2.5" />
                            {(dipindahKeBulanIni?.jumlah ?? 0) > 0 ? (
                                <>
                                    <p className="text-sm font-semibold text-slate-800">
                                        {dipindahKeBulanIni.jumlah} angsuran periode {labelBulan} belum dibayar (<span className="privacy-blur">{formatRupiah(dipindahKeBulanIni.nominal)}</span>)
                                    </p>
                                    <p className="text-xs sm:text-sm text-slate-500 mt-1">
                                        Seluruh tagihan yang belum dibayar dari bulan lalu sudah otomatis dipindahkan ke bulan berjalan agar proses konfirmasi terpusat.
                                    </p>
                                </>
                            ) : (
                                <>
                                    <p className="text-sm font-semibold text-slate-800">
                                        Seluruh angsuran jatuh tempo {labelBulan} sudah lunas.
                                    </p>
                                    <p className="text-xs sm:text-sm text-slate-500 mt-1">
                                        Periode bulan lalu tampil sebagai arsip riwayat pembayaran di tab Sudah Dikonfirmasi.
                                    </p>
                                </>
                            )}
                            <div className="mt-4 flex flex-wrap items-center justify-center gap-2">
                                <Button type="button" variant="primary" size="sm" onClick={bukaTerlambatBulanIni} className="rounded-full">
                                    Lihat di Tab Terlambat (Bulan Ini)
                                    <ChevronRight size={14} aria-hidden="true" />
                                </Button>
                                <Button type="button" variant="neutral" size="sm" onClick={() => gantiTab('sudah')} className="rounded-full">
                                    Lihat Arsip Sudah Dikonfirmasi
                                </Button>
                            </div>
                        </div>
                    </div>
                ) : tabStatus === 'belum' ? (
                    daftarAngsuran.length === 0 ? (
                        <div className="text-center py-12 px-4">
                            <Calendar size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                            <p className="text-sm font-semibold text-slate-600">Tidak ada angsuran jatuh tempo di {labelBulan}.</p>
                            {(bulan !== kunciBulanIni || cabangAktif) && (
                                <p className="text-sm text-slate-500 mt-1">
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
                        <p className="text-sm text-slate-500 text-center py-10">
                            Tidak ada yang cocok dengan pencarian &ldquo;{cari}&rdquo;.
                        </p>
                    ) : (
                        <ul className="divide-y divide-slate-50">
                            {tampil.map((a) => {
                                const dipilih = terpilih.includes(a.id);
                                return (
                                    <li key={a.id}>
                                        <label
                                            className={`flex items-start gap-3 px-2 py-3 rounded-xl cursor-pointer transition-all has-[:focus-visible]:bg-brand-green-light/40 ${
                                                dipilih ? 'bg-brand-green-light/45 hover:bg-brand-green-light/65' : 'hover:bg-brand-green-light/30'
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={dipilih}
                                                onChange={() => toggleSatu(a.id)}
                                                aria-label={`Pilih angsuran ${a.nama}, cicilan ke-${a.cicilan_ke}`}
                                                className="mt-1 w-5 h-5 rounded-lg border-slate-300 text-brand-green focus:ring-brand-green/30 shrink-0"
                                            />
                                            <span className="hidden sm:inline-flex shrink-0">
                                                <FotoAnggota nama={a.nama} fotoUrl={a.foto_url} ukuran="sm" />
                                            </span>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    <p className="text-sm font-semibold text-slate-800">{a.nama}</p>
                                                    {a.terlambat && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200/80 shadow-2xs">
                                                            Terlambat
                                                        </span>
                                                    )}
                                                    {a.ada_pengajuan_percepatan && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80 shadow-2xs">
                                                            Perubahan tenor
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-xs text-slate-500 mt-0.5 truncate">
                                                    {a.no_karyawan} • {a.cabang} • Cicilan ke-{a.cicilan_ke} • Tempo {a.tanggal_jatuh_tempo}
                                                </p>
                                            </div>
                                            <div className="text-right shrink-0 pl-2">
                                                <p className="text-sm font-bold text-slate-800 whitespace-nowrap tabular-nums privacy-blur">{formatRupiah(a.total_bayar)}</p>
                                                <p className="text-xs text-brand-green-dark whitespace-nowrap tabular-nums privacy-blur">+{formatRupiah(a.nominal_bunga)}</p>
                                            </div>
                                        </label>
                                    </li>
                                );
                            })}
                        </ul>
                    )
                ) : tabStatus === 'terlambat' ? (
                    dataTerlambat.length === 0 ? (
                        <div className="text-center py-12 px-4">
                            <Check size={28} aria-hidden="true" className="mx-auto text-brand-green mb-3" />
                            <p className="text-sm font-semibold text-slate-700">Tidak ada angsuran terlambat dari bulan-bulan sebelumnya.</p>
                        </div>
                    ) : tampilTerlambat.length === 0 ? (
                        <p className="text-sm text-slate-500 text-center py-10">
                            Tidak ada angsuran terlambat yang cocok dengan pencarian &ldquo;{cari}&rdquo;.
                        </p>
                    ) : (
                        <>
                            <div className="rounded-xl bg-rose-50/60 border border-rose-200/70 px-3 py-2 text-xs text-rose-800 mb-2 mt-1">
                                Pilih manual angsuran terlambat yang sudah dibayar. Tagihan di tab ini tidak ikut terkonfirmasi saat klik Pilih semua di bulan berjalan.
                            </div>
                            <ul className="divide-y divide-slate-50">
                                {tampilTerlambat.map((a) => {
                                    const dipilih = terpilih.includes(a.id);
                                    return (
                                        <li key={a.id}>
                                            <label
                                                className={`flex items-start gap-3 px-2 py-3 rounded-xl cursor-pointer transition-all has-[:focus-visible]:bg-rose-50/70 ${
                                                    dipilih ? 'bg-rose-50/70 hover:bg-rose-50' : 'hover:bg-slate-50'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={dipilih}
                                                    onChange={() => toggleSatu(a.id)}
                                                    aria-label={`Pilih angsuran terlambat ${a.nama}, cicilan ke-${a.cicilan_ke} tempo ${a.tanggal_jatuh_tempo}`}
                                                    className="mt-1 w-5 h-5 rounded-lg border-slate-300 text-brand-green focus:ring-brand-green/30 shrink-0"
                                                />
                                                <span className="hidden sm:inline-flex shrink-0">
                                                    <FotoAnggota nama={a.nama} fotoUrl={a.foto_url} ukuran="sm" />
                                                </span>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-1.5 flex-wrap">
                                                        <p className="text-sm font-semibold text-slate-800">{a.nama}</p>
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200/80 shadow-2xs">
                                                            Terlambat
                                                        </span>
                                                        {a.periode_asal_label && (
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                                                                {a.periode_asal_label}
                                                            </span>
                                                        )}
                                                        {a.ada_pengajuan_percepatan && (
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80 shadow-2xs">
                                                                Perubahan tenor
                                                            </span>
                                                        )}
                                                    </div>
                                                    <p className="text-xs text-slate-500 mt-0.5 truncate">
                                                        {a.no_karyawan} • {a.cabang} • Cicilan ke-{a.cicilan_ke} • Tempo {a.tanggal_jatuh_tempo}
                                                    </p>
                                                </div>
                                                <div className="text-right shrink-0 pl-2">
                                                    <p className="text-sm font-bold text-slate-800 whitespace-nowrap tabular-nums privacy-blur">{formatRupiah(a.total_bayar)}</p>
                                                    <p className="text-xs text-brand-green-dark whitespace-nowrap tabular-nums privacy-blur">+{formatRupiah(a.nominal_bunga)}</p>
                                                </div>
                                            </label>
                                        </li>
                                    );
                                })}
                            </ul>
                        </>
                    )
                ) : dataSudah.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <Calendar size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                        <p className="text-sm font-semibold text-slate-600">Belum ada angsuran yang dikonfirmasi untuk {labelBulan}.</p>
                    </div>
                ) : tampilSudah.length === 0 ? (
                    <p className="text-sm text-slate-500 text-center py-10">
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
                                title={a.bisa_dibatalkan ? 'Klik untuk rincian atau rollback konfirmasi angsuran' : 'Klik untuk rincian angsuran'}
                                className="group flex items-start gap-3 px-2 py-3 rounded-xl cursor-pointer hover:bg-brand-green-light/30 transition-all focus-visible:outline-none focus-visible:bg-brand-green-light/40"
                            >
                                <span className="hidden sm:inline-flex shrink-0">
                                    <FotoAnggota nama={a.nama} fotoUrl={a.foto_url} ukuran="sm" />
                                </span>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-1.5 flex-wrap">
                                        <p className="text-sm font-semibold text-slate-800 group-hover:text-brand-navy">{a.nama}</p>
                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-2xs">
                                            Lunas
                                        </span>
                                        {a.dari_bulan_lain && a.periode_asal_label && (
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/80">
                                                Tempo {a.periode_asal_label}
                                            </span>
                                        )}
                                        {a.is_percepatan && (
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/80 shadow-2xs">
                                                Perubahan tenor
                                            </span>
                                        )}
                                        <ChevronRight size={14} className="text-slate-300 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all" aria-hidden="true" />
                                    </div>
                                    <p className="text-xs text-slate-500 mt-0.5 truncate">
                                        {a.no_karyawan} • {a.cabang} • Cicilan ke-{a.cicilan_ke} • Tempo {a.tanggal_jatuh_tempo}
                                        {a.tanggal_konfirmasi_bayar ? ` • Dikonfirmasi ${a.tanggal_konfirmasi_bayar}` : ''}
                                    </p>
                                </div>
                                <div className="text-right shrink-0 pl-2">
                                    <p className="text-sm font-bold text-slate-800 whitespace-nowrap tabular-nums privacy-blur">{formatRupiah(a.total_bayar)}</p>
                                    <p className="text-xs text-brand-green-dark whitespace-nowrap tabular-nums privacy-blur">+{formatRupiah(a.nominal_bunga)}</p>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            {(tabStatus === 'belum' || tabStatus === 'terlambat') && terpilih.length > 0 && (
                <div className="sticky bottom-0 z-30 mt-4 bg-white border border-slate-200 shadow-xl rounded-t-2xl px-4 py-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))]">
                    <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <p className="text-sm font-semibold text-slate-700 text-center sm:text-left tabular-nums">
                            {terpilih.length} dipilih •{' '}
                            <span className="text-brand-green-dark font-bold privacy-blur">{formatRupiah(totalTerpilih)}</span>
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
                layout="split"
                onClose={() => setItemBatal(null)}
                title={itemBatal?.bisa_dibatalkan ? 'Rincian & Rollback Konfirmasi Angsuran' : 'Rincian Angsuran'}
                description={
                    itemBatal?.bisa_dibatalkan
                        ? 'Membatalkan konfirmasi akan mengembalikan cicilan ke status belum bayar dan menerbitkan jurnal pembalik (storno) di Buku Kas.'
                        : 'Detail cicilan pinjaman yang telah dikonfirmasi lunas.'
                }
                badge={
                    itemBatal && (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full border text-xs font-semibold bg-brand-green-light text-brand-green-dark border-emerald-200/80">
                            Lunas
                        </span>
                    )
                }
            >
                {itemBatal && (
                    <>
                        <DrawerBody>
                            <DrawerSection tone={itemBatal.bisa_dibatalkan ? 'rose' : 'emerald'} padding="sm">
                                <div className="flex items-center gap-3">
                                    <FotoAnggota nama={itemBatal.nama} fotoUrl={itemBatal.foto_url} ukuran="md" className="shrink-0" />
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-bold text-slate-900 truncate">{itemBatal.nama}</p>
                                        <p className="text-xs text-slate-500 truncate mt-0.5">
                                            <span className="font-mono">{itemBatal.no_karyawan}</span> • {itemBatal.cabang} • Cicilan ke-{itemBatal.cicilan_ke}
                                        </p>
                                    </div>
                                    <div className="text-right shrink-0">
                                        <p className="text-[11px] text-slate-500">Total Bayar</p>
                                        <p
                                            className={`text-base sm:text-lg font-extrabold tabular-nums privacy-blur ${
                                                itemBatal.bisa_dibatalkan ? 'text-rose-600' : 'text-brand-green-dark'
                                            }`}
                                        >
                                            {formatRupiah(itemBatal.total_bayar)}
                                        </p>
                                    </div>
                                </div>
                            </DrawerSection>

                            {itemBatal.bisa_dibatalkan ? (
                                <DrawerSection title="Rollback Konfirmasi Angsuran" icon={RotateCcw} tone="rose">
                                    <form id={batalFormId} onSubmit={submitBatal}>
                                        <FormField
                                            label="Alasan Pembatalan / Rollback"
                                            error={batalForm.errors.alasan}
                                            required
                                            hint="Contoh: Salah contreng cicilan anggota saat konfirmasi massal"
                                        >
                                            <TextField
                                                data-autofocus
                                                size="sm"
                                                as="textarea"
                                                rows={3}
                                                value={batalForm.data.alasan}
                                                onChange={(e) => batalForm.setData('alasan', e.target.value)}
                                                placeholder="Jelaskan alasan pembatalan konfirmasi angsuran ini..."
                                                required
                                            />
                                        </FormField>
                                    </form>
                                </DrawerSection>
                            ) : (
                                <DrawerSection title="Status Rollback" icon={AlertCircle} tone="amber">
                                    <p className="text-xs sm:text-sm text-amber-900">
                                        {itemBatal.alasan_tidak_bisa_batal ?? 'Angsuran ini tidak memenuhi syarat untuk dibatalkan.'}
                                    </p>
                                </DrawerSection>
                            )}

                            <DrawerSection title="Rincian Angsuran" icon={Wallet}>
                                <DrawerKeyValue
                                    rows={[
                                        { label: 'Anggota', value: itemBatal.nama },
                                        { label: 'No. Karyawan / Cabang', value: `${itemBatal.no_karyawan} • ${itemBatal.cabang}` },
                                        {
                                            label: 'Cicilan',
                                            value: `Ke-${itemBatal.cicilan_ke}${itemBatal.is_percepatan ? ' (Perubahan tenor)' : ''}`,
                                        },
                                        {
                                            label: 'Jatuh Tempo / Dikonfirmasi',
                                            value: `${itemBatal.tanggal_jatuh_tempo} / ${itemBatal.tanggal_konfirmasi_bayar ?? '—'}`,
                                        },
                                        {
                                            label: 'Pokok + Bunga',
                                            value: `${formatRupiah(itemBatal.nominal_pokok)} + ${formatRupiah(itemBatal.nominal_bunga)}`,
                                            privacy: true,
                                        },
                                        {
                                            label: 'Total Bayar',
                                            value: formatRupiah(itemBatal.total_bayar),
                                            tone: itemBatal.bisa_dibatalkan ? 'rose' : 'emerald',
                                            emphasize: true,
                                            privacy: true,
                                        },
                                    ]}
                                />
                            </DrawerSection>
                        </DrawerBody>

                        <DrawerFooter>
                            <DrawerFooterActions cols={itemBatal.bisa_dibatalkan ? 2 : 1} stackOnMobile>
                                <Button type="button" variant="neutral" size="drawer" onClick={() => setItemBatal(null)}>
                                    Tutup
                                </Button>
                                {itemBatal.bisa_dibatalkan && (
                                    <Button type="submit" form={batalFormId} variant="danger" size="drawer" disabled={batalForm.processing}>
                                        <RotateCcw size={16} aria-hidden="true" />
                                        {batalForm.processing ? 'Memproses...' : 'Batalkan Konfirmasi Angsuran'}
                                    </Button>
                                )}
                            </DrawerFooterActions>
                        </DrawerFooter>
                    </>
                )}
            </Drawer>
        </AppLayout>
    );
}
