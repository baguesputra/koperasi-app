import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { User, Building2, CalendarDays, Phone, MapPin } from 'lucide-react';
import Card from '@/Components/ui/Card';
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

export default function Edit({ pengguna }) {
    const inisial = pengguna?.name?.charAt(0)?.toUpperCase() ?? '?';
    const a = pengguna?.anggota;

    return (
        <AppLayout>
            <Head title="Profil Saya" />

            <Card className="mb-5">
                <div className="flex items-center gap-4">
                    <div className="w-14 h-14 rounded-2xl bg-brand-navy text-white flex items-center justify-center text-xl font-bold shrink-0">
                        {inisial}
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="text-xl font-bold text-slate-800 truncate">{pengguna?.name}</p>
                        <p className="text-sm text-slate-400">
                            {(pengguna?.roles ?? []).map((r) => labelRole[r] ?? r).join(', ') || '-'} • {pengguna?.no_karyawan}
                        </p>
                    </div>
                    {a?.status && <StatusBadge status={a.status} />}
                </div>
            </Card>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <Card>
                    <div className="flex items-center gap-2.5 mb-2">
                        <div className="w-9 h-9 rounded-lg bg-slate-50 text-slate-400 flex items-center justify-center">
                            <User size={16} />
                        </div>
                        <p className="text-base font-bold text-slate-700">Info Akun</p>
                    </div>
                    <Baris label="Nama Lengkap" value={pengguna?.name} />
                    <Baris label="No. Karyawan" value={pengguna?.no_karyawan} />
                    <Baris label="Email" value={pengguna?.email ?? '-'} />
                </Card>

                {a && (
                    <Card>
                        <div className="flex items-center gap-2.5 mb-2">
                            <div className="w-9 h-9 rounded-lg bg-slate-50 text-slate-400 flex items-center justify-center">
                                <Building2 size={16} />
                            </div>
                            <p className="text-base font-bold text-slate-700">Organisasi</p>
                        </div>
                        <Baris label="No. Anggota" value={a.no_anggota} />
                        <Baris label="Perusahaan" value={a.perusahaan} />
                        <Baris label="Departemen" value={a.department} />
                        <Baris label="Divisi" value={a.divisi} />
                        <Baris label="Jabatan" value={a.jabatan} />
                        <Baris label="Unit Bisnis" value={a.unit_bisnis} />
                        <Baris label="Cabang" value={a.cabang} />
                    </Card>
                )}

                {a && (
                    <Card>
                        <div className="flex items-center gap-2.5 mb-2">
                            <div className="w-9 h-9 rounded-lg bg-slate-50 text-slate-400 flex items-center justify-center">
                                <CalendarDays size={16} />
                            </div>
                            <p className="text-base font-bold text-slate-700">Keanggotaan & Kontak</p>
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
                    </Card>
                )}
            </div>

            <p className="text-xs text-slate-400 mt-5">
                Data dikelola melalui GATE. Untuk perubahan, perbarui data di GATE lalu hubungi Admin koperasi untuk sinkron ulang.
            </p>
        </AppLayout>
    );
}
