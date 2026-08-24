import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

export default function Show({ pengajuan }) {
    const [aksi, setAksi] = useState(null);
    const { data, setData, post, processing, errors } = useForm({ catatan: '' });

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('bendahara.percepatan.approve', pengajuan.id)
            : route('bendahara.percepatan.reject', pengajuan.id);
        post(url);
    }

    const bisaDiproses = pengajuan.status === 'diajukan';

    return (
        <AppLayout>
            <Head title={`Perubahan Tenor - ${pengajuan.anggota.nama}`} />

            <Link href={route('bendahara.percepatan.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Kembali
            </Link>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div className="lg:col-span-2 space-y-5">
                    <Card>
                        <p className="text-sm font-semibold text-slate-400 mb-1">Jenis Pengajuan</p>
                        <p className="text-xl font-bold text-slate-800 mb-4">{pengajuan.tipe_label ?? pengajuan.tipe}</p>

                        <div className="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p className="text-sm text-slate-400">Tenor Saat Ini</p>
                                <p className="text-base font-semibold text-slate-700">{pengajuan.tenor_lama} bulan</p>
                            </div>
                            {pengajuan.tenor_baru && (
                                <div>
                                    <p className="text-sm text-slate-400">Tenor Diminta</p>
                                    <p className="text-base font-semibold text-brand-green-dark">{pengajuan.tenor_baru} bulan</p>
                                </div>
                            )}
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
                                    <textarea
                                        value={data.catatan}
                                        onChange={(e) => setData('catatan', e.target.value)}
                                        rows={3}
                                        placeholder="Catatan keputusan"
                                        className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                        autoFocus
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
                        <div><p className="text-sm text-slate-400">No. Anggota</p><p className="text-base font-semibold text-slate-800">{pengajuan.anggota.no_anggota}</p></div>
                        <div><p className="text-sm text-slate-400">Sisa Angsuran</p><p className="text-base font-semibold text-slate-800">{pengajuan.pinjaman.sisa_angsuran}x</p></div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}