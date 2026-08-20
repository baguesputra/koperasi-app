import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, Lock, Plus, Pencil, Trash2 } from 'lucide-react';
import Button from '@/Components/ui/Button';
import FormField from '@/Components/ui/FormField';
import TextField from '@/Components/ui/TextField';

const kelompokLabel = {
    anggota: 'Anggota',
    simpanan: 'Simpanan',
    pinjaman: 'Pinjaman',
    angsuran: 'Angsuran',
    kas: 'Kas Koperasi',
    laporan: 'Laporan',
    pengaturan: 'Pengaturan',
    portal: 'Portal Anggota',
    user: 'Akun & Hak Akses',
};

const permissionLabel = {
    'anggota.lihat': 'Lihat data anggota',
    'anggota.kelola': 'Tambah / ubah data anggota',
    'simpanan.lihat': 'Lihat data simpanan',
    'simpanan.konfirmasi': 'Konfirmasi simpanan bulanan',
    'pinjaman.lihat': 'Lihat semua data pinjaman',
    'pinjaman.tinjau-bendahara': 'Tinjau & setujui pinjaman (tahap Bendahara)',
    'pinjaman.approve-ketua': 'Setujui final pinjaman (tahap Ketua)',
    'angsuran.konfirmasi': 'Konfirmasi pembayaran angsuran',
    'kas.lihat': 'Lihat saldo & riwayat kas',
    'kas.topup': 'Tambah saldo kas koperasi',
    'laporan.lihat': 'Akses halaman laporan',
    'pengaturan.kelola': 'Kelola pengaturan sistem',
    'user.kelola': 'Kelola akun pengguna',
    'portal.akses': 'Akses portal anggota',
};

export default function SheetKelolaRole({ roleList, semuaPermission }) {
    const [view, setView] = useState('list');
    const [target, setTarget] = useState(null);

    const buat = useForm({ name: '' });
    const akses = useForm({ permissions: [] });

    function submitBuat(e) {
        e.preventDefault();
        buat.post(route('role.store'), {
            preserveScroll: true,
            onSuccess: () => {
                buat.reset();
                setView('list');
            },
        });
    }

    function bukaAkses(role) {
        akses.setData('permissions', role.permissions);
        setTarget(role);
        setView('akses');
    }

    function togglePermission(name) {
        akses.setData('permissions',
            akses.data.permissions.includes(name)
                ? akses.data.permissions.filter((p) => p !== name)
                : [...akses.data.permissions, name]
        );
    }

    function submitAkses(e) {
        e.preventDefault();
        akses.put(route('role.update', target.id), {
            preserveScroll: true,
            onSuccess: () => setView('list'),
        });
    }

    function hapus(role) {
        if (confirm(`Hapus role "${role.name}"?`)) {
            router.delete(route('role.destroy', role.id), {
                preserveScroll: true,
                preserveState: true,
            });
        }
    }

    const headerKecil = (children) => (
        <div className="flex items-center gap-2 mb-4">
            <button
                onClick={() => setView('list')}
                className="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-brand-navy transition-colors"
            >
                <ArrowLeft size={16} />
            </button>
            <h3 className="text-base font-bold text-slate-800">{children}</h3>
        </div>
    );

    if (view === 'tambah') {
        return (
            <div>
                {headerKecil('Tambah Role')}
                <form onSubmit={submitBuat}>
                    <FormField label="Nama Role" error={buat.errors.name} hint="Gunakan tanda hubung untuk kata terpisah, contoh: admin-cabang">
                        <TextField size="sm" value={buat.data.name} onChange={(e) => buat.setData('name', e.target.value)} autoFocus />
                    </FormField>
                    <div className="flex items-center gap-3">
                        <Button type="submit" variant="primary" disabled={buat.processing}>
                            {buat.processing ? 'Menyimpan...' : 'Buat Role'}
                        </Button>
                        <Button type="button" variant="ghost" onClick={() => setView('list')}>
                            Batal
                        </Button>
                    </div>
                </form>
            </div>
        );
    }

    if (view === 'akses') {
        return (
            <div>
                <div className="flex items-center gap-2 mb-1">
                    <button
                        onClick={() => setView('list')}
                        className="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-brand-navy transition-colors"
                    >
                        <ArrowLeft size={16} />
                    </button>
                    <h3 className="text-base font-bold text-slate-800 capitalize flex items-center gap-2">
                        {target?.name.replace('_', ' ')}
                        {target?.dilindungi && <Lock size={14} className="text-slate-300" />}
                    </h3>
                </div>
                <p className="text-sm text-slate-400 mb-4">
                    Pilih hak akses yang dimiliki role ini. {target?.jumlah_user} pengguna memakai role ini.
                </p>

                <form onSubmit={submitAkses}>
                    <div className="space-y-4 mb-4 max-h-[55vh] overflow-y-auto pr-1">
                        {Object.entries(semuaPermission).map(([kelompok, items]) => (
                            <div key={kelompok} className="border border-slate-100 rounded-xl p-4">
                                <p className="text-sm font-bold text-slate-700 mb-2.5">
                                    {kelompokLabel[kelompok] ?? kelompok}
                                </p>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                                    {items.map((permission) => (
                                        <label key={permission.name} className="flex items-center gap-2.5 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={akses.data.permissions.includes(permission.name)}
                                                onChange={() => togglePermission(permission.name)}
                                                className="w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-brand-green/30"
                                            />
                                            <span className="text-sm text-slate-700">
                                                {permissionLabel[permission.name] ?? permission.name}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>

                    {akses.errors.permissions && (
                        <p className="text-sm font-medium text-red-600 mb-3">{akses.errors.permissions}</p>
                    )}

                    <div className="flex items-center gap-3">
                        <Button type="submit" variant="primary" disabled={akses.processing}>
                            {akses.processing ? 'Menyimpan...' : 'Simpan Hak Akses'}
                        </Button>
                        <Button type="button" variant="ghost" onClick={() => setView('list')}>
                            Batal
                        </Button>
                    </div>
                </form>
            </div>
        );
    }

    return (
        <div>
            <div className="flex items-center justify-between flex-wrap gap-2 mb-4">
                <Button variant="outline" size="sm" onClick={() => setView('tambah')}>
                    <Plus size={16} />
                    Tambah Role
                </Button>
            </div>

            <div className="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden">
                {roleList.length === 0 ? (
                    <p className="text-sm text-slate-400 text-center py-8">Belum ada role.</p>
                ) : (
                    roleList.map((role) => (
                        <div key={role.id} className="flex items-center gap-3 px-4 py-3 bg-white">
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-semibold text-slate-800 capitalize flex items-center gap-2">
                                    {role.name.replace('_', ' ')}
                                    {role.dilindungi && <Lock size={13} className="text-slate-300" />}
                                </p>
                                <p className="text-xs text-slate-400">{role.jumlah_user} pengguna</p>
                            </div>
                            <div className="flex items-center gap-1 shrink-0">
                                <button
                                    onClick={() => bukaAkses(role)}
                                    className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-sm font-semibold text-brand-navy hover:bg-slate-100 transition-colors"
                                >
                                    <Pencil size={14} />
                                    Atur Akses
                                </button>
                                {!role.dilindungi && (
                                    <button
                                        onClick={() => hapus(role)}
                                        title="Hapus"
                                        className="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-slate-100"
                                    >
                                        <Trash2 size={15} />
                                    </button>
                                )}
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
}