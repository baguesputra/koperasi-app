import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

export default function BackLink({ href, children = 'Kembali', className = '' }) {
    return (
        <Link
            href={href}
            className={`inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-4 ${className}`}
        >
            <ArrowLeft size={16} />
            {children}
        </Link>
    );
}