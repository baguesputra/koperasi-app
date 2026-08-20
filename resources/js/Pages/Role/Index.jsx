import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Pencil, Trash2, Lock } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import Breadcrumb from '@/Components/ui/Breadcrumb';

export default function Index({ roles }) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ name: '' });

    function submit(e) {
        e.preventDefault();
        post(route('role.store'), { onSuccess: () => { reset(); setShowForm(false); } });
    }

    function hapus(role) {
        if (confirm(`Hapus role "${role.name}"?`)) {
            router.delete(route('role.destroy', role.id));
        }
    }

    return (
        <AppLayout>
            <Head title="Kelola Role" />

            <Breadcrumb
                items={[
                    { label: 'Pengaturan', href: route('pengaturan.index') },
                    { label: 'Kelola Role' },
                ]}
            />

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-800">Kelola Role</h1>
                    <p className="text-base text-slate-400 mt-1">Atur peran dan hak akses pengguna sistem</p>
                </div>
                <Button variant="primary" onClick={() => setShowForm(!showForm)}>
                    <Plus size={18} />
                    Tambah Role
                </Button>
            </div>

            {showForm && (
                <Card className="mb-5">
                    <form onSubmit={submit} className="flex items-end gap-3">
                        <div className="flex-1 max-w-xs">
                            <label className="block text-sm font-semibold text-slate-600 mb-1.5">Nama Role</label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Contoh: accounting"
                                className="w-full px-4 py-2.5 text-base rounded-xl border border-slate-300 focus:border-brand-green outline-none"
                                autoFocus
                            />
                            {errors.name && <p className="text-sm text-red-600 mt-1">{errors.name}</p>}
                        </div>
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Buat Role'}
                        </Button>
                    </form>
                </Card>
            )}

            <Card padding="none">
                <div className="divide-y divide-slate-50">
                    {roles.map((role) => (
                        <div key={role.id} className="flex items-center justify-between px-5 py-4">
                            <div className="flex items-center gap-3">
                                <div>
                                    <p className="text-base font-bold text-slate-800 capitalize flex items-center gap-2">
                                        {role.name.replace('_', ' ')}
                                        {role.dilindungi && (
                                            <span title="Role bawaan sistem, tidak dapat dihapus">
                                                <Lock size={14} className="text-slate-300" />
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-sm text-slate-400">{role.jumlah_user} pengguna</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <Link href={route('role.edit', role.id)}>
                                    <Button variant="ghost" size="sm">
                                        <Pencil size={16} />
                                        Atur Akses
                                    </Button>
                                </Link>
                                {!role.dilindungi && (
                                    <button onClick={() => hapus(role)} className="text-slate-400 hover:text-red-600 p-2">
                                        <Trash2 size={16} />
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </Card>
        </AppLayout>
    );
}