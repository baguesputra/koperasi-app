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
            className="inline-flex items-center gap-3.5 rounded-2xl border border-slate-200/80 bg-white px-4 py-2.5 shadow-[0_1px_2px_rgba(15,23,42,0.03),0_4px_16px_-4px_rgba(15,23,42,0.04)]"
            title={`Waktu perangkat: ${hariTeks}, ${tanggalLengkap} pukul ${jamTeks}${zonaWaktu ? ` ${zonaWaktu}` : ''}`}
        >
            {/* Kolom Kiri: Jam Digital Tegas */}
            <div className="flex items-baseline gap-1.5">
                <span className="text-xl sm:text-[22px] font-bold text-[#0F1E36] tabular-nums tracking-[-0.03em] leading-none">
                    {jamTeks}
                </span>
                {zonaWaktu && (
                    <span className="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-brand-green-dark">
                        <span className="h-1.5 w-1.5 rounded-full bg-brand-green shrink-0" aria-hidden="true" />
                        {zonaWaktu}
                    </span>
                )}
            </div>

            {/* Pemisah Vertikal Hairline */}
            <span className="h-7 w-px bg-slate-200/80 shrink-0" aria-hidden="true" />

            {/* Kolom Kanan: Hari & Tanggal Bertingkat */}
            <time dateTime={sekarang.toISOString()} className="flex flex-col justify-center leading-tight">
                <span className="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                    {hariTeks}
                </span>
                <span className="text-xs sm:text-[13px] font-semibold text-slate-700 whitespace-nowrap mt-0.5">
                    <span className="hidden sm:inline">{tanggalLengkap}</span>
                    <span className="sm:hidden">{tanggalRingkas}</span>
                </span>
            </time>
        </div>
    );
}
