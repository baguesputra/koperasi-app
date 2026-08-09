import { useForm } from '@inertiajs/react';
import Button from '@/Components/ui/Button';
import TextField from '@/Components/ui/TextField';

export default function TabBunga({ bungaSaatIni }) {
    const { data, setData, post, processing, errors, reset } = useForm({ persentase: '' });

    function submit(e) {
        e.preventDefault();
        post(route('pengaturan.bunga.update'), { onSuccess: () => reset() });
    }

    return (
        <div>
            <p className="text-sm text-slate-400 mb-4">
                Bunga dihitung menurun dari sisa pokok tiap bulan. Perubahan hanya berlaku untuk pengajuan baru.
            </p>

            <div className="flex items-center gap-3 mb-5 p-4 bg-slate-50 rounded-xl w-fit">
                <span className="text-sm text-slate-500">Saat ini berlaku:</span>
                <span className="text-lg font-bold text-brand-navy">{bungaSaatIni?.persentase}% / bulan</span>
            </div>

            <form onSubmit={submit} className="flex items-end gap-3">
                <div className="w-48">
                    <label className="block text-sm font-semibold text-slate-600 mb-1.5">Persentase Baru (%)</label>
                    <TextField
                        type="number"
                        step="0.01"
                        value={data.persentase}
                        onChange={(e) => setData('persentase', e.target.value)}
                        placeholder="Contoh: 1.5"
                    />
                    {errors.persentase && <p className="text-sm text-red-600 mt-1">{errors.persentase}</p>}
                </div>
                <Button type="submit" variant="primary" disabled={processing}>
                    Simpan
                </Button>
            </form>
        </div>
    );
}