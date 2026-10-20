import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import {
    User, Building2, CalendarDays, Phone, MapPin,
    ArrowUpRight, PiggyBank, HandCoins,
    Gauge, ShieldCheck, Mail, Briefcase, Clock,
} from 'lucide-react';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';
import PageHeader from '@/Components/ui/PageHeader';
import { formatRupiah } from '@/Utils/formatCurrency';

const labelRole = {
    admin: 'Administrator',
    bendahara: 'Bendahara Koperasi',
    ketua_koperasi: 'Ketua Koperasi',
    pengawas: 'Pengawas Koperasi',
    anggota: 'Anggota Koperasi',
};

function ItemInfo({ ikon: Icon, label, value }) {
    return (
        <div className="flex items-start gap-3 py-2.5 border-b border-slate-100 last:border-0">
            {Icon && (
                <div className="w-8 h-8 rounded-lg bg-slate-50 border border-slate-100 text-slate-500 flex items-center justify-center shrink-0 mt-0.5">
                    <Icon size={15} />
                </div>
            )}
            <div className="min-w-0 flex-1">
                <p className="text-xs font-medium text-slate-400">{label}</p>
                <p className="text-sm font-semibold text-slate-800 break-words mt-0.5">{value || '-'}</p>
            </div>
        </div>
    );
}

export default function Edit({ pengguna, ringkasan }) {
    const inisial = pengguna?.name?.charAt(0)?.toUpperCase() ?? '?';
    const a = pengguna?.anggota;
    const roles = pengguna?.roles ?? [];
    const isPengurus = roles.some((r) => ['admin', 'bendahara', 'ketua_koperasi'].includes(r));

    return (
        <AppLayout>
            <Head title="Profil Saya" />

            <PageHeader
                title="Profil Saya"
                subtitle="Identitas akun, hak akses pengurus, dan data keanggotaan terdaftar"
            />

            {/* Header Profil Pengurus */}
            <Card className="mb-5 border-slate-200/80 shadow-sm">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-4 min-w-0">
                        {a?.foto_url ? (
                            <FotoAnggota nama={pengguna?.name} fotoUrl={a.foto_url} ukuran="lg" />
                        ) : (
                            <div className="w-16 h-16 rounded-2xl bg-brand-navy text-white flex items-center justify-center text-xl font-bold shrink-0 shadow-sm">
                                {inisial}
                            </div>
                        )}
                        <div className="min-w-0 flex-1">
                            <div className="flex items-center gap-2.5 flex-wrap">
                                <h2 className="text-xl font-bold text-slate-800 truncate">{pengguna?.name}</h2>
                                {a?.status && <StatusBadge status={a.status} />}
                            </div>
                            <div className="flex items-center gap-2 flex-wrap mt-2">
                                {roles.map((r) => (
                                    <span
                                        key={r}
                                        className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-navy text-white shadow-xs"
                                    >
                                        {labelRole[r] ?? r}
                                    </span>
                                ))}
                                {pengguna?.no_karyawan && (
                                    <span className="text-xs text-slate-500 font-medium">
                                        NIK: <span className="font-mono text-slate-700 font-semibold">{pengguna.no_karyawan}</span>
                                    </span>
                                )}
                                {a?.no_anggota && (
                                    <span className="text-xs text-slate-500 font-medium">
                                        • No. Anggota: <span className="font-mono text-slate-700 font-semibold">{a.no_anggota}</span>
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {isPengurus && (
                        <Link
                            href={route('portal.dashboard')}
                            className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-brand-navy bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors shrink-0"
                        >
                            Buka Portal Anggota
                            <ArrowUpRight size={15} />
                        </Link>
                    )}
                </div>
            </Card>

            {/* Ringkasan Personal Pengurus */}
            {ringkasan && isPengurus && (
                <Card className="mb-5 border-slate-200/80 shadow-sm">
                    <div className="flex items-center justify-between gap-3 mb-3 pb-2.5 border-b border-slate-100">
                        <div>
                            <h3 className="text-sm font-bold text-slate-800">Ringkasan Personal</h3>
                            <p className="text-xs text-slate-400 mt-0.5">Status simpanan, pinjaman, dan antrean pengajuan akun Anda</p>
                        </div>
                        <span className="text-xs font-semibold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">
                            Read-only
                        </span>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        {/* Simpanan */}
                        <div className="bg-slate-50/80 rounded-xl border border-slate-200/70 p-3.5 flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <PiggyBank size={20} />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-medium text-slate-500">Simpanan Terkumpul</p>
                                <p className="text-base font-bold text-slate-900 mt-0.5 tabular-nums truncate">
                                    {formatRupiah(ringkasan.simpanan_total)}
                                </p>
                                <p className="text-[11px] text-slate-400">Pokok + Wajib</p>
                            </div>
                        </div>

                        {/* Pinjaman */}
                        <div className="bg-slate-50/80 rounded-xl border border-slate-200/70 p-3.5 flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-brand-navy/5 border border-brand-navy/10 text-brand-navy flex items-center justify-center shrink-0">
                                <HandCoins size={20} />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-medium text-slate-500">Pinjaman Personal</p>
                                <p className="text-base font-bold text-slate-900 mt-0.5 tabular-nums">
                                    {ringkasan.pinjaman_total} Pengajuan
                                </p>
                                <p className="text-[11px] text-slate-400">{ringkasan.pinjaman_aktif} aktif</p>
                            </div>
                        </div>

                        {/* Limit */}
                        <div className="bg-slate-50/80 rounded-xl border border-slate-200/70 p-3.5 flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center shrink-0">
                                <Gauge size={20} />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-medium text-slate-500">Pengajuan Limit</p>
                                <p className="text-base font-bold text-slate-900 mt-0.5 tabular-nums">
                                    {ringkasan.limit_berjalan} Berjalan
                                </p>
                                <p className="text-[11px] text-slate-400">
                                    {ringkasan.limit_berjalan > 0 ? 'Dalam peninjauan' : 'Tidak ada antrean'}
                                </p>
                            </div>
                        </div>

                        {/* Tenor */}
                        <div className="bg-slate-50/80 rounded-xl border border-slate-200/70 p-3.5 flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center shrink-0">
                                <Clock size={20} />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-medium text-slate-500">Perubahan Tenor</p>
                                <p className="text-base font-bold text-slate-900 mt-0.5 tabular-nums">
                                    {ringkasan.tenor_berjalan} Berjalan
                                </p>
                                <p className="text-[11px] text-slate-400">
                                    {ringkasan.tenor_berjalan > 0 ? 'Menunggu keputusan' : 'Tidak ada antrean'}
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>
            )}

            {/* Detail Informasi 2 Kolom: Organisasi vs Keanggotaan & Kontak */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
                {/* Organisasi & Penempatan */}
                <Card className="border-slate-200/80 shadow-sm">
                    <div className="flex items-center gap-2.5 pb-3 mb-2 border-b border-slate-100">
                        <div className="w-8 h-8 rounded-lg bg-brand-navy/5 text-brand-navy flex items-center justify-center shrink-0">
                            <Building2 size={16} />
                        </div>
                        <div>
                            <h3 className="text-sm font-bold text-slate-800">Organisasi &amp; Penempatan Kerja</h3>
                            <p className="text-xs text-slate-400">Struktur posisi dan lokasi penugasan</p>
                        </div>
                    </div>

                    <div className="divide-y divide-slate-100">
                        <ItemInfo ikon={Building2} label="Perusahaan" value={a?.perusahaan} />
                        <ItemInfo ikon={Briefcase} label="Unit Bisnis" value={a?.unit_bisnis} />
                        <ItemInfo ikon={MapPin} label="Cabang" value={a?.cabang} />
                        <ItemInfo ikon={User} label="Departemen" value={a?.department} />
                        <ItemInfo ikon={Building2} label="Divisi" value={a?.divisi} />
                        <ItemInfo ikon={Briefcase} label="Jabatan" value={a?.jabatan} />
                    </div>
                </Card>

                {/* Keanggotaan & Kontak Akun */}
                <Card className="border-slate-200/80 shadow-sm">
                    <div className="flex items-center gap-2.5 pb-3 mb-2 border-b border-slate-100">
                        <div className="w-8 h-8 rounded-lg bg-brand-navy/5 text-brand-navy flex items-center justify-center shrink-0">
                            <CalendarDays size={16} />
                        </div>
                        <div>
                            <h3 className="text-sm font-bold text-slate-800">Keanggotaan &amp; Kontak</h3>
                            <p className="text-xs text-slate-400">Data kepegawaian dan kontak akun terdaftar</p>
                        </div>
                    </div>

                    <div className="divide-y divide-slate-100">
                        <ItemInfo ikon={CalendarDays} label="Tanggal Jadi Anggota" value={a?.tanggal_jadi_anggota} />
                        <ItemInfo ikon={Clock} label="Masa Keanggotaan" value={a?.lama_keanggotaan_tahun ? `${a.lama_keanggotaan_tahun} tahun` : null} />
                        <ItemInfo ikon={CalendarDays} label="Tanggal Mulai Kerja" value={a?.tanggal_mulai_kerja} />
                        <ItemInfo ikon={Mail} label="Email Akun" value={pengguna?.email} />
                        <ItemInfo ikon={Phone} label="No. Handphone" value={a?.no_hp} />
                        <ItemInfo ikon={MapPin} label="Alamat Domisili" value={a?.alamat} />
                    </div>
                </Card>
            </div>

            {/* Banner Sinkronisasi GATE */}
            <div className="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 flex items-start gap-3.5 text-xs text-slate-500">
                <ShieldCheck size={20} className="text-brand-navy shrink-0 mt-0.5" />
                <div className="leading-relaxed">
                    <p className="font-semibold text-slate-700">Data Dikelola Terpusat Melalui GATE SSO</p>
                    <p className="mt-0.5 text-slate-500">
                        Identitas pengguna, jabatan, dan struktur keanggotaan dikelola terpusat oleh GATE.
                        Pembaruan data personal atau mutasi organisasi di GATE akan tersinkronisasi secara otomatis pada sistem koperasi.
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
