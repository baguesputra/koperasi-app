import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { AlertTriangle, HandCoins, CheckCircle2, Loader2 } from 'lucide-react';
import Button from '@/Components/ui/Button';
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
        konfirmasi_pelunasan: false,
    });

    useEffect(() => {
        setLoading(true);
        axios.get(route('anggota.ringkasan-resign', anggota.id))
            .then((res) => {
                setRingkasan(res.data.ringkasan);
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
    const shortfall = ringkasan?.estimasi_pengembalian?.shortfall ?? 0;
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
        <form onSubmit={submit} className="space-y-3">
            <div className="rounded-xl bg-rose-50 border border-rose-200 px-3 py-2.5 flex gap-2.5">
                <AlertTriangle className="text-rose-600 shrink-0 mt-0.5" size={18} />
                <div className="text-sm text-rose-800">
                    <p className="font-semibold">Tindakan ini tidak dapat dibatalkan.</p>
                    <p className="mt-0.5 text-rose-700">
                        Pinjaman dilunasi otomatis dari simpanan pokok & wajib. Dana sosial hangus.
                    </p>
                </div>
            </div>

            {loading && (
                <div className="flex items-center justify-center py-8 gap-2 text-sm text-slate-500">
                    <Loader2 className="animate-spin" size={18} />
                    Memuat ringkasan...
                </div>
            )}

            {fetchError && (
                <div className="rounded-xl bg-red-50 border border-red-200 px-3 py-2.5 text-sm text-red-700">
                    {fetchError}
                </div>
            )}

            {!loading && !fetchError && ringkasan && (
                <>
                    <RingkasanSimpanan simpanan={ringkasan.simpanan} />
                    <RingkasanPinjaman pinjaman={ringkasan.pinjaman} />
                    <EstimasiPengembalian
                        estimasi={ringkasan.estimasi_pengembalian}
                        sisaTagihan={ringkasan.pinjaman.sisa_tagihan}
                        totalPokokWajib={ringkasan.simpanan.total_pokok_wajib}
                        jatuhTempo={jatuhTempo}
                    />

                    <FormField label="Tanggal Resign" error={errors.tanggal_resign} required>
                        <TextField
                            type="date"
                            size="sm"
                            value={data.tanggal_resign}
                            onChange={(e) => setData('tanggal_resign', e.target.value)}
                            max={(() => { const d = new Date(); return new Date(d.getFullYear(), d.getMonth() + 2, 0).toISOString().slice(0, 10); })()}
                            required
                        />
                        <p className="text-xs text-slate-400 mt-1">Boleh sampai akhir bulan depan. Cicilan akhir jatuh tempo akhir bulan tanggal ini.</p>
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

                    {cukup ? (
                        <label className="flex items-start gap-2.5 cursor-pointer rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5">
                            <input
                                type="checkbox"
                                checked={data.konfirmasi_pelunasan}
                                onChange={(e) => setData('konfirmasi_pelunasan', e.target.checked)}
                                className="mt-1 w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                            />
                            <span className="text-sm text-slate-700">
                                Saya memahami <strong>{formatRupiah(ringkasan.pinjaman.sisa_tagihan)}</strong> dilunasi
                                otomatis dan <strong>{formatRupiah(ringkasan.estimasi_pengembalian.total_dikembalikan)}</strong> dikembalikan.
                                Akun login dinonaktifkan.
                            </span>
                        </label>
                    ) : (
                        <label className="flex items-start gap-2.5 cursor-pointer rounded-xl bg-amber-50 border border-amber-200 px-3 py-2.5">
                            <input
                                type="checkbox"
                                checked={data.konfirmasi_pelunasan}
                                onChange={(e) => setData('konfirmasi_pelunasan', e.target.checked)}
                                className="mt-1 w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                            />
                            <span className="text-sm text-amber-800">
                                Simpanan tidak cukup. Seluruh simpanan dialokasikan ke angsuran terawal,
                                sisa <strong>{formatRupiah(shortfall)}</strong> menjadi cicilan akhir jatuh tempo{' '}
                                <strong>{jatuhTempo}</strong>. Status menjadi menunggu sampai lunas via Konfirmasi Angsuran.
                            </span>
                        </label>
                    )}
                    {errors.konfirmasi_pelunasan && (
                        <p className="text-xs text-red-600">{errors.konfirmasi_pelunasan}</p>
                    )}

                    <div className="flex justify-end gap-2 pt-3 border-t border-slate-100 sticky bottom-0 bg-white pb-1">
                        <Button type="button" variant="outline" size="sm" onClick={onClose} disabled={processing}>
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            variant="danger"
                            size="sm"
                            disabled={processing || !data.konfirmasi_pelunasan || !data.alasan_resign}
                        >
                            {processing ? 'Memproses...' : cukup ? 'Konfirmasi Resign' : 'Resign + Jadwalkan Cicilan Akhir'}
                        </Button>
                    </div>
                </>
            )}
        </form>
    );
}

function Seksi({ title, children }) {
    return (
        <section className="rounded-xl border border-slate-100 bg-slate-50/50 p-3">
            <h3 className="text-sm font-bold text-slate-700 mb-2">{title}</h3>
            {children}
        </section>
    );
}

function Baris({ label, value }) {
    return (
        <div className="flex justify-between items-center gap-3 text-sm">
            <span className="text-slate-500">{label}</span>
            <span className="text-slate-800 text-right">{value}</span>
        </div>
    );
}

function RingkasanSimpanan({ simpanan }) {
    return (
        <Seksi title="Simpanan">
            <div className="space-y-1.5">
                <Baris label="Pokok" value={formatRupiah(simpanan.pokok)} />
                <Baris label="Wajib" value={formatRupiah(simpanan.wajib)} />
                <Baris label="Dana sosial (hangus)" value={<span className="text-rose-600">{formatRupiah(simpanan.dana_sosial)}</span>} />
                <div className="border-t border-slate-200/70 pt-1.5">
                    <Baris label="Total pokok + wajib" value={<strong>{formatRupiah(simpanan.total_pokok_wajib)}</strong>} />
                </div>
            </div>
        </Seksi>
    );
}

function RingkasanPinjaman({ pinjaman }) {
    if (pinjaman.jumlah_pinjaman_aktif === 0) {
        return (
            <Seksi title="Pinjaman Aktif">
                <div className="flex items-center gap-2 text-sm text-slate-600">
                    <CheckCircle2 size={16} className="text-brand-green" />
                    Tidak ada pinjaman aktif.
                </div>
            </Seksi>
        );
    }
    return (
        <Seksi title={`Pinjaman Aktif (${pinjaman.jumlah_pinjaman_aktif})`}>
            <div className="space-y-1.5">
                {pinjaman.detail.map((p) => (
                    <div key={p.id} className="rounded-lg bg-white border border-slate-100 px-2.5 py-1.5 text-xs">
                        <div className="flex justify-between gap-2">
                            <span className="text-slate-500">Pinjaman #{p.id}</span>
                            <span className="font-semibold text-slate-800">{formatRupiah(p.sisa_tagihan)}</span>
                        </div>
                        <div className="text-slate-400 mt-0.5">
                            Sisa {p.sisa_cicilan} cicilan • Pokok {formatRupiah(p.nominal_awal)}
                        </div>
                    </div>
                ))}
                <div className="border-t border-slate-200/70 pt-1.5">
                    <Baris label="Total tagihan" value={<strong className="text-rose-600">{formatRupiah(pinjaman.sisa_tagihan)}</strong>} />
                </div>
            </div>
        </Seksi>
    );
}

function EstimasiPengembalian({ estimasi, sisaTagihan, totalPokokWajib, jatuhTempo }) {
    if (!estimasi.cukup_untuk_pelunasan) {
        return (
            <div className="rounded-xl bg-amber-50 border border-amber-200 px-3 py-2.5 text-sm text-amber-800">
                <strong>Simpanan tidak cukup — tetap bisa resign.</strong>
                <div className="mt-1.5 space-y-1">
                    <div className="flex justify-between gap-3"><span>Simpanan terpakai pelunasan</span><strong>{formatRupiah(totalPokokWajib)}</strong></div>
                    <div className="flex justify-between gap-3"><span>Sisa cicilan akhir</span><strong>{formatRupiah(estimasi.shortfall ?? (sisaTagihan - totalPokokWajib))}</strong></div>
                    <div className="flex justify-between gap-3"><span>Jatuh tempo akhir</span><strong>{jatuhTempo}</strong></div>
                    <div className="flex justify-between gap-3"><span>Dikembalikan sekarang</span><strong>{formatRupiah(0)}</strong></div>
                </div>
                <p className="mt-1.5 text-xs">Status menjadi menunggu sampai cicilan akhir lunas via Konfirmasi Angsuran.</p>
            </div>
        );
    }
    return (
        <Seksi title="Estimasi Pengembalian">
            <div className="space-y-1.5">
                <div className="flex items-center gap-1.5 text-sm text-brand-green-dark">
                    <CheckCircle2 size={15} />
                    <span className="font-semibold">Simpanan cukup untuk pelunasan.</span>
                </div>
                <Baris label="Alokasi pelunasan" value={formatRupiah(estimasi.alokasi_dari_pokok)} />
                <Baris label="Kembali pokok" value={formatRupiah(estimasi.kembali_pokok)} />
                <Baris label="Kembali wajib" value={formatRupiah(estimasi.kembali_wajib)} />
                <div className="border-t border-slate-200/70 pt-1.5 flex items-center gap-1.5 text-sm">
                    <HandCoins size={15} className="text-brand-green-dark" />
                    <span>Total kembali: <strong>{formatRupiah(estimasi.total_dikembalikan)}</strong></span>
                </div>
                <p className="text-xs text-slate-400">Dana sosial hangus: {formatRupiah(estimasi.dana_sosial_hangus)}</p>
            </div>
        </Seksi>
    );
}
