import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, router, Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    ChevronDown, CheckCircle2, Clock, XCircle, ArrowLeft,
    HandCoins, PiggyBank, TrendingUp,
} from 'lucide-react';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

const statusLabel = {
    diajukan: 'Diajukan',
    approved_bendahara: 'Disetujui Bendahara',
    aktif: 'Aktif',
    lunas: 'Lunas',
    ditolak: 'Ditolak',
};

const statusStyle = {
    aktif: 'bg-blue-50 text-blue-700',
    lunas: 'bg-brand-green-light text-brand-green-dark',
    ditolak: 'bg-red-50 text-red-600',
    diajukan: 'bg-amber-50 text-amber-700',
    approved_bendahara: 'bg-amber-50 text-amber-700',
};

const statusIcon = {
    aktif: Clock,
    lunas: CheckCircle2,
    ditolak: XCircle,
    diajukan: Clock,
    approved_bendahara: Clock,
};

const jenisSimpananLabel = { pokok: 'Simpanan Pokok', wajib: 'Simpanan Wajib', dana_sosial: 'Dana Sosial' };

export default function Riwayat({ pinjaman, simpanan, daftarBulanTersedia, bulanFilter, ringkasan }) {
    const [tab, setTab] = useState('pinjaman');

    return (
        <AnggotaLayout>
            <Head title="Riwayat" />

            <Link href={route('portal.dashboard')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-4">
                <ArrowLeft size={16} />
                Kembali ke Beranda
            </Link>

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Riwayat</h1>
                <p className="text-base text-slate-400 mt-1">Riwayat lengkap pinjaman dan simpanan Anda</p>
            </div>

            {/* Ringkasan */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div className="bg-white rounded-2xl border border-slate-100 p-5">
                    <div className="w-10 h-10 rounded-xl bg-brand-navy/5 text-brand-navy flex items-center justify-center mb-3">
                        <HandCoins size={20} />
                    </div>
                    <p className="text-sm text-slate-400">Total Pinjaman Diajukan</p>
                    <p className="text-xl font-bold text-slate-800 mt-0.5">{ringkasan.total_pinjaman_diajukan}x</p>
                </div>
                <div className="bg-white rounded-2xl border border-slate-100 p-5">
                    <div className="w-10 h-10 rounded-xl bg-brand-green-light text-brand-green-dark flex items-center justify-center mb-3">
                        <CheckCircle2 size={20} />
                    </div>
                    <p className="text-sm text-slate-400">Pinjaman Lunas</p>
                    <p className="text-xl font-bold text-slate-800 mt-0.5">{ringkasan.total_pinjaman_lunas}x</p>
                </div>
                <div className="bg-white rounded-2xl border border-slate-100 p-5">
                    <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center mb-3">
                        <PiggyBank size={20} />
                    </div>
                    <p className="text-sm text-slate-400">Total Simpanan Terkumpul</p>
                    <p className="text-xl font-bold text-slate-800 mt-0.5">{formatRupiah(ringkasan.total_simpanan_terkumpul)}</p>
                </div>
            </div>

            {/* Tab */}
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
            {tab === 'simpanan' && (
                <RiwayatSimpanan
                    simpanan={simpanan}
                    daftarBulanTersedia={daftarBulanTersedia}
                    bulanFilter={bulanFilter}
                />
            )}
        </AnggotaLayout>
    );
}

function RiwayatPinjaman({ pinjaman }) {
    const [expandedId, setExpandedId] = useState(null);

    if (pinjaman.length === 0) {
        return (
            <div className="bg-white rounded-2xl border border-slate-100 p-10 text-center">
                <p className="text-base text-slate-400">Belum ada riwayat pinjaman.</p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {pinjaman.map((p) => {
                const isExpanded = expandedId === p.id;
                const StatusIcon = statusIcon[p.status] ?? Clock;
                const progress = p.angsuran.length > 0
                    ? Math.round((p.angsuran.filter((a) => a.status === 'lunas').length / p.angsuran.length) * 100)
                    : 0;

                return (
                    <div key={p.id} className="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                        <button
                            onClick={() => setExpandedId(isExpanded ? null : p.id)}
                            className="w-full flex items-center gap-4 p-5 text-left hover:bg-slate-50 transition-colors"
                        >
                            <div className={`w-11 h-11 rounded-xl flex items-center justify-center shrink-0 ${statusStyle[p.status]}`}>
                                <StatusIcon size={20} />
                            </div>

                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2 flex-wrap">
                                    <p className="text-lg font-bold text-slate-800">{formatRupiah(p.nominal)}</p>
                                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-semibold ${statusStyle[p.status]}`}>
                                        {statusLabel[p.status]}
                                    </span>
                                </div>
                                <p className="text-sm text-slate-400 mt-0.5">
                                    {p.tenor_bulan} bulan &bull; Diajukan {p.tanggal_pengajuan}
                                </p>
                                {p.status === 'aktif' && (
                                    <div className="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mt-2 max-w-xs">
                                        <div className="h-full bg-brand-green rounded-full" style={{ width: `${progress}%` }} />
                                    </div>
                                )}
                            </div>

                            <ChevronDown
                                size={18}
                                className={`text-slate-400 transition-transform shrink-0 ${isExpanded ? 'rotate-180' : ''}`}
                            />
                        </button>

                        {isExpanded && (
                            <div className="border-t border-slate-100">
                                {p.keperluan && (
                                    <div className="p-4 bg-slate-50 border-b border-slate-100">
                                        <p className="text-xs text-slate-400 mb-0.5">Keperluan</p>
                                        <p className="text-sm text-slate-700">{p.keperluan}</p>
                                    </div>
                                )}
                                {p.rekening?.bank && (
                                    <div className="p-4 border-b border-slate-100">
                                        <p className="text-xs text-slate-400 mb-0.5">Rekening Tujuan</p>
                                        <p className="text-sm font-semibold text-slate-700">{p.rekening.bank} &bull; {p.rekening.no_rekening}</p>
                                        <p className="text-xs text-slate-400">a.n. {p.rekening.atas_nama}</p>
                                    </div>
                                )}

                                {p.status === 'ditolak' && (p.catatan_bendahara || p.catatan_ketua) && (
                                    <div className="p-4 bg-red-50 border-b border-slate-100">
                                        <p className="text-sm font-semibold text-red-700 mb-0.5">Alasan Penolakan</p>
                                        <p className="text-sm text-red-600">{p.catatan_ketua || p.catatan_bendahara}</p>
                                    </div>
                                )}

                                {p.angsuran.length === 0 ? (
                                    <p className="p-5 text-sm text-slate-400">
                                        Jadwal angsuran belum tersedia (pinjaman belum aktif).
                                    </p>
                                ) : (
                                    <div className="divide-y divide-slate-50">
                                        {p.angsuran.map((a) => (
                                            <div key={a.cicilan_ke} className="flex items-center justify-between px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    {a.status === 'lunas' ? (
                                                        <CheckCircle2 size={18} className="text-brand-green shrink-0" />
                                                    ) : (
                                                        <Clock size={18} className="text-slate-300 shrink-0" />
                                                    )}
                                                    <div>
                                                        <p className="text-sm font-semibold text-slate-700">
                                                            Cicilan ke-{a.cicilan_ke}
                                                        </p>
                                                        <p className="text-xs text-slate-400">
                                                            Jatuh tempo {a.tanggal_jatuh_tempo}
                                                            {a.tanggal_konfirmasi_bayar && ` \u00b7 Dibayar ${a.tanggal_konfirmasi_bayar}`}
                                                        </p>
                                                    </div>
                                                </div>
                                                <p className="text-sm font-bold text-slate-800">
                                                    {formatRupiah(a.total_bayar)}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

function RiwayatSimpanan({ simpanan, daftarBulanTersedia, bulanFilter }) {
    function ubahFilterBulan(bulan) {
        router.get(route('portal.riwayat'), bulan ? { bulan } : {}, { preserveState: true, preserveScroll: true });
    }

    return (
        <div>
            {daftarBulanTersedia.length > 0 && (
                <div className="mb-4">
                    <select
                        value={bulanFilter ?? ''}
                        onChange={(e) => ubahFilterBulan(e.target.value)}
                        className="px-4 py-2.5 text-sm font-semibold rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                    >
                        <option value="">Semua Periode</option>
                        {daftarBulanTersedia.map((b) => (
                            <option key={b} value={b}>{b}</option>
                        ))}
                    </select>
                </div>
            )}

            {simpanan.length === 0 ? (
                <div className="bg-white rounded-2xl border border-slate-100 p-10 text-center">
                    <p className="text-base text-slate-400">Belum ada riwayat simpanan.</p>
                </div>
            ) : (
                <div className="bg-white rounded-2xl border border-slate-100 divide-y divide-slate-50">
                    {simpanan.map((s, i) => (
                        <div key={i} className="flex items-center gap-4 px-5 py-4">
                            <div className="w-10 h-10 rounded-xl bg-brand-green-light text-brand-green-dark flex items-center justify-center shrink-0">
                                <TrendingUp size={18} />
                            </div>
                            <div className="flex-1">
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
            )}
        </div>
    );
}