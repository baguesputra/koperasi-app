import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { CheckCircle2, X } from 'lucide-react';

export default function FlashToast() {
    const { flash } = usePage().props;
    const [pesan, setPesan] = useState(null);

    useEffect(() => {
        if (flash?.status) {
            setPesan(flash.status);
            const t = setTimeout(() => setPesan(null), 4000);
            return () => clearTimeout(t);
        }
    }, [flash?.status]);

    if (!pesan) return null;

    return (
        <div
            role="status"
            className="mb-6 flex items-center gap-2.5 rounded-xl border border-brand-green bg-brand-green-light px-4 py-3 text-sm font-semibold text-brand-green-dark"
        >
            <CheckCircle2 size={18} className="shrink-0" aria-hidden="true" />
            <span>{pesan}</span>
            <button
                onClick={() => setPesan(null)}
                aria-label="Tutup notifikasi"
                className="ml-auto shrink-0 rounded-md p-0.5 hover:bg-brand-green/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40"
            >
                <X size={16} />
            </button>
        </div>
    );
}
