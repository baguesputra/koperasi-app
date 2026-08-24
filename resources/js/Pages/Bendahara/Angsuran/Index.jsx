import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { TrendingUp, Wallet, Calendar, Check, ChevronLeft, ChevronRight, Search } from 'lucide-react';
import Card from '@/Components/ui/Card';
import StatWidget from '@/Components/ui/StatWidget';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

export default function Index({ bulan, daftarAngsuran, cabangAktif, daftarCabang, tagihanPerCabang, totalTagihanBulanIni, totalKeuntunganBulanIni, totalKeuntunganKeseluruhan }) {
    const [terpilih, setTerpilih] = useState([]);
    const [processing, setProcessing] = useState(false);
    const [cari, setCari] = useState('');

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

    const kataCari = cari.trim().toLowerCase();
    const tampil = useMemo(
        () => daftarAngsuran.filter((a) =>
            !kataCari || a.nama.toLowerCase().includes(kataCari) || a.no_anggota.toLowerCase().includes(kataCari)
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

    const widgets = [
        {
            label: 'Total Tagihan Bulan Ini',
            value: formatRupiah(totalTagihanBulanIni),
            icon: Calendar,
            tone: 'amber',
        },
        {
            label: 'Keuntungan Bulan Ini',
            value: formatRupiah(totalKeuntunganBulanIni),
            icon: TrendingUp,
            tone: 'green',
        },
        {
            label: 'Total Keuntungan Keseluruhan',
            value: formatRupiah(totalKeuntunganKeseluruhan),
            icon: Wallet,
            tone: 'navy',
        },
    ];

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

            <PageHeader title="Konfirmasi Angsuran" subtitle="Tandai angsuran yang sudah dipotong dari gaji anggota">
                <div className="flex flex-wrap items-center gap-2">
                    <div className="flex items-center rounded-xl border border-slate-300 bg-white overflow-hidden">
                        <button
                            onClick={() => geserBulan(-1)}
                            aria-label={`Bulan sebelum ${labelBulan}`}
                            className={`px-2.5 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors ${fokusRing}`}
                        >
                            <ChevronLeft size={18} />
                        </button>
                        <input
                            type="month"
                            value={bulan}
                            onChange={(e) => e.target.value && ubahBulan(e.target.value)}
                            aria-label="Pilih bulan"
                            className="w-[9.5rem] px-2 py-2.5 text-sm font-semibold text-slate-700 border-x border-slate-300 bg-white focus:border-brand-green outline-none"
                        />
                        <button
                            onClick={() => geserBulan(1)}
                            aria-label={`Bulan setelah ${labelBulan}`}
                            className={`px-2.5 py-2.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors ${fokusRing}`}
                        >
                            <ChevronRight size={18} />
                        </button>
                    </div>
                    {bulan !== kunciBulanIni && (
                        <button
                            onClick={() => ubahBulan(kunciBulanIni)}
                            className={`px-4 py-2.5 text-sm font-semibold text-brand-green-dark bg-brand-green-light rounded-xl hover:bg-brand-green/20 transition-colors ${fokusRing}`}
                        >
                            Bulan ini
                        </button>
                    )}
                </div>
            </PageHeader>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                {widgets.map((w) => (
                    <StatWidget key={w.label} label={w.label} value={w.value} icon={w.icon} tone={w.tone} />
                ))}
            </div>

            <div className="relative mb-6">
                <div className="flex items-center gap-2 bg-slate-100 p-1 rounded-xl w-fit max-w-full overflow-x-auto scrollbar-hide">
                    {tab.map((t) => (
                        <button
                            key={t.key}
                            onClick={() => pindahTab(t.key)}
                            aria-current={cabangAktif === t.key ? 'true' : undefined}
                            className={`flex flex-col items-start px-4 py-2 text-sm font-semibold rounded-lg whitespace-nowrap transition-colors shrink-0 ${fokusRing} ${
                                cabangAktif === t.key ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            <span>{t.label}</span>
                            <span className={`text-xs font-normal ${cabangAktif === t.key ? 'text-brand-green' : 'text-slate-400'}`}>
                                {formatRupiah(t.nominal)}
                            </span>
                        </button>
                    ))}
                </div>
                <div aria-hidden="true" className="pointer-events-none absolute inset-y-0 right-0 w-10 bg-gradient-to-l from-slate-100 to-transparent rounded-r-xl" />
            </div>

            <Card padding="none">
                <div className="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <h2 className="text-lg font-bold text-slate-800">
                        Jatuh Tempo {labelBulan}
                        <span className="ml-2 text-base font-normal text-slate-400">
                            {tampil.length}{kataCari && daftarAngsuran.length > 0 ? ` dari ${daftarAngsuran.length}` : ''} angsuran belum dikonfirmasi
                        </span>
                    </h2>
                    {tampil.length > 0 && (
                        <button
                            onClick={toggleSemua}
                            className={`text-sm font-semibold text-brand-green hover:text-brand-green-dark ${fokusRing} rounded-md px-1`}
                        >
                            {semuaTampilTerpilih ? 'Batalkan semua' : 'Pilih semua'}
                        </button>
                    )}
                </div>

                {daftarCabang.length > 0 && (
                    <div className="px-5 pt-4 pb-1">
                        <div className="relative max-w-xs">
                            <Search size={16} aria-hidden="true" className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                type="search"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Cari nama / no. anggota..."
                                aria-label="Cari nama atau nomor anggota"
                                className="w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-slate-300 bg-white placeholder:text-slate-400 focus:border-brand-green outline-none"
                            />
                        </div>
                    </div>
                )}

                {daftarAngsuran.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <Calendar size={28} aria-hidden="true" className="mx-auto text-slate-300 mb-3" />
                        <p className="text-base text-slate-500">Tidak ada angsuran jatuh tempo di {labelBulan}.</p>
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
                    <p className="text-base text-slate-400 text-center py-10">
                        Tidak ada yang cocok dengan pencarian &ldquo;{cari}&rdquo;.
                    </p>
                ) : (
                    <div className="divide-y divide-slate-50">
                        {tampil.map((a) => (
                            <label
                                key={a.id}
                                className="flex items-start gap-3 sm:gap-4 px-4 sm:px-5 py-4 hover:bg-slate-50 has-[:focus-visible]:bg-slate-50 cursor-pointer transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    checked={terpilih.includes(a.id)}
                                    onChange={() => toggleSatu(a.id)}
                                    aria-label={`Pilih angsuran ${a.nama}, cicilan ke-${a.cicilan_ke}`}
                                    className="mt-1 w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-brand-green/30"
                                />
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <p className="text-base font-semibold text-slate-800">{a.nama}</p>
                                        {a.terlambat && (
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700">
                                                Terlambat
                                            </span>
                                        )}
                                        {a.ada_pengajuan_percepatan && (
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700">
                                                Perubahan tenor diajukan
                                            </span>
                                        )}
                                    </div>
                                    <p className="text-sm text-slate-400 mt-0.5">
                                        {a.no_anggota} &bull; {a.cabang} &bull; Cicilan ke-{a.cicilan_ke} &bull; Jatuh tempo {a.tanggal_jatuh_tempo}
                                    </p>
                                </div>
                                <div className="text-right shrink-0 pl-2">
                                    <p className="text-base font-bold text-slate-800 whitespace-nowrap">{formatRupiah(a.total_bayar)}</p>
                                    <p className="text-sm text-brand-green whitespace-nowrap">+{formatRupiah(a.nominal_bunga)} untung</p>
                                </div>
                            </label>
                        ))}
                    </div>
                )}
            </Card>

            {terpilih.length > 0 && (
                <div className="sticky bottom-0 z-30 -mx-4 sm:-mx-6 lg:-mx-8 mt-6 bg-white border-t border-slate-200 shadow-lg px-4 sm:px-6 pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:py-4">
                    <div className="max-w-[1400px] mx-auto flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <p className="text-base font-semibold text-slate-700 text-center sm:text-left">
                            {terpilih.length} angsuran dipilih &middot;{' '}
                            <span className="text-brand-green-dark">{formatRupiah(totalTerpilih)}</span>
                        </p>
                        <Button variant="primary" onClick={konfirmasi} disabled={processing} className="w-full sm:w-auto">
                            <Check size={18} aria-hidden="true" />
                            {processing ? 'Memproses...' : 'Konfirmasi Terpilih'}
                        </Button>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
