import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import BackLink from '@/Components/ui/BackLink';
import Button from '@/Components/ui/Button';
import Card from '@/Components/ui/Card';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import TextField from '@/Components/ui/TextField';

export default function Create({ noAnggotaBerikutnya, daftarCabang, daftarPerusahaan = [], daftarDivisi = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        nama: '',
        perusahaan_id: '',
        divisi_id: '',
        no_karyawan: '',
        email: '',
        cabang: '',
        unit_bisnis: '',
        jabatan: '',
        tanggal_mulai_kerja: '',
        tanggal_jadi_anggota: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('anggota.store'));
    }

    return (
        <AppLayout>
            <Head title="Tambah Anggota" />

            <div className="mb-6">
                <BackLink href={route('anggota.index')}>Kembali ke daftar anggota</BackLink>
                <h1 className="text-2xl font-bold text-slate-800">Tambah Anggota</h1>
                <p className="text-base text-slate-400 mt-1">
                    Nomor anggota: <span className="font-semibold text-slate-600">{noAnggotaBerikutnya}</span> (otomatis)
                </p>
            </div>

            <Card padding="sm" className="max-w-2xl">
                <form onSubmit={submit}>
                    <FormField label="Nama Lengkap" error={errors.nama}>
                        <TextField
                            size="sm"
                            value={data.nama}
                            onChange={(e) => setData('nama', e.target.value)}
                            placeholder="Contoh: Budi Santoso"
                            autoFocus
                        />
                    </FormField>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <FormField
                            label="No Karyawan"
                            error={errors.no_karyawan}
                            hint="Dipakai sebagai akun login (password awal = no karyawan)"
                        >
                            <TextField
                                size="sm"
                                value={data.no_karyawan}
                                onChange={(e) => setData('no_karyawan', e.target.value)}
                                placeholder="Contoh: TOP-100099"
                            />
                        </FormField>

                        <FormField label="Email (opsional)" error={errors.email}>
                            <TextField
                                size="sm"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="Contoh: budi@koperasi.test"
                            />
                        </FormField>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <FormField label="Cabang" error={errors.cabang}>
                            <Select
                                size="sm"
                                value={data.cabang}
                                onChange={(e) => setData('cabang', e.target.value)}
                            >
                                <option value="">Pilih cabang</option>
                                {daftarCabang.map((c) => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField label="Jabatan" error={errors.jabatan}>
                            <TextField
                                size="sm"
                                value={data.jabatan}
                                onChange={(e) => setData('jabatan', e.target.value)}
                                placeholder="Contoh: Fullstack Developer"
                            />
                        </FormField>
                    </div>

                    <FormField label="Unit Bisnis" error={errors.unit_bisnis}>
                        <TextField
                            size="sm"
                            value={data.unit_bisnis}
                            onChange={(e) => setData('unit_bisnis', e.target.value)}
                            placeholder="Contoh: Operasional"
                        />
                    </FormField>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                        <FormField label="Perusahaan (manual)" error={errors.perusahaan_id}>
                            <Select
                                size="sm"
                                value={data.perusahaan_id}
                                onChange={(e) => setData('perusahaan_id', e.target.value)}
                            >
                                <option value="">Pilih perusahaan</option>
                                {daftarPerusahaan.map((p) => (
                                    <option key={p.id} value={p.id}>{p.nama}</option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField label="Divisi (manual)" error={errors.divisi_id}>
                            <Select
                                size="sm"
                                value={data.divisi_id}
                                onChange={(e) => setData('divisi_id', e.target.value)}
                            >
                                <option value="">Pilih divisi</option>
                                {daftarDivisi.map((d) => (
                                    <option key={d.id} value={d.id}>{d.nama}</option>
                                ))}
                            </Select>
                        </FormField>
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

                        <FormField
                            label="Tanggal Jadi Anggota"
                            error={errors.tanggal_jadi_anggota}
                            hint="Acuan hitung lama keanggotaan"
                        >
                            <TextField
                                size="sm"
                                type="date"
                                value={data.tanggal_jadi_anggota}
                                onChange={(e) => setData('tanggal_jadi_anggota', e.target.value)}
                            />
                        </FormField>
                    </div>

                    <div className="flex items-center gap-3 mt-2">
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan Anggota'}
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