import { useForm } from '@inertiajs/react';
import Button from '@/Components/ui/Button';
import FormField from '@/Components/ui/FormField';
import TextField from '@/Components/ui/TextField';

export default function TabBunga({ bungaSaatIni }) {
    const { data, setData, post, processing, errors, reset } = useForm({ persentase: '' });

    function submit(e) {
        e.preventDefault();
        post(route('pengaturan.bunga.update'), { preserveScroll: true, onSuccess: () => reset() });
    }

    return (
        <div>
            <p className="text-sm text-slate-400 mb-4">
                Bunga dihitung menurun dari sisa pokok tiap bulan. Perubahan hanya berlaku untuk pengajuan baru.
            </p>

            <div className="flex items-center gap-3 mb-5 p-4 bg-gradient-to-r from-brand-navy/5 to-brand-green-light/60 rounded-2xl w-fit border border-slate-100">
                <span className="text-sm text-slate-500">Saat ini berlaku:</span>
                <span className="text-lg font-bold text-brand-navy tabular-nums">{bungaSaatIni?.persentase}% / bulan</span>
            </div>

            <form onSubmit={submit} className="flex items-end gap-3">
                <div className="w-48">
                    <FormField label="Persentase Baru (%)" error={errors.persentase} required>
                        <TextField
                            size="sm"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.persentase}
                            onChange={(e) => setData('persentase', e.target.value)}
                            placeholder="Contoh: 1.5"
                            required
                            className="tabular-nums"
                        />
                    </FormField>
                </div>
                <div className="pb-4">
                    <Button type="submit" size="sm" disabled={processing} className="rounded-full shadow-md shadow-brand-green/25">
                        {processing ? 'Menyimpan...' : 'Simpan'}
                    </Button>
                </div>
            </form>
        </div>
    );
}
