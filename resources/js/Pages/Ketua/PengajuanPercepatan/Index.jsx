import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { FastForward, ShieldCheck, XCircle, CalendarClock } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Drawer from '@/Components/ui/Drawer';
import Button from '@/Components/ui/Button';
import { formatRupiah } from '@/Utils/formatCurrency';

const tipeLabel = { ubah_tenor: 'Ubah Tenor', lunas_total: 'Lunas Sekarang' };
const statusLabel = { diajukan: 'Diajukan', approved_bendahara: 'Disetujui Bendahara', aktif: 'Aktif', ditolak: 'Ditolak' };
const statusStyle = { diajukan: 'bg-amber-50 text-amber-700', approved_bendahara: 'bg-amber-50 text-amber-700', aktif: 'bg-brand-green-light text-brand-green-dark', ditolak: 'bg-red-50 text-red-600' };

export default function Index({ pengajuan }) {
    const [detail, setDetail] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);

    function buka(p) {
        setDetail(p);
        setDrawerOpen(true);
    }

    function tutup() {
        setDrawerOpen(false);
    }

    return (
        <AppLayout>
            <Head title="Percepatan Pinjaman" />

            <Link href={route('ketua.pinjaman.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <FastForward size={16} /> Kembali
            </Link>

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Percepatan Pinjaman</h1>
                <p className="text-base text-slate-400 mt-1">{pengajuan.length} pengajuan menunggu approval final</p>
            </div>

            <Card padding="none">
                <div className="divide-y divide-slate-50">
                    {pengajuan.length === 0 ? (
                        <p className="px-5 py-10 text-center text-base text-slate-400">Tidak ada pengajuan percepatan.</p>
                    ) : (
                        pengajuan.map((p) => (
                            <button key={p.id} onClick={() => buka(p)} className="w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50 transition-colors">
                                <div>
                                    <p className="text-base font-semibold text-slate-800">{p.anggota.nama}</p>
                                    <p className="text-sm text-slate-400">
                                        {tipeLabel[p.tipe]} &bull; Sisa pokok {formatRupiah(p.sisa_pokok_saat_ajukan)}
                                        {p.tipe === 'ubah_tenor' && ` \u00b7 Tenor ${p.tenor_lama} \u2192 ${p.tenor_baru}`}
                                    </p>
                                </div>
                                <span className={`px-2.5 py-0.5 rounded-full text-xs font-semibold ${statusStyle[p.status]}`}>{statusLabel[p.status]}</span>
                            </button>
                        ))
                    )}
                </div>
            </Card>

            <Drawer show={drawerOpen} title="Approval Final Percepatan" onClose={tutup}>
                {detail && <Keputusan pengajuan={detail} onClose={tutup} />}
            </Drawer>
        </AppLayout>
    );
}

function Keputusan({ pengajuan, onClose }) {
    const [catatan, setCatatan] = useState('');
    const [bulanBerlaku, setBulanBerlaku] = useState('bulan_ini');
    const [processing, setProcessing] = useState(false);

    function kirim(aksi) {
        setProcessing(true);
        router.post(
            route('ketua.pengajuan-percepatan.keputusan', pengajuan.id),
            { aksi, catatan, bulan_berlaku: bulanBerlaku },
            { preserveScroll: true, onSuccess: () => onClose(), onFinish: () => setProcessing(false) }
        );
    }

    return (
        <div>
            <div className="space-y-2 mb-4">
                <p className="text-sm text-slate-400">Anggota</p>
                <p className="text-base font-semibold text-slate-800">{pengajuan.anggota.nama} ({pengajuan.anggota.no_anggota})</p>
                <p className="text-sm text-slate-400">Tipe: {tipeLabel[pengajuan.tipe]}</p>
                {pengajuan.tipe === 'ubah_tenor' && (
                    <p className="text-sm text-slate-400">Tenor: {pengajuan.tenor_lama} \u2192 {pengajuan.tenor_baru} bulan</p>
                )}
                <p className="text-sm text-slate-400">Sisa pokok saat ajukan: {formatRupiah(pengajuan.sisa_pokok_saat_ajukan)}</p>
                {pengajuan.nominal_final > 0 && (
                    <p className="text-sm text-slate-400">Nominal final: {formatRupiah(pengajuan.nominal_final)}</p>
                )}
                {pengajuan.catatan_bendahara && (
                    <p className="text-sm text-slate-700 mt-2">Catatan Bendahara: {pengajuan.catatan_bendahara}</p>
                )}
                <p className="text-sm text-slate-700 mt-2">{pengajuan.keterangan}</p>
            </div>

            <div className="mb-3">
                <p className="text-sm font-semibold text-slate-700 mb-1">Waktu Berlaku</p>
                <div className="flex gap-2">
                    {[['bulan_ini', 'Berlaku Bulan Ini'], ['bulan_depan', 'Berlaku Bulan Depan']].map(([v, l]) => (
                        <button
                            key={v}
                            type="button"
                            onClick={() => setBulanBerlaku(v)}
                            className={`flex-1 flex items-center justify-center gap-2 px-3 py-2.5 text-sm font-semibold rounded-xl border transition-colors ${
                                bulanBerlaku === v ? 'border-brand-green bg-brand-green-light text-brand-green-dark' : 'border-slate-300 text-slate-600'
                            }`}
                        >
                            <CalendarClock size={16} /> {l}
                        </button>
                    ))}
                </div>
            </div>

            <textarea
                value={catatan}
                onChange={(e) => setCatatan(e.target.value)}
                rows={3}
                placeholder="Catatan (opsional)"
                className="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none mb-3"
            />

            <div className="flex items-center gap-3">
                <Button type="button" variant="primary" disabled={processing} onClick={() => kirim('setuju')}>
                    <ShieldCheck size={18} /> Setuju
                </Button>
                <Button type="button" variant="danger" disabled={processing} onClick={() => kirim('tolak')}>
                    <XCircle size={18} /> Tolak
                </Button>
            </div>
        </div>
    );
}
