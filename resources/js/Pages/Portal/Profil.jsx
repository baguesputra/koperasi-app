import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, useForm, router, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    User, Building2, Briefcase, Calendar, Mail,
    CreditCard, Plus, Star, Trash2, Landmark, Network,
    LogOut, Info, ShieldCheck, ChevronRight, Copy, Check,
    Sparkles, X,
} from 'lucide-react';

export default function Profil({ anggota, rekening }) {
    const { auth } = usePage().props;
    const roles = auth?.user?.roles ?? [];
    const isPengurus = roles.some((r) => ['super_admin', 'admin', 'bendahara', 'ketua_koperasi', 'pengawas'].includes(r));

    const [showForm, setShowForm] = useState(false);
    const [copiedId, setCopiedId] = useState(null);
    const [fotoGagal, setFotoGagal] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        nama_bank: '',
        no_rekening: '',
        atas_nama: '',
    });

    const initial = anggota?.nama?.charAt(0)?.toUpperCase() ?? '?';

    function submit(e) {
        e.preventDefault();
        post(route('portal.profil.rekening.store'), {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    function jadikanDefault(id) {
        router.put(route('portal.profil.rekening.default', id), {}, { preserveScroll: true });
    }

    function hapus(id) {
        if (confirm('Hapus rekening ini dari daftar pencairan?')) {
            router.delete(route('portal.profil.rekening.destroy', id), { preserveScroll: true });
        }
    }

    function salinRekening(text, id) {
        navigator.clipboard.writeText(text);
        setCopiedId(id);
        setTimeout(() => setCopiedId(null), 2000);
    }

    const infoKepegawaian = [
        { icon: Landmark, label: 'Perusahaan', value: anggota.perusahaan || '-' },
        { icon: Building2, label: 'Cabang', value: anggota.cabang || '-' },
        { icon: Briefcase, label: 'Unit Bisnis', value: anggota.unit_bisnis || '-' },
        { icon: Briefcase, label: 'Jabatan', value: anggota.jabatan || '-' },
        { icon: Network, label: 'Departemen', value: anggota.departemen || '-' },
        { icon: Network, label: 'Divisi', value: anggota.divisi || '-' },
        { icon: Mail, label: 'Email Kantor / Akun', value: anggota.email || '-' },
        { icon: Calendar, label: 'Mulai Bekerja', value: anggota.tanggal_mulai_kerja || '-' },
        { icon: Calendar, label: 'Menjadi Anggota', value: anggota.tanggal_jadi_anggota || '-' },
    ];

    return (
        <AnggotaLayout mobileTitle="Profil Saya" backHref={route('portal.dashboard')}>
            <Head title="Profil Anggota" />

            <div className="space-y-6 sm:space-y-8">
                {/* =====================================================
                    1. HERO PROFILE BANNER (Executive Identity)
                ====================================================== */}
                <div className="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-white border border-slate-100 p-5 sm:p-7 shadow-[0_4px_24px_rgba(15,30,54,0.04)]">
                    {/* Decorative Ambient Glow */}
                    <div className="absolute top-0 right-0 w-80 h-80 bg-gradient-to-bl from-emerald-500/10 via-blue-500/5 to-transparent rounded-full blur-3xl pointer-events-none" />

                    <div className="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                        <div className="flex items-center gap-4 sm:gap-5">
                            {/* Member Photo / Avatar */}
                            <div className="relative shrink-0">
                                {anggota.foto_url && !fotoGagal ? (
                                    <img
                                        src={anggota.foto_url}
                                        alt={anggota.nama}
                                        loading="lazy"
                                        referrerPolicy="no-referrer"
                                        onError={() => setFotoGagal(true)}
                                        className="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover border-2 border-emerald-500/20 shadow-md bg-slate-100"
                                    />
                                ) : (
                                    <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-tr from-brand-green to-emerald-400 text-white flex items-center justify-center text-2xl sm:text-3xl font-extrabold shadow-md border border-white">
                                        {initial}
                                    </div>
                                )}
                                <div
                                    className="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center shadow-xs"
                                    title="Anggota Aktif"
                                >
                                    <span className="w-2 h-2 rounded-full bg-white animate-pulse" />
                                </div>
                            </div>

                            {/* Core Identity Details */}
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-[11px] font-bold text-brand-green-dark border border-emerald-100">
                                        <span className="w-1.5 h-1.5 rounded-full bg-brand-green" />
                                        Anggota Aktif
                                    </span>
                                    {anggota.no_anggota && (
                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-[11px] font-mono font-medium text-slate-600">
                                            #{anggota.no_anggota}
                                        </span>
                                    )}
                                </div>

                                <h1 className="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight truncate mt-1">
                                    {anggota.nama}
                                </h1>

                                <p className="text-xs sm:text-sm text-slate-500 font-medium truncate mt-0.5">
                                    NIP: <span className="font-mono text-slate-700 font-semibold">{anggota.no_karyawan}</span> &bull; {anggota.jabatan ?? 'Anggota'}
                                </p>

                                <p className="text-xs text-slate-400 mt-1 truncate">
                                    {anggota.perusahaan ? `${anggota.perusahaan} • ` : ''}Bergabung {anggota.lama_keanggotaan_label}
                                </p>
                            </div>
                        </div>

                        {/* Status Verification Pill */}
                        <div className="flex sm:flex-col items-start sm:items-end justify-between sm:justify-center border-t sm:border-t-0 border-slate-100 pt-3 sm:pt-0 gap-1 shrink-0">
                            <span className="text-[10px] sm:text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                Sumber Data
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200/80">
                                <ShieldCheck size={14} className="text-brand-green" />
                                Terverifikasi HRIS GATE
                            </span>
                        </div>
                    </div>
                </div>

                {/* =====================================================
                    2. MAIN GRID: DATA KEPEGAWAIAN & REKENING PENCAIRAN
                ====================================================== */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* LEFT (7 cols): Data Kepegawaian & Koperasi */}
                    <div className="lg:col-span-7 bg-white rounded-2xl sm:rounded-3xl border border-slate-100 p-5 sm:p-7 shadow-[0_4px_24px_rgba(15,30,54,0.03)] flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                                <div>
                                    <h2 className="text-base font-bold text-slate-800">
                                        Informasi Kepegawaian
                                    </h2>
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Rincian penugasan & masa keanggotaan
                                    </p>
                                </div>
                                <span className="text-[11px] font-semibold text-slate-500 bg-slate-50 border border-slate-200/60 px-2.5 py-1 rounded-lg">
                                    Read Only
                                </span>
                            </div>

                            {/* Structured Key-Value Grid */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {infoKepegawaian.map((item, index) => {
                                    const Icon = item.icon;
                                    return (
                                        <div
                                            key={index}
                                            className="flex items-start gap-3 p-3 rounded-xl bg-slate-50/70 border border-slate-100/80"
                                        >
                                            <div className="w-8 h-8 rounded-lg bg-white text-slate-500 flex items-center justify-center shrink-0 shadow-2xs border border-slate-100">
                                                <Icon size={15} />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="text-[11px] font-medium text-slate-400">
                                                    {item.label}
                                                </p>
                                                <p className="text-xs sm:text-sm font-semibold text-slate-800 truncate mt-0.5">
                                                    {item.value}
                                                </p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* GATE Sync Callout */}
                        <div className="mt-6 pt-4 border-t border-slate-100 flex items-start gap-2.5 text-xs text-slate-500">
                            <Info size={16} className="text-slate-400 shrink-0 mt-0.5" />
                            <p className="leading-relaxed">
                                Data kepegawaian (mutasi, departemen, dan jabatan) dikelola terpusat melalui HRIS GATE. Apabila ada ketidaksesuaian data, silakan laporkan ke tim HR untuk sinkronisasi ulang.
                            </p>
                        </div>
                    </div>

                    {/* RIGHT (5 cols): Rekening Bank Pencairan */}
                    <div className="lg:col-span-5 bg-white rounded-2xl sm:rounded-3xl border border-slate-100 p-5 sm:p-7 shadow-[0_4px_24px_rgba(15,30,54,0.03)] flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                                <div>
                                    <h2 className="text-base font-bold text-slate-800">
                                        Rekening Pencairan
                                    </h2>
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Tujuan transfer pencairan pinjaman
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setShowForm(!showForm)}
                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-green-light hover:bg-emerald-100 text-xs font-bold text-brand-green-dark transition-colors"
                                >
                                    {showForm ? <X size={14} /> : <Plus size={14} />}
                                    <span>{showForm ? 'Batal' : 'Tambah'}</span>
                                </button>
                            </div>

                            {/* Add Account Inline Form */}
                            {showForm && (
                                <form onSubmit={submit} className="mb-5 p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-3 animate-in fade-in duration-200">
                                    <p className="text-xs font-bold text-slate-700">Tambah Rekening Baru</p>

                                    <div>
                                        <input
                                            type="text"
                                            value={data.nama_bank}
                                            onChange={(e) => setData('nama_bank', e.target.value)}
                                            placeholder="Nama Bank (misal: BCA, Mandiri, BRI)"
                                            className="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-brand-green focus:ring-1 focus:ring-brand-green outline-none bg-white"
                                        />
                                        {errors.nama_bank && <p className="text-[11px] text-red-600 mt-1">{errors.nama_bank}</p>}
                                    </div>

                                    <div>
                                        <input
                                            type="text"
                                            inputMode="numeric"
                                            value={data.no_rekening}
                                            onChange={(e) => setData('no_rekening', e.target.value)}
                                            placeholder="Nomor Rekening"
                                            className="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-brand-green focus:ring-1 focus:ring-brand-green outline-none bg-white font-mono"
                                        />
                                        {errors.no_rekening && <p className="text-[11px] text-red-600 mt-1">{errors.no_rekening}</p>}
                                    </div>

                                    <div>
                                        <input
                                            type="text"
                                            value={data.atas_nama}
                                            onChange={(e) => setData('atas_nama', e.target.value)}
                                            placeholder="Nama Pemilik Rekening (a.n.)"
                                            className="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-brand-green focus:ring-1 focus:ring-brand-green outline-none bg-white"
                                        />
                                        {errors.atas_nama && <p className="text-[11px] text-red-600 mt-1">{errors.atas_nama}</p>}
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full py-2.5 text-xs sm:text-sm font-bold rounded-xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors disabled:opacity-50 shadow-sm"
                                    >
                                        {processing ? 'Menyimpan...' : 'Simpan Rekening'}
                                    </button>
                                </form>
                            )}

                            {/* Bank Accounts List */}
                            {rekening.length === 0 ? (
                                <div className="text-center py-8 px-4 rounded-2xl bg-slate-50 border border-dashed border-slate-200">
                                    <div className="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                                        <CreditCard size={20} />
                                    </div>
                                    <p className="text-xs sm:text-sm font-semibold text-slate-600">Belum Ada Rekening</p>
                                    <p className="text-xs text-slate-400 mt-1">Tambahkan rekening bank untuk pencairan pinjaman Anda.</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {rekening.map((r) => (
                                        <div
                                            key={r.id}
                                            className={`p-4 rounded-2xl border transition-all ${
                                                r.is_default
                                                    ? 'border-emerald-300/80 bg-gradient-to-br from-emerald-50/50 to-white shadow-xs'
                                                    : 'border-slate-100 bg-white hover:border-slate-200'
                                            }`}
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="flex items-center gap-2.5">
                                                    <div className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${
                                                        r.is_default ? 'bg-brand-green text-white shadow-xs' : 'bg-slate-100 text-slate-600'
                                                    }`}>
                                                        <CreditCard size={18} />
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <p className="text-sm font-bold text-slate-800 uppercase tracking-wide">
                                                                {r.nama_bank}
                                                            </p>
                                                            {r.is_default && (
                                                                <span className="inline-flex items-center gap-1 text-[10px] font-bold text-brand-green-dark bg-emerald-100/70 border border-emerald-200/80 px-2 py-0.5 rounded-full">
                                                                    <Star size={10} className="fill-current" />
                                                                    Utama
                                                                </span>
                                                            )}
                                                        </div>
                                                        <p className="text-xs text-slate-500 mt-0.5">
                                                            a.n. <span className="font-semibold text-slate-700">{r.atas_nama}</span>
                                                        </p>
                                                    </div>
                                                </div>

                                                {/* Action Buttons */}
                                                <div className="flex items-center gap-1 shrink-0">
                                                    {!r.is_default && (
                                                        <button
                                                            type="button"
                                                            onClick={() => jadikanDefault(r.id)}
                                                            title="Jadikan Rekening Utama"
                                                            className="p-1.5 rounded-lg text-slate-400 hover:text-amber-500 hover:bg-amber-50 transition-colors"
                                                        >
                                                            <Star size={16} />
                                                        </button>
                                                    )}
                                                    <button
                                                        type="button"
                                                        onClick={() => hapus(r.id)}
                                                        title="Hapus Rekening"
                                                        className="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                                    >
                                                        <Trash2 size={16} />
                                                    </button>
                                                </div>
                                            </div>

                                            {/* Account Number & 1-Click Copy */}
                                            <div className="mt-3 pt-2.5 border-t border-slate-100/80 flex items-center justify-between">
                                                <span className="font-mono text-sm sm:text-base font-bold text-slate-800 tracking-wider">
                                                    {r.no_rekening}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => salinRekening(r.no_rekening, r.id)}
                                                    className="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 hover:text-slate-800 px-2 py-1 rounded-md hover:bg-slate-100 transition-colors"
                                                >
                                                    {copiedId === r.id ? (
                                                        <>
                                                            <Check size={13} className="text-brand-green" />
                                                            <span className="text-brand-green">Tersalin</span>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Copy size={13} />
                                                            <span>Salin</span>
                                                        </>
                                                    )}
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <p className="text-[11px] text-slate-400 mt-6 pt-3 border-t border-slate-100">
                            Rekening bertanda <span className="font-semibold text-brand-green-dark">Utama</span> akan diprioritaskan saat proses pencairan dana pinjaman yang disetujui.
                        </p>
                    </div>
                </div>

                {/* =====================================================
                    3. BANTUAN & PENGATURAN AKUN (Bottom Section)
                ====================================================== */}
                <div className="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 p-5 sm:p-7 shadow-[0_4px_24px_rgba(15,30,54,0.03)] space-y-4">
                    <div>
                        <h2 className="text-base font-bold text-slate-800">
                            Bantuan & Navigasi
                        </h2>
                        <p className="text-xs text-slate-400 mt-0.5">
                            Pusat panduan aturan koperasi dan akses sistem
                        </p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <button
                            type="button"
                            onClick={() => window.dispatchEvent(new CustomEvent('open-panduan'))}
                            className="group flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:border-emerald-200 hover:bg-emerald-50/30 transition-all text-left"
                        >
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 group-hover:bg-brand-green group-hover:text-white transition-colors">
                                    <Info size={18} />
                                </div>
                                <div>
                                    <p className="text-sm font-bold text-slate-800 group-hover:text-brand-green-dark transition-colors">
                                        Tata Cara & Syarat Pinjaman
                                    </p>
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Panduan eligibilitas, limit, dan tenor
                                    </p>
                                </div>
                            </div>
                            <ChevronRight size={16} className="text-slate-400 group-hover:translate-x-0.5 transition-transform" />
                        </button>

                        {isPengurus && (
                            <Link
                                href={route('dashboard')}
                                className="group flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                        <ShieldCheck size={18} />
                                    </div>
                                    <div>
                                        <p className="text-sm font-bold text-slate-800 group-hover:text-blue-700 transition-colors">
                                            Dashboard Pengurus
                                        </p>
                                        <p className="text-xs text-slate-400 mt-0.5">
                                            Akses panel manajemen admin & kas
                                        </p>
                                    </div>
                                </div>
                                <ChevronRight size={16} className="text-slate-400 group-hover:translate-x-0.5 transition-transform" />
                            </Link>
                        )}
                    </div>

                    {/* Logout Button */}
                    <div className="pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            onClick={() => router.post(route('logout'))}
                            className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-red-600 font-semibold text-xs sm:text-sm bg-red-50 hover:bg-red-100 active:bg-red-200 transition-colors"
                        >
                            <LogOut size={16} />
                            <span>Keluar dari Akun Koperasi</span>
                        </button>
                    </div>
                </div>
            </div>
        </AnggotaLayout>
    );
}
