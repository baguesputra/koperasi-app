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
    const [aksi, setAksi] = useState(null); // 'approve' | 'reject' | null
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
        nominal: String(pinjaman.nominal_diminta ?? pinjaman.nominal),
        tenor_bulan: '',
    });
    const nominalUsulan = Number(data.nominal) || 0;

    function submit(e) {
        e.preventDefault();
        const url = aksi === 'approve'
            ? route('bendahara.pinjaman.approve', pinjaman.id)
            : route('bendahara.pinjaman.reject', pinjaman.id);
        post(url, withIdempotencyKey());
    }

    const bisaDiproses = pinjaman.status === 'diajukan';

    return (
        <AppLayout>
            <Head title={`Pinjaman - ${pinjaman.anggota.nama}`} />

            <Link href={route('bendahara.pinjaman.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Kembali
            </Link>

            <RingkasanKas ringkasan={ringkasanKas} judul="Beban Persetujuan Bulan Ini" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div className="lg:col-span-2 space-y-5">
                    <Card>
                        <p className="text-sm font-semibold text-slate-400 mb-1">Nominal Diminta Anggota</p>
                        <p className="text-3xl font-bold text-slate-800 mb-4">{formatRupiah(pinjaman.nominal_diminta)}</p>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-slate-400">Tenor Diminta</p>
                                <p className="text-base font-semibold text-slate-700">{pinjaman.tenor_diminta} bulan</p>
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

                    <JejakNominal pinjaman={pinjaman} />
                    <KartuKas kas={pinjaman.kas} nominalTahap={pinjaman.nominal_disetujui_bendahara ?? pinjaman.nominal_diminta} label="Nominal usulan tahap ini" />

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
                                    {aksi === 'approve' && (
                                        <div className="space-y-3 mb-4">
                                            <div>
                                                <label className="block text-sm font-semibold text-slate-600 mb-2">
                                                    Nominal Usulan (maks {formatRupiah(pinjaman.limit_tersedia)})
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
                                                {nominalUsulan > (pinjaman.limit_tersedia ?? 0) && nominalUsulan > 0 && (
                                                    <p className="text-sm text-amber-600 mt-1.5">Melebihi limit tersedia anggota — server akan menolak.</p>
                                                )}
                                            </div>
                                            <div>
                                                <label className="block text-sm font-semibold text-slate-600 mb-2">
                                                    Tenor Usulan (kosongkan = auto-clamp maks tabel)
                                                </label>
                                                <div className="relative">
                                                    <input
                                                        type="number"
                                                        min={1}
                                                        max={120}
                                                        value={data.tenor_bulan}
                                                        onChange={(e) => setData('tenor_bulan', e.target.value)}
                                                        placeholder={`Auto: maks ${pinjaman.tenor_maksimal_diminta ?? '-'} bln`}
                                                        className="w-full px-4 py-2.5 text-lg font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                                    />
                                                    <span className="absolute right-4 top-1/2 -translate-y-1/2 text-base font-semibold text-slate-400">bln</span>
                                                </div>
                                                {errors.tenor_bulan && <p className="text-sm text-red-600 mt-1.5">{errors.tenor_bulan}</p>}
                                            </div>
                                            <PreviewNominal
                                                routeName="bendahara.pinjaman.preview"
                                                pinjamanId={pinjaman.id}
                                                nominal={data.nominal}
                                                tenorBulan={data.tenor_bulan}
                                            />
                                        </div>
                                    )}
                                    {errors.keputusan && <p className="text-sm text-red-600 mb-3">{errors.keputusan}</p>}
                                    <label className="block text-sm font-semibold text-slate-600 mb-2">
                                        Catatan {aksi === 'approve' ? 'Persetujuan' : 'Penolakan'}
                                    </label>
                                    <textarea
                                        value={data.catatan}
                                        onChange={(e) => setData('catatan', e.target.value)}
                                        rows={3}
                                        placeholder={aksi === 'approve' ? 'Contoh: Data lengkap, memenuhi syarat.' : 'Contoh: Dokumen belum lengkap.'}
                                        className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
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