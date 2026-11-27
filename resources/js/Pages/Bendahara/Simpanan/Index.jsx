import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useId, useMemo, useState } from 'react';
import { HeartHandshake, Check, ChevronLeft, ChevronRight, Search, X, RefreshCw, MapPin, Calendar, RotateCcw, AlertCircle, Clock, AlarmClock, Users, Wallet } from 'lucide-react';
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
    isBulanDepan: isBulanDepanProp,
    tabAwal = 'belum',
    belumSimpanan = [],
    terlambatSimpanan = [],
    sudahSimpanan = [],
    totalSudahSimpanan = 0,
    totalTerlambatSimpanan = 0,
    dipindahKeBulanIni = { jumlah: 0, nominal: 0 },
    anggotaBelumGenapEnamBulan = [],
    cabangAktif = '',
    daftarCabang = [],
    ringkasanCabang = {},
    nominalPerAnggota = 0,
    totalDanaSosialTerkumpul = 0,
}) {
    const { errors } = usePage().props;
    const daftarCabangAman = Array.isArray(daftarCabang) ? daftarCabang : Object.values(daftarCabang ?? {});
    const [terpilih, setTerpilih] = useState([]);
    const [processing, setProcessing] = useState(false);
    const [cari, setCari] = useState('');
    const [tabStatus, setTabStatus] = useState(tabAwal);
    const [itemBatal, setItemBatal] = useState(null);
    const [itemDimuka, setItemDimuka] = useState(null);
    const batalFormId = useId();
    const dimukaFormId = useId();

    const batalForm = useForm({
        bulan_periode: bulan,
        alasan: '',
    });

    const dimukaForm = useForm({
        jumlah_bulan: 6,
    });

    useEffect(() => {
        setCari('');
        setTerpilih([]);
        setItemBatal(null);
        setItemDimuka(null);
        setTabStatus(tabAwal);
        batalForm.setData('bulan_periode', bulan);
    }, [bulan, cabangAktif, tabAwal]);

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

    const bulanIniDate = new Date();
    const kunciBulanIni = bulanIniServer || `${bulanIniDate.getFullYear()}-${String(bulanIniDate.getMonth() + 1).padStart(2, '0')}`;
    const isBulanLalu = bulan < kunciBulanIni;
    const isBulanDepan = isBulanDepanProp ?? (bulan > kunciBulanIni);

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
            route('bendahara.simpanan.index'),
            { bulan: kunciBulanIni, cabang: cabangAktif, tab: 'terlambat' },
            { preserveState: true }
        );
    }

    function pindahTab(cabang) {
        router.get(route('bendahara.simpanan.index'), { bulan, cabang }, { preserveState: true });
    }

    function resetFilter() {
        setCari('');
        router.get(route('bendahara.simpanan.index'), { bulan: kunciBulanIni, cabang: '' }, { preserveState: true });
    }

    const kataCari = cari.trim().toLowerCase();
    const dataBelumSimpanan = Array.isArray(belumSimpanan) ? belumSimpanan : Object.values(belumSimpanan ?? {});
    const dataTerlambat = Array.isArray(terlambatSimpanan) ? terlambatSimpanan : Object.values(terlambatSimpanan ?? {});
    const dataSudahSimpanan = Array.isArray(sudahSimpanan) ? sudahSimpanan : Object.values(sudahSimpanan ?? {});
    const dataBelumGenap = Array.isArray(anggotaBelumGenapEnamBulan)
        ? anggotaBelumGenapEnamBulan
        : Object.values(anggotaBelumGenapEnamBulan ?? {});
    const tampil = useMemo(
        () => dataBelumSimpanan.filter((a) =>
            !kataCari || a.nama.toLowerCase().includes(kataCari) || (a.no_karyawan ?? '').toLowerCase().includes(kataCari)
        ),
        [dataBelumSimpanan, kataCari]
    );
    const tampilTerlambat = useMemo(
        () => dataTerlambat.filter((a) =>
            !kataCari
            || a.nama.toLowerCase().includes(kataCari)
            || (a.no_karyawan ?? '').toLowerCase().includes(kataCari)
            || (a.bulan_periode_label ?? '').toLowerCase().includes(kataCari)
            || (a.bulan_periode ?? '').toLowerCase().includes(kataCari)
        ),
        [dataTerlambat, kataCari]
    );
    const tampilSudah = useMemo(
        () => dataSudahSimpanan.filter((a) =>
            !kataCari || a.nama.toLowerCase().includes(kataCari) || (a.no_karyawan ?? '').toLowerCase().includes(kataCari)
        ),
        [dataSudahSimpanan, kataCari]
    );
    const tampilBelumGenap = useMemo(
        () => dataBelumGenap.filter((a) =>
            !kataCari || a.nama.toLowerCase().includes(kataCari) || (a.no_karyawan ?? '').toLowerCase().includes(kataCari)
        ),
        [dataBelumGenap, kataCari]
    );

    const tampilSelectable = useMemo(
        () => tampil.filter((a) => a.bisa_dipilih !== false),
        [tampil]
    );
    const semuaTampilTerpilih = tampilSelectable.length > 0 && tampilSelectable.every((a) => terpilih.includes(a.id));

    function toggleSatu(id, bisaDipilih = true) {
        if (!bisaDipilih || isBulanDepan) return;
        setTerpilih((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    }

    function toggleTerlambat(item) {
        if (isBulanDepan) return;
        const sudah = terpilih.includes(item.id);
        setTerpilih((prev) => {
            const milikAnggota = dataTerlambat.filter((r) => r.anggota_id === item.anggota_id);
            if (sudah) {
                const batalIds = new Set(
                    milikAnggota
                        .filter((r) => r.bulan_periode >= item.bulan_periode)
                        .map((r) => r.id)
                );
                return prev.filter((x) => !batalIds.has(x));
            }
            const tambahIds = milikAnggota
                .filter((r) => r.bulan_periode <= item.bulan_periode)
                .map((r) => r.id);
            return [...new Set([...prev, ...tambahIds])];
        });
    }

    function toggleSemua() {
        if (isBulanDepan || tampilSelectable.length === 0) return;
        setTerpilih((prev) => semuaTampilTerpilih
            ? prev.filter((id) => !tampilSelectable.some((a) => a.id === id))
            : [...new Set([...prev, ...tampilSelectable.map((a) => a.id)])]);
    }

    function konfirmasi() {
        setProcessing(true);
        const payload = tabStatus === 'terlambat'
            ? {
                items: dataTerlambat
                    .filter((a) => terpilih.includes(a.id))
                    .map((a) => ({ anggota_id: a.anggota_id, bulan_periode: a.bulan_periode })),
            }
            : { anggota_ids: terpilih, bulan_periode: bulan };

        router.post(
            route('bendahara.simpanan.konfirmasi'),
            payload,
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => setTerpilih([]),
                onFinish: () => setProcessing(false),
            })
        );
    }

    function bukaModalBatal(a) {
        batalForm.clearErrors();
        batalForm.setData({ bulan_periode: a.bulan_periode ?? bulan, alasan: '' });
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

    function bukaModalDimuka(a) {
        dimukaForm.clearErrors();
        dimukaForm.setData('jumlah_bulan', a.kekurangan_bulan ?? 6);
        setItemDimuka(a);
    }

    function submitDimuka(e) {
        e.preventDefault();
        if (!itemDimuka) return;
        dimukaForm.post(
            route('bendahara.simpanan.bayar-dimuka', itemDimuka.id),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    setItemDimuka(null);
                },
            })
        );
    }

    const totalBelumSemuaCabang = Object.values(ringkasanCabang ?? {}).reduce((total, r) => total + r.nominal, 0);
    const totalTerlambatCabang = dataTerlambat.reduce((sum, a) => sum + (a.nominal ?? nominalPerAnggota), 0);
    const jumlahTerlambatWidget = isBulanLalu ? (dipindahKeBulanIni?.jumlah ?? 0) : dataTerlambat.length;
    const nominalTerlambatWidget = isBulanLalu ? (dipindahKeBulanIni?.nominal ?? 0) : totalTerlambatCabang;
    const totalTerpilihSimpanan = tabStatus === 'terlambat'
        ? dataTerlambat
            .filter((a) => terpilih.includes(a.id))
            .reduce((sum, a) => sum + (a.nominal ?? nominalPerAnggota), 0)
        : terpilih.length * nominalPerAnggota;
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

            <UnifiedLedgerStrip
                columns={4}
                items={[
                    {
                        key: 'tagihan',
                        variant: 'anchor',
                        label: `Tagihan ${labelBulan}`,
                        icon: Calendar,
                        value: formatRupiah(totalBelumSemuaCabang),
                        meta: (
                            <>
                                {belumSimpanan.length} belum •{' '}
                                <PrivacyWrapper>{formatRupiah(nominalPerAnggota)}</PrivacyWrapper>/org
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
                        value: dataSudahSimpanan.length,
                        privacy: false,
                        onClick: () => gantiTab('sudah'),
                        active: tabStatus === 'sudah',
                        meta: (
                            <>
                                <PrivacyWrapper>{formatRupiah(totalSudahSimpanan)}</PrivacyWrapper> terkumpul
                            </>
                        ),
                    },
                    {
                        key: 'dansos',
                        label: 'Dana Sosial',
                        icon: HeartHandshake,
                        tone: 'navy',
                        value: formatRupiah(totalDanaSosialTerkumpul),
                        meta: 'Iuran sosial kumulatif',
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
                            Belum Dikonfirmasi ({dataBelumSimpanan.length})
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
                            Sudah Dikonfirmasi ({dataSudahSimpanan.length})
                        </button>
                        <button
                            type="button"
                            onClick={() => gantiTab('dimuka')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${fokusRing} ${
                                tabStatus === 'dimuka'
                                    ? 'bg-white text-amber-800 border border-amber-200/80 shadow-2xs'
                                    : 'text-slate-600 hover:text-[#0F1E36] border border-transparent'
                            }`}
                        >
                            Belum Genap 6 Bln ({dataBelumGenap.length})
                        </button>
                    </div>

                    <div className="flex flex-col md:flex-row md:items-center gap-2">
                        <div className="flex items-center gap-1.5 min-w-0">
                            <p className="text-sm text-slate-500 truncate">
                                {tabStatus === 'belum'
                                    ? 'Belum konfirmasi'
                                    : tabStatus === 'terlambat'
                                    ? 'Tunggakan sebelum'
                                    : tabStatus === 'sudah'
                                    ? 'Sudah konfirmasi'
                                    : 'Belum genap 6 bulan keanggotaan'}{' '}
                                {tabStatus !== 'dimuka' && (
                                    <span className="font-bold text-slate-800">{labelBulan}</span>
                                )}
                                <span className="text-slate-500">
                                    {' '}•{' '}
                                    {tabStatus === 'belum'
                                        ? tampil.length
                                        : tabStatus === 'terlambat'
                                        ? tampilTerlambat.length
                                        : tabStatus === 'sudah'
                                        ? tampilSudah.length
                                        : tampilBelumGenap.length}
                                    {kataCari &&
                                    (tabStatus === 'belum'
                                        ? dataBelumSimpanan.length
                                        : tabStatus === 'terlambat'
                                        ? dataTerlambat.length
                                        : tabStatus === 'sudah'
                                        ? dataSudahSimpanan.length
                                        : dataBelumGenap.length) > 0
                                        ? ` dari ${
                                              tabStatus === 'belum'
                                                  ? dataBelumSimpanan.length
                                                  : tabStatus === 'terlambat'
                                                  ? dataTerlambat.length
                                                  : tabStatus === 'sudah'
                                                  ? dataSudahSimpanan.length
                                                  : dataBelumGenap.length
                                          }`
                                        : ''}{' '}
                                    {tabStatus === 'terlambat' ? 'tagihan' : 'anggota'}
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
                            {tabStatus === 'belum' && !isBulanDepan && tampilSelectable.length > 0 && (
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

                {errors?.konfirmasi && (
                    <div className="my-2 rounded-xl border border-rose-200 bg-rose-50/80 px-3.5 py-2.5 flex items-start gap-2 text-xs sm:text-sm text-rose-800" role="alert">
                        <AlertCircle size={16} className="shrink-0 mt-0.5 text-rose-600" aria-hidden="true" />
                        <span>{errors.konfirmasi}</span>
                    </div>
                )}

                {(tabStatus === 'belum' || tabStatus === 'terlambat') && isBulanLalu ? (
                    <div className="text-center py-10 px-4">
                        <div className="max-w-lg mx-auto rounded-xl border border-slate-200/80 bg-slate-50/70 p-5">
                            <AlarmClock size={26} aria-hidden="true" className="mx-auto text-slate-400 mb-2.5" />
                            {(dipindahKeBulanIni?.jumlah ?? 0) > 0 ? (
                                <>
                                    <p className="text-sm font-semibold text-slate-800">
                                        {dipindahKeBulanIni.jumlah} tagihan simpanan wajib periode {labelBulan} belum dibayar (<span className="privacy-blur">{formatRupiah(dipindahKeBulanIni.nominal)}</span>)
                                    </p>
                                    <p className="text-xs sm:text-sm text-slate-500 mt-1">
                                        Tagihan yang belum dikonfirmasi dari bulan lalu sudah otomatis dipindahkan ke bulan berjalan agar pemrosesan tetap terpusat.
                                    </p>
                                </>
                            ) : (
                                <>
                                    <p className="text-sm font-semibold text-slate-800">
                                        Tidak ada tunggakan simpanan wajib periode {labelBulan}.
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
                    belumSimpanan.length === 0 ? (
                        <div className="text-center py-12 px-4">
                            <Check size={28} aria-hidden="true" className="mx-auto text-brand-green mb-3" />
                            <p className="text-sm font-semibold text-slate-600">Semua anggota aktif sudah dikonfirmasi untuk {labelBulan}.</p>
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
                        <>
                            {isBulanDepan && (
                                <div className="rounded-xl bg-slate-50 border border-slate-200/80 px-3 py-2 text-xs text-slate-600 mb-2 mt-1">
                                    Pratinjau periode mendatang. Simpanan wajib hanya dapat dikonfirmasi pada bulan berjalan (atau gunakan Bayar di Muka untuk anggota &lt; 6 bulan).
                                </div>
                            )}
                            <ul className="divide-y divide-slate-50">
                                {tampil.map((a) => {
                                    const terkunci = isBulanDepan || a.bisa_dipilih === false;
                                    const dipilih = !terkunci && terpilih.includes(a.id);
                                    return (
                                        <li key={a.id}>
                                            <label
                                                className={`flex items-center gap-3 px-2 py-3 rounded-xl transition-all has-[:focus-visible]:bg-brand-green-light/40 ${
                                                    terkunci
                                                        ? 'opacity-70 bg-slate-50/40 cursor-not-allowed'
                                                        : dipilih
                                                            ? 'bg-brand-green-light/45 hover:bg-brand-green-light/65 cursor-pointer'
                                                            : 'hover:bg-brand-green-light/30 cursor-pointer'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={dipilih}
                                                    disabled={terkunci}
                                                    onChange={() => toggleSatu(a.id, !terkunci)}
                                                    aria-label={`Pilih simpanan wajib ${a.nama}`}
                                                    className="w-5 h-5 rounded-lg border-slate-300 text-brand-green focus:ring-brand-green/30 disabled:opacity-45 disabled:cursor-not-allowed shrink-0"
                                                />
                                                <span className="hidden sm:inline-flex shrink-0">
                                                    <FotoAnggota nama={a.nama} fotoUrl={a.foto_url} ukuran="sm" />
                                                </span>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-1.5 flex-wrap">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{a.nama}</p>
                                                        {isBulanDepan ? (
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/80">
                                                                Periode Mendatang
                                                            </span>
                                                        ) : a.ada_tunggakan_terlambat ? (
                                                            <button
                                                                type="button"
                                                                onClick={(e) => {
                                                                    e.preventDefault();
                                                                    e.stopPropagation();
                                                                    bukaTerlambatBulanIni();
                                                                }}
                                                                className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/80 hover:bg-rose-100 transition-colors"
                                                            >
                                                                Tunggakan {a.periode_terlambat_tertua} →
                                                            </button>
                                                        ) : null}
                                                    </div>
                                                    <p className="text-xs text-slate-500 mt-0.5 truncate">{a.no_karyawan} • {a.cabang}</p>
                                                </div>
                                                <div className="text-right shrink-0 pl-2">
                                                    <p className="text-sm font-bold text-slate-800 whitespace-nowrap tabular-nums privacy-blur">{formatRupiah(nominalPerAnggota)}</p>
                                                    <p className="text-[11px] text-slate-500">wajib + dansos</p>
                                                </div>
                                            </label>
                                        </li>
                                    );
                                })}
                            </ul>
                        </>
                    )
                ) : tabStatus === 'terlambat' ? (
                    dataTerlambat.length === 0 ? (
                        <div className="text-center py-12 px-4">
                            <Check size={28} aria-hidden="true" className="mx-auto text-brand-green mb-3" />
                            <p className="text-sm font-semibold text-slate-700">Tidak ada tunggakan simpanan wajib dari bulan-bulan sebelumnya.</p>
                        </div>
                    ) : tampilTerlambat.length === 0 ? (
                        <p className="text-sm text-slate-500 text-center py-10">
                            Tidak ada simpanan terlambat yang cocok dengan pencarian &ldquo;{cari}&rdquo;.
                        </p>
                    ) : (
                        <>
                            <div className="rounded-xl bg-rose-50/60 border border-rose-200/70 px-3 py-2 text-xs text-rose-800 mb-2 mt-1">
                                Pilih manual simpanan terlambat yang sudah dibayar. Tagihan di tab ini tidak ikut terkonfirmasi saat klik Pilih semua di bulan berjalan.
                            </div>
                            <ul className="divide-y divide-slate-50">
                                {tampilTerlambat.map((a) => {
                                    const dipilih = terpilih.includes(a.id);
                                    const nominalItem = a.nominal ?? nominalPerAnggota;
                                    return (
                                        <li key={a.id}>
                                            <label
                                                className={`flex items-center gap-3 px-2 py-3 rounded-xl cursor-pointer transition-all has-[:focus-visible]:bg-rose-50/70 ${
                                                    dipilih ? 'bg-rose-50/70 hover:bg-rose-50' : 'hover:bg-slate-50'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={dipilih}
                                                    onChange={() => toggleTerlambat(a)}
                                                    aria-label={`Pilih simpanan terlambat ${a.nama} periode ${a.bulan_periode_label}`}
                                                    className="w-5 h-5 rounded-lg border-slate-300 text-brand-green focus:ring-brand-green/30 shrink-0"
                                                />
                                                <span className="hidden sm:inline-flex shrink-0">
                                                    <FotoAnggota nama={a.nama} fotoUrl={a.foto_url} ukuran="sm" />
                                                </span>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-1.5 flex-wrap">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">{a.nama}</p>
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200/80 shadow-2xs">
                                                            Terlambat
                                                        </span>
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                                                            Periode {a.bulan_periode_label}
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-slate-500 mt-0.5 truncate">{a.no_karyawan} • {a.cabang}</p>
                                                </div>
                                                <div className="text-right shrink-0 pl-2">
                                                    <p className="text-sm font-bold text-slate-800 whitespace-nowrap tabular-nums privacy-blur">{formatRupiah(nominalItem)}</p>
                                                    <p className="text-[11px] text-slate-500">wajib + dansos</p>
                                                </div>
                                            </label>
                                        </li>
                                    );
                                })}
                            </ul>
                        </>
                    )
                ) : tabStatus === 'sudah' ? (
                    dataSudahSimpanan.length === 0 ? (
                        <div className="text-center py-12 px-4">
                            <Users size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                            <p className="text-sm font-semibold text-slate-600">Belum ada simpanan yang dikonfirmasi untuk {labelBulan}.</p>
                        </div>
                    ) : tampilSudah.length === 0 ? (
                        <p className="text-sm text-slate-500 text-center py-10">
                            Tidak ada yang cocok dengan pencarian &ldquo;{cari}&rdquo;.
                        </p>
                    ) : (
                        <ul className="divide-y divide-slate-50">
                            {tampilSudah.map((a) => (
                                <li
                                    key={a.key ?? `${a.id}|${a.bulan_periode ?? bulan}`}
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
                                            {a.dari_bulan_lain && a.bulan_periode_label && (
                                                <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/80">
                                                    Periode {a.bulan_periode_label}
                                                </span>
                                            )}
                                            <ChevronRight size={14} className="text-slate-300 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all" aria-hidden="true" />
                                        </div>
                                        <p className="text-xs text-slate-500 mt-0.5 truncate">
                                            {a.no_karyawan} • {a.cabang}{a.tanggal_input ? ` • Dikonfirmasi ${a.tanggal_input}` : ''}
                                        </p>
                                    </div>
                                    <div className="text-right shrink-0 pl-2">
                                        <p className="text-sm font-bold text-slate-800 whitespace-nowrap tabular-nums privacy-blur">{formatRupiah(a.nominal_total)}</p>
                                        <p className="text-[11px] text-slate-500">wajib + dansos</p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )
                ) : dataBelumGenap.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <Check size={28} aria-hidden="true" className="mx-auto text-brand-green mb-3" />
                        <p className="text-sm font-semibold text-slate-600">Tidak ada anggota aktif di bawah 6 bulan yang masih memiliki kekurangan simpanan wajib.</p>
                    </div>
                ) : tampilBelumGenap.length === 0 ? (
                    <p className="text-sm text-slate-400 text-center py-10">
                        Tidak ada yang cocok dengan pencarian &ldquo;{cari}&rdquo;.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-50">
                        {tampilBelumGenap.map((a) => (
                            <li
                                key={a.id}
                                onClick={() => bukaModalDimuka(a)}
                                onKeyDown={(e) => {
                                    if (e.target === e.currentTarget && (e.key === 'Enter' || e.key === ' ')) {
                                        e.preventDefault();
                                        bukaModalDimuka(a);
                                    }
                                }}
                                tabIndex={0}
                                title="Klik untuk mencatat pembayaran simpanan di muka hingga genap 6 bulan"
                                className="group flex items-center gap-3 px-2 py-3 rounded-xl cursor-pointer hover:bg-brand-green-light/30 transition-all focus-visible:outline-none focus-visible:bg-brand-green-light/40"
                            >
                                <span className="hidden sm:inline-flex shrink-0">
                                    <FotoAnggota nama={a.nama} fotoUrl={a.foto_url} ukuran="sm" />
                                </span>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-1.5 flex-wrap">
                                        <p className="text-sm font-semibold text-slate-800 group-hover:text-brand-navy truncate">{a.nama}</p>
                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/80 shadow-2xs">
                                            {a.bulan_terbayar}/6 bln
                                        </span>
                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/80">
                                            Kurang {a.kekurangan_bulan} bln
                                        </span>
                                        <ChevronRight size={14} className="text-slate-300 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all" aria-hidden="true" />
                                    </div>
                                    <p className="text-xs text-slate-400 mt-0.5 truncate">
                                        {a.no_karyawan} • {a.cabang}{a.tanggal_jadi_anggota ? ` • Gabung ${a.tanggal_jadi_anggota}` : ''}
                                    </p>
                                </div>
                                <div className="text-right shrink-0 pl-2">
                                    <p className="text-sm font-bold text-slate-800 whitespace-nowrap tabular-nums privacy-blur">{formatRupiah(a.total_pelunasan)}</p>
                                    <p className="text-[11px] text-slate-400">wajib + dansos</p>
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
                            <span className="text-brand-green-dark font-bold privacy-blur">{formatRupiah(totalTerpilihSimpanan)}</span>
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
                title="Rincian & Rollback Konfirmasi Simpanan"
                description="Membatalkan konfirmasi akan menghapus simpanan wajib periode ini dan menerbitkan jurnal pembalik (storno) di Buku Kas."
                badge={
                    itemBatal && (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full border text-xs font-semibold bg-brand-green-light text-brand-green-dark border-emerald-200/80">
                            Terkonfirmasi
                        </span>
                    )
                }
            >
                {itemBatal && (
                    <>
                        <DrawerBody>
                            <DrawerSection tone={itemBatal.bisa_dibatalkan ? 'rose' : 'default'} padding="sm">
                                <div className="flex items-center gap-3">
                                    <FotoAnggota nama={itemBatal.nama} fotoUrl={itemBatal.foto_url} ukuran="md" className="shrink-0" />
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-bold text-slate-900 truncate">{itemBatal.nama}</p>
                                        <p className="text-xs text-slate-500 truncate mt-0.5">
                                            <span className="font-mono">{itemBatal.no_karyawan}</span> • {itemBatal.cabang} • {itemBatal.bulan_periode_label ?? labelBulan}
                                        </p>
                                    </div>
                                    <div className="text-right shrink-0">
                                        <p className="text-[11px] text-slate-500">Total Simpanan</p>
                                        <p className="text-base sm:text-lg font-extrabold text-rose-600 tabular-nums privacy-blur">
                                            {formatRupiah(itemBatal.nominal_total)}
                                        </p>
                                    </div>
                                </div>
                            </DrawerSection>

                            {itemBatal.bisa_dibatalkan ? (
                                <DrawerSection title="Rollback Konfirmasi Simpanan" icon={RotateCcw} tone="rose">
                                    <form id={batalFormId} onSubmit={submitBatal}>
                                        <FormField
                                            label="Alasan Pembatalan / Rollback"
                                            error={batalForm.errors.alasan}
                                            required
                                            hint="Contoh: Salah contreng anggota saat konfirmasi massal"
                                        >
                                            <TextField
                                                data-autofocus
                                                size="sm"
                                                as="textarea"
                                                rows={3}
                                                value={batalForm.data.alasan}
                                                onChange={(e) => batalForm.setData('alasan', e.target.value)}
                                                placeholder="Jelaskan alasan pembatalan konfirmasi simpanan ini..."
                                                required
                                            />
                                        </FormField>
                                    </form>
                                </DrawerSection>
                            ) : (
                                <DrawerSection title="Status Rollback" icon={AlertCircle} tone="amber">
                                    <p className="text-xs sm:text-sm text-amber-900">
                                        {itemBatal.alasan_tidak_bisa_batal
                                            ? itemBatal.alasan_tidak_bisa_batal
                                            : `Anggota ini berstatus ${itemBatal.status_anggota} sehingga konfirmasi simpanan tidak dapat dibatalkan.`}
                                    </p>
                                </DrawerSection>
                            )}

                            <DrawerSection title="Rincian Komponen" icon={Wallet}>
                                <DrawerKeyValue
                                    rows={[
                                        { label: 'Anggota', value: itemBatal.nama },
                                        { label: 'No. Karyawan / Cabang', value: `${itemBatal.no_karyawan} • ${itemBatal.cabang}` },
                                        { label: 'Periode', value: itemBatal.bulan_periode_label ?? labelBulan },
                                        { label: 'Simpanan Wajib', value: formatRupiah(itemBatal.nominal_wajib), privacy: true },
                                        { label: 'Dana Sosial', value: formatRupiah(itemBatal.nominal_dana_sosial), privacy: true },
                                        { label: 'Total Dibalikkan', value: formatRupiah(itemBatal.nominal_total), tone: 'rose', emphasize: true, privacy: true },
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
                                        {batalForm.processing ? 'Memproses...' : 'Batalkan Konfirmasi Simpanan'}
                                    </Button>
                                )}
                            </DrawerFooterActions>
                        </DrawerFooter>
                    </>
                )}
            </Drawer>

            <Drawer
                show={Boolean(itemDimuka)}
                layout="split"
                onClose={() => setItemDimuka(null)}
                title="Bayar Simpanan di Muka (Syarat 6 Bulan)"
                description="Catat pelunasan simpanan wajib + dana sosial ke depan agar anggota di bawah 6 bulan langsung memenuhi syarat pinjaman."
                badge={
                    itemDimuka && (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full border text-xs font-semibold bg-amber-50 text-amber-800 border-amber-200/80">
                            {itemDimuka.bulan_terbayar}/6 bulan
                        </span>
                    )
                }
            >
                {itemDimuka && (() => {
                    const jumlahBulanDipilih = Math.min(
                        Math.max(1, Number(dimukaForm.data.jumlah_bulan || itemDimuka.kekurangan_bulan || 1)),
                        itemDimuka.kekurangan_bulan || 1
                    );
                    const totalWajibDimuka = jumlahBulanDipilih * (itemDimuka.nominal_wajib || 0);
                    const totalDansosDimuka = jumlahBulanDipilih * (itemDimuka.nominal_dana_sosial || 0);
                    const totalBayarDimuka = jumlahBulanDipilih * (itemDimuka.nominal_per_bulan || 0);
                    const periodePreview = (itemDimuka.periode_berikutnya ?? []).slice(0, jumlahBulanDipilih);

                    return (
                        <>
                            <DrawerBody>
                                <DrawerSection padding="sm">
                                    <div className="flex items-center gap-3">
                                        <FotoAnggota nama={itemDimuka.nama} fotoUrl={itemDimuka.foto_url} ukuran="md" className="shrink-0" />
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-bold text-slate-900 truncate">{itemDimuka.nama}</p>
                                            <p className="text-xs text-slate-500 truncate mt-0.5">
                                                <span className="font-mono">{itemDimuka.no_karyawan}</span> • {itemDimuka.cabang}
                                                {itemDimuka.tanggal_jadi_anggota ? ` • Gabung ${itemDimuka.tanggal_jadi_anggota}` : ''}
                                            </p>
                                        </div>
                                        <div className="text-right shrink-0">
                                            <p className="text-[11px] text-slate-500">Total di Muka</p>
                                            <p className="text-base sm:text-lg font-extrabold text-brand-green-dark tabular-nums privacy-blur">
                                                {formatRupiah(totalBayarDimuka)}
                                            </p>
                                        </div>
                                    </div>
                                </DrawerSection>

                                <DrawerSection title="Pembayaran di Muka" icon={Calendar}>
                                    <form id={dimukaFormId} onSubmit={submitDimuka} className="space-y-3">
                                        <FormField
                                            label="Jumlah Bulan Dibayar"
                                            error={dimukaForm.errors.jumlah_bulan}
                                            required
                                            hint={`Kekurangan saat ini ${itemDimuka.kekurangan_bulan} bulan untuk genap 6 bulan keanggotaan.`}
                                        >
                                            <TextField
                                                data-autofocus
                                                as="select"
                                                size="sm"
                                                value={jumlahBulanDipilih}
                                                onChange={(e) => dimukaForm.setData('jumlah_bulan', Number(e.target.value))}
                                            >
                                                {Array.from({ length: itemDimuka.kekurangan_bulan }, (_, i) => i + 1).map((n) => (
                                                    <option key={n} value={n}>
                                                        {n} bulan{n === itemDimuka.kekurangan_bulan ? ' (Genap 6 bulan — buka pinjaman)' : ''}
                                                    </option>
                                                ))}
                                            </TextField>
                                        </FormField>

                                        {periodePreview.length > 0 && (
                                            <div>
                                                <p className="text-xs font-semibold text-slate-600 mb-1.5">Periode yang akan dicatat</p>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {periodePreview.map((p) => (
                                                        <span
                                                            key={p}
                                                            className="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold font-mono bg-emerald-50 text-emerald-800 border border-emerald-200/80"
                                                        >
                                                            {p}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </form>
                                </DrawerSection>

                                <DrawerSection title="Rincian Komponen" icon={Wallet}>
                                    <DrawerKeyValue
                                        rows={[
                                            { label: 'Progres Simpanan Wajib', value: `${itemDimuka.bulan_terbayar}/6 bulan` },
                                            { label: 'Periode Dibayar', value: `${jumlahBulanDipilih} bulan (${periodePreview.join(', ')})` },
                                            { label: 'Simpanan Wajib', value: formatRupiah(totalWajibDimuka), privacy: true },
                                            { label: 'Dana Sosial', value: formatRupiah(totalDansosDimuka), privacy: true },
                                            { label: 'Total Masuk Kas', value: formatRupiah(totalBayarDimuka), emphasize: true, privacy: true },
                                        ]}
                                    />
                                </DrawerSection>
                            </DrawerBody>

                            <DrawerFooter>
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="neutral" size="drawer" onClick={() => setItemDimuka(null)}>
                                        Batal
                                    </Button>
                                    <Button type="submit" form={dimukaFormId} variant="primary" size="drawer" disabled={dimukaForm.processing}>
                                        <Check size={16} aria-hidden="true" />
                                        {dimukaForm.processing ? 'Memproses...' : 'Konfirmasi Bayar di Muka'}
                                    </Button>
                                </DrawerFooterActions>
                            </DrawerFooter>
                        </>
                    );
                })()}
            </Drawer>
        </AppLayout>
    );
}
