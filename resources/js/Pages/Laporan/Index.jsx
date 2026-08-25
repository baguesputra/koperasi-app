import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    Wallet, Landmark, TrendingUp, HandCoins, CalendarClock, Repeat,
    PiggyBank, CalendarCheck, Users, UserMinus, Receipt, HeartHandshake,
    ShieldCheck, Search, ChevronRight,
} from 'lucide-react';
import PageHeader from '@/Components/ui/PageHeader';

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

    function buka(laporan) {
        router.get(route('laporan.show', laporan.slug));
    }

    return (
        <AppLayout>
            <Head title="Laporan" />

            <PageHeader title="Laporan" subtitle="Pilih laporan, atur periodenya, lalu cetak atau unduh Excel.">
                <div className="relative w-full sm:w-72">
                    <Search size={16} aria-hidden="true" className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        placeholder="Cari nama laporan..."
                        aria-label="Cari nama laporan"
                        className="w-full pl-9 pr-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white placeholder:text-slate-400 focus:border-brand-green outline-none"
                    />
                </div>
            </PageHeader>

            {grupTampil.length === 0 && (
                <p className="text-base text-slate-400 py-10 text-center">Tidak ada laporan yang cocok dengan pencarian &ldquo;{cari}&rdquo;.</p>
            )}

            <div className="space-y-7">
                {grupTampil.map(([kategori, items]) => (
                    <section key={kategori}>
                        <h2 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">{kategori}</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            {items.map((l) => {
                                const Icon = ikonMap[l.ikon] ?? Receipt;
                                return (
                                    <button
                                        key={l.slug}
                                        onClick={() => buka(l)}
                                        aria-label={`Buka ${l.judul}`}
                                        className={`group text-left bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:border-brand-green/40 hover:shadow transition-all ${fokusRing}`}
                                    >
                                        <div className="flex items-start justify-between gap-2 mb-3">
                                            <span className="w-10 h-10 rounded-xl bg-brand-navy/5 text-brand-navy flex items-center justify-center shrink-0 group-hover:bg-brand-green-light group-hover:text-brand-green-dark transition-colors">
                                                <Icon size={20} aria-hidden="true" />
                                            </span>
                                            <ChevronRight size={16} className="text-slate-300 group-hover:text-brand-green transition-colors mt-1" />
                                        </div>
                                        <p className="text-base font-bold text-slate-800">{l.judul}</p>
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
