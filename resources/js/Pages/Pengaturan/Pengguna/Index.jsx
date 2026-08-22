import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Plus, Pencil, Trash2, KeyRound, Ban, CheckCircle2, Search, Lock,
} from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import Drawer from '@/Components/ui/Drawer';
import FormField from '@/Components/ui/FormField';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';
import Breadcrumb from '@/Components/ui/Breadcrumb';
import Pagination from '@/Components/ui/Pagination';

const labelRole = { admin: 'Admin', bendahara: 'Bendahara', ketua_koperasi: 'Ketua', anggota: 'Anggota' };

export default function Index({ pengguna, daftarRole, filters }) {
    const [showTambah, setShowTambah] = useState(false);
    const [editUser, setEditUser] = useState(null);
    const [resetUser, setResetUser] = useState(null);
    const [cari, setCari] = useState(filters.cari ?? '');

    const tambah = useForm({ name: '', no_karyawan: '', email: '', role: '', password: '' });
    const edit = useForm({ name: '', no_karyawan: '', email: '', role: '', status: '' });
    const reset = useForm({ password: '', tanpa_wajib_ganti: false });

    function terapkanFilter({ role, status, cari: nilaiCari }) {
        router.get(
            route('pengaturan.pengguna.index'),
            { cari: nilaiCari ?? cari, role: role ?? filters.role ?? '', status: status ?? filters.status ?? '' },
            { preserveState: true, replace: true }
        );
    }

    function submitTambah(e) {
        e.preventDefault();
        tambah.post(route('pengaturan.pengguna.store'), {
            onSuccess: () => {
                tambah.reset();
                setShowTambah(false);
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
        setEditUser(user);
    }

    function submitEdit(e) {
        e.preventDefault();
        edit.put(route('pengaturan.pengguna.update', editUser.id), {
            onSuccess: () => setEditUser(null),
        });
    }

    function bukaReset(user) {
        reset.reset();
        setResetUser(user);
    }

    function submitReset(e) {
        e.preventDefault();
        reset.post(route('pengaturan.pengguna.reset-password', resetUser.id), {
            onSuccess: () => {
                reset.reset();
                setResetUser(null);
            },
        });
    }

    function toggleStatus(user) {
        router.post(route('pengaturan.pengguna.toggle-status', user.id));
    }

    function hapus(user) {
        if (confirm(`Hapus akun "${user.name}" (${user.no_karyawan})?`)) {
            router.delete(route('pengaturan.pengguna.destroy', user.id));
        }
    }

    const inisial = (nama) => nama.charAt(0).toUpperCase();

    return (
        <AppLayout>
            <Head title="Kelola Pengguna" />

            <Breadcrumb
                items={[
                    { label: 'Pengaturan', href: route('pengaturan.index') },
                    { label: 'Kelola Pengguna' },
                ]}
            />

            <div className="flex items-center justify-between flex-wrap gap-4 mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-800">Kelola Pengguna</h1>
                    <p className="text-base text-slate-400 mt-1">
                        Atur akun login, role, dan status pengguna sistem
                    </p>
                </div>
                <Button variant="primary" onClick={() => setShowTambah(true)}>
                    <Plus size={18} />
                    Tambah Pengguna
                </Button>
            </div>

            <Card className="mb-5">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div className="relative">
                        <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                        <input
                            type="text"
                            value={cari}
                            onChange={(e) => setCari(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && terapkanFilter({ cari })}
                            placeholder="Cari nama, no karyawan, atau email..."
                            className="w-full pl-10 pr-4 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none"
                        />
                    </div>
                    <Select
                        size="md"
                        value={filters.role ?? ''}
                        onChange={(e) => terapkanFilter({ role: e.target.value })}
                    >
                        <option value="">Semua Role</option>
                        {daftarRole.map((r) => (
                            <option key={r} value={r}>{labelRole[r] ?? r}</option>
                        ))}
                    </Select>
                    <Select
                        size="md"
                        value={filters.status ?? ''}
                        onChange={(e) => terapkanFilter({ status: e.target.value })}
                    >
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </Select>
                </div>
            </Card>

            <Card padding="none">
                {pengguna.data.length === 0 ? (
                    <p className="text-base text-slate-400 text-center py-10">
                        Tidak ada pengguna yang cocok.
                    </p>
                ) : (
                    <div className="divide-y divide-slate-50">
                        {pengguna.data.map((user) => (
                            <div key={user.id} className="flex items-center gap-4 px-5 py-4">
                                <div className="w-11 h-11 rounded-full bg-brand-green text-white flex items-center justify-center text-sm font-bold shrink-0">
                                    {inisial(user.name)}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-base font-semibold text-slate-800 flex items-center gap-2 truncate">
                                        {user.name}
                                        {user.dilindungi && (
                                            <span title="Akun utama sistem">
                                                <Lock size={14} className="text-slate-300" />
                                            </span>
                                        )}
                                        {user.harus_ganti_password && (
                                            <span className="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700">
                                                Perlu ganti password
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-sm text-slate-400 truncate">
                                        {user.no_karyawan}{user.email ? ` \u2022 ${user.email}` : ''}
                                        {user.anggota && ` \u2022 Anggota ${user.anggota.no_anggota}`}
                                    </p>
                                </div>
                                <div className="hidden md:flex items-center gap-1.5">
                                    {user.roles.map((role) => (
                                        <span key={role} className={`inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full ${
                                            role === 'admin' ? 'bg-brand-navy text-white' : 'bg-slate-100 text-slate-600'
                                        }`}>
                                            {labelRole[role] ?? role}
                                        </span>
                                    ))}
                                    <span className={`inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full ${
                                        user.status === 'aktif' ? 'bg-brand-green-light text-brand-green-dark' : 'bg-red-50 text-red-600'
                                    }`}>
                                        {user.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                    </span>
                                </div>
                                <div className="flex items-center gap-1 shrink-0">
                                    <button
                                        onClick={() => bukaReset(user)}
                                        title="Reset password"
                                        className="p-2 rounded-lg text-slate-400 hover:text-brand-navy hover:bg-slate-100 transition-colors"
                                    >
                                        <KeyRound size={16} />
                                    </button>
                                    <button
                                        onClick={() => bukaEdit(user)}
                                        title="Edit pengguna"
                                        className="p-2 rounded-lg text-slate-400 hover:text-brand-navy hover:bg-slate-100 transition-colors"
                                    >
                                        <Pencil size={16} />
                                    </button>
                                    <button
                                        onClick={() => toggleStatus(user)}
                                        disabled={user.dilindungi}
                                        title={user.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'}
                                        className={`p-2 rounded-lg transition-colors ${
                                            user.dilindungi
                                                ? 'text-slate-200 cursor-not-allowed'
                                                : user.status === 'aktif'
                                                    ? 'text-slate-400 hover:text-red-600 hover:bg-slate-100'
                                                    : 'text-slate-400 hover:text-brand-green hover:bg-slate-100'
                                        }`}
                                    >
                                        {user.status === 'aktif' ? <Ban size={16} /> : <CheckCircle2 size={16} />}
                                    </button>
                                    <button
                                        onClick={() => hapus(user)}
                                        disabled={user.dilindungi}
                                        title="Hapus"
                                        className={`p-2 rounded-lg transition-colors ${
                                            user.dilindungi
                                                ? 'text-slate-200 cursor-not-allowed'
                                                : 'text-slate-400 hover:text-red-600 hover:bg-slate-100'
                                        }`}
                                    >
                                        <Trash2 size={16} />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </Card>

            <Pagination links={pengguna.links} />

            <Drawer show={showTambah} title="Tambah Pengguna" onClose={() => setShowTambah(false)}>
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
                        <Button type="button" variant="ghost" onClick={() => setShowTambah(false)}>
                            Batal
                        </Button>
                    </div>
                </form>
            </Drawer>

            <Drawer show={editUser !== null} title="Edit Pengguna" onClose={() => setEditUser(null)}>
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
                    {edit.errors.pengguna && (
                        <p className="text-sm font-medium text-red-600 mb-4">{edit.errors.pengguna}</p>
                    )}
                    <div className="flex items-center gap-3 mt-4">
                        <Button type="submit" variant="primary" disabled={edit.processing || editUser?.dilindungi}>
                            {edit.processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                        <Button type="button" variant="ghost" onClick={() => setEditUser(null)}>
                            Batal
                        </Button>
                    </div>
                </form>
            </Drawer>

            <Drawer show={resetUser !== null} title={`Reset Password - ${resetUser?.name ?? ''}`} onClose={() => setResetUser(null)}>
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
                        <Button type="button" variant="ghost" onClick={() => setResetUser(null)}>
                            Batal
                        </Button>
                    </div>
                </form>
            </Drawer>
        </AppLayout>
    );
}