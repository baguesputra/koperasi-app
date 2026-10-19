import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Check, ChevronDown, ChevronUp, Users, Wallet } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

/**
 * Tab pembayaran: aktif setelah Final.
 * Meliputi:
 * 1. Pembayaran SHU Anggota (Jasa Simpanan + Transaksi)
 * 2. Pembayaran Komponen SHU & Pengelola (Pengurus, Pengawas, Pendidikan, Sosial, Wilker, Custom)
 *    Dicatat total per komponen, rincian tetap tersimpan & ditampilkan.
 *    Dana Cadangan dikecualikan karena memupuk modal sendiri (uang mengendap di bank).
 */
export default function BayarTab({ periode, ringkasan, komponenBayar = [], akun, setAkun, tanggal, setTanggal }) {
    const hariIni = new Date().toISOString().slice(0, 10);
    const [bukaRincian, setBukaRincian] = useState({});

    if (!periode.is_final) {
        return (
            <Card>
                <p className="text-base font-bold text-slate-800">Pembayaran terkunci</p>
                <p className="text-sm text-slate-500 mt-0.5">
                    Status saat ini: <strong>{periode.status}</strong>. Pembayaran dibuka setelah
                    periode Diajukan, dibahas meeting, lalu difinalisasi.
                </p>
            </Card>
        );
    }

    function toggleRincian(id) {
        setBukaRincian((prev) => ({ ...prev, [id]: !prev[id] }));
    }

    function bayarSemuaAnggota(e) {
        e.preventDefault();
        if (!confirm(`Bayar semua SHU anggota belum dibayar via ${akun === 'bank' ? 'Bank' : 'Kas Kecil'}?`)) return;
        router.post(
            route('shu.bayar-semua', periode.id),
            { akun, tanggal: tanggal || hariIni },
            withIdempotencyKey({ preserveScroll: true })
        );
    }

    function bayarSatuKomponen(item) {
        if (!confirm(`Bayar ${item.nama_komponen} sebesar ${formatRupiah(item.nominal)} via ${akun === 'bank' ? 'Bank' : 'Kas Kecil'}?`)) return;
        router.post(
            route('shu.komponen.bayar', item.id),
            { akun, tanggal: tanggal || hariIni },
            withIdempotencyKey({ preserveScroll: true })
        );
    }

    function bayarSemuaKomponen(e) {
        e.preventDefault();
        if (!confirm(`Bayar semua komponen SHU belum dibayar via ${akun === 'bank' ? 'Bank' : 'Kas Kecil'}?`)) return;
        router.post(
            route('shu.bayar-semua-komponen', periode.id),
            { akun, tanggal: tanggal || hariIni },
            withIdempotencyKey({ preserveScroll: true })
        );
    }

    const sisaKomponenBelumBayar = komponenBayar.filter((k) => k.status_bayar !== 'sudah' && k.nominal > 0).length;

    return (
        <div className="space-y-4">
            {/* Bar Kontrol Global: Sumber Dana & Tanggal */}
            <Card className="border-slate-200">
                <div className="flex flex-wrap items-center gap-4">
                    <div className="flex-1 min-w-[200px]">
                        <p className="text-sm font-bold text-slate-800">Pengaturan Pembayaran</p>
                        <p className="text-xs text-slate-400 mt-0.5">
                            Sumber dana dan tanggal bayar yang dipilih berlaku untuk pembayaran anggota maupun komponen.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-end gap-3">
                        <FormField label="Sumber dana">
                            <Select value={akun} onChange={(e) => setAkun(e.target.value)} className="w-40">
                                <option value="bank">Bank (transfer)</option>
                                <option value="kas_kecil">Kas Kecil (tunai)</option>
                            </Select>
                        </FormField>
                        <FormField label="Tanggal bayar">
                            <TextField
                                type="date"
                                value={tanggal || hariIni}
                                onChange={(e) => setTanggal(e.target.value)}
                                className="w-40"
                            />
                        </FormField>
                    </div>
                </div>
            </Card>

            {/* SEKSI 1: Pembayaran SHU Anggota */}
            <Card className="border-brand-green/30">
                <div className="flex flex-wrap items-center justify-between gap-3 mb-3 pb-3 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                        <span className="w-8 h-8 rounded-xl bg-brand-green-light text-brand-green-dark inline-flex items-center justify-center shrink-0">
                            <Users size={16} />
                        </span>
                        <div>
                            <p className="text-base font-bold text-slate-800">1. Pembayaran SHU Anggota (Distribusi)</p>
                            <p className="text-xs text-slate-400">
                                Bagian simpanan dan transaksi untuk seluruh anggota koperasi aktif.
                            </p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-sm font-bold text-slate-800 tabular-nums">
                            Diserahkan {formatRupiah(ringkasan.terbayar)} / {formatRupiah(ringkasan.total_bersih)}
                        </p>
                        <span className="text-xs font-bold px-2.5 py-0.5 rounded-full bg-brand-green-light text-brand-green-dark inline-block mt-0.5">
                            {ringkasan.sudah_bayar} / {ringkasan.anggota} anggota lunas
                        </span>
                    </div>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <p className="text-xs text-slate-500">
                        Pembayaran per baris anggota juga dapat dilakukan langsung melalui tab <strong>Distribusi SHU</strong>.
                    </p>
                    {ringkasan.sudah_bayar < ringkasan.anggota ? (
                        <Button type="button" size="sm" onClick={bayarSemuaAnggota}>
                            Bayar Semua Anggota Belum Dibayar ({ringkasan.anggota - ringkasan.sudah_bayar})
                        </Button>
                    ) : (
                        <span className="inline-flex items-center gap-1 text-xs font-bold text-brand-green-dark bg-brand-green-light px-3 py-1.5 rounded-full">
                            <Check size={14} /> Seluruh SHU Anggota Selesai Dibayarkan
                        </span>
                    )}
                </div>
            </Card>

            {/* SEKSI 2: Pembayaran Komponen SHU & Pengelola */}
            <Card className="border-sky-300/60">
                <div className="flex flex-wrap items-center justify-between gap-3 mb-3 pb-3 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                        <span className="w-8 h-8 rounded-xl bg-sky-100 text-sky-700 inline-flex items-center justify-center shrink-0">
                            <Wallet size={16} />
                        </span>
                        <div>
                            <p className="text-base font-bold text-slate-800">2. Pembayaran Komponen SHU & Pengelola</p>
                            <p className="text-xs text-slate-400">
                                Dicatat kas keluar total per komponen dengan rincian penerima tetap tersimpan.
                            </p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-sm font-bold text-slate-800 tabular-nums">
                            Diserahkan {formatRupiah(ringkasan.terbayar_komponen ?? 0)} / {formatRupiah(ringkasan.total_komponen_bayar ?? 0)}
                        </p>
                        <span className="text-xs font-bold px-2.5 py-0.5 rounded-full bg-sky-100 text-sky-700 inline-block mt-0.5">
                            {ringkasan.sudah_bayar_komponen ?? 0} / {ringkasan.jumlah_komponen_bayar ?? 0} komponen lunas
                        </span>
                    </div>
                </div>

                {komponenBayar.length === 0 ? (
                    <p className="text-sm text-slate-400 py-4 text-center">Belum ada komponen alokasi non-anggota.</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wide text-slate-400 border-b border-slate-100">
                                    <th className="py-2.5 pr-3">Komponen & Rincian</th>
                                    <th className="py-2.5 px-3 text-right">Nominal Total</th>
                                    <th className="py-2.5 px-3">Status Pembayaran</th>
                                    <th className="py-2.5 pl-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {komponenBayar.map((k) => {
                                    const sudah = k.status_bayar === 'sudah';
                                    const adaRincian = Array.isArray(k.rincian) && k.rincian.length > 0;
                                    const isOpen = Boolean(bukaRincian[k.id]);

                                    return (
                                        <tr key={k.id} className="align-top hover:bg-slate-50/50 transition-colors">
                                            <td className="py-3 pr-3">
                                                <div className="flex items-baseline gap-2 flex-wrap">
                                                    <p className="font-semibold text-slate-800">{k.nama_komponen}</p>
                                                    {adaRincian && (
                                                        <button
                                                            type="button"
                                                            onClick={() => toggleRincian(k.id)}
                                                            className="text-xs text-brand-green-dark hover:underline inline-flex items-center gap-0.5 font-medium"
                                                        >
                                                            {adaRincian ? `${k.rincian.length} orang` : ''}
                                                            {isOpen ? <ChevronUp size={12} /> : <ChevronDown size={12} />}
                                                        </button>
                                                    )}
                                                </div>

                                                {k.kode_komponen === 'pengurus' && (ringkasan.sisa_dana?.pengurus ?? 0) > 0 && (
                                                    <p className="text-[11px] text-emerald-700 mt-0.5">
                                                        Alokasi {formatRupiah(ringkasan.sisa_dana?.alokasi_pengurus ?? 0)} • Sisa {formatRupiah(ringkasan.sisa_dana.pengurus)} tetap di kas
                                                    </p>
                                                )}
                                                {k.kode_komponen === 'pengawas' && (ringkasan.sisa_dana?.pengawas ?? 0) > 0 && (
                                                    <p className="text-[11px] text-emerald-700 mt-0.5">
                                                        Alokasi {formatRupiah(ringkasan.sisa_dana?.alokasi_pengawas ?? 0)} • Sisa {formatRupiah(ringkasan.sisa_dana.pengawas)} tetap di kas
                                                    </p>
                                                )}

                                                {/* Expandable rincian personil */}
                                                {adaRincian && isOpen && (
                                                    <div className="mt-2 pl-3 border-l-2 border-brand-green/30 space-y-1 bg-slate-50/70 p-2 rounded-r-lg">
                                                        <p className="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                                                            Rincian Penerima ({k.rincian.length} orang):
                                                        </p>
                                                        {k.rincian.map((r, idx) => (
                                                            <div key={idx} className="flex items-center justify-between text-xs text-slate-600">
                                                                <span>
                                                                    <strong>{r.nama}</strong>
                                                                    {r.jabatan ? ` (${r.jabatan})` : ''}
                                                                </span>
                                                                <span className="tabular-nums font-medium text-slate-700">
                                                                    {formatRupiah(r.nominal)}
                                                                </span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="py-3 px-3 text-right tabular-nums font-bold text-slate-800 whitespace-nowrap">
                                                {formatRupiah(k.nominal)}
                                            </td>
                                            <td className="py-3 px-3 whitespace-nowrap">
                                                {sudah ? (
                                                    <div>
                                                        <span className="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full bg-brand-green-light text-brand-green-dark">
                                                            <Check size={12} /> Lunas
                                                        </span>
                                                        <p className="text-[11px] text-slate-400 mt-0.5">
                                                            {k.tanggal_bayar || k.dibayar_pada} • {k.akun === 'bank' ? 'Bank' : 'Kas Kecil'}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
                                                        Belum dibayar
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3 pl-3 text-right whitespace-nowrap">
                                                {!sudah && k.nominal > 0 ? (
                                                    <button
                                                        type="button"
                                                        onClick={() => bayarSatuKomponen(k)}
                                                        className="text-sm font-semibold text-brand-green-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40 rounded px-2 py-1 bg-brand-green-light/40 hover:bg-brand-green-light"
                                                    >
                                                        Bayar
                                                    </button>
                                                ) : sudah ? (
                                                    <span className="text-xs text-slate-400 italic">Tercatat di kas</span>
                                                ) : (
                                                    <span className="text-xs text-slate-400">Rp 0</span>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 border-slate-200 bg-slate-50/70 font-bold">
                                    <td className="py-2.5 pr-3 text-slate-700">Total Komponen yang Disalurkan</td>
                                    <td className="py-2.5 px-3 text-right tabular-nums text-slate-800">
                                        {formatRupiah(ringkasan.total_komponen_bayar ?? 0)}
                                    </td>
                                    <td colSpan={2} className="py-2.5 px-3 text-right text-xs text-slate-500 font-normal">
                                        * Sisa dana pengurus/pengawas & pembulatan anggota ({formatRupiah(ringkasan.sisa_dana?.total ?? 0)}) serta Cadangan ({formatRupiah(ringkasan.komponen?.cadangan ?? 0)}) tetap di kas.
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                )}

                {sisaKomponenBelumBayar > 0 && (
                    <div className="flex justify-end pt-3 mt-3 border-t border-slate-100">
                        <Button type="button" size="sm" variant="secondary" onClick={bayarSemuaKomponen}>
                            Bayar Semua Komponen Belum Dibayar ({sisaKomponenBelumBayar})
                        </Button>
                    </div>
                )}
            </Card>
        </div>
    );
}
