import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    Wallet, Landmark, TrendingUp, HandCoins, CalendarClock, Repeat,
    PiggyBank, CalendarCheck, Users, UserMinus, Receipt, HeartHandshake,
    ShieldCheck, Search, ChevronRight, X,
} from 'lucide-react';
import PageHeader from '@/Components/ui/PageHeader';
import TextField from '@/Components/ui/TextField';
import Button from '@/Components/ui/Button';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

const ikonMap = {
    wallet: Wallet,
    landmark: Landmark,
    'trending-up': TrendingUp,
    'hand-coins': HandCoins,
    'calendar-clock': CalendarClock,
    repeat: Repeat,
    'piggy-bank': PiggyBank,
    'calendar-check': CalendarCheck,
    users: Users,
    'user-minus': UserMinus,
    receipt: Receipt,
    'heart-handshake': HeartHandshake,
    'shield-check': ShieldCheck,
};

export default function Index({ kelompok }) {
    const [cari, setCari] = useState('');
    const kataCari = cari.trim().toLowerCase();

    const grupTampil = useMemo(
        () => Object.entries(kelompok)
            .map(([kategori, items]) => [
                kategori,
                items.filter((l) =>
                    !kataCari
                    || l.judul.toLowerCase().includes(kataCari)
                    || (l.deskripsi ?? '').toLowerCase().includes(kataCari)
                ),
            ])
            .filter(([, items]) => items.length > 0),
        [kelompok, kataCari]
    );

    const totalTampil = grupTampil.reduce((s, [, items]) => s + items.length, 0);

    function buka(laporan) {
        router.get(route('laporan.show', laporan.slug));
    }

    return (
        <AppLayout>
            <Head title="Laporan" />

            <PageHeader title="Laporan" subtitle={`${totalTampil} laporan tersedia • pilih, atur periode, lalu cetak atau unduh Excel`}>
                <div className="relative w-full sm:w-72 group">
                    <Search size={16} aria-hidden="true" className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-green transition-colors pointer-events-none" />
                    <TextField
                        type="search"
                        size="sm"
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        placeholder="Cari nama laporan..."
                        aria-label="Cari nama laporan"
                        className="pl-10 pr-8 text-sm rounded-full border-slate-200 bg-white shadow-sm focus:shadow-md transition-all"
                    />
                    {cari && (
                        <button
                            type="button"
                            onClick={() => setCari('')}
                            aria-label="Hapus pencarian"
                            className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 inline-flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-200/70"
                        >
                            <X size={14} />
                        </button>
                    )}
                </div>
            </PageHeader>

            {grupTampil.length === 0 && (
                <div className="text-center py-12 px-4">
                    <p className="text-sm font-semibold text-slate-600">Tidak ada laporan yang cocok dengan pencarian &ldquo;{cari}&rdquo;.</p>
                    <p className="text-sm text-slate-400 mt-1">Coba kata kunci lain.</p>
                    <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={() => setCari('')}>
                        Tampilkan semua laporan
                    </Button>
                </div>
            )}

            <div className="space-y-7">
                {grupTampil.map(([kategori, items]) => (
                    <section key={kategori}>
                        <div className="flex items-center gap-2 mb-3">
                            <h2 className="text-xs font-bold uppercase tracking-wider text-slate-400">{kategori}</h2>
                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500 tabular-nums">
                                {items.length}
                            </span>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            {items.map((l) => {
                                const Icon = ikonMap[l.ikon] ?? Receipt;
                                return (
                                    <button
                                        key={l.slug}
                                        onClick={() => buka(l)}
                                        aria-label={`Buka ${l.judul}`}
                                        className={`group text-left bg-white rounded-2xl border border-slate-200/70 shadow-sm p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:border-brand-green/40 ${fokusRing}`}
                                    >
                                        <div className="flex items-start justify-between gap-2 mb-3">
                                            <span className="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-navy/5 to-brand-green-light/60 text-brand-navy flex items-center justify-center shrink-0 group-hover:from-brand-green-light group-hover:to-brand-green-light group-hover:text-brand-green-dark transition-colors">
                                                <Icon size={20} aria-hidden="true" />
                                            </span>
                                            <ChevronRight size={16} className="text-slate-300 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 group-hover:text-brand-green transition-all mt-1" />
                                        </div>
                                        <p className="text-base font-bold text-slate-800 group-hover:text-brand-navy transition-colors">{l.judul}</p>
                                        <p className="text-sm text-slate-400 mt-1 leading-snug">{l.deskripsi}</p>
                                    </button>
                                );
                            })}
                        </div>
                    </section>
                ))}
            </div>
        </AppLayout>
    );
}
