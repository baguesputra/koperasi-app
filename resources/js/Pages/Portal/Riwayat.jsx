import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { ChevronDown, CheckCircle2, Clock } from 'lucide-react';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

const statusLabel = {
    diajukan: 'Diajukan',
    ditinjau_bendahara: 'Ditinjau Bendahara',
    approved_bendahara: 'Disetujui Bendahara',
    approved_ketua: 'Disetujui Ketua',
    aktif: 'Aktif',
    lunas: 'Lunas',
    ditolak: 'Ditolak',
};

const statusStyle = {
    aktif: 'bg-blue-50 text-blue-700',
    lunas: 'bg-brand-green-light text-brand-green-dark',
    ditolak: 'bg-red-50 text-red-600',
    diajukan: 'bg-amber-50 text-amber-700',
    ditinjau_bendahara: 'bg-amber-50 text-amber-700',
    approved_bendahara: 'bg-amber-50 text-amber-700',
    approved_ketua: 'bg-amber-50 text-amber-700',
};

const jenisSimpananLabel = {
    pokok: 'Simpanan Pokok',
    wajib: 'Simpanan Wajib',
    dana_sosial: 'Dana Sosial',
};

export default function Riwayat({ pinjaman, simpanan }) {
    const [tab, setTab] = useState('pinjaman');

    return (
        <AnggotaLayout>
            <Head title="Riwayat" />

            <div className="mb-6">
                <h1 className="text-xl sm:text-2xl font-bold text-slate-800">Riwayat</h1>
                <p className="text-base text-slate-400 mt-1">Riwayat pinjaman dan simpanan Anda</p>
            </div>

            <div className="flex items-center gap-2 mb-5 bg-slate-100 p-1 rounded-xl w-fit">
                <button
                    onClick={() => setTab('pinjaman')}
                    className={`px-5 py-2 text-sm font-semibold rounded-lg transition-colors ${
                        tab === 'pinjaman' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'
                    }`}
                >
                    Pinjaman
                </button>
                <button
                    onClick={() => setTab('simpanan')}
                    className={`px-5 py-2 text-sm font-semibold rounded-lg transition-colors ${
                        tab === 'simpanan' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'
                    }`}
                >
                    Simpanan
                </button>
            </div>

            {tab === 'pinjaman' && <RiwayatPinjaman pinjaman={pinjaman} />}
            {tab === 'simpanan' && <RiwayatSimpanan simpanan={simpanan} />}
        </AnggotaLayout>
    );
}

function RiwayatPinjaman({ pinjaman }) {
    const [expandedId, setExpandedId] = useState(null);

    if (pinjaman.length === 0) {
        return (
            <div className="bg-white rounded-2xl border border-slate-100 p-8 text-center">
                <p className="text-base text-slate-400">Belum ada riwayat pinjaman.</p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {pinjaman.map((p) => {
                const isExpanded = expandedId === p.id;
                return (
                    <div key={p.id} className="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                        <button
                            onClick={() => setExpandedId(isExpanded ? null : p.id)}
                            className="w-full flex items-center justify-between p-5 text-left"
                        >
                            <div>
                                <p className="text-lg font-bold text-slate-800">{formatRupiah(p.nominal)}</p>
                                <p className="text-sm text-slate-400 mt-0.5">
                                    {p.tenor_bulan} bulan &bull; Diajukan {p.tanggal_pengajuan}
                                </p>
                            </div>
                            <div className="flex items-center gap-3">
                                <span className={`px-3 py-1 rounded-full text-sm font-semibold ${statusStyle[p.status]}`}>
                                    {statusLabel[p.status]}
                                </span>
                                <ChevronDown
                                    size={18}
                                    className={`text-slate-400 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                                />
                            </div>
                        </button>

                        {isExpanded && (
                            <div className="border-t border-slate-100 divide-y divide-slate-50">
                                {p.angsuran.length === 0 ? (
                                    <p className="p-5 text-sm text-slate-400">
                                        Jadwal angsuran belum tersedia (pinjaman belum aktif).
                                    </p>
                                ) : (
                                    p.angsuran.map((a) => (
                                        <div key={a.cicilan_ke} className="flex items-center justify-between px-5 py-3.5">
                                            <div className="flex items-center gap-3">
                                                {a.status === 'lunas' ? (
                                                    <CheckCircle2 size={20} className="text-brand-green" />
                                                ) : (
                                                    <Clock size={20} className="text-slate-300" />
                                                )}
                                                <div>
                                                    <p className="text-base font-semibold text-slate-700">
                                                        Cicilan ke-{a.cicilan_ke}
                                                    </p>
                                                    <p className="text-sm text-slate-400">
                                                        Jatuh tempo {a.tanggal_jatuh_tempo}
                                                        {a.tanggal_konfirmasi_bayar && ` · Dibayar ${a.tanggal_konfirmasi_bayar}`}
                                                    </p>
                                                </div>
                                            </div>
                                            <p className="text-base font-bold text-slate-800">
                                                {formatRupiah(a.total_bayar)}
                                            </p>
                                        </div>
                                    ))
                                )}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

function RiwayatSimpanan({ simpanan }) {
    if (simpanan.length === 0) {
        return (
            <div className="bg-white rounded-2xl border border-slate-100 p-8 text-center">
                <p className="text-base text-slate-400">Belum ada riwayat simpanan.</p>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-2xl border border-slate-100 divide-y divide-slate-50">
            {simpanan.map((s, i) => (
                <div key={i} className="flex items-center justify-between px-5 py-4">
                    <div>
                        <p className="text-base font-semibold text-slate-700">
                            {jenisSimpananLabel[s.jenis]}
                        </p>
                        <p className="text-sm text-slate-400">
                            Periode {s.bulan_periode} &bull; {s.tanggal_input}
                        </p>
                    </div>
                    <p className="text-base font-bold text-slate-800">
                        {formatRupiah(s.jumlah)}
                    </p>
                </div>
            ))}
        </div>
    );
}