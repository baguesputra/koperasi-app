import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';
import JejakNominal from '@/Pages/PinjamanApproval/JejakNominal';
import KartuKas from '@/Pages/PinjamanApproval/KartuKas';
import PreviewNominal from '@/Pages/PinjamanApproval/PreviewNominal';
import RingkasanKas from '@/Pages/PinjamanApproval/RingkasanKas';

const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

export default function Show({ pinjaman, ringkasanKas }) {
    const [aksi, setAksi] = useState(null);
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
        nominal: String(pinjaman.nominal_disetujui_bendahara ?? pinjaman.nominal_diminta ?? pinjaman.nominal),
        tenor_bulan: '',
    });
    const nominalFinal = Number(data.nominal) || 0;

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('ketua.pinjaman.approve', pinjaman.id)
            : route('ketua.pinjaman.reject', pinjaman.id);
        post(url, withIdempotencyKey());
    }

    const bisaDiproses = pinjaman.status === 'approved_bendahara';

    return (
        <AppLayout>
            <Head title={`Pinjaman - ${pinjaman.anggota.nama}`} />

            <Link href={route('ketua.pinjaman.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Kembali
            </Link>

            <RingkasanKas ringkasan={ringkasanKas} judul="Beban Persetujuan Bulan Ini" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div className="lg:col-span-2 space-y-5">
                    <JejakNominal pinjaman={pinjaman} finalLabel="Final (cair)" />
                    <KartuKas kas={pinjaman.kas} nominalTahap={pinjaman.nominal_disetujui_bendahara ?? pinjaman.nominal_diminta} label="Nominal usulan Bendahara" />
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

                    {pinjaman.catatan_bendahara && (
                        <Card className="bg-slate-50">
                            <p className="text-sm font-semibold text-slate-400 mb-1.5">Catatan dari Bendahara</p>
                            <p className="text-base text-slate-700">{pinjaman.catatan_bendahara}</p>
                        </Card>
                    )}

                    {bisaDiproses && (
                        <Card>
                            <p className="text-base font-bold text-slate-800 mb-4">Keputusan Final</p>

                            {!aksi ? (
                                <div className="flex items-center gap-3">
                                    <Button variant="primary" onClick={() => setAksi('approve')}>
                                        Setujui & Cairkan
                                    </Button>
                                    <Button variant="danger" onClick={() => setAksi('reject')}>
                                        Tolak
                                    </Button>
                                </div>
                            ) : (
                                <form onSubmit={submit}>
                                    {aksi === 'approve' && (
                                        <div className="space-y-3 mb-4">
                                            <div>
                                                <label className="block text-sm font-semibold text-slate-600 mb-2">
                                                    Nominal Final Cair (maks {formatRupiah(pinjaman.limit_tersedia)})
                                                </label>
                                                <div className="relative">
                                                    <span className="absolute left-4 top-1/2 -translate-y-1/2 text-base font-semibold text-slate-400">Rp</span>
                                                    <input
                                                        type="number"
                                                        min={1}
                                                        value={data.nominal}
                                                        onChange={(e) => setData('nominal', e.target.value)}
                                                        className="w-full pl-12 pr-4 py-2.5 text-lg font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                                        autoFocus
                                                    />
                                                </div>
                                                {errors.nominal && <p className="text-sm text-red-600 mt-1.5">{errors.nominal}</p>}
                                                {nominalFinal > (pinjaman.limit_tersedia ?? 0) && (
                                                    <p className="text-sm text-amber-600 mt-1.5">Melebihi limit tersedia anggota — server akan menolak.</p>
                                                )}
                                            </div>
                                            <div>
                                                <label className="block text-sm font-semibold text-slate-600 mb-2">
                                                    Tenor Final (kosongkan = ikut usulan / auto-clamp)
                                                </label>
                                                <div className="relative">
                                                    <input
                                                        type="number"
                                                        min={1}
                                                        max={120}
                                                        value={data.tenor_bulan}
                                                        onChange={(e) => setData('tenor_bulan', e.target.value)}
                                                        placeholder={`Default: ${pinjaman.tenor_disetujui_bendahara ?? pinjaman.tenor_bulan} bln`}
                                                        className="w-full px-4 py-2.5 text-lg font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                                    />
                                                    <span className="absolute right-4 top-1/2 -translate-y-1/2 text-base font-semibold text-slate-400">bln</span>
                                                </div>
                                                {errors.tenor_bulan && <p className="text-sm text-red-600 mt-1.5">{errors.tenor_bulan}</p>}
                                            </div>
                                            <PreviewNominal
                                                routeName="ketua.pinjaman.preview"
                                                pinjamanId={pinjaman.id}
                                                nominal={data.nominal}
                                                tenorBulan={data.tenor_bulan}
                                            />
                                        </div>
                                    )}
                                    <label className="block text-sm font-semibold text-slate-600 mb-2">
                                        Catatan {aksi === 'approve' ? 'Persetujuan' : 'Penolakan'}
                                    </label>
                                    <textarea
                                        value={data.catatan}
                                        onChange={(e) => setData('catatan', e.target.value)}
                                        rows={3}
                                        placeholder={aksi === 'approve' ? 'Contoh: Disetujui, dana dicairkan.' : 'Contoh: Melebihi kapasitas kas bulan ini.'}
                                        className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                    />
                                    {errors.catatan && <p className="text-sm text-red-600 mt-1.5">{errors.catatan}</p>}
                                    {errors.keputusan && <p className="text-sm text-red-600 mt-1.5">{errors.keputusan}</p>}

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
                </div>

                <Card>
                    <p className="text-sm font-semibold text-slate-400 mb-3">Data Anggota</p>
                    <div className="space-y-3">
                        <div>
                            <p className="text-sm text-slate-400">Nama</p>
                            <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.nama}</p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-400">No. Karyawan</p>
                            <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.no_karyawan}</p>
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