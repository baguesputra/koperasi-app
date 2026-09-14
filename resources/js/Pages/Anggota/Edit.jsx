import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import BackLink from '@/Components/ui/BackLink';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import TextField from '@/Components/ui/TextField';

export default function Edit({ anggota, daftarCabang }) {
    const { data, setData, put, processing, errors } = useForm({
        nama: anggota.nama,
        cabang: anggota.cabang,
        unit_bisnis: anggota.unit_bisnis,
        tanggal_mulai_kerja: anggota.tanggal_mulai_kerja?.slice(0, 10) ?? '',
        tanggal_jadi_anggota: anggota.tanggal_jadi_anggota?.slice(0, 10) ?? '',
        status: anggota.status,
        limit_custom: anggota.limit_custom ?? '',
        limit_custom_keterangan: anggota.limit_custom_keterangan ?? '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('anggota.update', anggota.id));
    }

    return (
        <AppLayout>
            <Head title={`Edit ${anggota.nama}`} />

            <div className="mb-6">
                <BackLink href={route('anggota.index')}>Kembali ke daftar anggota</BackLink>
                <h1 className="text-2xl font-bold text-slate-800">Edit Anggota</h1>
                <p className="text-base text-slate-400 mt-1">
                    Nomor karyawan: <span className="font-semibold text-slate-600">{anggota.no_karyawan}</span>
                </p>
            </div>

            <Card padding="sm" className="max-w-2xl">
                <form onSubmit={submit}>
                    <FormField label="Nama Lengkap" error={errors.nama}>
                        <TextField
                            size="sm"
                            value={data.nama}
                            onChange={(e) => setData('nama', e.target.value)}
                            autoFocus
                        />
                    </FormField>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <FormField label="Cabang" error={errors.cabang}>
                            <Select
                                size="sm"
                                value={data.cabang}
                                onChange={(e) => setData('cabang', e.target.value)}
                            >
                                {daftarCabang.map((c) => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </Select>
                        </FormField>

                        <div className="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                            <p className="text-sm text-slate-400">Jabatan (otomatis dari GATE)</p>
                            <p className="text-base font-semibold text-slate-700">{anggota.jabatanMaster?.nama ?? anggota.jabatan ?? '-'}</p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div className="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                            <p className="text-sm text-slate-400">Perusahaan (otomatis dari GATE)</p>
                            <p className="text-base font-semibold text-slate-700">{anggota.perusahaan?.nama ?? '-'}</p>
                        </div>
                        <div className="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                            <p className="text-sm text-slate-400">Departemen (otomatis dari GATE)</p>
                            <p className="text-base font-semibold text-slate-700">{anggota.departemen?.nama ?? '-'}</p>
                        </div>
                        <div className="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                            <p className="text-sm text-slate-400">Divisi (otomatis dari GATE)</p>
                            <p className="text-base font-semibold text-slate-700">{anggota.divisiMaster?.nama ?? '-'}</p>
                        </div>
                        <div className="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                            <p className="text-sm text-slate-400">Unit Bisnis</p>
                            <p className="text-base font-semibold text-slate-700">{anggota.unit_bisnis ?? '-'}</p>
                        </div>
                    </div>

                    <div className="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3 mb-4">
                        <p className="text-sm text-slate-400">Unit Bisnis (otomatis dari GATE)</p>
                        <p className="text-base font-semibold text-slate-700">{anggota.unit_bisnis ?? '-'}</p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <FormField label="Tanggal Mulai Kerja" error={errors.tanggal_mulai_kerja}>
                            <TextField
                                size="sm"
                                type="date"
                                value={data.tanggal_mulai_kerja}
                                onChange={(e) => setData('tanggal_mulai_kerja', e.target.value)}
                            />
                        </FormField>

                        <FormField label="Tanggal Jadi Anggota" error={errors.tanggal_jadi_anggota}>
                            <TextField
                                size="sm"
                                type="date"
                                value={data.tanggal_jadi_anggota}
                                onChange={(e) => setData('tanggal_jadi_anggota', e.target.value)}
                            />
                        </FormField>
                    </div>

                    <FormField label="Status" error={errors.status}>
                        <Select
                            size="sm"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                        >
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </Select>
                    </FormField>

                    <div className="mt-5 pt-5 border-t border-slate-100">
                        <p className="text-base font-bold text-slate-700 mb-1">Limit Pinjaman Khusus (Opsional)</p>
                        <p className="text-sm text-slate-400 mb-4">
                            Isi jika anggota ini punya kebijakan limit berbeda dari aturan umum. Kosongkan untuk memakai aturan otomatis berdasarkan jabatan &amp; lama keanggotaan.
                        </p>

                        <FormField label="Nominal Limit Khusus" error={errors.limit_custom} hint="Kosongkan untuk hapus limit khusus">
                            <TextField
                                size="sm"
                                type="number"
                                value={data.limit_custom}
                                onChange={(e) => setData('limit_custom', e.target.value)}
                                placeholder="Contoh: 10000000"
                            />
                        </FormField>

                        <FormField label="Alasan / Keterangan" error={errors.limit_custom_keterangan}>
                            <TextField
                                size="sm"
                                value={data.limit_custom_keterangan}
                                onChange={(e) => setData('limit_custom_keterangan', e.target.value)}
                                placeholder="Contoh: Kebijakan khusus dari Ketua Koperasi, karyawan lama pindah cabang"
                            />
                        </FormField>
                    </div>

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