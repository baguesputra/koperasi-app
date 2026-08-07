import { Link, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';

export default function Topbar() {
    const { auth } = usePage().props;
    const initial = auth.user?.name?.charAt(0)?.toUpperCase() ?? '?';

    return (
        <header className="flex items-center justify-between bg-brand-navy px-6 py-3.5">
            <div className="text-sm text-slate-300">Selamat datang kembali</div>

            <div className="flex items-center gap-4">
                <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold">
                        {initial}
                    </div>
                    <div className="leading-tight">
                        <p className="text-sm font-semibold text-white">{auth.user?.name}</p>
                        <p className="text-xs text-slate-300 capitalize">
                            {auth.user?.roles?.[0]?.replace('_', ' ') ?? '-'}
                        </p>
                    </div>
                </div>

                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="flex items-center gap-1.5 text-sm text-slate-300 hover:text-white transition-colors border-l border-white/15 pl-4"
                >
                    <LogOut size={16} />
                    Logout
                </Link>
            </div>
        </header>
    );
}