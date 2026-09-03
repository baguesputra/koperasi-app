import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { ArrowLeft, RefreshCw, AlertCircle } from 'lucide-react';

export default function SsoGagal({ error }) {
    const { props } = usePage();
    const ssoLogoutUrl = props.ssoLogoutUrl ?? 'https://gate.appdutamall.com/dashboard';

    useEffect(() => {
        document.title = 'Login SSO Gagal - Koperasi App';
    }, []);

    return (
        <div className="min-h-screen flex items-center justify-center bg-slate-50 px-4">
            <Head title="Login SSO Gagal" />

            <div className="w-full max-w-md text-center">
                <div className="mb-8">
                    <img src="/images/logo.png" alt="Koperasi App" className="w-16 h-16 mx-auto mb-4" />
                    <h1 className="text-2xl font-bold text-brand-navy">Koperasi App</h1>
                </div>

                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
                    <div className="flex items-center justify-center gap-3 mb-6">
                        <div className="w-14 h-14 rounded-full bg-brand-red/10 flex items-center justify-center">
                            <AlertCircle className="w-7 h-7 text-brand-red" />
                        </div>
                    </div>

                    <h2 className="text-xl font-semibold text-slate-800 mb-3">
                        Login SSO Gagal
                    </h2>

                    <p className="text-slate-500 mb-6 leading-relaxed">
                        {error || 'Terjadi kesalahan saat login via SSO. Silakan coba lagi atau hubungi pihak koperasi.'}
                    </p>

                    <div className="space-y-3">
                        <Link
                            href={route('sso.redirect')}
                            className="inline-flex items-center justify-center gap-2 w-full py-3 px-4 text-base font-semibold rounded-xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors"
                        >
                            <RefreshCw className="w-5 h-5" />
                            Coba Lagi
                        </Link>

                        <a
                            href={ssoLogoutUrl}
                            className="inline-flex items-center justify-center gap-2 w-full py-3 px-4 text-base font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                            Kembali ke Portal Perusahaan
                        </a>
                    </div>
                </div>

                <p className="text-sm text-slate-400 mt-6">
                    &copy; {new Date().getFullYear()} Koperasi App - PT. Tata Optima Property
                </p>
            </div>
        </div>
    );
}