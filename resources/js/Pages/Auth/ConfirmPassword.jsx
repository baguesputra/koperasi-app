import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import { Head, useForm } from '@inertiajs/react';
import { Lock, ShieldAlert } from 'lucide-react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    }

    return (
        <div className="min-h-screen flex items-center justify-center bg-slate-50 px-4">
            <Head title="Konfirmasi Password" />

            <div className="w-full max-w-sm bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div className="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
                    <ShieldAlert size={24} />
                </div>

                <h1 className="text-xl font-bold text-slate-800 mb-2">Area Sensitif</h1>
                <p className="text-base text-slate-500 mb-6">
                    Untuk melanjutkan ke halaman ini, masukkan kembali password Anda.
                </p>

                <form onSubmit={submit}>
                    <InputLabel htmlFor="password" value="Password" className="text-base font-semibold text-slate-700" />
                    <div className="relative mt-1.5">
                        <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                        <input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="block w-full pl-11 pr-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                            autoFocus
                        />
                    </div>
                    <InputError message={errors.password} className="mt-2" />

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full mt-5 py-3.5 text-base font-bold rounded-xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50"
                    >
                        {processing ? 'Memverifikasi...' : 'Konfirmasi'}
                    </button>
                </form>
            </div>
        </div>
    );
}