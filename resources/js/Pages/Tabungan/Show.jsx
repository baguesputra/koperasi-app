import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowDownCircle, ArrowUpCircle, FileText, Percent, Plus, RotateCcw, Wallet,
} from 'lucide-react';
import BackLink from '@/Components/ui/BackLink';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import ChipNominal from '@/Components/ui/ChipNominal';
import Drawer from '@/Components/ui/Drawer';
import {
    DrawerBody,
    DrawerFooter,
    DrawerFooterActions,
    DrawerKeyValue,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import FormField from '@/Components/ui/FormField';
import PageHeader from '@/Components/ui/PageHeader';
import Pagination from '@/Components/ui/Pagination';
import Select from '@/Components/ui/Select';
import StatusBadge from '@/Components/ui/StatusBadge';
import TextField from '@/Components/ui/TextField';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

const jenisLabel = {
    setor: 'Setoran',
    tarik: 'Penarikan',
    bunga: 'Bunga Tabungan',
    koreksi_masuk: 'Koreksi Masuk',
    koreksi_keluar: 'Koreksi Keluar',
    tutup_rekening: 'Penutupan Rekening',
};

export default function Show({
    rekening,
    sukuBungaAktif,
    minSetor = 50000,
    estimasiBunga,
    transaksi,
    riwayatBunga = [],
    filters = {},
}) {
    const { auth } = usePage().props;
    const bisaKelola = auth.user?.permissions?.includes('tabungan.kelola');

    const [tab, setTab] = useState('mutasi');
    const [showSetorTunai, setShowSetorTunai] = useState(false);
    const [trxBatal, setTrxBatal] = useState(null);

    const hariIni = new Date().toISOString().slice(0, 10);
    const setorForm = useForm({
        rekening_tabungan_id: rekening.id,
        nominal: '',
        akun_kas: 'kas_kecil',
        tanggal: hariIni,
        keterangan: '',
        bukti: null,
    });

    const batalForm = useForm({ alasan: '' });

    function submitSetorTunai(e) {
        e.preventDefault();
        setorForm.post(route('bendahara.tabungan.setor-tunai'), {
            ...withIdempotencyKey({
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setorForm.reset('nominal', 'keterangan', 'bukti');
                    setShowSetorTunai(false);
                },
            }),
        });
    }

    function submitBatal(e) {
        e.preventDefault();
        if (!trxBatal) return;
        batalForm.post(
            route('bendahara.tabungan.transaksi.batalkan', trxBatal.id),
            withIdempotencyKey({
                preserveScroll: true,
                onSuccess: () => {
                    batalForm.reset();
                    setTrxBatal(null);
                },
            })
        );
    }

    function filterJenis(jenis) {
        router.get(route('tabungan.show', rekening.id), jenis ? { jenis } : {}, { preserveState: true, preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title={`Tabungan - ${rekening.anggota?.nama ?? ''}`} />

            <BackLink href={route('tabungan.index')}>Kembali ke daftar tabungan</BackLink>
            <PageHeader
                title={rekening.anggota?.nama ?? '-'}
                subtitle={`${rekening.anggota?.no_karyawan ?? '-'} • No. Rek ${rekening.no_rekening ?? '(Belum terbit)'}`}
                actions={
                    bisaKelola && rekening.status === 'aktif' ? (
                        <Button size="sm" onClick={() => setShowSetorTunai(true)}>
                            <Plus size={15} /> Setor Tunai
                        </Button>
                    ) : null
                }
            >
                <StatusBadge status={rekening.status} />
            </PageHeader>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
                <div className="lg:col-span-2 rounded-2xl bg-gradient-to-br from-brand-navy via-[#13294B] to-[#0E3A24] text-white p-5 shadow-md">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs text-slate-300 font-semibold">Saldo Tabungan Saat Ini</span>
                        <span className="px-2.5 py-0.5 rounded-full bg-white/10 text-xs font-semibold tabular-nums">
                            Tarif aktif {sukuBungaAktif}% / thn
                        </span>
                    </div>
                    <p className="text-3xl font-bold tabular-nums mt-1 privacy-blur">{formatRupiah(rekening.saldo)}</p>
                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4 pt-3.5 border-t border-white/10 text-xs">
                        <div>
                            <p className="text-slate-300">Tanggal Buka</p>
                            <p className="font-semibold mt-0.5">{rekening.tanggal_buka ?? '-'}</p>
                        </div>
                        <div>
                            <p className="text-slate-300">Bunga Saat Buka (Snapshot)</p>
                            <p className="font-semibold mt-0.5 tabular-nums">{rekening.suku_bunga_saat_buka}% / thn</p>
                        </div>
                        <div>
                            <p className="text-slate-300">Estimasi Bunga Bulan Ini</p>
                            <p className="font-semibold text-emerald-300 mt-0.5 tabular-nums privacy-blur">
                                +{formatRupiah(estimasiBunga?.bunga_bersih ?? 0)}
                            </p>
                        </div>
                    </div>
                </div>

                <Card>
                    <h2 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Informasi Rekening</h2>
                    <dl className="space-y-2 text-xs sm:text-sm">
                        <div className="flex justify-between gap-2">
                            <dt className="text-slate-500">Cabang</dt>
                            <dd className="font-semibold text-slate-800">{rekening.anggota?.cabang ?? '-'}</dd>
                        </div>
                        <div className="flex justify-between gap-2">
                            <dt className="text-slate-500">Unit / Jabatan</dt>
                            <dd className="font-semibold text-slate-800 text-right">{rekening.anggota?.unit_bisnis ?? '-'} / {rekening.anggota?.jabatan ?? '-'}</dd>
                        </div>
                        <div className="flex justify-between gap-2">
                            <dt className="text-slate-500">Tgl Pengajuan</dt>
                            <dd className="font-semibold text-slate-800">{rekening.tanggal_pengajuan ?? '-'}</dd>
                        </div>
                        <div className="flex justify-between gap-2">
                            <dt className="text-slate-500">Diproses Oleh</dt>
                            <dd className="font-semibold text-slate-800">{rekening.diproses_oleh ?? '-'}</dd>
                        </div>
                        <div className="flex justify-between gap-2">
                            <dt className="text-slate-500">Versi S&amp;K</dt>
                            <dd className="font-mono text-xs font-semibold text-slate-700">{rekening.versi_syarat ?? '-'}</dd>
                        </div>
                    </dl>
                </Card>
            </div>

            <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-xl w-fit mb-4">
                <button
                    type="button"
                    onClick={() => setTab('mutasi')}
                    className={`px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-colors ${
                        tab === 'mutasi' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-500'
                    }`}
                >
                    Riwayat Mutasi
                </button>
                <button
                    type="button"
                    onClick={() => setTab('bunga')}
                    className={`px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-colors ${
                        tab === 'bunga' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-500'
                    }`}
                >
                    Riwayat Bunga ({riwayatBunga.length})
                </button>
            </div>

            {tab === 'mutasi' && (
                <Card padding="none" className="overflow-hidden">
                    <div className="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                        <h2 className="text-sm font-bold text-slate-800">Buku Tabungan</h2>
                        <div className="w-44">
                            <Select size="sm" value={filters.jenis ?? ''} onChange={(e) => filterJenis(e.target.value)}>
                                <option value="">Semua Jenis</option>
                                <option value="setor">Setoran</option>
                                <option value="tarik">Penarikan</option>
                                <option value="bunga">Bunga Tabungan</option>
                                <option value="koreksi_masuk">Koreksi Masuk</option>
                                <option value="koreksi_keluar">Koreksi Keluar</option>
                            </Select>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th className="px-4 py-2.5">Tanggal</th>
                                    <th className="px-4 py-2.5">No. Bukti</th>
                                    <th className="px-4 py-2.5">Keterangan</th>
                                    <th className="px-4 py-2.5">Kanal</th>
                                    <th className="px-4 py-2.5 text-right">Nominal</th>
                                    <th className="px-4 py-2.5 text-right">Saldo Setelah</th>
                                    <th className="px-4 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {transaksi.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-10 text-center text-slate-400">
                                            Belum ada mutasi transaksi.
                                        </td>
                                    </tr>
                                ) : (
                                    transaksi.data.map((t) => (
                                        <tr key={t.id} className="hover:bg-[#F2FBF5] transition-colors">
                                            <td className="px-4 py-2.5 whitespace-nowrap text-slate-600">{t.tanggal}</td>
                                            <td className="px-4 py-2.5 whitespace-nowrap font-mono text-xs text-slate-700">{t.no_bukti}</td>
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <span className="font-semibold text-slate-800">{jenisLabel[t.jenis] ?? t.jenis}</span>
                                                    {t.dibatalkan_pada && <span className="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-600">Dibatalkan</span>}
                                                    {t.koreksi_dari_no_bukti && <span className="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700">Storno {t.koreksi_dari_no_bukti}</span>}
                                                </div>
                                                <p className="text-xs text-slate-500 mt-0.5">{t.keterangan}</p>
                                            </td>
                                            <td className="px-4 py-2.5 capitalize text-slate-600 whitespace-nowrap">{t.kanal}</td>
                                            <td className={`px-4 py-2.5 text-right font-bold tabular-nums privacy-blur whitespace-nowrap ${t.is_kredit ? 'text-brand-green-dark' : 'text-rose-600'}`}>
                                                {t.is_kredit ? '+' : '−'}{formatRupiah(t.nominal)}
                                            </td>
                                            <td className="px-4 py-2.5 text-right font-semibold text-slate-800 tabular-nums privacy-blur whitespace-nowrap">
                                                {formatRupiah(t.saldo_sesudah)}
                                            </td>
                                            <td className="px-4 py-2.5 text-right whitespace-nowrap">
                                                <div className="inline-flex items-center gap-1.5">
                                                    {t.bukti_url && (
                                                        <a
                                                            href={t.bukti_url}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="px-2 py-1 rounded-lg border border-slate-200 text-xs font-semibold text-brand-navy hover:bg-slate-50 inline-flex items-center gap-1"
                                                        >
                                                            <FileText size={12} /> Bukti
                                                        </a>
                                                    )}
                                                    {bisaKelola && t.bisa_dibatalkan && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setTrxBatal(t)}
                                                            className="px-2 py-1 rounded-lg border border-rose-200 text-xs font-semibold text-rose-600 hover:bg-rose-50 inline-flex items-center gap-1"
                                                        >
                                                            <RotateCcw size={12} /> Batalkan
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="px-4 py-3 border-t border-slate-100">
                        <Pagination links={transaksi.links} />
                    </div>
                </Card>
            )}

            {tab === 'bunga' && (
                <Card padding="none" className="overflow-hidden">
                    <div className="px-4 py-3 border-b border-slate-100">
                        <h2 className="text-sm font-bold text-slate-800">Riwayat Bunga Bulanan (Snapshot Historis)</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th className="px-4 py-2.5">Periode</th>
                                    <th className="px-4 py-2.5">Tarif Snapshot</th>
                                    <th className="px-4 py-2.5 text-right">Hari</th>
                                    <th className="px-4 py-2.5 text-right">Saldo Dasar</th>
                                    <th className="px-4 py-2.5 text-right">Bunga Bersih</th>
                                    <th className="px-4 py-2.5">Diposting Oleh</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {riwayatBunga.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-10 text-center text-slate-400">
                                            Belum ada bunga yang dibukukan.
                                        </td>
                                    </tr>
                                ) : (
                                    riwayatBunga.map((b) => (
                                        <tr key={b.id} className="hover:bg-[#F2FBF5] transition-colors">
                                            <td className="px-4 py-2.5 font-semibold text-slate-800">{b.periode}</td>
                                            <td className="px-4 py-2.5 tabular-nums text-slate-700">
                                                {b.suku_bunga_snapshot}% / thn
                                                {b.rincian_tarif?.length > 1 && (
                                                    <p className="text-[11px] text-slate-500">
                                                        {b.rincian_tarif.map((s) => `${s.dari}..${s.sampai}: ${s.persentase}%`).join(' | ')}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums text-slate-600">{b.jumlah_hari}</td>
                                            <td className="px-4 py-2.5 text-right tabular-nums privacy-blur text-slate-700">{formatRupiah(b.saldo_dasar)}</td>
                                            <td className="px-4 py-2.5 text-right font-bold text-brand-green-dark tabular-nums privacy-blur">+{formatRupiah(b.bunga_bersih)}</td>
                                            <td className="px-4 py-2.5 text-xs text-slate-500">{b.diposting_oleh} &bull; {b.diposting_pada}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}

            {/* DRAWER SETOR TUNAI */}
            <Drawer
                show={showSetorTunai}
                title="Input Setoran Tunai"
                description={`${rekening.anggota?.nama} • ${rekening.no_rekening}`}
                onClose={() => setShowSetorTunai(false)}
                layout="split"
            >
                <form onSubmit={submitSetorTunai} className="flex-1 min-h-0 flex flex-col">
                    <DrawerBody>
                        <DrawerSection title="Detail Setoran Tunai" icon={ArrowDownCircle}>
                            <FormField label="Nominal Setor (Rp)" error={setorForm.errors.nominal || setorForm.errors.setor_tunai} hint={`Minimal ${formatRupiah(minSetor)}`} required>
                                <TextField
                                    type="number"
                                    min={minSetor}
                                    value={setorForm.data.nominal}
                                    onChange={(e) => setorForm.setData('nominal', e.target.value)}
                                    className="tabular-nums"
                                    required
                                />
                                <ChipNominal grup="tabungan" nilai={setorForm.data.nominal} onPilih={(v) => setorForm.setData('nominal', v)} />
                            </FormField>

                            <FormField label="Masuk ke Akun Kas" error={setorForm.errors.akun_kas} required>
                                <Select value={setorForm.data.akun_kas} onChange={(e) => setorForm.setData('akun_kas', e.target.value)}>
                                    <option value="kas_kecil">Kas Kecil (Tunai di Kantor)</option>
                                    <option value="bank">Bank Koperasi</option>
                                </Select>
                            </FormField>

                            <FormField label="Tanggal Setor" error={setorForm.errors.tanggal} required>
                                <TextField
                                    type="date"
                                    max={hariIni}
                                    value={setorForm.data.tanggal}
                                    onChange={(e) => setorForm.setData('tanggal', e.target.value)}
                                    required
                                />
                            </FormField>

                            <FormField label="Keterangan (Opsional)" error={setorForm.errors.keterangan}>
                                <TextField
                                    as="textarea"
                                    rows={2}
                                    value={setorForm.data.keterangan}
                                    onChange={(e) => setorForm.setData('keterangan', e.target.value)}
                                    placeholder="Catatan setoran tunai"
                                />
                            </FormField>

                            <FormField label="Lampiran Kwitansi/Bukti (Opsional)" error={setorForm.errors.bukti}>
                                <input
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    onChange={(e) => setorForm.setData('bukti', e.target.files?.[0] ?? null)}
                                    className="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-navy/10 file:text-brand-navy"
                                />
                            </FormField>
                        </DrawerSection>
                    </DrawerBody>

                    <DrawerFooter>
                        <DrawerFooterActions cols={2} stackOnMobile>
                            <Button type="button" variant="neutral" size="drawer" onClick={() => setShowSetorTunai(false)}>Batal</Button>
                            <Button type="submit" size="drawer" disabled={setorForm.processing}>
                                {setorForm.processing ? 'Menyimpan...' : 'Catat Setoran Tunai'}
                            </Button>
                        </DrawerFooterActions>
                    </DrawerFooter>
                </form>
            </Drawer>

            {/* DRAWER STORNO TRANSAKSI */}
            <Drawer
                show={Boolean(trxBatal)}
                title="Batalkan Transaksi (Storno)"
                description={trxBatal ? `${trxBatal.no_bukti} • ${formatRupiah(trxBatal.nominal)}` : ''}
                onClose={() => { setTrxBatal(null); batalForm.reset(); }}
                layout="split"
            >
                {trxBatal && (
                    <form onSubmit={submitBatal} className="flex-1 min-h-0 flex flex-col">
                        <DrawerBody>
                            <DrawerSection title="Rincian Transaksi yang Dibatalkan" icon={Wallet} tone="rose">
                                <DrawerKeyValue
                                    rows={[
                                        { label: 'No. Bukti', value: trxBatal.no_bukti, mono: true },
                                        { label: 'Jenis', value: jenisLabel[trxBatal.jenis] ?? trxBatal.jenis },
                                        { label: 'Tanggal', value: trxBatal.tanggal },
                                        { label: 'Nominal', value: formatRupiah(trxBatal.nominal), bold: true },
                                    ]}
                                />
                            </DrawerSection>
                            <DrawerSection title="Alasan Pembatalan" icon={RotateCcw}>
                                <FormField label="Alasan Storno" error={batalForm.errors.alasan} required>
                                    <TextField
                                        as="textarea"
                                        rows={3}
                                        value={batalForm.data.alasan}
                                        onChange={(e) => batalForm.setData('alasan', e.target.value)}
                                        placeholder="Jelaskan alasan pembatalan (min. 3 karakter)"
                                        required
                                    />
                                </FormField>
                            </DrawerSection>
                        </DrawerBody>
                        <DrawerFooter>
                            <DrawerFooterActions cols={2} stackOnMobile>
                                <Button type="button" variant="neutral" size="drawer" onClick={() => setTrxBatal(null)}>Tutup</Button>
                                <Button type="submit" variant="danger" size="drawer" disabled={batalForm.processing}>
                                    {batalForm.processing ? 'Memproses...' : 'Konfirmasi Batalkan'}
                                </Button>
                            </DrawerFooterActions>
                        </DrawerFooter>
                    </form>
                )}
            </Drawer>
        </AppLayout>
    );
}
