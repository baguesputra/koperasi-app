import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ArrowLeft, RefreshCw, AlertCircle } from 'lucide-react';

export default function SsoGagal({ error }) {
    const { props } = usePage();
    const ssoLogoutUrl = props.ssoLogoutUrl ?? 'https://gate.appdutamall.com/dashboard';
    const [isMounted, setIsMounted] = useState(false);

    useEffect(() => {
        document.title = 'Login SSO Gagal - Koperasi App';
        setIsMounted(true);
    }, []);

    return (
        <div className="min-h-screen flex items-center justify-center bg-slate-50 px-4">
            <Head title="Login SSO Gagal" />

            <div className="w-full max-w-md text-center">
                <div className="mb-6">
                    <img src="/images/logo.png" alt="Koperasi App" className="w-12 h-12 mx-auto mb-3" />
                    <h1 className="text-xl font-bold text-brand-navy">Koperasi App</h1>
                </div>

                <div 
                    className={`bg-white rounded-xl shadow-lg border border-slate-100 p-6 transition-all duration-500 ease-in-out ${isMounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'}`}
                >
                    <div className="flex items-center justify-center gap-3 mb-4">
                        <div className="w-10 h-10 rounded-full bg-brand-red/10 flex items-center justify-center">
                            <AlertCircle className="w-5 h-5 text-brand-red" />
                        </div>
                    </div>

                    <h2 className="text-lg font-semibold text-slate-800 mb-3">
                        Login SSO Gagal
                    </h2>

                    <p className="text-slate-500 mb-5">
                        {error || 'Terjadi kesalahan saat login via SSO. Silakan coba lagi atau hubungi pihak koperasi.'}
                    </p>

                    <div className="space-y-3">
                        <Link
                            href={route('sso.redirect')}
                            className="w-full inline-flex items-center justify-center gap-2 py-2 px-4 text-sm font-semibold rounded-lg bg-brand-green text-white hover:bg-brand-green-dark transition-colors duration-200 active:scale-[0.95]"
                        >
                            <RefreshCw className="w-4 h-4" />
                            Coba Lagi
                        </Link>

                        <a
                            href={ssoLogoutUrl}
                            className="w-full inline-flex items-center justify-center gap-2 py-2 px-4 text-sm font-semibold rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors duration-200 active:scale-[0.95]"
                        >
                            <ArrowLeft className="w-4 h-4" />
                            Kembali ke Portal Perusahaan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    );
}
