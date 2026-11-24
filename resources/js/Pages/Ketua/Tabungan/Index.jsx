import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    ArrowUpCircle, CheckCircle2, ClipboardCheck, Landmark, UserCheck, Wallet, XCircle,
} from 'lucide-react';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import CountBadge from '@/Components/ui/CountBadge';
import Drawer from '@/Components/ui/Drawer';
import {
    DrawerBody,
    DrawerField,
    DrawerFieldGrid,
    DrawerFooter,
    DrawerFooterActions,
    DrawerKeyValue,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import FormField from '@/Components/ui/FormField';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import PageHeader from '@/Components/ui/PageHeader';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

export default function Index({
    tabAktif = 'buka',
    antreanBuka = [],
    antreanTarik = [],
    kasFisik = { saldo_bank: 0, saldo_kas_kecil: 0 },
    counts = { buka: 0, tarik: 0 },
}) {
    const [bukaDipilih, setBukaDipilih] = useState(null);
    const [tarikDipilih, setTarikDipilih] = useState(null);
    const [showTolakMode, setShowTolakMode] = useState(false);

    const bukaCatatanRef = useRef(null);
    const tarikCatatanRef = useRef(null);
    const form = useForm({ catatan: '' });

    useEffect(() => {
        if (!showTolakMode) return undefined;
        const id = window.requestAnimationFrame(() => {
            if (bukaDipilih) bukaCatatanRef.current?.focus({ preventScroll: false });
            else if (tarikDipilih) tarikCatatanRef.current?.focus({ preventScroll: false });
        });
        return () => window.cancelAnimationFrame(id);
    }, [showTolakMode, bukaDipilih, tarikDipilih]);

    function pindahTab(tab) {
        router.get(route('ketua.tabungan.index'), { tab }, { preserveState: false, replace: true });
    }

    function tutupDrawers() {
        setBukaDipilih(null);
        setTarikDipilih(null);
        setShowTolakMode(false);
        form.reset('catatan');
        form.clearErrors();
    }

    function submitApproveBuka(e) {
        e.preventDefault();
        if (!bukaDipilih) return;
        form.post(
            route('ketua.tabungan.buka.approve', bukaDipilih.id),
            withIdempotencyKey({ preserveScroll: true, onSuccess: tutupDrawers }),
        );
    }

    function submitRejectBuka(e) {
        e.preventDefault();
        if (!bukaDipilih) return;
        form.post(
            route('ketua.tabungan.buka.reject', bukaDipilih.id),
            withIdempotencyKey({ preserveScroll: true, onSuccess: tutupDrawers }),
        );
    }

    function submitApproveTarik(e) {
        e.preventDefault();
        if (!tarikDipilih) return;
        form.post(
            route('ketua.tabungan.pengajuan.approve', tarikDipilih.id),
            withIdempotencyKey({ preserveScroll: true, onSuccess: tutupDrawers }),
        );
    }

    function submitRejectTarik(e) {
        e.preventDefault();
        if (!tarikDipilih) return;
        form.post(
            route('ketua.tabungan.pengajuan.reject', tarikDipilih.id),
            withIdempotencyKey({ preserveScroll: true, onSuccess: tutupDrawers }),
        );
    }

    const tabs = [
        { key: 'buka', label: 'Pembukaan Rekening', count: counts.buka },
        { key: 'tarik', label: 'Penarikan & Tutup Rekening', count: counts.tarik },
    ];

    return (
        <AppLayout>
            <Head title="Persetujuan Tabungan (Ketua)" />

            <PageHeader
                title="Persetujuan Ketua — Tabungan"
                subtitle="Tahap 3: Keputusan final pembukaan rekening dan persetujuan pencairan penarikan / penutupan tabungan"
            />

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <Card padding="sm">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-slate-500">Saldo Bank Koperasi</p>
                            <p className="text-lg font-bold text-slate-800 tabular-nums privacy-blur mt-0.5">{formatRupiah(kasFisik.saldo_bank)}</p>
                        </div>
                        <span className="w-9 h-9 rounded-xl bg-brand-navy/5 text-brand-navy flex items-center justify-center">
                            <Landmark size={18} />
                        </span>
                    </div>
                </Card>
                <Card padding="sm">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-slate-500">Saldo Kas Kecil</p>
                            <p className="text-lg font-bold text-slate-800 tabular-nums privacy-blur mt-0.5">{formatRupiah(kasFisik.saldo_kas_kecil)}</p>
                        </div>
                        <span className="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                            <Wallet size={18} />
                        </span>
                    </div>
                </Card>
            </div>

            <div className="flex flex-wrap gap-1.5 mb-4 bg-slate-100/90 border border-slate-200/70 p-1 rounded-xl w-full sm:w-fit">
                {tabs.map((t) => {
                    const aktif = tabAktif === t.key;
                    return (
                        <button
                            key={t.key}
                            type="button"
                            onClick={() => pindahTab(t.key)}
                            className={`flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all ${
                                aktif
                                    ? 'bg-gradient-to-r from-brand-navy to-brand-navy-light text-white shadow-xs'
                                    : 'text-slate-600 hover:bg-white/80 hover:text-brand-navy'
                            }`}
                        >
                            <span>{t.label}</span>
                            {t.count > 0 && <CountBadge count={t.count} active={aktif} />}
                        </button>
                    );
                })}
            </div>

            {tabAktif === 'buka' && (
                <Card padding="none" className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th className="px-4 py-3">Anggota</th>
                                    <th className="px-4 py-3">Cabang</th>
                                    <th className="px-4 py-3">Tgl Pengajuan</th>
                                    <th className="px-4 py-3">Tarif Berlaku</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {antreanBuka.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-12 text-center text-slate-400">
                                            Tidak ada pengajuan pembukaan rekening yang menunggu keputusan Ketua.
                                        </td>
                                    </tr>
                                ) : (
                                    antreanBuka.map((r) => (
                                        <tr
                                            key={r.id}
                                            onClick={() => { setBukaDipilih(r); setShowTolakMode(false); }}
                                            className="hover:bg-[#F2FBF5] transition-colors cursor-pointer"
                                        >
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-2.5">
                                                    <FotoAnggota src={r.anggota?.foto_url} nama={r.anggota?.nama} size="sm" />
                                                    <div>
                                                        <p className="font-semibold text-slate-800">{r.anggota?.nama}</p>
                                                        <p className="text-xs text-slate-500">{r.anggota?.no_karyawan}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-600">{r.anggota?.cabang ?? '-'}</td>
                                            <td className="px-4 py-2.5 text-slate-600">{r.tanggal_pengajuan}</td>
                                            <td className="px-4 py-2.5 font-semibold text-brand-navy tabular-nums">{r.suku_bunga_saat_buka}% / thn</td>
                                            <td className="px-4 py-2.5"><StatusBadge status={r.status} /></td>
                                            <td className="px-4 py-2.5 text-right">
                                                <Button size="sm" variant="neutral" onClick={(e) => { e.stopPropagation(); setBukaDipilih(r); setShowTolakMode(false); }}>
                                                    Putuskan
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}

            {tabAktif === 'tarik' && (
                <Card padding="none" className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th className="px-4 py-3">Anggota</th>
                                    <th className="px-4 py-3">Jenis &amp; Kanal</th>
                                    <th className="px-4 py-3 text-right">Saldo Rek</th>
                                    <th className="px-4 py-3 text-right">Nominal Tarik</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {antreanTarik.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-12 text-center text-slate-400">
                                            Tidak ada pengajuan penarikan tabungan yang menunggu persetujuan Ketua.
                                        </td>
                                    </tr>
                                ) : (
                                    antreanTarik.map((p) => (
                                        <tr
                                            key={p.id}
                                            onClick={() => { setTarikDipilih(p); setShowTolakMode(false); }}
                                            className="hover:bg-[#F2FBF5] transition-colors cursor-pointer"
                                        >
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-2.5">
                                                    <FotoAnggota src={p.anggota?.foto_url} nama={p.anggota?.nama} size="sm" />
                                                    <div>
                                                        <p className="font-semibold text-slate-800">{p.anggota?.nama}</p>
                                                        <p className="text-xs text-slate-500">{p.no_rekening}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <p className="font-semibold text-slate-800">
                                                    {p.jenis === 'tutup_rekening' ? 'Tutup Rekening' : 'Penarikan Saldo'}
                                                </p>
                                                <p className="text-xs text-slate-500 capitalize">Via {p.kanal}</p>
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums privacy-blur text-slate-600">{formatRupiah(p.saldo_rekening)}</td>
                                            <td className="px-4 py-2.5 text-right font-bold text-rose-600 tabular-nums privacy-blur">{formatRupiah(p.nominal)}</td>
                                            <td className="px-4 py-2.5"><StatusBadge status={p.status} /></td>
                                            <td className="px-4 py-2.5 text-right">
                                                <Button size="sm" variant="neutral" onClick={(e) => { e.stopPropagation(); setTarikDipilih(p); setShowTolakMode(false); }}>
                                                    Putuskan
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}

            {/* DRAWER BUKA REKENING */}
            <Drawer
                show={Boolean(bukaDipilih)}
                title="Persetujuan Ketua — Buka Rekening"
                description={bukaDipilih?.anggota?.nama}
                badge={bukaDipilih ? <StatusBadge status={bukaDipilih.status} /> : null}
                onClose={tutupDrawers}
                layout="split"
            >
                {bukaDipilih && (
                    <form onSubmit={showTolakMode ? submitRejectBuka : submitApproveBuka} className="flex-1 min-h-0 flex flex-col">
                        <DrawerBody>
                            <DrawerSection title="Profil Pemohon" icon={UserCheck}>
                                <DrawerFieldGrid cols={2}>
                                    <DrawerField label="Nama Lengkap" value={bukaDipilih.anggota?.nama} />
                                    <DrawerField label="No. Karyawan" value={bukaDipilih.anggota?.no_karyawan} mono />
                                    <DrawerField label="Cabang" value={bukaDipilih.anggota?.cabang} />
                                    <DrawerField label="Tgl Pengajuan" value={bukaDipilih.tanggal_pengajuan} />
                                    <DrawerField label="Tarif Berlaku" value={`${bukaDipilih.suku_bunga_saat_buka}% / tahun`} />
                                    <DrawerField label="Versi S&K Disetujui" value={bukaDipilih.versi_syarat} mono />
                                </DrawerFieldGrid>
                            </DrawerSection>

                            <DrawerSection title={showTolakMode ? 'Alasan Penolakan' : 'Catatan Ketua (Opsional)'} tone={showTolakMode ? 'rose' : 'default'}>
                                <FormField label={showTolakMode ? 'Alasan Penolakan (wajib)' : 'Catatan'} error={form.errors.catatan || form.errors.aksi} required={showTolakMode}>
                                    <TextField
                                        ref={bukaCatatanRef}
                                        data-autofocus
                                        as="textarea"
                                        rows={2}
                                        value={form.data.catatan}
                                        onChange={(e) => form.setData('catatan', e.target.value)}
                                        placeholder={showTolakMode ? 'Tuliskan alasan penolakan (min. 3 karakter)...' : 'Catatan persetujuan Ketua...'}
                                        required={showTolakMode}
                                    />
                                </FormField>
                            </DrawerSection>

                            <DrawerSection title="Jejak Verifikasi Sebelumnya" icon={ClipboardCheck}>
                                <DrawerKeyValue
                                    rows={[
                                        { label: 'Sekretaris', value: `${bukaDipilih.admin_oleh ?? '-'} (${bukaDipilih.disetujui_admin_pada ?? '-'})` },
                                        { label: 'Catatan Sekretaris', value: bukaDipilih.catatan_admin || '-' },
                                        { label: 'Bendahara', value: `${bukaDipilih.bendahara_oleh ?? '-'} (${bukaDipilih.disetujui_bendahara_pada ?? '-'})` },
                                        { label: 'Catatan Bendahara', value: bukaDipilih.catatan_bendahara || '-' },
                                    ]}
                                />
                            </DrawerSection>
                        </DrawerBody>

                        <DrawerFooter>
                            {showTolakMode ? (
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="neutral" size="drawer" onClick={() => setShowTolakMode(false)}>Kembali</Button>
                                    <Button type="submit" variant="danger" size="drawer" disabled={form.processing}>
                                        {form.processing ? 'Menolak...' : 'Konfirmasi Tolak'}
                                    </Button>
                                </DrawerFooterActions>
                            ) : (
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="dangerSoft" size="drawer" onClick={() => setShowTolakMode(true)}>
                                        <XCircle size={15} /> Tolak
                                    </Button>
                                    <Button type="submit" size="drawer" disabled={form.processing}>
                                        <CheckCircle2 size={15} /> {form.processing ? 'Mengaktifkan...' : 'Setujui & Aktifkan Rekening'}
                                    </Button>
                                </DrawerFooterActions>
                            )}
                        </DrawerFooter>
                    </form>
                )}
            </Drawer>

            {/* DRAWER PERSETUJUAN PENARIKAN */}
            <Drawer
                show={Boolean(tarikDipilih)}
                title={tarikDipilih?.jenis === 'tutup_rekening' ? 'Persetujuan Ketua — Tutup Rekening' : 'Persetujuan Ketua — Penarikan Tabungan'}
                description={tarikDipilih ? `${tarikDipilih.anggota?.nama} • ${tarikDipilih.no_rekening}` : ''}
                badge={tarikDipilih ? <StatusBadge status={tarikDipilih.status} /> : null}
                onClose={tutupDrawers}
                layout="split"
            >
                {tarikDipilih && (
                    <form onSubmit={showTolakMode ? submitRejectTarik : submitApproveTarik} className="flex-1 min-h-0 flex flex-col">
                        <DrawerBody>
                            <DrawerSection title="Ringkasan Pengajuan" icon={ArrowUpCircle} tone="amber">
                                <DrawerKeyValue
                                    rows={[
                                        { label: 'Anggota', value: `${tarikDipilih.anggota?.nama} (${tarikDipilih.anggota?.no_karyawan})` },
                                        { label: 'No. Rekening', value: tarikDipilih.no_rekening, mono: true },
                                        { label: 'Saldo Saat Ini', value: formatRupiah(tarikDipilih.saldo_rekening), privacy: true },
                                        { label: 'Nominal Diajukan', value: formatRupiah(tarikDipilih.nominal), tone: 'rose', emphasize: true, privacy: true },
                                        { label: 'Kanal', value: tarikDipilih.kanal },
                                        ...(tarikDipilih.catatan_anggota ? [{ label: 'Catatan Anggota', value: tarikDipilih.catatan_anggota }] : []),
                                    ]}
                                />
                            </DrawerSection>

                            <DrawerSection title={showTolakMode ? 'Alasan Penolakan' : 'Catatan Ketua (Opsional)'} tone={showTolakMode ? 'rose' : 'default'}>
                                <FormField label={showTolakMode ? 'Alasan Penolakan (wajib)' : 'Catatan'} error={form.errors.catatan || form.errors.aksi} required={showTolakMode}>
                                    <TextField
                                        ref={tarikCatatanRef}
                                        data-autofocus
                                        as="textarea"
                                        rows={2}
                                        value={form.data.catatan}
                                        onChange={(e) => form.setData('catatan', e.target.value)}
                                        placeholder={showTolakMode ? 'Tuliskan alasan penolakan (min. 3 karakter)...' : 'Catatan persetujuan sebelum pencairan Bendahara...'}
                                        required={showTolakMode}
                                    />
                                </FormField>
                            </DrawerSection>

                            <DrawerSection title="Jejak Verifikasi Sebelumnya" icon={ClipboardCheck}>
                                <DrawerKeyValue
                                    rows={[
                                        { label: 'Sekretaris', value: `${tarikDipilih.admin_oleh ?? '-'} (${tarikDipilih.disetujui_admin_pada ?? '-'})` },
                                        { label: 'Catatan Sekretaris', value: tarikDipilih.catatan_admin || '-' },
                                        { label: 'Bendahara', value: `${tarikDipilih.bendahara_oleh ?? '-'} (${tarikDipilih.disetujui_bendahara_pada ?? '-'})` },
                                        { label: 'Catatan Bendahara', value: tarikDipilih.catatan_bendahara || '-' },
                                    ]}
                                />
                            </DrawerSection>
                        </DrawerBody>

                        <DrawerFooter>
                            {showTolakMode ? (
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="neutral" size="drawer" onClick={() => setShowTolakMode(false)}>Kembali</Button>
                                    <Button type="submit" variant="danger" size="drawer" disabled={form.processing}>
                                        {form.processing ? 'Menolak...' : 'Konfirmasi Tolak'}
                                    </Button>
                                </DrawerFooterActions>
                            ) : (
                                <DrawerFooterActions cols={2} stackOnMobile>
                                    <Button type="button" variant="dangerSoft" size="drawer" onClick={() => setShowTolakMode(true)}>
                                        <XCircle size={15} /> Tolak
                                    </Button>
                                    <Button type="submit" size="drawer" disabled={form.processing}>
                                        <CheckCircle2 size={15} /> {form.processing ? 'Menyetujui...' : 'Setujui & Teruskan Pencairan'}
                                    </Button>
                                </DrawerFooterActions>
                            )}
                        </DrawerFooter>
                    </form>
                )}
            </Drawer>
        </AppLayout>
    );
}
