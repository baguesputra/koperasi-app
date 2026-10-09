import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { User, Building2, CalendarDays, Phone, MapPin, CreditCard, TrendingUp, FileText, Clock, HeartHandshake, ArrowRight } from 'lucide-react';
import Card from '@/Components/ui/Card';
import FotoAnggota from '@/Components/ui/FotoAnggota';
import StatusBadge from '@/Components/ui/StatusBadge';

const labelRole = { admin: 'Admin', bendahara: 'Bendahara', ketua_koperasi: 'Ketua', anggota: 'Anggota' };

function Baris({ label, value }) {
    return (
        <div className="flex items-start justify-between gap-4 py-2.5 border-b border-slate-50 last:border-0">
            <p className="text-sm text-slate-400 shrink-0">{label}</p>
            <p className="text-sm font-semibold text-slate-700 text-right break-words">{value ?? '-'}</p>
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

            {/* Name Card */}
            <Card className="mb-5 relative overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-brand-navy via-brand-green to-brand-navy" aria-hidden="true" />
                <div className="flex items-center gap-4">
                    {a?.foto_url ? (
                        <FotoAnggota nama={pengguna?.name} fotoUrl={a.foto_url} ukuran="lg" />
                    ) : (
                        <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-navy to-brand-navy-light text-white flex items-center justify-center text-xl font-bold shrink-0 shadow-sm">
                            {inisial}
                        </div>
                    )}
                    <div className="min-w-0 flex-1">
                        <p className="text-xl font-bold text-slate-800 truncate">{pengguna?.name}</p>
                        <p className="text-sm text-slate-400">
                            {(pengguna?.roles ?? []).map((r) => labelRole[r] ?? r).join(', ') || '-'} • {pengguna?.no_karyawan}
                        </p>
                    </div>
                    {a?.status && <StatusBadge status={a.status} />}
                </div>
            </Card>

            {/* Summary Section for Pengurus */}
            {ringkasan && isPengurus && (
                <Card className="mb-5">
                    <div className="flex items-center justify-between gap-3 mb-1">
                        <p className="text-base font-bold text-slate-700">Ringkasan Personal</p>
                        <span className="text-xs font-semibold text-slate-400 bg-slate-50 px-2.5 py-1 rounded-full">Read-only</span>
                    </div>
                    <p className="text-sm text-slate-400 mb-3">Pinjaman, simpanan, tenor, limit, dan santunan — detail lengkap di portal anggota.</p>

                    <div className="grid grid-cols-2 lg:grid-cols-5 gap-2 px-3">
                        <div>
                            <div className="text-xs text-slate-500">Pinjaman</div>
                            <div className="text-2xl font-bold text-slate-800">{ringkasan.pinjaman_total} pengajuan</div>
                            <div className="text-xs text-slate-400">{ringkasan.pinjaman_aktif} aktif</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-500">Simpanan</div>
                            <div className="text-2xl font-bold text-brand-green-light">Rp{new Intl.NumberFormat('id-ID').format(ringkasan.simpanan_total)}</div>
                            <div className="text-xs text-slate-400">Pokok + Wajib</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-500">Limit</div>
                            <div className="text-2xl font-bold text-amber-600">{ringkasan.limit_berjalan} berjalan</div>
                            <div className="text-xs text-slate-400">Pengajuan</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-500">Tenor</div>
                            <div className="text-2xl font-bold text-purple-600">{ringkasan.tenor_berjalan} berjalan</div>
                            <div className="text-xs text-slate-400">Perubahan</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-500">Santunan</div>
                            <div className="text-2xl font-bold text-pink-600">{ringkasan.santunan_berjalan} berjalan</div>
                            <div className="text-xs text-slate-400">Klaim</div>
                        </div>
                    </div>

                    <Link
                        href={route('portal.dashboard')}
                        className="mt-3 inline-flex w-full sm:w-auto items-center justify-center gap-2 min-h-[44px] px-5 text-sm font-bold rounded-xl bg-brand-navy text-white hover:bg-brand-navy-light transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green focus-visible:ring-offset-2"
                    >
                        Buka Portal Anggota
                        <ArrowRight size={16} />
                    </Link>
                </Card>
            )}

            {/* Merged Card for all users */}
            <Card>
                <div className="flex items-center gap-2.5 mb-2">
                    <div className="w-9 h-9 rounded-lg bg-slate-50 text-slate-400 flex items-center justify-center">
                        <User size={16} />
                    </div>
                    <p className="text-base font-bold text-slate-700">Profil Lengkap</p>
                </div>

                {/* Info Akun */}
                <Baris label="Nama Lengkap" value={pengguna?.name} />
                <Baris label="No. Karyawan" value={pengguna?.no_karyawan} />
                <Baris label="Email" value={pengguna?.email ?? '-'} />

                {a && (
                    <>
                        {/* Divider */}
                        <div className="h-px bg-slate-100 my-3" aria-hidden="true" />
                        {/* Organisasi */}
                        <div className="flex items-center gap-2 text-sm font-semibold text-slate-700 py-2">
                            <Building2 size={14} className="text-slate-400" />
                            Organisasi
                        </div>
                        <Baris label="No. Anggota" value={a.no_anggota} />
                        <Baris label="Perusahaan" value={a.perusahaan} />
                        <Baris label="Departemen" value={a.department} />
                        <Baris label="Divisi" value={a.divisi} />
                        <Baris label="Jabatan" value={a.jabatan} />
                        <Baris label="Unit Bisnis" value={a.unit_bisnis} />
                        <Baris label="Cabang" value={a.cabang} />

                        {/* Divider */}
                        <div className="h-px bg-slate-100 my-3" aria-hidden="true" />
                        {/* Keanggotaan & Kontak */}
                        <div className="flex items-center gap-2 text-sm font-semibold text-slate-700 py-2">
                            <CalendarDays size={14} className="text-slate-400" />
                            Keanggotaan & Kontak
                        </div>
                        <Baris label="Tanggal Jadi Anggota" value={a.tanggal_jadi_anggota} />
                        <Baris label="Lama Keanggotaan" value={`${a.lama_keanggotaan_tahun} tahun`} />
                        <Baris label="Tanggal Mulai Kerja" value={a.tanggal_mulai_kerja} />
                        <div className="flex items-start gap-2 py-2.5 border-b border-slate-50">
                            <Phone size={14} className="text-slate-300 mt-0.5 shrink-0" />
                            <div className="flex-1 min-w-0">
                                <p className="text-xs text-slate-400">No. HP</p>
                                <p className="text-sm font-semibold text-slate-700">{a.no_hp ?? '-'}</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-2 py-2.5">
                            <MapPin size={14} className="text-slate-300 mt-0.5 shrink-0" />
                            <div className="flex-1 min-w-0">
                                <p className="text-xs text-slate-400">Alamat</p>
                                <p className="text-sm font-semibold text-slate-700 break-words">{a.alamat ?? '-'}</p>
                            </div>
                        </div>
                    </>
                )}
            </Card>

            <p className="text-xs text-slate-400 mt-5">
                Data dikelola melalui GATE. Untuk perubahan, perbarui data di GATE lalu hubungi Admin koperasi untuk sinkron ulang.
            </p>
        </AppLayout>
    );
}
