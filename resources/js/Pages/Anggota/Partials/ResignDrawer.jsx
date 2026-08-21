import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { AlertTriangle, Wallet, HandCoins, TrendingDown, CheckCircle2, Loader2 } from 'lucide-react';
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

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 flex gap-3">
                <AlertTriangle className="text-rose-600 shrink-0 mt-0.5" size={20} />
                <div className="text-sm text-rose-800">
                    <p className="font-semibold">Perhatian: Tindakan ini tidak dapat dibatalkan.</p>
                    <p className="mt-1">
                        Pinjaman aktif akan dilunasi otomatis menggunakan simpanan pokok & wajib anggota.
                        Sisa simpanan (jika ada) akan dikembalikan sebagai jurnal keluar kas.
                        Dana sosial bersifat <strong>hangus</strong> dan tidak dikembalikan.
                    </p>
                </div>
            </div>

            {loading && (
                <div className="flex items-center justify-center py-10 gap-2 text-slate-500">
                    <Loader2 className="animate-spin" size={20} />
                    <span>Memuat ringkasan simpanan & pinjaman...</span>
                </div>
            )}

            {fetchError && (
                <div className="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {fetchError}
                </div>
            )}

            {!loading && !fetchError && ringkasan && (
                <>
                    <Card title="Ringkasan Simpanan">
                        <div className="space-y-2 text-sm">
                            <Row label="Simpanan Pokok" value={formatRupiah(ringkasan.simpanan.pokok)} />
                            <Row label="Simpanan Wajib" value={formatRupiah(ringkasan.simpanan.wajib)} />
                            <Row label="Dana Sosial (hangus)" value={<span className="text-rose-600">{formatRupiah(ringkasan.simpanan.dana_sosial)}</span>} />
                            <div className="border-t border-slate-100 pt-2 mt-2">
                                <Row label="Total Pokok + Wajib" value={<strong>{formatRupiah(ringkasan.simpanan.total_pokok_wajib)}</strong>} />
                            </div>
                        </div>
                    </Card>

                    <Card title="Ringkasan Pinjaman Aktif">
                        {ringkasan.pinjaman.jumlah_pinjaman_aktif === 0 ? (
                            <div className="flex items-center gap-2 text-sm text-slate-600">
                                <CheckCircle2 size={16} className="text-brand-green" />
                                Tidak ada pinjaman aktif.
                            </div>
                        ) : (
                            <div className="space-y-2 text-sm">
                                <Row label="Jumlah Pinjaman Aktif" value={`${ringkasan.pinjaman.jumlah_pinjaman_aktif} pinjaman`} />
                                {ringkasan.pinjaman.detail.map((p) => (
                                    <div key={p.id} className="rounded-lg bg-slate-50 px-3 py-2 text-xs">
                                        <div className="flex justify-between">
                                            <span className="text-slate-500">Pinjaman #{p.id}</span>
                                            <span className="font-semibold">{formatRupiah(p.sisa_tagihan)}</span>
                                        </div>
                                        <div className="text-slate-400 mt-0.5">
                                            Sisa {p.sisa_cicilan} cicilan • Pokok awal {formatRupiah(p.nominal_awal)}
                                        </div>
                                    </div>
                                ))}
                                <div className="border-t border-slate-100 pt-2 mt-2">
                                    <Row label="Total Tagihan" value={<strong className="text-rose-600">{formatRupiah(ringkasan.pinjaman.sisa_tagihan)}</strong>} />
                                </div>
                            </div>
                        )}
                    </Card>

                    <Card title="Estimasi Pengembalian">
                        {ringkasan.estimasi_pengembalian.cukup_untuk_pelunasan ? (
                            <div className="space-y-2 text-sm">
                                <div className="flex items-center gap-2 text-brand-green-dark">
                                    <CheckCircle2 size={16} />
                                    <span className="font-semibold">Simpanan cukup untuk melunasi pinjaman.</span>
                                </div>
                                <Row label="Alokasi dari Pokok (pelunasan)" value={formatRupiah(ringkasan.estimasi_pengembalian.alokasi_dari_pokok)} />
                                <Row label="Kembali ke Pokok" value={<strong className="text-brand-green-dark">{formatRupiah(ringkasan.estimasi_pengembalian.kembali_pokok)}</strong>} />
                                <Row label="Kembali ke Wajib" value={<strong className="text-brand-green-dark">{formatRupiah(ringkasan.estimasi_pengembalian.kembali_wajib)}</strong>} />
                                <div className="border-t border-slate-100 pt-2 mt-2 flex items-center gap-2">
                                    <HandCoins size={16} className="text-brand-green-dark" />
                                    <strong className="text-base">
                                        Total Dikembalikan: {formatRupiah(ringkasan.estimasi_pengembalian.total_dikembalikan)}
                                    </strong>
                                </div>
                                <div className="text-xs text-slate-500 mt-1">
                                    Dana sosial hangus: {formatRupiah(ringkasan.estimasi_pengembalian.dana_sosial_hangus)}
                                </div>
                            </div>
                        ) : (
                            <div className="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">
                                <strong>Tidak bisa resign.</strong> Simpanan pokok+wajib tidak cukup melunasi pinjaman.
                                Selisih: {formatRupiah(ringkasan.pinjaman.sisa_tagihan - ringkasan.simpanan.total_pokok_wajib)}.
                                Minta anggota melunasi sebagian via menu Konfirmasi Angsuran terlebih dahulu.
                            </div>
                        )}
                    </Card>

                    {ringkasan.estimasi_pengembalian.cukup_untuk_pelunasan && (
                        <>
                            <FormField label="Tanggal Resign" error={errors.tanggal_resign} required>
                                <TextField
                                    type="date"
                                    size="sm"
                                    value={data.tanggal_resign}
                                    onChange={(e) => setData('tanggal_resign', e.target.value)}
                                    max={new Date().toISOString().slice(0, 10)}
                                    required
                                />
                            </FormField>

                            <FormField label="Alasan Resign" error={errors.alasan_resign} required>
                                <textarea
                                    value={data.alasan_resign}
                                    onChange={(e) => setData('alasan_resign', e.target.value)}
                                    rows={3}
                                    className="block w-full rounded-lg border-slate-200 text-sm focus:border-brand-green focus:ring-brand-green"
                                    placeholder="Contoh: Mengundurkan diri, habis masa kontrak, pindah cabang, dll."
                                    required
                                />
                            </FormField>

                            <label className="flex items-start gap-2.5 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.konfirmasi_pelunasan}
                                    onChange={(e) => setData('konfirmasi_pelunasan', e.target.checked)}
                                    className="mt-1 w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                />
                                <span className="text-sm text-slate-700">
                                    Saya memahami bahwa <strong>{formatRupiah(ringkasan.pinjaman.sisa_tagihan)}</strong> akan
                                    dilunasi otomatis dari simpanan dan <strong>{formatRupiah(ringkasan.estimasi_pengembalian.total_dikembalikan)}</strong>{' '}
                                    akan dikembalikan ke anggota. Akun login akan dinonaktifkan.
                                </span>
                            </label>
                            {errors.konfirmasi_pelunasan && (
                                <p className="text-sm text-red-600">{errors.konfirmasi_pelunasan}</p>
                            )}

                            <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                                <Button type="button" variant="outline" onClick={onClose} disabled={processing}>
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    variant="danger"
                                    disabled={processing || !data.konfirmasi_pelunasan || !data.alasan_resign}
                                >
                                    {processing ? 'Memproses...' : 'Konfirmasi Resign'}
                                </Button>
                            </div>
                        </>
                    )}
                </>
            )}
        </form>
    );
}

function Card({ title, children }) {
    return (
        <div className="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
            <h3 className="text-sm font-bold text-slate-700 mb-3">{title}</h3>
            {children}
        </div>
    );
}

function Row({ label, value }) {
    return (
        <div className="flex justify-between items-center">
            <span className="text-slate-500">{label}</span>
            <span className="text-slate-800">{value}</span>
        </div>
    );
}
