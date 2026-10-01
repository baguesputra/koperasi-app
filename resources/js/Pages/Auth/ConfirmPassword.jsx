import { Head, useForm, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { ShieldAlert, LogOut } from 'lucide-react';
import FormField from '@/Components/ui/FormField';
import KataSandi from '@/Components/ui/KataSandi';
import Button from '@/Components/ui/Button';

export default function ConfirmPassword() {
    const { props } = usePage();
    const csrfToken = typeof document !== 'undefined'
        ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        : null;
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
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-b from-brand-navy/5 to-slate-50 px-4 py-10">
            <Head title="Konfirmasi Password" />

            <div className="w-full max-w-sm">
                <div className="bg-white rounded-2xl border border-slate-200/70 shadow-md p-6">
                    <div className="flex items-center gap-3 mb-4">
                        <span className="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex items-center justify-center shadow-md shadow-amber-500/25 shrink-0">
                            <ShieldAlert size={24} />
                        </span>
                        <div className="min-w-0">
                            <h1 className="text-xl font-bold text-slate-800 leading-tight">Area Sensitif</h1>
                            <p className="text-xs text-slate-400">Pengaturan & kelola pengguna dikunci</p>
                        </div>
                    </div>

                    <p className="text-sm text-slate-500 mb-5 leading-relaxed">
                        Sesi konfirmasi kedaluwarsa atau belum dilakukan. Masukkan kembali password untuk membuka
                        <strong> Pengaturan</strong> dan <strong>Kelola Pengguna</strong>.
                    </p>

                    <form onSubmit={submit}>
                        <FormField label="Password Anda" error={errors.password} required>
                            <KataSandi
                                size="sm"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="••••••••"
                                autoFocus
                                required
                                autoComplete="current-password"
                            />
                        </FormField>

                        <Button type="submit" disabled={processing} className="w-full rounded-full shadow-md shadow-brand-green/25 mt-1">
                            {processing ? 'Memverifikasi...' : 'Buka Area Sensitif'}
                        </Button>
                    </form>
                </div>

                <form method="POST" action={route('logout')} className="mt-3 text-center">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <button
                        type="submit"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-slate-600 transition-colors"
                    >
                        <LogOut size={13} />
                        Bukan Anda? Keluar
                    </button>
                </form>
                {props.flash?.status && (
                    <p className="text-xs text-brand-green-dark text-center mt-2">{props.flash.status}</p>
                )}
            </div>
        </div>
    );
}
