import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';

export default function Show({ pengajuan }) {
    const [aksi, setAksi] = useState(null);
    const { data, setData, post, processing, errors } = useForm({ catatan: '', bulan_berlaku: '' });

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('ketua.percepatan.approve', pengajuan.id)
            : route('ketua.percepatan.reject', pengajuan.id);
        post(url);
    }

    const bisaDiproses = pengajuan.status === 'approved_bendahara';

    return (
        <AppLayout>
            <Head title={`Perubahan Tenor - ${pengajuan.anggota.nama}`} />

            <Link href={route('ketua.percepatan.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Kembali
            </Link>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div className="lg:col-span-2 space-y-5">
                    <Card>
                        <p className="text-sm font-semibold text-slate-400 mb-1">Jenis Pengajuan</p>
                        <p className="text-xl font-bold text-slate-800 mb-4">{pengajuan.tipe_label}</p>

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

                    {pengajuan.catatan_bendahara && (
                        <Card className="bg-slate-50">
                            <p className="text-sm font-semibold text-slate-400 mb-1.5">Catatan dari Bendahara</p>
                            <p className="text-base text-slate-700">{pengajuan.catatan_bendahara}</p>
                        </Card>
                    )}

                    {bisaDiproses && (
                        <Card>
                            <p className="text-base font-bold text-slate-800 mb-4">Keputusan Final</p>

                            {!aksi ? (
                                <div className="flex items-center gap-3">
                                    <Button variant="primary" onClick={() => setAksi('approve')}>Setujui</Button>
                                    <Button variant="danger" onClick={() => setAksi('reject')}>Tolak</Button>
                                </div>
                            ) : (
                                <form onSubmit={submit}>
                                    {aksi === 'approve' && (
                                        <div className="mb-4">
                                            <label className="block text-sm font-semibold text-slate-600 mb-2">Berlaku Mulai</label>
                                            <div className="flex gap-3">
                                                <label className={`flex-1 flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer ${data.bulan_berlaku === 'bulan_ini' ? 'border-brand-green bg-brand-green-light' : 'border-slate-200'}`}>
                                                    <input type="radio" checked={data.bulan_berlaku === 'bulan_ini'} onChange={() => setData('bulan_berlaku', 'bulan_ini')} />
                                                    <span className="text-sm font-semibold">Bulan Ini</span>
                                                </label>
                                                <label className={`flex-1 flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer ${data.bulan_berlaku === 'bulan_depan' ? 'border-brand-green bg-brand-green-light' : 'border-slate-200'}`}>
                                                    <input type="radio" checked={data.bulan_berlaku === 'bulan_depan'} onChange={() => setData('bulan_berlaku', 'bulan_depan')} />
                                                    <span className="text-sm font-semibold">Bulan Depan</span>
                                                </label>
                                            </div>
                                            {errors.bulan_berlaku && <p className="text-sm text-red-600 mt-1.5">{errors.bulan_berlaku}</p>}
                                        </div>
                                    )}

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
                        <div><p className="text-sm text-slate-400">No. Anggota</p><p className="text-base font-semibold text-slate-800">{pengajuan.anggota.no_anggota}</p></div>
                        <div><p className="text-sm text-slate-400">Sisa Angsuran</p><p className="text-base font-semibold text-slate-800">{pengajuan.pinjaman.sisa_angsuran}x</p></div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}