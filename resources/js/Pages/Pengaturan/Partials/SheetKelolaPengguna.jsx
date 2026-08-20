import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Pencil, Trash2, KeyRound, Ban, CheckCircle2, Search, Lock, ArrowLeft, Plus,
} from 'lucide-react';
import Button from '@/Components/ui/Button';
import FormField from '@/Components/ui/FormField';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';

const labelRole = { admin: 'Admin', bendahara: 'Bendahara', ketua_koperasi: 'Ketua', anggota: 'Anggota' };

export default function SheetKelolaPengguna({ pengguna, filterPengguna, daftarRole, tabAktif }) {
    const [view, setView] = useState('list');
    const [target, setTarget] = useState(null);
    const [cari, setCari] = useState(filterPengguna.cari ?? '');

    const tambah = useForm({ name: '', no_karyawan: '', email: '', role: '', password: '' });
    const edit = useForm({ name: '', no_karyawan: '', email: '', role: '', status: '' });
    const reset = useForm({ password: '', tanpa_wajib_ganti: false });

    function muat(params = {}) {
        router.get(route('pengaturan.index'), {
            tab: tabAktif,
            panel: 'kelola-pengguna',
            cari: params.cari ?? filterPengguna.cari ?? '',
            role: params.role ?? filterPengguna.role ?? '',
            status: params.status ?? filterPengguna.status ?? '',
            page: params.page ?? 1,
        }, { preserveState: true, replace: true });
    }

    function submitTambah(e) {
        e.preventDefault();
        tambah.post(route('pengaturan.pengguna.store'), {
            preserveScroll: true,
            onSuccess: () => {
                tambah.reset();
                setView('list');
            },
        });
    }

    function bukaEdit(user) {
        edit.setData({
            name: user.name,
            no_karyawan: user.no_karyawan,
            email: user.email ?? '',
            role: user.roles[0] ?? '',
            status: user.status,
        });
        setTarget(user);
        setView('edit');
    }

    function submitEdit(e) {
        e.preventDefault();
        edit.put(route('pengaturan.pengguna.update', target.id), {
            preserveScroll: true,
            onSuccess: () => setView('list'),
        });
    }

    function bukaReset(user) {
        reset.reset();
        setTarget(user);
        setView('reset');
    }

    function submitReset(e) {
        e.preventDefault();
        reset.post(route('pengaturan.pengguna.reset-password', target.id), {
            preserveScroll: true,
            onSuccess: () => setView('list'),
        });
    }

    function toggleStatus(user) {
        router.post(route('pengaturan.pengguna.toggle-status', user.id), {}, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function hapus(user) {
        if (confirm(`Hapus akun "${user.name}" (${user.no_karyawan})?`)) {
            router.delete(route('pengaturan.pengguna.destroy', user.id), {
                preserveScroll: true,
                preserveState: true,
            });
        }
    }

    const inisial = (nama) => nama.charAt(0).toUpperCase();

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
                {headerKecil('Tambah Pengguna')}
                <form onSubmit={submitTambah}>
                    <FormField label="Nama Lengkap" error={tambah.errors.name}>
                        <TextField size="sm" value={tambah.data.name} onChange={(e) => tambah.setData('name', e.target.value)} autoFocus />
                    </FormField>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <FormField label="No Karyawan" error={tambah.errors.no_karyawan} hint="Dipakai untuk login">
                            <TextField size="sm" value={tambah.data.no_karyawan} onChange={(e) => tambah.setData('no_karyawan', e.target.value)} />
                        </FormField>
                        <FormField label="Role" error={tambah.errors.role}>
                            <Select size="sm" value={tambah.data.role} onChange={(e) => tambah.setData('role', e.target.value)}>
                                <option value="">Pilih role</option>
                                {daftarRole.map((r) => (
                                    <option key={r} value={r}>{labelRole[r] ?? r}</option>
                                ))}
                            </Select>
                        </FormField>
                    </div>
                    <FormField label="Email" error={tambah.errors.email}>
                        <TextField size="sm" type="email" value={tambah.data.email} onChange={(e) => tambah.setData('email', e.target.value)} />
                    </FormField>
                    <FormField label="Password" error={tambah.errors.password} hint="Kosongkan agar memakai no karyawan (wajib ganti saat login pertama)">
                        <TextField size="sm" type="password" value={tambah.data.password} onChange={(e) => tambah.setData('password', e.target.value)} />
                    </FormField>
                    <div className="flex items-center gap-3 mt-4">
                        <Button type="submit" variant="primary" disabled={tambah.processing}>
                            {tambah.processing ? 'Menyimpan...' : 'Simpan Pengguna'}
                        </Button>
                        <Button type="button" variant="ghost" onClick={() => setView('list')}>
                            Batal
                        </Button>
                    </div>
                </form>
            </div>
        );
    }

    if (view === 'edit') {
        return (
            <div>
                {headerKecil(`Edit Pengguna - ${target?.name ?? ''}`)}
                <form onSubmit={submitEdit}>
                    <FormField label="Nama Lengkap" error={edit.errors.name}>
                        <TextField size="sm" value={edit.data.name} onChange={(e) => edit.setData('name', e.target.value)} />
                    </FormField>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <FormField label="No Karyawan" error={edit.errors.no_karyawan}>
                            <TextField size="sm" value={edit.data.no_karyawan} onChange={(e) => edit.setData('no_karyawan', e.target.value)} />
                        </FormField>
                        <FormField label="Role" error={edit.errors.role}>
                            <Select size="sm" value={edit.data.role} onChange={(e) => edit.setData('role', e.target.value)}>
                                <option value="">Pilih role</option>
                                {daftarRole.map((r) => (
                                    <option key={r} value={r}>{labelRole[r] ?? r}</option>
                                ))}
                            </Select>
                        </FormField>
                    </div>
                    <FormField label="Email" error={edit.errors.email}>
                        <TextField size="sm" type="email" value={edit.data.email} onChange={(e) => edit.setData('email', e.target.value)} />
                    </FormField>
                    <FormField label="Status" error={edit.errors.status}>
                        <Select size="sm" value={edit.data.status} onChange={(e) => edit.setData('status', e.target.value)}>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </Select>
                    </FormField>
                    {edit.errors.pengguna && <p className="text-sm font-medium text-red-600 mb-4">{edit.errors.pengguna}</p>}
                    <div className="flex items-center gap-3 mt-4">
                        <Button type="submit" variant="primary" disabled={edit.processing || target?.dilindungi}>
                            {edit.processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                        <Button type="button" variant="ghost" onClick={() => setView('list')}>
                            Batal
                        </Button>
                    </div>
                </form>
            </div>
        );
    }

    if (view === 'reset') {
        return (
            <div>
                {headerKecil(`Reset Password - ${target?.name ?? ''}`)}
                <form onSubmit={submitReset}>
                    <FormField label="Password Baru" error={reset.errors.password}>
                        <TextField size="sm" type="password" value={reset.data.password} onChange={(e) => reset.setData('password', e.target.value)} autoFocus />
                    </FormField>
                    <label className="flex items-center gap-2 text-sm font-semibold text-slate-600 mb-4">
                        <input
                            type="checkbox"
                            checked={reset.data.tanpa_wajib_ganti}
                            onChange={(e) => reset.setData('tanpa_wajib_ganti', e.target.checked)}
                            className="w-4 h-4 rounded border-slate-300 text-brand-green focus:ring-brand-green/30"
                        />
                        Jangan wajibkan ganti password saat login
                    </label>
                    <p className="text-sm text-slate-400 mb-4">
                        {reset.data.tanpa_wajib_ganti
                            ? 'Pengguna bisa langsung login dengan password baru.'
                            : 'Pengguna akan diminta mengganti password pada login berikutnya.'}
                    </p>
                    <div className="flex items-center gap-3">
                        <Button type="submit" variant="primary" disabled={reset.processing}>
                            {reset.processing ? 'Menyimpan...' : 'Reset Password'}
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
                    Tambah Pengguna
                </Button>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-4">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                    <input
                        type="text"
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && muat({ cari })}
                        placeholder="Cari nama / no karyawan / email..."
                        className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-300 bg-white focus:border-brand-green outline-none"
                    />
                </div>
                <Select size="sm" value={filterPengguna.role ?? ''} onChange={(e) => muat({ role: e.target.value })}>
                    <option value="">Semua Role</option>
                    {daftarRole.map((r) => (
                        <option key={r} value={r}>{labelRole[r] ?? r}</option>
                    ))}
                </Select>
                <Select size="sm" value={filterPengguna.status ?? ''} onChange={(e) => muat({ status: e.target.value })}>
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </Select>
            </div>

            {pengguna.data.length === 0 ? (
                <p className="text-sm text-slate-400 text-center py-8">Tidak ada pengguna yang cocok.</p>
            ) : (
                <div className="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden">
                    {pengguna.data.map((user) => (
                        <div key={user.id} className="flex items-center gap-3 px-4 py-3 bg-white">
                            <div className="w-10 h-10 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold shrink-0">
                                {inisial(user.name)}
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-semibold text-slate-800 flex items-center gap-2 truncate">
                                    {user.name}
                                    {user.dilindungi && <Lock size={13} className="text-slate-300 shrink-0" />}
                                    {user.harus_ganti_password && (
                                        <span className="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700">
                                            ganti pwd
                                        </span>
                                    )}
                                </p>
                                <p className="text-xs text-slate-400 truncate">
                                    {user.no_karyawan}{user.email ? ` • ${user.email}` : ''}
                                </p>
                            </div>
                            <div className="hidden sm:flex items-center gap-1.5 shrink-0">
                                {user.roles.map((role) => (
                                    <span key={role} className={`inline-flex items-center px-2 py-0.5 text-[11px] font-semibold rounded-full ${
                                        role === 'admin' ? 'bg-brand-navy text-white' : 'bg-slate-100 text-slate-600'
                                    }`}>
                                        {labelRole[role] ?? role}
                                    </span>
                                ))}
                                <span className={`inline-flex items-center px-2 py-0.5 text-[11px] font-semibold rounded-full ${
                                    user.status === 'aktif' ? 'bg-brand-green-light text-brand-green-dark' : 'bg-red-50 text-red-600'
                                }`}>
                                    {user.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                </span>
                            </div>
                            <div className="flex items-center gap-0.5 shrink-0">
                                <button onClick={() => bukaReset(user)} title="Reset password" className="p-1.5 rounded-lg text-slate-400 hover:text-brand-navy hover:bg-slate-100">
                                    <KeyRound size={15} />
                                </button>
                                <button onClick={() => bukaEdit(user)} title="Edit pengguna" className="p-1.5 rounded-lg text-slate-400 hover:text-brand-navy hover:bg-slate-100">
                                    <Pencil size={15} />
                                </button>
                                <button
                                    onClick={() => toggleStatus(user)}
                                    disabled={user.dilindungi}
                                    title={user.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'}
                                    className={`p-1.5 rounded-lg ${
                                        user.dilindungi
                                            ? 'text-slate-200 cursor-not-allowed'
                                            : user.status === 'aktif'
                                                ? 'text-slate-400 hover:text-red-600 hover:bg-slate-100'
                                                : 'text-slate-400 hover:text-brand-green hover:bg-slate-100'
                                    }`}
                                >
                                    {user.status === 'aktif' ? <Ban size={15} /> : <CheckCircle2 size={15} />}
                                </button>
                                <button
                                    onClick={() => hapus(user)}
                                    disabled={user.dilindungi}
                                    title="Hapus"
                                    className={`p-1.5 rounded-lg ${
                                        user.dilindungi
                                            ? 'text-slate-200 cursor-not-allowed'
                                            : 'text-slate-400 hover:text-red-600 hover:bg-slate-100'
                                    }`}
                                >
                                    <Trash2 size={15} />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {pengguna.last_page > 1 && (
                <div className="flex items-center justify-between mt-4 text-sm">
                    <button
                        disabled={!pengguna.prev_page_url}
                        onClick={() => muat({ page: pengguna.current_page - 1 })}
                        className="px-3 py-1.5 rounded-lg font-semibold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        ← Sebelumnya
                    </button>
                    <span className="text-slate-500 font-semibold">
                        Halaman {pengguna.current_page} dari {pengguna.last_page}
                    </span>
                    <button
                        disabled={!pengguna.next_page_url}
                        onClick={() => muat({ page: pengguna.current_page + 1 })}
                        className="px-3 py-1.5 rounded-lg font-semibold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        Berikutnya →
                    </button>
                </div>
            )}
        </div>
    );
}