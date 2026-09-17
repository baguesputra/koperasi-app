import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import useDebouncedValue from '@/Utils/useDebouncedValue';
import {
    Pencil, Trash2, KeyRound, Ban, CheckCircle2, Search, Lock, ArrowLeft, Plus, X, RefreshCw,
} from 'lucide-react';
import Button from '@/Components/ui/Button';
import FormField from '@/Components/ui/FormField';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';
import KataSandi from '@/Components/ui/KataSandi';
import Pagination from '@/Components/ui/Pagination';

const labelRole = { admin: 'Admin', bendahara: 'Bendahara', ketua_koperasi: 'Ketua', anggota: 'Anggota' };

function TombolAksi({ label, title, onClick, tone, disabled }) {
    const tones = {
        navy: 'hover:text-brand-navy hover:bg-slate-100',
        green: 'hover:text-brand-green-dark hover:bg-brand-green-light',
        red: 'hover:text-red-600 hover:bg-red-50',
    };
    return (
        <button
            onClick={onClick}
            disabled={disabled}
            aria-label={label}
            title={title}
            className={`inline-flex items-center justify-center w-9 h-9 rounded-xl border border-transparent text-slate-400 transition-all duration-200 ${tones[tone]} ${disabled ? 'opacity-30 cursor-not-allowed' : 'hover:border-slate-200 hover:shadow-sm'}`}
        >
            {title === 'Reset password' ? <KeyRound size={15} /> : null}
            {title === 'Edit pengguna' ? <Pencil size={15} /> : null}
            {title === 'Nonaktifkan' || title === 'Aktifkan' ? (title === 'Nonaktifkan' ? <Ban size={15} /> : <CheckCircle2 size={15} />) : null}
            {title === 'Hapus' ? <Trash2 size={15} /> : null}
        </button>
    );
}

export default function KelolaPengguna({
    pengguna,
    filterPengguna = {},
    daftarRole = [],
    mode = 'drawer',
    ruteIndex = 'pengaturan.index',
    paramTambahan = {},
}) {
    const [view, setView] = useState('list');
    const [target, setTarget] = useState(null);
    const [cari, setCari] = useState(filterPengguna.cari ?? '');
    const cariDebounced = useDebouncedValue(cari);
    const pertama = useRef(true);

    const tambah = useForm({ name: '', no_karyawan: '', email: '', role: '', password: '' });
    const edit = useForm({ name: '', no_karyawan: '', email: '', role: '', status: '' });
    const reset = useForm({ password: '', tanpa_wajib_ganti: false });

    const filterAktif = Boolean((filterPengguna.cari ?? '') || (filterPengguna.role ?? '') || (filterPengguna.status ?? ''));

    function muat(params = {}) {
        router.get(route(ruteIndex), {
            ...paramTambahan,
            cari: params.cari ?? filterPengguna.cari ?? '',
            role: params.role ?? filterPengguna.role ?? '',
            status: params.status ?? filterPengguna.status ?? '',
            page: params.page ?? 1,
        }, { preserveState: true, replace: true });
    }

    function resetFilter() {
        setCari('');
        router.get(route(ruteIndex), { ...paramTambahan }, { preserveState: true, replace: true });
    }

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        if ((cariDebounced ?? '') !== (filterPengguna.cari ?? '')) {
            muat({ cari: cariDebounced ?? '' });
        }
    }, [cariDebounced]);

    useEffect(() => {
        setCari(filterPengguna.cari ?? '');
    }, [filterPengguna.cari]);

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

    const headerKecil = (children) => (
        <div className="flex items-center gap-2 mb-4">
            <button
                onClick={() => setView('list')}
                aria-label="Kembali ke daftar"
                className="w-8 h-8 inline-flex items-center justify-center rounded-lg text-sm font-semibold text-slate-500 hover:text-brand-navy hover:bg-slate-100 transition-colors"
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
                    <FormField label="Nama Lengkap" error={tambah.errors.name} required>
                        <TextField size="sm" value={tambah.data.name} onChange={(e) => tambah.setData('name', e.target.value)} placeholder="Contoh: Budi Santoso" autoFocus required />
                    </FormField>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                        <FormField label="No. Karyawan" error={tambah.errors.no_karyawan} hint="Dipakai untuk login" required>
                            <TextField size="sm" value={tambah.data.no_karyawan} onChange={(e) => tambah.setData('no_karyawan', e.target.value)} placeholder="ADM-000002" required />
                        </FormField>
                        <FormField label="Role" error={tambah.errors.role} required>
                            <Select size="sm" value={tambah.data.role} onChange={(e) => tambah.setData('role', e.target.value)} required>
                                <option value="">Pilih role</option>
                                {daftarRole.map((r) => (
                                    <option key={r} value={r}>{labelRole[r] ?? r}</option>
                                ))}
                            </Select>
                        </FormField>
                    </div>
                    <FormField label="Email" error={tambah.errors.email}>
                        <TextField size="sm" type="email" value={tambah.data.email} onChange={(e) => tambah.setData('email', e.target.value)} placeholder="budi@koperasi.test" />
                    </FormField>
                    <FormField label="Password" error={tambah.errors.password} hint="Kosongkan agar memakai no. karyawan (wajib ganti saat login pertama)">
                        <KataSandi size="sm" meter value={tambah.data.password} onChange={(e) => tambah.setData('password', e.target.value)} placeholder="Min. 8 karakter" />
                    </FormField>
                    <div className="flex items-center gap-2 pt-1">
                        <Button type="submit" size="sm" disabled={tambah.processing} className="rounded-full shadow-md shadow-brand-green/25">
                            {tambah.processing ? 'Menyimpan...' : 'Simpan Pengguna'}
                        </Button>
                        <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={() => setView('list')}>
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
                {headerKecil(`Edit — ${target?.name ?? ''}`)}
                <form onSubmit={submitEdit}>
                    <FormField label="Nama Lengkap" error={edit.errors.name} required>
                        <TextField size="sm" value={edit.data.name} onChange={(e) => edit.setData('name', e.target.value)} required />
                    </FormField>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                        <FormField label="No. Karyawan" error={edit.errors.no_karyawan} required>
                            <TextField size="sm" value={edit.data.no_karyawan} onChange={(e) => edit.setData('no_karyawan', e.target.value)} required />
                        </FormField>
                        <FormField label="Role" error={edit.errors.role} required>
                            <Select size="sm" value={edit.data.role} onChange={(e) => edit.setData('role', e.target.value)} required>
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
                    <FormField label="Status" error={edit.errors.status} required>
                        <Select size="sm" value={edit.data.status} onChange={(e) => edit.setData('status', e.target.value)} required>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </Select>
                    </FormField>
                    {edit.errors.pengguna && <p className="text-xs font-medium text-red-600 mb-3">{edit.errors.pengguna}</p>}
                    <div className="flex items-center gap-2 pt-1">
                        <Button type="submit" size="sm" disabled={edit.processing || target?.dilindungi} className="rounded-full shadow-md shadow-brand-green/25">
                            {edit.processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                        <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={() => setView('list')}>
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
                {headerKecil(`Reset Password — ${target?.name ?? ''}`)}
                <div className="flex items-start gap-2.5 rounded-2xl bg-amber-50 border border-amber-200 px-3.5 py-2.5 text-xs text-amber-800 mb-4">
                    <Lock size={15} className="shrink-0 mt-0.5" />
                    <p>Password baru langsung berlaku. Beri tahu pengguna lewat jalur aman, bukan grup umum.</p>
                </div>
                <form onSubmit={submitReset}>
                    <FormField label="Password Baru" error={reset.errors.password} hint="Min. 8 karakter" required>
                        <KataSandi size="sm" meter value={reset.data.password} onChange={(e) => reset.setData('password', e.target.value)} placeholder="Min. 8 karakter" autoFocus required />
                    </FormField>
                    <label className="flex items-start gap-2.5 cursor-pointer rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5 mb-4">
                        <input
                            type="checkbox"
                            checked={reset.data.tanpa_wajib_ganti}
                            onChange={(e) => reset.setData('tanpa_wajib_ganti', e.target.checked)}
                            className="mt-0.5 w-4 h-4 rounded border-slate-300 text-brand-green focus:ring-brand-green/30"
                        />
                        <span className="text-sm text-slate-700">
                            <span className="font-semibold">Jangan wajibkan ganti password saat login.</span>
                            <span className="block text-xs text-slate-400 mt-0.5">
                                {reset.data.tanpa_wajib_ganti
                                    ? 'Pengguna bisa langsung login dengan password baru.'
                                    : 'Pengguna diminta mengganti password pada login berikutnya.'}
                            </span>
                        </span>
                    </label>
                    <div className="flex items-center gap-2">
                        <Button type="submit" size="sm" disabled={reset.processing} className="rounded-full shadow-md shadow-brand-green/25">
                            {reset.processing ? 'Menyimpan...' : 'Reset Password'}
                        </Button>
                        <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={() => setView('list')}>
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
                <Button size="sm" className="rounded-full shadow-md shadow-brand-green/25" onClick={() => setView('tambah')}>
                    <Plus size={16} />
                    Tambah Pengguna
                </Button>
                {mode === 'halaman' && filterAktif && (
                    <Button type="button" variant="ghost" size="sm" className="rounded-full" onClick={resetFilter}>
                        <RefreshCw size={14} />
                        Reset
                    </Button>
                )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-4">
                <div className="relative group">
                    <Search size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-green transition-colors pointer-events-none" />
                    <TextField
                        type="search"
                        size="sm"
                        value={cari}
                        onChange={(e) => setCari(e.target.value)}
                        placeholder="Cari nama / no. karyawan / email..."
                        aria-label="Cari pengguna"
                        className="pl-10 pr-8 text-sm rounded-full border-slate-200 bg-slate-50/60 focus:bg-white"
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
                <Select size="sm" value={filterPengguna.role ?? ''} onChange={(e) => muat({ role: e.target.value })} aria-label="Filter role" className="rounded-full border-slate-200 bg-slate-50/60 text-sm">
                    <option value="">Semua Role</option>
                    {daftarRole.map((r) => (
                        <option key={r} value={r}>{labelRole[r] ?? r}</option>
                    ))}
                </Select>
                <Select size="sm" value={filterPengguna.status ?? ''} onChange={(e) => muat({ status: e.target.value })} aria-label="Filter status" className="rounded-full border-slate-200 bg-slate-50/60 text-sm">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </Select>
            </div>

            {pengguna.data.length === 0 ? (
                <div className="text-center py-10 px-4">
                    <p className="text-sm font-semibold text-slate-600">Tidak ada pengguna yang cocok</p>
                    <p className="text-sm text-slate-400 mt-1">Coba ubah kata kunci atau filter.</p>
                    {filterAktif && mode === 'halaman' && (
                        <Button type="button" variant="outline" size="sm" className="mt-3 rounded-full" onClick={resetFilter}>
                            Tampilkan semua
                        </Button>
                    )}
                </div>
            ) : (
                <ul className="divide-y divide-slate-50 border border-slate-100 rounded-2xl overflow-hidden">
                    {pengguna.data.map((user) => (
                        <li key={user.id} className="flex items-center gap-3 px-4 py-3 bg-white hover:bg-slate-50/60 transition-colors">
                            <div className="w-10 h-10 rounded-full bg-gradient-to-br from-brand-navy to-brand-navy-light text-white flex items-center justify-center text-sm font-bold shrink-0 shadow-sm" aria-hidden="true">
                                {user.name.charAt(0).toUpperCase()}
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-semibold text-slate-800 flex items-center gap-1.5 truncate">
                                    <span className="truncate">{user.name}</span>
                                    {user.dilindungi && <Lock size={13} className="text-slate-300 shrink-0" />}
                                    {user.harus_ganti_password && (
                                        <span className="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700">
                                            Ganti password
                                        </span>
                                    )}
                                </p>
                                <p className="text-xs text-slate-400 truncate">
                                    {user.no_karyawan}{user.email ? ` • ${user.email}` : ''}
                                </p>
                                <div className="flex sm:hidden items-center gap-1 mt-1">
                                    {user.roles.map((role) => (
                                        <span key={role} className={`inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded-full ${role === 'admin' ? 'bg-brand-navy text-white' : 'bg-slate-100 text-slate-600'}`}>
                                            {labelRole[role] ?? role}
                                        </span>
                                    ))}
                                </div>
                            </div>
                            <div className="hidden sm:flex items-center gap-1.5 shrink-0">
                                {user.roles.map((role) => (
                                    <span key={role} className={`inline-flex items-center px-2 py-0.5 text-[11px] font-bold rounded-full ${role === 'admin' ? 'bg-brand-navy text-white' : 'bg-slate-100 text-slate-600'}`}>
                                        {labelRole[role] ?? role}
                                    </span>
                                ))}
                                <span className={`inline-flex items-center px-2 py-0.5 text-[11px] font-bold rounded-full ${user.status === 'aktif' ? 'bg-brand-green-light text-brand-green-dark' : 'bg-red-50 text-red-600'}`}>
                                    {user.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                </span>
                            </div>
                            <div className="flex items-center gap-0.5 shrink-0" onClick={(e) => e.stopPropagation()}>
                                <TombolAksi label={`Reset password ${user.name}`} title="Reset password" tone="navy" onClick={() => bukaReset(user)} />
                                <TombolAksi label={`Edit ${user.name}`} title="Edit pengguna" tone="navy" onClick={() => bukaEdit(user)} />
                                <TombolAksi label={user.status === 'aktif' ? `Nonaktifkan ${user.name}` : `Aktifkan ${user.name}`} title={user.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'} tone={user.status === 'aktif' ? 'red' : 'green'} disabled={user.dilindungi} onClick={() => toggleStatus(user)} />
                                <TombolAksi label={`Hapus ${user.name}`} title="Hapus" tone="red" disabled={user.dilindungi} onClick={() => hapus(user)} />
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {mode === 'halaman' && pengguna.links && (
                <div className="mt-4">
                    <Pagination links={pengguna.links} />
                </div>
            )}

            {mode === 'drawer' && pengguna.last_page > 1 && (
                <div className="flex items-center justify-between mt-4 text-sm">
                    <button
                        disabled={!pengguna.prev_page_url}
                        onClick={() => muat({ page: pengguna.current_page - 1 })}
                        className="px-3 py-1.5 rounded-lg font-semibold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        ← Sebelumnya
                    </button>
                    <span className="text-slate-500 font-semibold tabular-nums">
                        {pengguna.current_page}/{pengguna.last_page}
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
