import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import BackLink from '@/Components/ui/BackLink';
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

export default function Index({ periode = [], filters = {}, persenDefault = {}, tahunSaran }) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        tahun_buku: tahunSaran ?? new Date().getFullYear(),
        total_shu: '',
        persen: persenDefault,
        komponen_custom: [],
    });

    function submit(e) {
        e.preventDefault();
        post(
            route('shu.store'),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => { reset('total_shu'); setShowForm(false); },
            })
        );
    }

    return (
        <AppLayout>
            <Head title="Pembagian SHU" />

            <BackLink href={route('dashboard')}>Dashboard</BackLink>
            <PageHeader title="Pembagian SHU" subtitle="SHU tahunan dibagi per anggota tiap akhir tahun">
                {!showForm && (
                    <Button size="sm" onClick={() => setShowForm(true)} className="rounded-full">
                        <Plus size={16} />
                        Buat Periode
                    </Button>
                )}
            </PageHeader>

            {showForm && (
                <Card className="mb-4 border-brand-green/30">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-2 gap-3 mb-3">
                            <FormField label="Tahun buku" error={errors.tahun_buku}>
                                <TextField
                                    type="number"
                                    value={data.tahun_buku}
                                    onChange={(e) => setData('tahun_buku', e.target.value)}
                                />
                            </FormField>
                            <FormField label="Total SHU (kosong = dari Laba Rugi YTD)" error={errors.total_shu}>
                                <TextField
                                    type="number"
                                    min="0"
                                    placeholder="Otomatis dari Laba Rugi"
                                    value={data.total_shu}
                                    onChange={(e) => setData('total_shu', e.target.value)}
                                />
                            </FormField>
                        </div>
                        <p className="text-sm font-semibold text-slate-700 mb-2">Persentase komponen (total harus 100%)</p>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                            {Object.entries(LABEL_PERSEN).map(([kunci, label]) => (
                                <FormField key={kunci} label={`${label} %`} error={errors[`persen.${kunci}`]}>
                                    <TextField
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={Math.round((data.persen?.[kunci] ?? 0) * 10000) / 100}
                                        onChange={(e) => setData('persen', {
                                            ...data.persen,
                                            [kunci]: (parseFloat(e.target.value) || 0) / 100,
                                        })}
                                    />
                                </FormField>
                            ))}
                        </div>
                        {errors.persen && <p className="text-sm text-red-600 mb-2">{errors.persen}</p>}
                        <div className="mb-4 rounded-xl border border-slate-200 overflow-hidden">
                            <div className="flex items-center justify-between px-3 py-2 bg-slate-50">
                                <p className="text-sm font-semibold text-slate-700">Komponen tambahan <span className="font-normal text-slate-400">(opsional, dana tak dibagi)</span></p>
                                <button
                                    type="button"
                                    onClick={() => setData('komponen_custom', [...(data.komponen_custom ?? []), { label: '', persen: 0 }])}
                                    className="text-sm font-semibold text-brand-green-dark inline-flex items-center gap-1 hover:underline"
                                >
                                    <Plus size={14} />
                                    Tambah pembagian
                                </button>
                            </div>
                            {(data.komponen_custom ?? []).length === 0 && (
                                <p className="text-sm text-slate-400 px-3 py-2">Belum ada. Total 8 bawaan + tambahan harus 100%.</p>
                            )}
                            {(data.komponen_custom ?? []).map((r, i) => (
                                <div key={i} className="flex items-center gap-2 px-3 py-2 border-t border-slate-50">
                                    <FormField label={i === 0 ? 'Nama' : ''} className="flex-1" error={errors[`komponen_custom.${i}.label`]}>
                                        <TextField
                                            placeholder="cth. Dana Khusus"
                                            value={r.label ?? ''}
                                            onChange={(e) => setData('komponen_custom', (data.komponen_custom ?? []).map((x, j) => (j === i ? { ...x, label: e.target.value } : x)))}
                                        />
                                    </FormField>
                                    <FormField label={i === 0 ? '% ' : ''} className="w-28" error={errors[`komponen_custom.${i}.persen`]}>
                                        <TextField
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            value={Math.round((r.persen ?? 0) * 10000) / 100}
                                            onChange={(e) => setData('komponen_custom', (data.komponen_custom ?? []).map((x, j) => (j === i ? { ...x, persen: (parseFloat(e.target.value) || 0) / 100 } : x)))}
                                        />
                                    </FormField>
                                    <button
                                        type="button"
                                        onClick={() => setData('komponen_custom', (data.komponen_custom ?? []).filter((_, j) => j !== i))}
                                        aria-label={`Hapus komponen tambahan ${i + 1}`}
                                        className="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 self-end"
                                    >
                                        <Trash2 size={16} />
                                    </button>
                                </div>
                            ))}
                        </div>
                        {errors.komponen_custom && <p className="text-sm text-red-600 mb-2">{errors.komponen_custom}</p>}
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Menyimpan...' : 'Simpan Periode'}
                            </Button>
                            <Button type="button" variant="secondary" onClick={() => setShowForm(false)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                </Card>
            )}

            <Card padding="none">
                <div className="flex items-center gap-2 px-4 py-3 border-b border-slate-100">
                    <Select
                        value={filters.status ?? ''}
                        onChange={(e) => router.get(route('shu.index'), { status: e.target.value }, { preserveState: true, replace: true })}
                        aria-label="Filter status periode"
                        className="!w-auto"
                    >
                        <option value="">Semua status</option>
                        <option value="draft">Draft</option>
                        <option value="diajukan">Diajukan</option>
                        <option value="final">Final</option>
                    </Select>
                </div>
                {periode.length === 0 ? (
                    <p className="px-4 py-10 text-center text-sm text-slate-400">Belum ada periode SHU.</p>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {periode.map((p) => (
                            <li key={p.id}>
                                <Link
                                    href={route('shu.show', p.id)}
                                    className="flex items-center gap-3 px-4 py-3 hover:bg-slate-50"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="text-base font-bold text-slate-800">Tahun buku {p.tahun_buku}</p>
                                        <p className="text-sm text-slate-500 tabular-nums">
                                            {formatRupiah(p.total_shu)} • {p.jumlah_anggota} anggota
                                            {p.finalized_at ? ` • Final ${p.finalized_at}` : ''}
                                        </p>
                                    </div>
                                    <span className={`text-xs font-bold px-2.5 py-1 rounded-full ${badgeStatus(p.status)}`}>
                                        {labelStatus(p.status)}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </AppLayout>
    );
}

function badgeStatus(status) {
    if (status === 'final') return 'bg-brand-green-light text-brand-green-dark';
    if (status === 'diajukan') return 'bg-sky-100 text-sky-700';
    return 'bg-amber-100 text-amber-700';
}

function labelStatus(status) {
    if (status === 'final') return 'Final';
    if (status === 'diajukan') return 'Diajukan';
    return 'Draft';
}
