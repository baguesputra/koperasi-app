import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
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
        <div className="min-h-screen flex bg-gray-50">
            <Head title="Login" />

            {/* Panel kiri - branding, disembunyikan di layar kecil */}
            <div className="hidden lg:flex lg:w-1/2 bg-slate-900 text-white flex-col justify-between p-12">
                <div className="flex items-center gap-2">
                    <div className="w-9 h-9 rounded-lg bg-emerald-500 flex items-center justify-center font-bold text-slate-900">
                        K
                    </div>
                    <span className="text-lg font-semibold tracking-tight">
                        Koperasi App
                    </span>
                </div>

                <div>
                    <h1 className="text-3xl font-semibold leading-snug mb-3">
                        Kelola simpan pinjam anggota dengan lebih mudah.
                    </h1>
                    <p className="text-slate-400 text-sm leading-relaxed max-w-md">
                        Satu sistem terpadu untuk pengelolaan anggota, simpanan,
                        pinjaman, dan pelaporan koperasi.
                    </p>
                </div>

                <p className="text-xs text-slate-500">
                    &copy; {new Date().getFullYear()} Koperasi App. Internal use only.
                </p>
            </div>

            {/* Panel kanan - form login */}
            <div className="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
                <div className="w-full max-w-sm">
                    {/* Logo mobile - cuma muncul di layar kecil */}
                    <div className="flex lg:hidden items-center gap-2 mb-8">
                        <div className="w-9 h-9 rounded-lg bg-emerald-500 flex items-center justify-center font-bold text-slate-900">
                            K
                        </div>
                        <span className="text-lg font-semibold text-gray-800">
                            Koperasi App
                        </span>
                    </div>

                    <h2 className="text-2xl font-semibold text-gray-800 mb-1">
                        Selamat datang kembali
                    </h2>
                    <p className="text-sm text-gray-400 mb-8">
                        Masuk ke akun kamu untuk melanjutkan
                    </p>

                    {status && (
                        <div className="mb-6 text-sm font-medium text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-lg px-4 py-3">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <InputLabel htmlFor="email" value="Email" className="text-sm font-medium text-gray-700" />
                            <div className="relative mt-1.5">
                                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="block w-full pl-10 rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
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
                                <InputLabel htmlFor="password" value="Password" className="text-sm font-medium text-gray-700" />
                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="text-xs text-emerald-600 hover:text-emerald-700 font-medium"
                                    >
                                        Lupa password?
                                    </Link>
                                )}
                            </div>
                            <div className="relative mt-1.5">
                                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="block w-full pl-10 rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••"
                                />
                            </div>
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        <label className="flex items-center gap-2">
                            <Checkbox
                                name="remember"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                            />
                            <span className="text-sm text-gray-600">
                                Ingat saya di perangkat ini
                            </span>
                        </label>

                        <PrimaryButton
                            className="w-full justify-center py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 focus:bg-emerald-600 active:bg-emerald-700"
                            disabled={processing}
                        >
                            {processing ? 'Memproses...' : 'Masuk'}
                        </PrimaryButton>
                    </form>

                    <p className="text-xs text-center text-gray-400 mt-8">
                        Butuh bantuan? Hubungi tim IT internal.
                    </p>
                </div>
            </div>
        </div>
    );
}