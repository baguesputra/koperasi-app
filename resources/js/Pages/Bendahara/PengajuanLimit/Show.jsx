import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

export default function Show({ pengajuan }) {
    const [aksi, setAksi] = useState(null);
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
        limit_disetujui: String(pengajuan.limit_diminta ?? ''),
    });

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('bendahara.pengajuan-limit.approve', pengajuan.id)
            : route('bendahara.pengajuan-limit.reject', pengajuan.id);
        post(url, withIdempotencyKey());
    }

    const bisaDiproses = pengajuan.status === 'diajukan';

    return (
        <AppLayout>
            <Head title={`Pengajuan Limit - ${pengajuan.anggota.nama}`} />

            <Link href={route('bendahara.pengajuan-limit.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Kembali
            </Link>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div className="lg:col-span-2 space-y-5">
                    <Card>
                        <p className="text-sm font-semibold text-slate-400 mb-1">Pengajuan Kenaikan Limit</p>
                        <div className="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p className="text-sm text-slate-400">Limit Saat Ini</p>
                                <p className="text-base font-semibold text-slate-700">{formatRupiah(pengajuan.limit_saat_ini)}</p>
                            </div>
                            <div>
                                <p className="text-sm text-slate-400">Limit Diminta</p>
                                <p className="text-base font-semibold text-brand-green-dark">{formatRupiah(pengajuan.limit_diminta)}</p>
                            </div>
                        </div>

                        <p className="text-sm text-slate-400 mb-1">Alasan</p>
                        <p className="text-base text-slate-700">{pengajuan.keterangan}</p>
                    </Card>

                    {bisaDiproses && (
                        <Card>
                            <p className="text-base font-bold text-slate-800 mb-4">Keputusan</p>

                            {!aksi ? (
                                <div className="flex items-center gap-3">
                                    <Button variant="primary" onClick={() => setAksi('approve')}>Setujui</Button>
                                    <Button variant="danger" onClick={() => setAksi('reject')}>Tolak</Button>
                                </div>
                            ) : (
                                <form onSubmit={submit}>
                                    {aksi === 'approve' && (
                                        <div className="mb-4">
                                            <label className="block text-sm font-semibold text-slate-600 mb-2">Nominal yang Disetujui</label>
                                            <div className="relative">
                                                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-base font-semibold text-slate-400">Rp</span>
                                                <input
                                                    type="number"
                                                    min={1}
                                                    value={data.limit_disetujui}
                                                    onChange={(e) => setData('limit_disetujui', e.target.value)}
                                                    className="w-full pl-12 pr-4 py-2.5 text-lg font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                                    autoFocus
                                                />
                                            </div>
                                            {errors.limit_disetujui && <p className="text-sm text-red-600 mt-1.5">{errors.limit_disetujui}</p>}
                                        </div>
                                    )}
                                    {errors.keputusan && <p className="text-sm text-red-600 mb-2">{errors.keputusan}</p>}
                                    <textarea
                                        value={data.catatan}
                                        onChange={(e) => setData('catatan', e.target.value)}
                                        rows={3}
                                        placeholder="Catatan keputusan"
                                        className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                    />
                                    {errors.catatan && <p className="text-sm text-red-600 mt-1.5">{errors.catatan}</p>}

                                    <div className="flex items-center gap-3 mt-4">
                                        <Button type="submit" variant={aksi === 'approve' ? 'primary' : 'danger'} disabled={processing}>
                                            {processing ? 'Memproses...' : `Konfirmasi ${aksi === 'approve' ? 'Setujui' : 'Tolak'}`}
                                        </Button>
                                        <Button type="button" variant="ghost" onClick={() => setAksi(null)}>Batal</Button>
                                    </div>
                                </form>
                            )}
                        </Card>
                    )}
                </div>

                <Card>
                    <p className="text-sm font-semibold text-slate-400 mb-3">Data Anggota</p>
                    <div className="space-y-3">
                        <div><p className="text-sm text-slate-400">Nama</p><p className="text-base font-semibold text-slate-800">{pengajuan.anggota.nama}</p></div>
                        <div><p className="text-sm text-slate-400">No. Karyawan</p><p className="text-base font-semibold text-slate-800">{pengajuan.anggota.no_karyawan}</p></div>
                        <div><p className="text-sm text-slate-400">Cabang</p><p className="text-base font-semibold text-slate-800">{pengajuan.anggota.cabang}</p></div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
