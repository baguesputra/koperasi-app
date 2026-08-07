import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Checkbox from '@/Components/Checkbox';
import { Head, Link, useForm } from '@inertiajs/react';
import { Lock, Mail } from 'lucide-react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen flex bg-slate-50">
            <Head title="Login" />

            {/* Panel kiri - branding */}
            <div className="hidden lg:flex lg:w-1/2 bg-brand-navy text-white flex-col justify-between p-12">
                <img src="/images/logo.png" alt="Koperasi App" className="w-12 h-12" />

                <div>
                    <h1 className="text-3xl font-bold leading-snug mb-3">
                        Kelola simpan pinjam anggota dengan lebih mudah.
                    </h1>
                    <p className="text-slate-300 text-base leading-relaxed max-w-md">
                        Satu sistem terpadu untuk pengelolaan anggota, simpanan,
                        pinjaman, dan pelaporan koperasi.
                    </p>
                </div>

                <p className="text-sm text-slate-400">
                    &copy; {new Date().getFullYear()} Koperasi App. Internal use only.
                </p>
            </div>

            {/* Panel kanan - form login */}
            <div className="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
                <div className="w-full max-w-sm">
                    <div className="flex lg:hidden items-center gap-2 mb-8">
                        <img src="/images/logo.png" alt="Koperasi App" className="w-10 h-10" />
                        <span className="text-lg font-bold text-slate-800">Koperasi App</span>
                    </div>

                    <h2 className="text-2xl font-bold text-slate-800 mb-1">
                        Selamat datang kembali
                    </h2>
                    <p className="text-base text-slate-400 mb-8">
                        Masuk ke akun kamu untuk melanjutkan
                    </p>

                    {status && (
                        <div className="mb-6 text-sm font-medium text-brand-green-dark bg-brand-green-light border border-brand-green/20 rounded-xl px-4 py-3">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <InputLabel htmlFor="email" value="Email" className="text-base font-semibold text-slate-700" />
                            <div className="relative mt-1.5">
                                <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="block w-full pl-11 pr-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                    autoComplete="username"
                                    autoFocus
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="nama@perusahaan.com"
                                />
                            </div>
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div>
                            <div className="flex items-center justify-between">
                                <InputLabel htmlFor="password" value="Password" className="text-base font-semibold text-slate-700" />
                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="text-sm text-brand-green font-semibold hover:text-brand-green-dark"
                                    >
                                        Lupa password?
                                    </Link>
                                )}
                            </div>
                            <div className="relative mt-1.5">
                                <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="block w-full pl-11 pr-4 py-3 text-base rounded-xl border border-slate-300 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••"
                                />
                            </div>
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        <label className="flex items-center gap-2.5">
                            <Checkbox
                                name="remember"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                            />
                            <span className="text-base text-slate-600">
                                Ingat saya di perangkat ini
                            </span>
                        </label>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full py-3.5 text-base font-semibold rounded-xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50"
                        >
                            {processing ? 'Memproses...' : 'Masuk'}
                        </button>
                    </form>

                    <p className="text-sm text-center text-slate-400 mt-8">
                        Butuh bantuan? Hubungi tim IT internal.
                    </p>
                </div>
            </div>
        </div>
    );
}