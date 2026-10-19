import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { Download, RotateCcw, Stamp } from 'lucide-react';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import BackLink from '@/Components/ui/BackLink';
import Card from '@/Components/ui/Card';
import { withIdempotencyKey } from '@/Utils/idempotency';
import { ringkasLive } from '@/Utils/shuLive';
import RingkasanShu from './Partials/RingkasanShu';
import KomponenEditor from './Partials/KomponenEditor';
import DistribusiTable from './Partials/DistribusiTable';
import BayarTab from './Partials/BayarTab';

const TABS = [
    { kunci: 'ringkasan', label: 'Ringkasan' },
    { kunci: 'komponen', label: 'Komponen SHU' },
    { kunci: 'distribusi', label: 'Distribusi SHU' },
    { kunci: 'bayar', label: 'Bayar' },
];

const BADGE = {
    draft: 'bg-amber-100 text-amber-700',
    diajukan: 'bg-sky-100 text-sky-700',
    final: 'bg-brand-green-light text-brand-green-dark',
};

export default function Show({ periode, ringkasan, detail, sums = {}, daftarAnggota = [], filters = {}, komponen_bayar = [] }) {
    const tabAwal = typeof window !== 'undefined'
        ? new URLSearchParams(window.location.search).get('tab')
        : null;
    const [tab, setTab] = useState(TABS.some((t) => t.kunci === tabAwal) ? tabAwal : 'ringkasan');

    // Live state: persen draft yang sedang diketik (mirror Excel, belum tersimpan).
    const [persenLive, setPersenLive] = useState(periode.persen);
    const [customLive, setCustomLive] = useState(periode.komponen_custom ?? []);
    const persenTersimpan = useMemo(() => JSON.stringify(periode.persen), [periode.persen]);
    const customTersimpan = useMemo(() => JSON.stringify(periode.komponen_custom ?? []), [periode]);
    const isPreview = JSON.stringify(persenLive) !== persenTersimpan || JSON.stringify(customLive) !== customTersimpan;

    // Sinkron ulang setelah server menyimpan (Simpan Persen / Hitung Ulang).
    useEffect(() => {
        setPersenLive(periode.persen);
    }, [persenTersimpan]);
    useEffect(() => {
        setCustomLive(periode.komponen_custom ?? []);
    }, [customTersimpan]);

    const preview = useMemo(
        () => ringkasLive(periode.total_shu, persenLive, ringkasan.total_modal, ringkasan.total_transaksi, customLive),
        [periode.total_shu, persenLive, ringkasan.total_modal, ringkasan.total_transaksi, customLive]
    );

    const liveRows = isPreview && periode.status === 'draft'
        ? {
            totalModal: ringkasan.total_modal,
            totalTransaksi: ringkasan.total_transaksi,
            poolSimpanan: preview.komponen.jasa_simpanan,
            poolTransaksi: preview.komponen.jasa_transaksi,
        }
        : null;

    const [bayarAkun, setBayarAkun] = useState('bank');
    const [bayarTanggal, setBayarTanggal] = useState(new Date().toISOString().slice(0, 10));

    function kembalikan() {
        if (!confirm(`Kembalikan SHU ${periode.tahun_buku} ke draft untuk revisi?`)) return;
        router.post(route('shu.kembalikan', periode.id), {}, withIdempotencyKey({ preserveScroll: true }));
    }

    function finalisasi() {
        if (!confirm(`Setujui & finalisasi SHU ${periode.tahun_buku}? Setelah final tidak bisa diubah.`)) return;
        router.post(route('shu.finalisasi', periode.id), {}, withIdempotencyKey({ preserveScroll: true }));
    }

    function bayarSatu(id) {
        router.post(
            route('shu.detail.bayar', id),
            { akun: bayarAkun, tanggal: bayarTanggal },
            withIdempotencyKey({ preserveScroll: true })
        );
    }

    return (
        <AppLayout>
            <Head title={`SHU ${periode.tahun_buku}`} />

            <BackLink href={route('shu.index')}>Semua periode SHU</BackLink>
            <PageHeader
                title={`SHU Tahun Buku ${periode.tahun_buku}`}
                subtitle={
                    periode.status === 'final'
                        ? `Final ${periode.finalized_at ?? ''}`
                        : periode.status === 'diajukan'
                            ? `Diajukan ${periode.diajukan_at ?? ''} — bahan meeting, dikunci`
                            : 'Draft — ubah & hitung ulang kapan pun sebelum diajukan'
                }
            >
                <span className={`text-xs font-bold px-2.5 py-1 rounded-full ${BADGE[periode.status] ?? BADGE.draft}`}>
                    {periode.status === 'final' ? 'Final' : periode.status === 'diajukan' ? 'Diajukan' : 'Draft'}
                </span>
                <a href={route('shu.export', periode.id)}>
                    <Button size="sm" variant="secondary">
                        <Download size={16} />
                        Excel
                    </Button>
                </a>
            </PageHeader>

            {periode.status === 'diajukan' && (
                <Card className="mb-4 border-sky-300">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-sm text-slate-600 flex-1 min-w-[200px]">
                            Periode dikunci untuk dibahas meeting. Kembalikan ke draft bila ada revisi,
                            atau setujui untuk finalisasi.
                        </p>
                        <Button size="sm" variant="secondary" onClick={kembalikan}>
                            <RotateCcw size={16} />
                            Kembalikan ke Draft
                        </Button>
                        <Button size="sm" onClick={finalisasi}>
                            <Stamp size={16} />
                            Setujui & Finalisasi
                        </Button>
                    </div>
                </Card>
            )}

            <div className="flex gap-1 mb-4 border-b border-slate-200" role="tablist" aria-label="Bagian SHU">
                {TABS.map((t) => (
                    <button
                        key={t.kunci}
                        type="button"
                        role="tab"
                        aria-selected={tab === t.kunci}
                        onClick={() => setTab(t.kunci)}
                        className={`px-4 py-2.5 text-sm font-semibold -mb-px border-b-2 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40 rounded-t-lg ${
                            tab === t.kunci
                                ? 'border-brand-green text-brand-green-dark'
                                : 'border-transparent text-slate-500 hover:text-slate-800'
                        }`}
                    >
                        {t.label}
                        {t.kunci === 'bayar' && !periode.is_final && (
                            <span className="ml-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-400">
                                Terkunci
                            </span>
                        )}
                    </button>
                ))}
            </div>

            <div role="tabpanel">
                {tab === 'ringkasan' && (
                    <RingkasanShu
                        periode={periode}
                        persen={isPreview ? persenLive : periode.persen}
                        custom={isPreview ? customLive : (ringkasan.custom ?? periode.komponen_custom ?? [])}
                        preview={isPreview ? preview : {
                            komponen: ringkasan.komponen,
                            custom: ringkasan.custom ?? [],
                            pool_anggota: ringkasan.pool_anggota,
                            dana_tak_dibagi: ringkasan.dana_tak_dibagi,
                        }}
                        ringkasan={ringkasan}
                        isPreview={isPreview}
                    />
                )}

                {tab === 'komponen' && periode.status === 'draft' && (
                    <KomponenEditor
                        periode={periode}
                        persenLive={persenLive}
                        setPersenLive={setPersenLive}
                        customLive={customLive}
                        setCustomLive={setCustomLive}
                        preview={preview}
                        ringkasan={ringkasan}
                        sudahDihitung={ringkasan.anggota > 0}
                        daftarAnggota={daftarAnggota}
                    />
                )}
                {tab === 'komponen' && periode.status !== 'draft' && (
                    <Card>
                        <p className="text-sm text-slate-500">
                            Komponen dikunci (status {periode.status}). Kembalikan ke draft untuk mengubah.
                        </p>
                    </Card>
                )}

                {tab === 'distribusi' && (
                    <DistribusiTable
                        periode={periode}
                        detail={detail}
                        sums={sums}
                        filters={filters}
                        live={liveRows}
                        isPreview={Boolean(liveRows)}
                        bisaBayar={periode.is_final}
                        onBayar={bayarSatu}
                        onKeKomponen={() => setTab('komponen')}
                        ringkasan={ringkasan}
                        preview={preview}
                    />
                )}

                {tab === 'bayar' && (
                    <BayarTab
                        periode={periode}
                        ringkasan={ringkasan}
                        komponenBayar={komponen_bayar}
                        akun={bayarAkun}
                        setAkun={setBayarAkun}
                        tanggal={bayarTanggal}
                        setTanggal={setBayarTanggal}
                    />
                )}
            </div>
        </AppLayout>
    );
}
