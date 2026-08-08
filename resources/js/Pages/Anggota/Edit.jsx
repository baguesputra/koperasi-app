import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import FormField from '@/Components/ui/FormField';
import TextField from '@/Components/ui/TextField';

export default function Edit({ anggota, daftarCabang }) {
    const { data, setData, put, processing, errors } = useForm({
        nama: anggota.nama,
        cabang: anggota.cabang,
        unit_bisnis: anggota.unit_bisnis,
        jabatan: anggota.jabatan,
        tanggal_mulai_kerja: anggota.tanggal_mulai_kerja?.slice(0, 10) ?? '',
        tanggal_jadi_anggota: anggota.tanggal_jadi_anggota?.slice(0, 10) ?? '',
        status: anggota.status,
    });

    function submit(e) {
        e.preventDefault();
        put(route('anggota.update', anggota.id));
    }

    return (
        <AppLayout>
            <Head title={`Edit ${anggota.nama}`} />

            <div className="mb-6">
                <Link
                    href={route('anggota.index')}
                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-3"
                >
                    <ArrowLeft size={16} />
                    Kembali ke daftar anggota
                </Link>
                <h1 className="text-2xl font-bold text-slate-800">Edit Anggota</h1>
                <p className="text-base text-slate-400 mt-1">
                    Nomor anggota: <span className="font-semibold text-slate-600">{anggota.no_anggota}</span>
                </p>
            </div>

            <Card className="max-w-2xl">
                <form onSubmit={submit}>
                    <FormField label="Nama Lengkap" error={errors.nama}>
                        <TextField
                            value={data.nama}
                            onChange={(e) => setData('nama', e.target.value)}
                            autoFocus
                        />
                    </FormField>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <FormField label="Cabang" error={errors.cabang}>
                            <select
                                value={data.cabang}
                                onChange={(e) => setData('cabang', e.target.value)}
                                className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                            >
                                {daftarCabang.map((c) => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </select>
                        </FormField>

                        <FormField label="Jabatan" error={errors.jabatan}>
                            <select
                                value={data.jabatan}
                                onChange={(e) => setData('jabatan', e.target.value)}
                                className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                            >
                                <option value="staff">Staff</option>
                                <option value="hod">HOD</option>
                            </select>
                        </FormField>
                    </div>

                    <FormField label="Unit Bisnis" error={errors.unit_bisnis}>
                        <TextField
                            value={data.unit_bisnis}
                            onChange={(e) => setData('unit_bisnis', e.target.value)}
                        />
                    </FormField>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <FormField label="Tanggal Mulai Kerja" error={errors.tanggal_mulai_kerja}>
                            <TextField
                                type="date"
                                value={data.tanggal_mulai_kerja}
                                onChange={(e) => setData('tanggal_mulai_kerja', e.target.value)}
                            />
                        </FormField>

                        <FormField label="Tanggal Jadi Anggota" error={errors.tanggal_jadi_anggota}>
                            <TextField
                                type="date"
                                value={data.tanggal_jadi_anggota}
                                onChange={(e) => setData('tanggal_jadi_anggota', e.target.value)}
                            />
                        </FormField>
                    </div>

                    <FormField label="Status" error={errors.status}>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="w-full px-4 py-3 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none transition-colors"
                        >
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </FormField>

                    <div className="flex items-center gap-3 mt-2">
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                        <Link href={route('anggota.index')}>
                            <Button type="button" variant="ghost">
                                Batal
                            </Button>
                        </Link>
                    </div>
                </form>
            </Card>
        </AppLayout>
    );
}