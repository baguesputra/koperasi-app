import { router } from '@inertiajs/react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';

/** Tab pembayaran: hanya aktif setelah Final. */
export default function BayarTab({ periode, ringkasan, akun, setAkun, tanggal, setTanggal }) {
    const hariIni = new Date().toISOString().slice(0, 10);

    if (!periode.is_final) {
        return (
            <Card>
                <p className="text-base font-bold text-slate-800">Pembayaran terkunci</p>
                <p className="text-sm text-slate-500 mt-0.5">
                    Status saat ini: <strong>{periode.status}</strong>. Pembayaran dibuka setelah
                    periode Diajukan, dibahas meeting, lalu difinalisasi.
                </p>
            </Card>
        );
    }

    function bayarSemua(e) {
        e.preventDefault();
        if (!confirm(`Bayar semua SHU belum dibayar via ${akun === 'bank' ? 'Bank' : 'Kas Kecil'}?`)) return;
        router.post(
            route('shu.bayar-semua', periode.id),
            { akun, tanggal: tanggal || hariIni },
            withIdempotencyKey({ preserveScroll: true })
        );
    }

    return (
        <Card className="border-brand-green/30">
            <div className="flex flex-wrap items-center gap-3 mb-4">
                <p className="text-base font-bold text-slate-800 flex-1 min-w-[160px]">
                    Sudah Diserahkan {formatRupiah(ringkasan.terbayar)} dari {formatRupiah(ringkasan.total_bersih)}
                </p>
                <span className="text-xs font-bold px-2.5 py-1 rounded-full bg-brand-green-light text-brand-green-dark">
                    {ringkasan.sudah_bayar} / {ringkasan.anggota} anggota
                </span>
            </div>
            <form onSubmit={bayarSemua}>
                <div className="flex flex-wrap items-end gap-3">
                    <FormField label="Sumber dana">
                        <Select value={akun} onChange={(e) => setAkun(e.target.value)}>
                            <option value="bank">Bank (transfer)</option>
                            <option value="kas_kecil">Kas Kecil (tunai)</option>
                        </Select>
                    </FormField>
                    <FormField label="Tanggal bayar">
                        <TextField
                            type="date"
                            value={tanggal || hariIni}
                            onChange={(e) => setTanggal(e.target.value)}
                        />
                    </FormField>
                    <Button type="submit" size="sm">
                        Bayar Semua Belum Dibayar
                    </Button>
                </div>
                <p className="text-xs text-slate-400 mt-2">
                    Sumber dana & tanggal yang sama dipakai tombol Bayar per baris di tab Distribusi.
                </p>
            </form>
        </Card>
    );
}
