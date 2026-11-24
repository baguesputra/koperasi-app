import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    Wallet, Landmark, TrendingUp, HandCoins, Repeat,
    PiggyBank, CalendarCheck, Users, UserMinus, Receipt, HeartHandshake,
    Search, ChevronRight, X, FileText, Calculator,
} from 'lucide-react';
import PageHeader from '@/Components/ui/PageHeader';
import Card from '@/Components/ui/Card';
import TextField from '@/Components/ui/TextField';
import Button from '@/Components/ui/Button';
import { ExcelIcon, PdfIcon } from '@/Components/ui/ExportIcons';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

const ikonMap = {
    wallet: Wallet,
    landmark: Landmark,
    'trending-up': TrendingUp,
    'hand-coins': HandCoins,
    repeat: Repeat,
    'piggy-bank': PiggyBank,
    'calendar-check': CalendarCheck,
    users: Users,
    'user-minus': UserMinus,
    receipt: Receipt,
    'heart-handshake': HeartHandshake,
    'file-text': FileText,
    calculator: Calculator,
};

const KATEGORI_TONE = {
    Accounting: {
        dot: 'bg-brand-navy',
        ikon: 'bg-brand-navy/8 text-brand-navy group-hover:bg-brand-navy group-hover:text-white',
    },
    Keuangan: {
        dot: 'bg-emerald-600',
        ikon: 'bg-emerald-50 text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white',
    },
    Pinjaman: {
        dot: 'bg-amber-500',
        ikon: 'bg-amber-50 text-amber-700 group-hover:bg-amber-500 group-hover:text-white',
    },
    Simpanan: {
        dot: 'bg-sky-600',
        ikon: 'bg-sky-50 text-sky-700 group-hover:bg-sky-600 group-hover:text-white',
    },
    Anggota: {
        dot: 'bg-violet-600',
        ikon: 'bg-violet-50 text-violet-700 group-hover:bg-violet-600 group-hover:text-white',
    },
    Operasional: {
        dot: 'bg-teal-600',
        ikon: 'bg-teal-50 text-teal-700 group-hover:bg-teal-600 group-hover:text-white',
    },
};

const TONE_DEFAULT = {
    dot: 'bg-slate-400',
    ikon: 'bg-slate-100 text-slate-600 group-hover:bg-brand-navy group-hover:text-white',
};

const PERIODE_BADGE = {
    bulan: 'Bulanan',
    tahun: 'Tahunan',
    tanggal: 'Cut-off',
    rentang: 'Rentang',
    tanpa_periode: 'Semua Data',
};

export default function Index({ kelompok = {} }) {
    const [kategoriAktif, setKategoriAktif] = useState('semua');
    const [cari, setCari] = useState('');
    const kataCari = cari.trim().toLowerCase();

    const daftarKategori = useMemo(
        () => Object.entries(kelompok).map(([nama, items]) => ({ nama, jumlah: items.length })),
        [kelompok]
    );

    const totalSemua = useMemo(
        () => daftarKategori.reduce((s, k) => s + k.jumlah, 0),
        [daftarKategori]
    );

    const grupTampil = useMemo(
        () => Object.entries(kelompok)
            .filter(([kategori]) => kategoriAktif === 'semua' || kategori === kategoriAktif)
            .map(([kategori, items]) => [
                kategori,
                items.filter((l) =>
                    !kataCari
                    || l.judul.toLowerCase().includes(kataCari)
                    || (l.deskripsi ?? '').toLowerCase().includes(kataCari)
                    || kategori.toLowerCase().includes(kataCari)
                ),
            ])
            .filter(([, items]) => items.length > 0),
        [kelompok, kategoriAktif, kataCari]
    );

    const totalTampil = grupTampil.reduce((s, [, items]) => s + items.length, 0);
    const sedangFilter = kategoriAktif !== 'semua' || kataCari !== '';
    const subjudul = sedangFilter
        ? `Menampilkan ${totalTampil} dari ${totalSemua} laporan`
        : `${totalSemua} laporan tersedia • pilih laporan untuk atur periode, atau unduh cepat sesuai periode default`;

    function resetFilter() {
        setKategoriAktif('semua');
        setCari('');
    }

    return (
        <AppLayout>
            <Head title="Laporan" />

            <PageHeader title="Laporan" subtitle={subjudul} />

            <Card padding="none" className="overflow-hidden shadow-sm border-slate-200/80">
                {/* Toolbar: Filter Chip Kategori + Search */}
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-2.5 px-4 py-3 bg-[#F8FAFC] border-b border-slate-200/70">
                    <div className="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filter kategori laporan">
                        <button
                            type="button"
                            onClick={() => setKategoriAktif('semua')}
                            className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border transition-all ${fokusRing} ${
                                kategoriAktif === 'semua'
                                    ? 'bg-brand-navy text-white border-brand-navy shadow-2xs'
                                    : 'bg-white text-slate-600 border-slate-200/80 hover:border-slate-300'
                            }`}
                        >
                            <span>Semua</span>
                            <span className={`tabular-nums text-[11px] ${kategoriAktif === 'semua' ? 'text-emerald-300' : 'text-slate-400'}`}>
                                {totalSemua}
                            </span>
                        </button>

                        {daftarKategori.map((k) => {
                            const aktif = kategoriAktif === k.nama;
                            return (
                                <button
                                    key={k.nama}
                                    type="button"
                                    onClick={() => setKategoriAktif(k.nama)}
                                    className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border transition-all ${fokusRing} ${
                                        aktif
                                            ? 'bg-brand-navy text-white border-brand-navy shadow-2xs'
                                            : 'bg-white text-slate-600 border-slate-200/80 hover:border-slate-300'
                                    }`}
                                >
                                    <span>{k.nama}</span>
                                    <span className={`tabular-nums text-[11px] ${aktif ? 'text-emerald-300' : 'text-slate-400'}`}>
                                        {k.jumlah}
                                    </span>
                                </button>
                            );
                        })}
                    </div>

                    <div className="relative w-full lg:w-64 shrink-0">
                        <Search size={14} aria-hidden="true" className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                        <TextField
                            type="search"
                            size="sm"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            placeholder="Cari nama laporan..."
                            aria-label="Cari nama laporan"
                            className="pl-8 pr-8 text-xs rounded-xl border-slate-200 bg-white"
                        />
                        {cari && (
                            <button
                                type="button"
                                onClick={() => setCari('')}
                                aria-label="Hapus pencarian"
                                className="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 inline-flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                            >
                                <X size={13} />
                            </button>
                        )}
                    </div>
                </div>

                {/* Empty state */}
                {grupTampil.length === 0 ? (
                    <div className="text-center py-12 px-4 bg-white">
                        <p className="text-sm font-semibold text-slate-700">
                            {kataCari
                                ? `Tidak ada laporan yang cocok dengan pencarian "${cari.trim()}".`
                                : 'Tidak ada laporan pada kategori ini.'}
                        </p>
                        <p className="text-xs sm:text-sm text-slate-400 mt-1">Coba ubah kategori atau kata kunci lain.</p>
                        <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={resetFilter}>
                            Tampilkan semua laporan
                        </Button>
                    </div>
                ) : (
                    <div>
                        {grupTampil.map(([kategori, items], idx) => {
                            const tone = KATEGORI_TONE[kategori] ?? TONE_DEFAULT;
                            return (
                                <section key={kategori}>
                                    <div className={`flex items-center justify-between px-4 sm:px-5 py-2 bg-[#F8FAFC] border-b border-slate-100 ${idx > 0 ? 'border-t border-slate-200/70' : ''}`}>
                                        <div className="flex items-center gap-2">
                                            <span className={`w-2 h-2 rounded-full ${tone.dot}`} aria-hidden="true" />
                                            <h2 className="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                                {kategori}
                                            </h2>
                                        </div>
                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-white border border-slate-200/70 text-slate-500 tabular-nums">
                                            {items.length}
                                        </span>
                                    </div>

                                    <div className="divide-y divide-slate-100 bg-white">
                                        {items.map((l) => {
                                            const Icon = ikonMap[l.ikon] ?? Receipt;
                                            const labelTipe = PERIODE_BADGE[l.tipe_periode] ?? 'Periode';
                                            const infoDefault = l.periode_default_label ?? 'periode default';

                                            return (
                                                <div
                                                    key={l.slug}
                                                    className="group flex items-center justify-between gap-2.5 sm:gap-4 px-4 sm:px-5 py-3.5 hover:bg-[#F7FBF9] transition-colors"
                                                >
                                                    <Link
                                                        href={route('laporan.show', l.slug)}
                                                        className={`flex items-start sm:items-center gap-3.5 min-w-0 flex-1 rounded-xl ${fokusRing}`}
                                                    >
                                                        <span className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors ${tone.ikon}`}>
                                                            <Icon size={19} aria-hidden="true" />
                                                        </span>
                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <p className="text-sm sm:text-[15px] font-bold text-slate-800 group-hover:text-brand-navy transition-colors">
                                                                    {l.judul}
                                                                </p>
                                                                <span className="md:hidden inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                                                    {labelTipe}
                                                                </span>
                                                            </div>
                                                            <p className="text-xs sm:text-[13px] text-slate-500 mt-0.5 leading-snug line-clamp-2 sm:line-clamp-1">
                                                                {l.deskripsi}
                                                            </p>
                                                        </div>
                                                    </Link>

                                                    <div className="flex items-center gap-1.5 sm:gap-2 shrink-0">
                                                        <span
                                                            title={`Periode default: ${infoDefault}`}
                                                            className="hidden md:inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100/90 text-slate-600 border border-slate-200/70 whitespace-nowrap"
                                                        >
                                                            {labelTipe}
                                                        </span>

                                                        <a
                                                            href={route('laporan.export', l.slug)}
                                                            title={`Unduh Excel (.xlsx) — ${infoDefault}`}
                                                            aria-label={`Unduh Excel ${l.judul} (${infoDefault})`}
                                                            className={`w-8 h-8 sm:w-9 sm:h-9 inline-flex items-center justify-center rounded-lg bg-white border border-slate-200/90 hover:border-emerald-300 hover:bg-emerald-50/70 hover:shadow-2xs transition-all ${fokusRing}`}
                                                        >
                                                            <ExcelIcon size={17} />
                                                        </a>

                                                        <a
                                                            href={route('laporan.pdf', l.slug)}
                                                            title={`Unduh PDF (.pdf) — ${infoDefault}`}
                                                            aria-label={`Unduh PDF ${l.judul} (${infoDefault})`}
                                                            className={`w-8 h-8 sm:w-9 sm:h-9 inline-flex items-center justify-center rounded-lg bg-white border border-slate-200/90 hover:border-rose-300 hover:bg-rose-50/70 hover:shadow-2xs transition-all ${fokusRing}`}
                                                        >
                                                            <PdfIcon size={17} />
                                                        </a>

                                                        <Link
                                                            href={route('laporan.show', l.slug)}
                                                            aria-label={`Buka filter ${l.judul}`}
                                                            className={`hidden sm:inline-flex w-7 h-8 items-center justify-center rounded-lg text-slate-300 group-hover:text-brand-green group-hover:translate-x-0.5 transition-all ${fokusRing}`}
                                                        >
                                                            <ChevronRight size={17} aria-hidden="true" />
                                                        </Link>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </section>
                            );
                        })}
                    </div>
                )}

                {/* Footer hint */}
                <div className="px-4 sm:px-5 py-2.5 bg-[#F8FAFC] border-t border-slate-100 text-[11px] text-slate-500 flex flex-wrap items-center justify-between gap-2">
                    <span>Klik nama laporan untuk mengatur periode &amp; pratinjau data.</span>
                    <span>Ikon Excel/PDF di baris daftar mengunduh langsung dengan periode default laporan.</span>
                </div>
            </Card>
        </AppLayout>
    );
}
