import { useEffect, useState } from 'react';
import { Calendar, Clock } from 'lucide-react';

const formatTanggalPanjang = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const formatTanggalRingkas = new Intl.DateTimeFormat('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const formatJam = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
});

export default function DashboardClock() {
    const [sekarang, setSekarang] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setSekarang(new Date()), 15000);
        return () => clearInterval(timer);
    }, []);

    const jamTeks = formatJam.format(sekarang).replace('.', ':');
    const tanggalLengkap = formatTanggalPanjang.format(sekarang);
    const tanggalRingkas = formatTanggalRingkas.format(sekarang);

    return (
        <div
            className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs shadow-sm transition-colors hover:border-slate-300"
            title={`Waktu perangkat: ${tanggalLengkap} pukul ${jamTeks}`}
        >
            <div className="flex items-center gap-1.5 font-medium text-slate-600">
                <Calendar size={13} aria-hidden="true" className="shrink-0 text-slate-400" />
                <time dateTime={sekarang.toISOString()}>
                    <span className="hidden sm:inline">{tanggalLengkap}</span>
                    <span className="sm:hidden">{tanggalRingkas}</span>
                </time>
            </div>

            <span className="h-3 w-px bg-slate-200 shrink-0" aria-hidden="true" />

            <div className="flex items-center gap-1.5 font-semibold text-slate-800 tabular-nums">
                <Clock size={13} aria-hidden="true" className="shrink-0 text-brand-green-dark" />
                <span>{jamTeks}</span>
            </div>
        </div>
    );
}

