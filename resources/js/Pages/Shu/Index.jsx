import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Plus } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
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

export default function Index({ periode = [], persenDefault = {}, tahunSaran }) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        tahun_buku: tahunSaran ?? new Date().getFullYear(),
        total_shu: '',
        persen: persenDefault,
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
                                    <span className={`text-xs font-bold px-2.5 py-1 rounded-full ${p.status === 'final' ? 'bg-brand-green-light text-brand-green-dark' : 'bg-amber-100 text-amber-700'}`}>
                                        {p.status === 'final' ? 'Final' : 'Draft'}
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
