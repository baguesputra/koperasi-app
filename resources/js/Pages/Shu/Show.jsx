import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Calculator, Download, Lock, Search, Wallet } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import Pagination from '@/Components/ui/Pagination';
import BackLink from '@/Components/ui/BackLink';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import { useEffect, useRef } from 'react';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const LABEL_PERSEN = {
    pct_cadangan: 'Cadangan',
    pct_jasa_simpanan: 'Jasa simpanan',
    pct_jasa_transaksi: 'Jasa transaksi',
    pct_pengurus: 'Pengurus',
    pct_pengawas: 'Pengawas',
    pct_pendidikan: 'Pendidikan',
    pct_sosial: 'Sosial',
    pct_wilker: 'Wilayah kerja',
};

export default function Show({ periode, ringkasan, detail, filters = {} }) {
    const hariIni = new Date().toISOString().slice(0, 10);
    const [cari, setCari] = useState(filters.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);

    const persenForm = useForm({ persen: periode.persen });
    const bayarForm = useForm({ akun: 'bank', tanggal: hariIni });

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        if ((cariDebounced ?? '') !== (filters.cari ?? '')) {
            router.get(route('shu.show', periode.id), { cari: cariDebounced ?? '', bayar: filters.bayar }, { preserveState: true, replace: true });
        }
    }, [cariDebounced]);

    function gantiFilterBayar(nilai) {
        router.get(route('shu.show', periode.id), { cari, bayar: nilai }, { preserveState: true, replace: true });
    }

    function hitung() {
        router.post(route('shu.hitung', periode.id), {}, withIdempotencyKey({ preserveScroll: true }));
    }

    function finalisasi() {
        if (!confirm(`Kunci SHU ${periode.tahun_buku}? Setelah final tidak bisa diubah.`)) return;
        router.post(route('shu.finalisasi', periode.id), {}, withIdempotencyKey({ preserveScroll: true }));
    }

    function bayarSemua(e) {
        e.preventDefault();
        if (!confirm(`Bayar semua SHU belum dibayar via ${bayarForm.data.akun === 'bank' ? 'Bank' : 'Kas Kecil'}?`)) return;
        bayarForm.post(route('shu.bayar-semua', periode.id), withIdempotencyKey({ preserveScroll: true }));
    }

    function bayarSatu(id) {
        router.post(
            route('shu.detail.bayar', id),
            { akun: bayarForm.data.akun, tanggal: bayarForm.data.tanggal },
            withIdempotencyKey({ preserveScroll: true })
        );
    }

    const komponen = ringkasan.komponen ?? {};

    return (
        <AppLayout>
            <Head title={`SHU ${periode.tahun_buku}`} />

            <BackLink href={route('shu.index')}>Semua periode SHU</BackLink>
            <PageHeader
                title={`SHU Tahun Buku ${periode.tahun_buku}`}
                subtitle={periode.is_final ? `Final ${periode.finalized_at ?? ''}` : 'Draft — hitung ulang kapan pun sebelum final'}
            >
                <span className={`text-xs font-bold px-2.5 py-1 rounded-full ${periode.is_final ? 'bg-brand-green-light text-brand-green-dark' : 'bg-amber-100 text-amber-700'}`}>
                    {periode.is_final ? 'Final' : 'Draft'}
                </span>
                <a href={route('shu.export', periode.id)}>
                    <Button size="sm" variant="secondary">
                        <Download size={16} />
                        Excel
                    </Button>
                </a>
            </PageHeader>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <Card><Stat label="Total SHU" value={formatRupiah(periode.total_shu)} hint={`Laba Rugi YTD: ${formatRupiah(periode.total_laba)}`} /></Card>
                <Card><Stat label="Pool anggota" value={formatRupiah(ringkasan.pool_anggota)} hint={`${ringkasan.anggota} anggota`} /></Card>
                <Card><Stat label="Diserahkan (floor 1000)" value={formatRupiah(ringkasan.total_bersih)} hint={`Bruto ${formatRupiah(ringkasan.total_bruto)}`} /></Card>
                <Card><Stat label="Terbayar" value={formatRupiah(ringkasan.terbayar)} hint={`${ringkasan.sudah_bayar} dari ${ringkasan.anggota} anggota`} /></Card>
            </div>

            <Card className="mb-4">
                <p className="text-base font-bold text-slate-800 mb-1">Komponen pembagian</p>
                <p className="text-sm text-slate-500 mb-3">
                    Dana tak dibagi (cadangan + pendidikan + wilker): {formatRupiah(ringkasan.dana_tak_dibagi)} •
                    Sisa pembulatan belum diserahkan: {formatRupiah(ringkasan.belum_diserahkan)}
                </p>
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    {Object.entries(LABEL_PERSEN).map(([kunci, label]) => (
                        <div key={kunci} className="rounded-xl bg-slate-50 px-3 py-2">
                            <p className="text-xs text-slate-500">{label} ({Math.round((periode.persen?.[kunci] ?? 0) * 10000) / 100}%)</p>
                            <p className="font-bold tabular-nums text-slate-800">{formatRupiah(komponen[kunci.replace('pct_', '')] ?? 0)}</p>
                        </div>
                    ))}
                </div>

                {!periode.is_final && (
                    <form
                        className="mt-4 pt-4 border-t border-slate-100"
                        onSubmit={(e) => {
                            e.preventDefault();
                            persenForm.put(route('shu.persen.update', periode.id), { preserveScroll: true });
                        }}
                    >
                        <p className="text-sm font-semibold text-slate-700 mb-2">Ubah persentase (lalu Hitung Ulang)</p>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                            {Object.entries(LABEL_PERSEN).map(([kunci, label]) => (
                                <FormField key={kunci} label={`${label} %`}>
                                    <TextField
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={Math.round((persenForm.data.persen?.[kunci] ?? 0) * 10000) / 100}
                                        onChange={(e) => persenForm.setData('persen', {
                                            ...persenForm.data.persen,
                                            [kunci]: (parseFloat(e.target.value) || 0) / 100,
                                        })}
                                    />
                                </FormField>
                            ))}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" size="sm" disabled={persenForm.processing}>Simpan Persen</Button>
                            <Button type="button" size="sm" variant="secondary" onClick={hitung}>
                                <Calculator size={16} />
                                Hitung Ulang
                            </Button>
                            <Button type="button" size="sm" variant="secondary" onClick={finalisasi}>
                                <Lock size={16} />
                                Finalisasi & Kunci
                            </Button>
                        </div>
                    </form>
                )}
            </Card>

            {periode.is_final && (
                <Card className="mb-4 border-brand-green/30">
                    <form onSubmit={bayarSemua}>
                        <div className="flex flex-wrap items-end gap-3">
                            <FormField label="Sumber dana">
                                <Select
                                    value={bayarForm.data.akun}
                                    onChange={(e) => bayarForm.setData('akun', e.target.value)}
                                >
                                    <option value="bank">Bank (transfer)</option>
                                    <option value="kas_kecil">Kas Kecil (tunai)</option>
                                </Select>
                            </FormField>
                            <FormField label="Tanggal bayar" error={bayarForm.errors.tanggal}>
                                <TextField
                                    type="date"
                                    value={bayarForm.data.tanggal}
                                    onChange={(e) => bayarForm.setData('tanggal', e.target.value)}
                                />
                            </FormField>
                            <Button type="submit" size="sm" disabled={bayarForm.processing}>
                                <Wallet size={16} />
                                Bayar Semua Belum Dibayar
                            </Button>
                        </div>
                    </form>
                </Card>
            )}

            <Card padding="none">
                <div className="flex flex-wrap items-center gap-2 px-4 py-3 border-b border-slate-100">
                    <div className="relative flex-1 min-w-[180px]">
                        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Cari nama / no karyawan..."
                            className="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                        />
                    </div>
                    <Select
                        value={filters.bayar ?? ''}
                        onChange={(e) => gantiFilterBayar(e.target.value)}
                    >
                        <option value="">Semua status</option>
                        <option value="belum">Belum dibayar</option>
                        <option value="sudah">Sudah dibayar</option>
                    </Select>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wide text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2.5">Anggota</th>
                                <th className="px-4 py-2.5 text-right">Modal</th>
                                <th className="px-4 py-2.5 text-right">Transaksi</th>
                                <th className="px-4 py-2.5 text-right">SHU Bruto</th>
                                <th className="px-4 py-2.5 text-right">Diserahkan</th>
                                <th className="px-4 py-2.5">Status</th>
                                <th className="px-4 py-2.5" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {detail.data.map((d) => (
                                <tr key={d.id} className="hover:bg-slate-50/60">
                                    <td className="px-4 py-2.5">
                                        <p className="font-semibold text-slate-800">{d.nama}</p>
                                        <p className="text-xs text-slate-400">{d.no_karyawan} • {d.cabang}</p>
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(d.modal)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(d.transaksi)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">{formatRupiah(d.shu_bruto)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums font-bold">{formatRupiah(d.shu_bersih)}</td>
                                    <td className="px-4 py-2.5">
                                        <span className={`text-xs font-bold px-2 py-0.5 rounded-full ${d.status_bayar === 'sudah' ? 'bg-brand-green-light text-brand-green-dark' : 'bg-slate-100 text-slate-500'}`}>
                                            {d.status_bayar === 'sudah' ? `Dibayar ${d.dibayar_pada ?? ''}` : 'Belum'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-2.5 text-right">
                                        {periode.is_final && d.status_bayar !== 'sudah' && (
                                            <Button size="sm" variant="secondary" onClick={() => bayarSatu(d.id)}>
                                                Bayar
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {detail.data.length === 0 && (
                        <p className="px-4 py-10 text-center text-sm text-slate-400">
                            Belum ada rincian. {periode.is_final ? '' : 'Klik Hitung Ulang untuk menghitung.'}
                        </p>
                    )}
                </div>

                <div className="px-4 py-3">
                    <Pagination links={detail.links} />
                </div>
            </Card>
        </AppLayout>
    );
}

function Stat({ label, value, hint }) {
    return (
        <div>
            <p className="text-xs text-slate-500">{label}</p>
            <p className="text-lg font-bold tabular-nums text-slate-800 leading-tight">{value}</p>
            {hint && <p className="text-xs text-slate-400 mt-0.5">{hint}</p>}
        </div>
    );
}
