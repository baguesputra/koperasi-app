import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

export default function Show({ pinjaman }) {
    const [aksi, setAksi] = useState(null); // 'approve' | 'reject' | null
    const { data, setData, post, processing, errors } = useForm({ catatan: '' });

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('bendahara.pinjaman.approve', pinjaman.id)
            : route('bendahara.pinjaman.reject', pinjaman.id);
        post(url);
    }

    const bisaDiproses = pinjaman.status === 'diajukan';

    return (
        <AppLayout>
            <Head title={`Pinjaman - ${pinjaman.anggota.nama}`} />

            <Link href={route('bendahara.pinjaman.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Kembali
            </Link>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div className="lg:col-span-2 space-y-5">
                    <Card>
                        <p className="text-sm font-semibold text-slate-400 mb-1">Nominal Pengajuan</p>
                        <p className="text-3xl font-bold text-slate-800 mb-4">{formatRupiah(pinjaman.nominal)}</p>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-slate-400">Tenor</p>
                                <p className="text-base font-semibold text-slate-700">{pinjaman.tenor_bulan} bulan</p>
                            </div>
                            <div>
                                <p className="text-sm text-slate-400">Bunga</p>
                                <p className="text-base font-semibold text-slate-700">{pinjaman.persentase_bunga}% / bulan</p>
                            </div>
                            <div>
                                <p className="text-sm text-slate-400">Tanggal Pengajuan</p>
                                <p className="text-base font-semibold text-slate-700">{pinjaman.tanggal_pengajuan}</p>
                            </div>
                            <div>
                                <p className="text-sm text-slate-400">Reloan</p>
                                <p className="text-base font-semibold text-slate-700">
                                    {pinjaman.sudah_pakai_privilege_reloan ? 'Ya' : 'Tidak'}
                                </p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <p className="text-sm font-semibold text-slate-400 mb-1">Terbilang</p>
                        <p className="text-base italic text-slate-700 mb-4">{pinjaman.terbilang}</p>

                        <p className="text-sm font-semibold text-slate-400 mb-1">Keperluan Peminjaman</p>
                        <p className="text-base text-slate-700 mb-4">{pinjaman.keperluan || '-'}</p>

                        <p className="text-sm font-semibold text-slate-400 mb-1.5">Rekening Tujuan Pencairan</p>
                        <div className="bg-slate-50 rounded-xl p-4">
                            <p className="text-base font-bold text-slate-800">{pinjaman.rekening.bank}</p>
                            <p className="text-sm text-slate-600">{pinjaman.rekening.no_rekening}</p>
                            <p className="text-sm text-slate-400">a.n. {pinjaman.rekening.atas_nama}</p>
                        </div>
                    </Card>

                    {bisaDiproses && (
                        <Card>
                            <p className="text-base font-bold text-slate-800 mb-4">Keputusan</p>

                            {!aksi ? (
                                <div className="flex items-center gap-3">
                                    <Button variant="primary" onClick={() => setAksi('approve')}>
                                        Setujui
                                    </Button>
                                    <Button variant="danger" onClick={() => setAksi('reject')}>
                                        Tolak
                                    </Button>
                                </div>
                            ) : (
                                <form onSubmit={submit}>
                                    <label className="block text-sm font-semibold text-slate-600 mb-2">
                                        Catatan {aksi === 'approve' ? 'Persetujuan' : 'Penolakan'}
                                    </label>
                                    <textarea
                                        value={data.catatan}
                                        onChange={(e) => setData('catatan', e.target.value)}
                                        rows={3}
                                        placeholder={aksi === 'approve' ? 'Contoh: Data lengkap, memenuhi syarat.' : 'Contoh: Dokumen belum lengkap.'}
                                        className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                        autoFocus
                                    />
                                    {errors.catatan && <p className="text-sm text-red-600 mt-1.5">{errors.catatan}</p>}

                                    <div className="flex items-center gap-3 mt-4">
                                        <Button
                                            type="submit"
                                            variant={aksi === 'approve' ? 'primary' : 'danger'}
                                            disabled={processing}
                                        >
                                            {processing ? 'Memproses...' : `Konfirmasi ${aksi === 'approve' ? 'Setujui' : 'Tolak'}`}
                                        </Button>
                                        <Button type="button" variant="ghost" onClick={() => setAksi(null)}>
                                            Batal
                                        </Button>
                                    </div>
                                </form>
                            )}
                        </Card>
                    )}

                    {pinjaman.catatan_bendahara && (
                        <Card>
                            <p className="text-sm font-semibold text-slate-400 mb-1.5">Catatan Bendahara</p>
                            <p className="text-base text-slate-700">{pinjaman.catatan_bendahara}</p>
                        </Card>
                    )}
                </div>

                <Card>
                    <p className="text-sm font-semibold text-slate-400 mb-3">Data Anggota</p>
                    <div className="space-y-3">
                        <div>
                            <p className="text-sm text-slate-400">Nama</p>
                            <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.nama}</p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-400">No. Anggota</p>
                            <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.no_anggota}</p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-400">Cabang</p>
                            <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.cabang}</p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-400">Jabatan</p>
                            <p className="text-base font-semibold text-slate-800">{jabatanLabel[pinjaman.anggota.jabatan]}</p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-400">Lama Keanggotaan</p>
                            <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.lama_keanggotaan_tahun} tahun</p>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}