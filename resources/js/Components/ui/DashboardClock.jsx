import { useEffect, useState } from 'react';

const formatHari = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
});

const formatTanggalPanjang = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const formatTanggalRingkas = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const formatJam = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
});

function deteksiZonaWaktu(date) {
    const offsetMenit = -date.getTimezoneOffset();
    if (offsetMenit === 420) return 'WIB';
    if (offsetMenit === 480) return 'WITA';
    if (offsetMenit === 540) return 'WIT';
    return '';
}

export default function DashboardClock() {
    const [sekarang, setSekarang] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setSekarang(new Date()), 15000);
        return () => clearInterval(timer);
    }, []);

    const jamTeks = formatJam.format(sekarang).replace('.', ':');
    const hariTeks = formatHari.format(sekarang);
    const tanggalLengkap = formatTanggalPanjang.format(sekarang);
    const tanggalRingkas = formatTanggalRingkas.format(sekarang);
    const zonaWaktu = deteksiZonaWaktu(sekarang);

    return (
        <div
            className="flex flex-col items-start sm:items-end leading-tight"
            title={`Waktu perangkat: ${hariTeks}, ${tanggalLengkap} pukul ${jamTeks}${zonaWaktu ? ` ${zonaWaktu}` : ''}`}
        >
            <time dateTime={sekarang.toISOString()} className="text-xs font-medium text-slate-500 whitespace-nowrap">
                <span className="hidden sm:inline">{hariTeks}, {tanggalLengkap}</span>
                <span className="sm:hidden">{hariTeks}, {tanggalRingkas}</span>
            </time>

            <div className="flex items-center gap-2 mt-1">
                <span className="text-2xl sm:text-[26px] font-extrabold text-[#0F1E36] tabular-nums tracking-[-0.03em] leading-none">
                    {jamTeks}
                </span>
                {zonaWaktu && (
                    <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                        <span className="h-1.5 w-1.5 rounded-full bg-brand-green animate-pulse shrink-0" aria-hidden="true" />
                        {zonaWaktu}
                    </span>
                )}
            </div>
        </div>
    );
}
