import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, router, Link, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import {
    ChevronDown, CheckCircle2, Clock, XCircle, ArrowLeft,
    HandCoins, PiggyBank, TrendingUp, FastForward, AlertCircle,
} from 'lucide-react';
import { formatRupiah } from '@/Utils/formatCurrency';

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

export default function Riwayat({ pinjaman, simpanan, daftarBulanTersedia, bulanFilter, ringkasan, autoPercepatan }) {
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

            {tab === 'pinjaman' && <RiwayatPinjaman pinjaman={pinjaman} autoPercepatan={autoPercepatan} />}
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

function RiwayatPinjaman({ pinjaman, autoPercepatan }) {
    const { errors } = usePage().props;
    const [expandedId, setExpandedId] = useState(null);
    const [formPinjaman, setFormPinjaman] = useState(null);
    const [tipe, setTipe] = useState('ubah_tenor');
    const [tenorBaru, setTenorBaru] = useState('');
    const [keterangan, setKeterangan] = useState('');
    const [preview, setPreview] = useState(null);

    function bukaForm(p, tipeDipilih) {
        setFormPinjaman(p);
        setTipe(tipeDipilih);
        setTenorBaru('');
        setKeterangan('');
        setPreview(null);
    }

    function fetchPreview() {
        if (!formPinjaman) return;
        setPreview(null);
        fetch(route('portal.pengajuan-percepatan.preview'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                pinjaman_id: formPinjaman.id,
                tipe,
                tenor_baru: tipe === 'ubah_tenor' ? (tenorBaru ? parseInt(tenorBaru, 10) : null) : null,
            }),
        })
            .then((r) => r.json())
            .then((d) => setPreview(d))
            .catch(() => setPreview(null));
    }

    // Auto-buka formulir percepatan bila diarahkan dari dashboard (?percepatan=1)
    useEffect(() => {
        if (!autoPercepatan) return;
        const target = pinjaman.find((p) => p.status === 'aktif' && !p.sudah_pernah_percepatan);
        if (target) {
            setExpandedId(target.id);
            bukaForm(target, 'ubah_tenor');
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (formPinjaman) {
            fetchPreview();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [formPinjaman, tipe, tenorBaru]);

    function kirim(e) {
        e.preventDefault();
        router.post(route('portal.pengajuan-percepatan.store'), {
            pinjaman_id: formPinjaman.id,
            tipe,
            tenor_baru: tenorBaru ? parseInt(tenorBaru, 10) : null,
            keterangan,
        }, { preserveScroll: true, onSuccess: () => setFormPinjaman(null) });
    }

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
                                {p.status === 'aktif' && !p.sudah_pernah_percepatan && (
                                    <div className="p-4 bg-brand-green-light/40 border-b border-slate-100 flex flex-wrap items-center gap-3">
                                        <FastForward size={18} className="text-brand-green-dark" />
                                        <span className="text-sm font-semibold text-slate-700">Ajukan Percepatan</span>
                                        <button onClick={() => bukaForm(p, 'ubah_tenor')} className="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Ubah Tenor</button>
                                        <button onClick={() => bukaForm(p, 'lunas_total')} className="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Lunas Sekarang</button>
                                    </div>
                                )}

                                {formPinjaman?.id === p.id && (
                                    <form onSubmit={kirim} className="p-4 border-b border-slate-100 space-y-3 bg-slate-50">
                                        <p className="text-sm font-semibold text-slate-700">
                                            {tipe === 'ubah_tenor' ? 'Ubah Tenor' : 'Lunas Sekarang'}
                                        </p>

                                        {tipe === 'ubah_tenor' && (
                                            <div>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    value={tenorBaru}
                                                    onChange={(e) => setTenorBaru(e.target.value)}
                                                    placeholder="Tenor baru (bulan)"
                                                    className="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                                                />
                                                {preview?.tenor_maksimal && (
                                                    <p className="text-xs text-slate-400 mt-1">
                                                        Maksimal tenor untuk nominal ini: {preview.tenor_maksimal} bulan
                                                    </p>
                                                )}
                                            </div>
                                        )}

                                        <textarea
                                            value={keterangan}
                                            onChange={(e) => setKeterangan(e.target.value)}
                                            rows={3}
                                            placeholder="Alasan pengajuan (min 10 karakter)"
                                            className="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                                        />

                                        {preview?.error && (
                                            <p className="text-xs font-semibold text-red-600">{preview.error}</p>
                                        )}

                                        {preview && !preview.error && preview.jadwal?.length > 0 && (
                                            <div className="rounded-xl border border-slate-200 bg-white overflow-hidden">
                                                <div className="flex items-center justify-between gap-2 px-3 py-2.5 bg-brand-green-light/50 border-b border-slate-200">
                                                    <div className="flex items-center gap-2">
                                                        <FastForward size={16} className="text-brand-green-dark" />
                                                        <p className="text-sm font-bold text-slate-800">
                                                            {tipe === 'ubah_tenor' ? 'Simulasi Ubah Tenor' : 'Simulasi Lunas Sekarang'}
                                                        </p>
                                                    </div>
                                                    <span className="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-white text-brand-green-dark border border-brand-green/20">
                                                        Mulai: {preview.bulan_berlaku === 'bulan_depan' ? 'Bulan Depan' : 'Bulan Ini'}
                                                    </span>
                                                </div>

                                                <div className="grid grid-cols-2 sm:grid-cols-3 gap-px bg-slate-100">
                                                    <div className="bg-slate-50 px-3 py-2.5">
                                                        <p className="text-[11px] text-slate-400">Sisa Pokok</p>
                                                        <p className="text-sm font-bold text-slate-800">{formatRupiah(preview.sisa_pokok)}</p>
                                                    </div>
                                                    {tipe === 'ubah_tenor' ? (
                                                        <div className="bg-slate-50 px-3 py-2.5">
                                                            <p className="text-[11px] text-slate-400">Tenor</p>
                                                            <p className="text-sm font-bold text-slate-800">
                                                                {formPinjaman.tenor_bulan} &rarr; {tenorBaru || '?'} bln
                                                            </p>
                                                        </div>
                                                    ) : (
                                                        <div className="bg-slate-50 px-3 py-2.5">
                                                            <p className="text-[11px] text-slate-400">Total Dibayar</p>
                                                            <p className="text-sm font-bold text-slate-800">{formatRupiah(preview.nominal_final)}</p>
                                                        </div>
                                                    )}
                                                    <div className="bg-slate-50 px-3 py-2.5 col-span-2 sm:col-span-1">
                                                        <p className="text-[11px] text-slate-400">Estimasi Total</p>
                                                        <p className="text-sm font-bold text-brand-navy">
                                                            {formatRupiah(preview.jadwal.reduce((s, j) => s + j.total_bayar, 0))}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="px-3 pt-2.5">
                                                    <p className="text-[11px] font-semibold text-slate-400 mb-1">Rincian cicilan per bulan</p>
                                                </div>
                                                <div className="max-h-48 overflow-y-auto border-t border-slate-100">
                                                    <table className="w-full text-xs">
                                                        <thead className="sticky top-0 bg-white">
                                                            <tr className="text-slate-400 text-left">
                                                                <th className="font-medium px-3 py-1.5">Cicilan</th>
                                                                <th className="font-medium px-3 py-1.5">Jatuh Tempo</th>
                                                                <th className="font-medium px-3 py-1.5 text-right">Pokok</th>
                                                                <th className="font-medium px-3 py-1.5 text-right">Bunga</th>
                                                                <th className="font-medium px-3 py-1.5 text-right">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody className="divide-y divide-slate-50">
                                                            {preview.jadwal.map((j) => (
                                                                <tr key={j.cicilan_ke} className="text-slate-600">
                                                                    <td className="px-3 py-1.5 font-semibold text-slate-700">ke-{j.cicilan_ke}</td>
                                                                    <td className="px-3 py-1.5 text-slate-400">{j.tanggal_jatuh_tempo}</td>
                                                                    <td className="px-3 py-1.5 text-right text-brand-navy">{formatRupiah(j.nominal_pokok)}</td>
                                                                    <td className="px-3 py-1.5 text-right text-amber-600">{formatRupiah(j.nominal_bunga)}</td>
                                                                    <td className="px-3 py-1.5 text-right font-semibold text-slate-800">{formatRupiah(j.total_bayar)}</td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        )}

                                        {errors?.percepatan && (
                                            <p className="text-xs font-semibold text-red-600">{errors.percepatan}</p>
                                        )}

                                        <div className="flex gap-2">
                                            <button
                                                type="submit"
                                                disabled={preview?.error || (tipe === 'ubah_tenor' && !tenorBaru)}
                                                className="text-sm font-semibold px-4 py-2 rounded-lg bg-brand-green text-white hover:bg-brand-green/90 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                Kirim
                                            </button>
                                            <button type="button" onClick={() => setFormPinjaman(null)} className="text-sm font-semibold px-4 py-2 rounded-lg border border-slate-300 text-slate-600">Batal</button>
                                        </div>
                                    </form>
                                )}

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

                                {p.angsuran.filter((a) => a.status !== 'digantikan').length === 0 ? (
                                    <p className="p-5 text-sm text-slate-400">
                                        Jadwal angsuran belum tersedia (pinjaman belum aktif).
                                    </p>
                                ) : (
                                    <div className="divide-y divide-slate-50">
                                        {p.angsuran.filter((a) => a.status !== 'digantikan').map((a) => (
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

                                {p.percepatan && p.percepatan.length > 0 && (
                                    <div className="p-4 border-b border-slate-100">
                                        <p className="text-xs font-semibold text-slate-500 mb-2">Riwayat Perubahan Tenor</p>
                                        <div className="space-y-3">
                                            {p.percepatan.map((pp) => (
                                                <div key={pp.id} className="rounded-xl border border-slate-200 p-3">
                                                    <div className="flex items-center justify-between mb-1">
                                                        <p className="text-sm font-semibold text-slate-700">
                                                            {pp.tipe === 'lunas_total' ? 'Lunas Sekarang' : `Ubah Tenor ${pp.tenor_lama} \u2192 ${pp.tenor_baru}`}
                                                        </p>
                                                        <span className={`px-2 py-0.5 rounded-full text-[10px] font-semibold ${statusStyle[pp.status]}`}>{statusLabel[pp.status]}</span>
                                                    </div>
                                                    {pp.tipe === 'lunas_total' && (
                                                        <p className="text-xs text-slate-400 mb-1">Nominal final: {formatRupiah(pp.nominal_final)}</p>
                                                    )}
                                                    <div className="space-y-1">
                                                        {pp.angsuran_baru.map((a) => (
                                                            <div key={a.cicilan_ke} className="flex items-center justify-between text-xs">
                                                                <span className="text-slate-500">Cicilan ke-{a.cicilan_ke} \u00b7 {a.tanggal_jatuh_tempo}</span>
                                                                <span className="font-semibold text-slate-700">{formatRupiah(a.total_bayar)}</span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
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