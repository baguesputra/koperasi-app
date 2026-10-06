import { useEffect, useState } from 'react';
import { CalendarClock } from 'lucide-react';

const formatTanggal = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const formatJam = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
});

export default function DashboardClock() {
    const [sekarang, setSekarang] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setSekarang(new Date()), 30000);
        return () => clearInterval(timer);
    }, []);

    return (
        <div className="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 shadow-sm">
            <CalendarClock size={18} aria-hidden="true" className="shrink-0 text-brand-green-dark" />
            <div className="leading-tight">
                <time dateTime={sekarang.toISOString()} className="block text-sm font-bold text-slate-700">
                    {formatTanggal.format(sekarang)}
                </time>
                <p className="text-xs text-slate-400">
                    Pukul {formatJam.format(sekarang)} waktu perangkat
                </p>
            </div>
        </div>
    );
}
