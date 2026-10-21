import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@/Components/ui/Button';
import ChipNominal from '@/Components/ui/ChipNominal';
import StatusBadge from '@/Components/ui/StatusBadge';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';
import JejakNominal from '@/Pages/PinjamanApproval/JejakNominal';
import KartuKas from '@/Pages/PinjamanApproval/KartuKas';
import PreviewNominal from '@/Pages/PinjamanApproval/PreviewNominal';
import RincianBebanPinjaman from '@/Pages/PinjamanApproval/RincianBebanPinjaman';

const jabatanLabel = { staff: 'Staff', hod: 'HOD' };

export default function KeputusanDrawer({ pinjaman, onClose }) {
    const [aksi, setAksi] = useState(null); // 'approve' | 'reject' | 'cair' | null
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
        nominal: String(pinjaman.nominal_disetujui_bendahara ?? pinjaman.nominal_diminta ?? pinjaman.nominal),
        tenor_bulan: '',
    });

    const bisaApprove = pinjaman.status === 'approved_admin';
    const bisaCair = pinjaman.status === 'approved_bendahara' && Boolean(pinjaman.cair_oleh_bendahara);
    const bisaDiproses = bisaApprove || bisaCair;

    const nominalUsulan = Number(data.nominal) || 0;
    const diAtasLimit = nominalUsulan > (pinjaman.limit_tersedia ?? 0);

    function submit(e) {
        e.preventDefault();
        let url;
        if (aksi === 'cair') {
            url = route('bendahara.pinjaman.cair', pinjaman.id);
        } else if (aksi === 'approve') {
            url = route('bendahara.pinjaman.approve', pinjaman.id);
        } else {
            url = route('bendahara.pinjaman.reject', pinjaman.id);
        }

        post(url, withIdempotencyKey({
            preserveScroll: true,
            onSuccess: () => onClose(),
        }));
    }

    return (
        <div className="space-y-4">
            {/* Catatan Admin jika ada */}
            {pinjaman.catatan_admin && (
                <div className="bg-sky-50 border border-sky-200 rounded-xl p-4">
                    <p className="text-xs font-bold text-sky-800 mb-1">Catatan dari Verifikasi Admin</p>
                    <p className="text-sm text-slate-700">{pinjaman.catatan_admin}</p>
                </div>
            )}

            <div className="bg-brand-navy rounded-2xl p-5 text-white">
                <p className="text-xs text-slate-300 mb-1">Nominal Diminta Anggota</p>
                <p className="text-3xl font-bold mb-2 privacy-blur">{formatRupiah(pinjaman.nominal_diminta)}</p>
                <p className="text-sm text-slate-300 italic mb-4 privacy-blur">{pinjaman.terbilang}</p>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-white/10">
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Tenor Diminta</p>
                        <p className="text-lg font-bold">{pinjaman.tenor_diminta} bulan</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Bunga</p>
                        <p className="text-lg font-bold">{pinjaman.persentase_bunga}% / bulan</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-300 mb-1">Tanggal Pengajuan</p>
                        <p className="text-lg font-bold">{pinjaman.tanggal_pengajuan}</p>
                    </div>
                </div>
            </div>

            <JejakNominal pinjaman={pinjaman} />
            <KartuKas kas={pinjaman.kas} nominalTahap={pinjaman.nominal_disetujui_bendahara ?? pinjaman.nominal_diminta} label="Nominal usulan tahap ini" />

            <div className="bg-slate-50 rounded-xl p-5 border border-slate-200">
                <p className="text-sm font-bold text-slate-700 mb-3">Data Anggota</p>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-4">
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Nama</p>
                        <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.nama}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">No. Karyawan</p>
                        <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.no_karyawan}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Cabang</p>
                        <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.cabang}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Jabatan</p>
                        <p className="text-base font-semibold text-slate-800">{jabatanLabel[pinjaman.anggota.jabatan]}</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Lama Keanggotaan</p>
                        <p className="text-base font-semibold text-slate-800">{pinjaman.anggota.lama_keanggotaan_tahun} tahun</p>
                    </div>
                    <div>
                        <p className="text-xs text-slate-400 mb-1">Reloan</p>
                        <p className="text-base font-semibold text-slate-800">
                            {pinjaman.sudah_pakai_privilege_reloan ? 'Ya' : 'Tidak'}
                        </p>
                    </div>
                </div>
            </div>

            <RincianBebanPinjaman pinjaman={pinjaman} />

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="bg-slate-50 rounded-xl p-4">
                    <p className="text-xs text-slate-400 mb-2">Keperluan Peminjaman</p>
                    <p className="text-sm text-slate-700">{pinjaman.keperluan || '-'}</p>
                </div>

                <div className="bg-slate-50 rounded-xl p-4">
                    <p className="text-xs text-slate-400 mb-2">Rekening Tujuan Pencairan</p>
                    <p className="text-sm font-bold text-slate-800">{pinjaman.rekening.bank}</p>
                    <p className="text-sm text-slate-600">{pinjaman.rekening.no_rekening}</p>
                    <p className="text-xs text-slate-400">a.n. {pinjaman.rekening.atas_nama}</p>
                </div>
            </div>

            {pinjaman.catatan_bendahara && (
                <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <p className="text-xs text-slate-400 mb-1">Catatan Bendahara</p>
                    <p className="text-sm text-slate-700">{pinjaman.catatan_bendahara}</p>
                </div>
            )}

            <div className="pt-4 border-t border-slate-100">
                {bisaDiproses ? (
                    <>
                        {!aksi ? (
                            <div className="flex items-center gap-3">
                                {bisaApprove && (
                                    <>
                                        <Button variant="primary" onClick={() => setAksi('approve')}>
                                            Setujui & Usulkan
                                        </Button>
                                        <Button variant="danger" onClick={() => setAksi('reject')}>
                                            Tolak
                                        </Button>
                                    </>
                                )}
                                {bisaCair && (
                                    <Button variant="primary" onClick={() => setAksi('cair')} className="bg-emerald-600 hover:bg-emerald-700 text-white">
                                        Cairkan Pinjaman (Mandiri Ketua)
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <form onSubmit={submit}>
                                {aksi === 'approve' && (
                                    <div className="space-y-3 mb-4">
                                        <div>
                                            <label className="block text-sm font-semibold text-slate-600 mb-2">
                                                Nominal Usulan (maks <span className="privacy-blur">{formatRupiah(pinjaman.limit_tersedia)}</span>)
                                            </label>
                                            <div className="relative">
                                                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-base font-semibold text-slate-400">Rp</span>
                                                <input
                                                    type="number"
                                                    min={1}
                                                    value={data.nominal}
                                                    onChange={(e) => setData('nominal', e.target.value)}
                                                    className="w-full pl-12 pr-4 py-2.5 text-lg font-bold rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors privacy-blur"
                                                    autoFocus
                                                />
                                            </div>
                                            <ChipNominal grup="pinjaman_usulan" nilai={data.nominal} onPilih={(n) => setData('nominal', n)} />
                                            {errors.nominal && <p className="text-sm text-red-600 mt-1.5">{errors.nominal}</p>}
                                            {diAtasLimit && (
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

                                {aksi === 'cair' && (
                                    <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl mb-4 space-y-2">
                                        <p className="text-sm font-bold text-emerald-800">Konfirmasi Pencairan Dana</p>
                                        <p className="text-xs text-emerald-700">
                                            Pinjaman ini diajukan mandiri oleh Ketua Koperasi. Sesuai ketentuan, pencairan dana dilakukan oleh Bendahara.
                                        </p>
                                        <p className="text-base font-bold text-slate-800">
                                            Nominal: <span className="privacy-blur">{formatRupiah(pinjaman.nominal_disetujui_bendahara ?? pinjaman.nominal)}</span>
                                        </p>
                                    </div>
                                )}

                                {errors.keputusan && (
                                    <p className="text-sm text-red-600 mb-3">{errors.keputusan}</p>
                                )}
                                <label className="block text-sm font-semibold text-slate-600 mb-2">
                                    Catatan {aksi === 'approve' ? 'Persetujuan' : aksi === 'cair' ? 'Pencairan' : 'Penolakan'}
                                </label>
                                <textarea
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    rows={3}
                                    placeholder={
                                        aksi === 'approve'
                                            ? 'Contoh: Data lengkap, saldo mencukupi, usulan nominal disetujui.'
                                            : aksi === 'cair'
                                            ? 'Contoh: Dana telah ditransfer ke rekening pemohon.'
                                            : 'Contoh: Dokumen atau kriteria belum memenuhi.'
                                    }
                                    className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                />
                                {errors.catatan && <p className="text-sm text-red-600 mt-1.5">{errors.catatan}</p>}

                                <div className="flex items-center gap-3 mt-4">
                                    <Button
                                        type="submit"
                                        variant={aksi === 'approve' || aksi === 'cair' ? 'primary' : 'danger'}
                                        disabled={processing}
                                        className={aksi === 'cair' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : ''}
                                    >
                                        {processing
                                            ? 'Memproses...'
                                            : aksi === 'cair'
                                            ? 'Konfirmasi Pencairan'
                                            : `Konfirmasi ${aksi === 'approve' ? 'Setujui' : 'Tolak'}`}
                                    </Button>
                                    <Button type="button" variant="ghost" onClick={() => setAksi(null)}>
                                        Batal
                                    </Button>
                                </div>
                            </form>
                        )}
                    </>
                ) : (
                    <div className="flex items-center gap-3">
                        <StatusBadge status={pinjaman.status} />
                    </div>
                )}
            </div>
        </div>
    );
}
