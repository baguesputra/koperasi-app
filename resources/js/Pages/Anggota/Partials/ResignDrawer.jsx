import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { AlertTriangle, ArrowLeft, HandCoins, CheckCircle2, Loader2, PiggyBank, CreditCard, CalendarDays, Landmark } from 'lucide-react';
import Button from '@/Components/ui/Button';
import {
    DrawerBody,
    DrawerFooter,
    DrawerFooterActions,
    DrawerKeyValue,
    DrawerNoteCallout,
    DrawerSection,
} from '@/Components/ui/DrawerSection';
import FormField from '@/Components/ui/FormField';
import TextField from '@/Components/ui/TextField';
import axios from 'axios';

function formatRupiah(n) {
    return 'Rp ' + Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

export default function ResignDrawer({ anggota, onClose }) {
    const [ringkasan, setRingkasan] = useState(null);
    const [loading, setLoading] = useState(true);
    const [fetchError, setFetchError] = useState(null);

    const { data, setData, post, processing, errors } = useForm({
        alasan_resign: '',
        tanggal_resign: new Date().toISOString().slice(0, 10),
        rekening_bank: '',
        rekening_nomor: '',
        rekening_atas_nama: '',
    });

    useEffect(() => {
        setLoading(true);
        axios.get(route('anggota.ringkasan-resign', anggota.id))
            .then((res) => {
                const payloadRingkasan = res.data.ringkasan;
                const rek = payloadRingkasan?.rekening_tujuan;
                setRingkasan(payloadRingkasan);
                setData((prev) => ({
                    ...prev,
                    rekening_bank: rek?.nama_bank ?? '',
                    rekening_nomor: rek?.no_rekening ?? '',
                    rekening_atas_nama: rek?.atas_nama ?? anggota.nama ?? '',
                }));
                setFetchError(null);
            })
            .catch((err) => {
                setFetchError(err.response?.data?.message ?? 'Gagal memuat ringkasan resign.');
            })
            .finally(() => setLoading(false));
    }, [anggota.id]);

    function submit(e) {
        e.preventDefault();
        post(route('anggota.resign', anggota.id), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    }

    const cukup = ringkasan?.estimasi_pengembalian?.cukup_untuk_pelunasan ?? true;
    const totalDikembalikan = ringkasan?.estimasi_pengembalian?.total_dikembalikan ?? 0;
    const daftarRekening = ringkasan?.daftar_rekening ?? [];
    const tanggalResign = data.tanggal_resign || new Date().toISOString().slice(0, 10);
    const jatuhTempo = (() => {
        try {
            const d = new Date(`${tanggalResign}T00:00:00`);
            return new Date(d.getFullYear(), d.getMonth() + 1, 0).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        } catch {
            return '-';
        }
    })();

    return (
        <form onSubmit={submit} className="flex-1 min-h-0 flex flex-col">
            <DrawerBody>
                <div className="rounded-xl bg-rose-50/60 border border-rose-200/80 px-3.5 py-3 flex gap-2.5">
                    <AlertTriangle className="text-rose-600 shrink-0 mt-0.5" size={18} aria-hidden="true" />
                    <div className="text-xs sm:text-sm text-rose-900">
                        <p className="font-bold">Tindakan ini tidak dapat dibatalkan.</p>
                        <p className="mt-0.5 text-rose-700">
                            Pinjaman dilunasi otomatis dari simpanan pokok &amp; wajib. Dana sosial hangus.
                        </p>
                    </div>
                </div>

                {loading && (
                    <div className="rounded-xl bg-white border border-slate-200/80 flex items-center justify-center py-10 gap-2 text-sm text-slate-500 shadow-[0_1px_3px_rgba(15,30,54,0.04)]">
                        <Loader2 className="animate-spin text-brand-navy" size={18} />
                        <span>Memuat ringkasan...</span>
                    </div>
                )}

                {fetchError && (
                    <div className="rounded-xl bg-rose-50/50 border border-rose-200/80 px-3.5 py-3 text-sm text-rose-700">
                        {fetchError}
                    </div>
                )}

                {!loading && !fetchError && ringkasan && (
                    <>
                        <RingkasanSimpanan simpanan={ringkasan.simpanan} tabungan={ringkasan.tabungan} />
                        <RingkasanPinjaman pinjaman={ringkasan.pinjaman} />
                        <EstimasiPengembalian
                            estimasi={ringkasan.estimasi_pengembalian}
                            sisaTagihan={ringkasan.pinjaman.sisa_tagihan}
                            totalPokokWajib={ringkasan.simpanan.total_pokok_wajib}
                            jatuhTempo={jatuhTempo}
                        />

                        <DrawerSection title="Detail Pengunduran Diri" icon={CalendarDays}>
                            <FormField
                                label="Tanggal Resign"
                                error={errors.tanggal_resign}
                                hint="Boleh sampai akhir bulan depan. Cicilan akhir jatuh tempo akhir bulan tanggal ini."
                                required
                            >
                                <TextField
                                    type="date"
                                    size="sm"
                                    value={data.tanggal_resign}
                                    onChange={(e) => setData('tanggal_resign', e.target.value)}
                                    max={(() => { const d = new Date(); return new Date(d.getFullYear(), d.getMonth() + 2, 0).toISOString().slice(0, 10); })()}
                                    required
                                />
                            </FormField>

                            <FormField label="Alasan Resign" error={errors.alasan_resign} required>
                                <TextField
                                    as="textarea"
                                    value={data.alasan_resign}
                                    onChange={(e) => setData('alasan_resign', e.target.value)}
                                    rows={2}
                                    placeholder="Contoh: Mengundurkan diri, habis kontrak, pindah cabang"
                                    required
                                />
                            </FormField>
                        </DrawerSection>

                        {totalDikembalikan > 0 && (
                            <DrawerSection
                                title="Rekening Tujuan Pengembalian"
                                description="Otomatis terisi dari rekening anggota dan diteruskan ke antrean Pencairan Dana Bendahara."
                                icon={Landmark}
                                tone="emerald"
                            >
                                {daftarRekening.length > 1 && (
                                    <div className="flex flex-wrap gap-1.5 mb-2">
                                        {daftarRekening.map((r) => {
                                            const terpilih =
                                                data.rekening_bank === r.nama_bank
                                                && data.rekening_nomor === r.no_rekening;
                                            return (
                                                <button
                                                    key={r.id}
                                                    type="button"
                                                    onClick={() => setData((prev) => ({
                                                        ...prev,
                                                        rekening_bank: r.nama_bank ?? '',
                                                        rekening_nomor: r.no_rekening ?? '',
                                                        rekening_atas_nama: r.atas_nama ?? anggota.nama ?? '',
                                                    }))}
                                                    className={`px-2.5 py-1 rounded-full text-xs font-semibold border transition-colors ${
                                                        terpilih
                                                            ? 'bg-brand-navy text-white border-brand-navy'
                                                            : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'
                                                    }`}
                                                >
                                                    {r.nama_bank} • {r.no_rekening}
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <FormField label="Nama Bank" error={errors.rekening_bank}>
                                        <TextField
                                            size="sm"
                                            value={data.rekening_bank}
                                            onChange={(e) => setData('rekening_bank', e.target.value)}
                                            placeholder="Contoh: BCA / Mandiri / BRI"
                                        />
                                    </FormField>
                                    <FormField label="No. Rekening" error={errors.rekening_nomor}>
                                        <TextField
                                            size="sm"
                                            value={data.rekening_nomor}
                                            onChange={(e) => setData('rekening_nomor', e.target.value)}
                                            placeholder="Kosongkan jika cair tunai"
                                        />
                                    </FormField>
                                </div>

                                <FormField label="Atas Nama" error={errors.rekening_atas_nama}>
                                    <TextField
                                        size="sm"
                                        value={data.rekening_atas_nama}
                                        onChange={(e) => setData('rekening_atas_nama', e.target.value)}
                                        placeholder="Nama pemilik rekening"
                                    />
                                </FormField>

                                <DrawerNoteCallout
                                    label="Alur Pencairan 2 Tahap"
                                    note={`Pengembalian sebesar ${formatRupiah(totalDikembalikan)} belum memotong kas. Bendahara akan mentransfer, mengunggah bukti transfer, dan mengirim 1x notifikasi WA + Slip PDF via Pusat Persetujuan.`}
                                    tone="emerald"
                                />
                            </DrawerSection>
                        )}
                    </>
                )}
            </DrawerBody>

            <DrawerFooter>
                <DrawerFooterActions cols={!loading && !fetchError && ringkasan ? 2 : 1} stackOnMobile>
                    <Button type="button" variant="neutral" size="drawer" onClick={onClose} disabled={processing}>
                        <ArrowLeft size={16} />
                        Batal
                    </Button>
                    {!loading && !fetchError && ringkasan && (
                        <Button
                            type="submit"
                            variant="danger"
                            size="drawer"
                            disabled={processing || !data.alasan_resign.trim()}
                        >
                            {processing ? 'Memproses...' : cukup ? 'Konfirmasi Resign' : 'Resign + Jadwalkan Cicilan Akhir'}
                        </Button>
                    )}
                </DrawerFooterActions>
            </DrawerFooter>
        </form>
    );
}

function RingkasanSimpanan({ simpanan, tabungan }) {
    const rows = [
        { label: 'Simpanan Pokok', value: formatRupiah(simpanan.pokok), privacy: true },
        { label: 'Simpanan Wajib', value: formatRupiah(simpanan.wajib), privacy: true },
        { label: 'Dana Sosial (hangus)', value: formatRupiah(simpanan.dana_sosial), tone: 'rose', privacy: true },
        { label: 'Total Pokok + Wajib', value: formatRupiah(simpanan.total_pokok_wajib), emphasize: true, privacy: true },
    ];
    if (tabungan?.ada) {
        rows.push(
            { label: `Tabungan (${tabungan.no_rekening})`, value: formatRupiah(tabungan.saldo), privacy: true },
            { label: 'Bunga Tabungan Pro-Rata', value: formatRupiah(tabungan.bunga_berjalan), privacy: true },
        );
    }

    return (
        <DrawerSection title="Simpanan" icon={PiggyBank}>
            <DrawerKeyValue rows={rows} />
        </DrawerSection>
    );
}

function RingkasanPinjaman({ pinjaman }) {
    if (pinjaman.jumlah_pinjaman_aktif === 0) {
        return (
            <DrawerSection title="Pinjaman Aktif" icon={CreditCard}>
                <div className="flex items-center gap-2 text-sm text-slate-600">
                    <CheckCircle2 size={16} className="text-brand-green shrink-0" />
                    <span>Tidak ada pinjaman aktif.</span>
                </div>
            </DrawerSection>
        );
    }

    return (
        <DrawerSection title={`Pinjaman Aktif (${pinjaman.jumlah_pinjaman_aktif})`} icon={CreditCard} padding="none">
            <ul className="divide-y divide-slate-100">
                {pinjaman.detail.map((p) => (
                    <li key={p.id} className="px-4 py-2.5 flex items-center justify-between gap-3 text-xs sm:text-sm">
                        <div className="min-w-0">
                            <p className="font-semibold text-slate-800">Pinjaman #{p.id}</p>
                            <p className="text-xs text-slate-500 mt-0.5">
                                Sisa {p.sisa_cicilan} cicilan • Pokok <span className="privacy-blur">{formatRupiah(p.nominal_awal)}</span>
                            </p>
                        </div>
                        <span className="font-bold text-slate-800 tabular-nums privacy-blur shrink-0">
                            {formatRupiah(p.sisa_tagihan)}
                        </span>
                    </li>
                ))}
            </ul>
            <div className="bg-[#F8FAFC] border-t border-slate-100 px-4 py-2.5 flex items-center justify-between text-xs sm:text-sm">
                <span className="font-semibold text-slate-700">Total Tagihan</span>
                <strong className="text-rose-600 font-bold tabular-nums privacy-blur">{formatRupiah(pinjaman.sisa_tagihan)}</strong>
            </div>
        </DrawerSection>
    );
}

function EstimasiPengembalian({ estimasi, sisaTagihan, totalPokokWajib, jatuhTempo }) {
    if (!estimasi.cukup_untuk_pelunasan) {
        return (
            <DrawerSection
                title="Simpanan Tidak Cukup — Tetap Bisa Resign"
                description="Status menjadi menunggu sampai cicilan akhir lunas via Konfirmasi Angsuran."
                icon={AlertTriangle}
                tone="amber"
            >
                <DrawerKeyValue
                    rows={[
                        { label: 'Simpanan terpakai pelunasan', value: formatRupiah(totalPokokWajib), privacy: true },
                        {
                            label: 'Sisa cicilan akhir',
                            value: formatRupiah(estimasi.shortfall ?? (sisaTagihan - totalPokokWajib)),
                            tone: 'amber',
                            emphasize: true,
                            privacy: true,
                        },
                        { label: 'Jatuh tempo akhir', value: jatuhTempo, bold: true },
                        { label: 'Dikembalikan sekarang', value: formatRupiah(0), privacy: true },
                    ]}
                />
            </DrawerSection>
        );
    }

    return (
        <DrawerSection
            title="Estimasi Pengembalian"
            description="Simpanan cukup untuk pelunasan pinjaman."
            icon={HandCoins}
            tone="emerald"
        >
            <DrawerKeyValue
                rows={[
                    { label: 'Alokasi pelunasan', value: formatRupiah(estimasi.alokasi_dari_pokok), privacy: true },
                    { label: 'Kembali pokok', value: formatRupiah(estimasi.kembali_pokok), privacy: true },
                    { label: 'Kembali wajib', value: formatRupiah(estimasi.kembali_wajib), privacy: true },
                    ...((estimasi.kembali_tabungan ?? 0) > 0
                        ? [{ label: 'Kembali tabungan (+ bunga)', value: formatRupiah(estimasi.kembali_tabungan), privacy: true }]
                        : []),
                    { label: 'Dana sosial hangus', value: formatRupiah(estimasi.dana_sosial_hangus), tone: 'rose', privacy: true },
                    { label: 'Total kembali', value: formatRupiah(estimasi.total_dikembalikan), tone: 'emerald', emphasize: true, privacy: true },
                ]}
            />
        </DrawerSection>
    );
}
