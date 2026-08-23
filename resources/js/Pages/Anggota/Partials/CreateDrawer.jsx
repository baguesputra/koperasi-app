import { useForm } from '@inertiajs/react';
import Button from '@/Components/ui/Button';
import FormField from '@/Components/ui/FormField';
import Select from '@/Components/ui/Select';
import TextField from '@/Components/ui/TextField';

const daftarDepartment = [
    'IT', 'Finance', 'HR', 'Operations', 'Marketing', 'Sales',
    'Legal', 'Procurement', 'Engineering', 'Customer Service',
    'Administration', 'Logistics', 'Quality Assurance', 'Research & Development',
];

export default function CreateDrawer({ noAnggotaBerikutnya, daftarCabang, onClose }) {
    const { data, setData, post, processing, errors } = useForm({
        nama: '',
        no_karyawan: '',
        email: '',
        cabang: '',
        unit_bisnis: '',
        department: '',
        jabatan: '',
        tanggal_mulai_kerja: '',
        tanggal_jadi_anggota: '',
        no_hp: '',
        alamat: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('anggota.store'), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    }

    return (
        <form onSubmit={submit}>
            <p className="text-sm text-slate-400 mb-5">
                Nomor anggota: <span className="font-semibold text-slate-600">{noAnggotaBerikutnya}</span> (otomatis)
            </p>

            <FormField label="Nama Lengkap" error={errors.nama}>
                <TextField
                    size="sm"
                    value={data.nama}
                    onChange={(e) => setData('nama', e.target.value)}
                    placeholder="Contoh: Budi Santoso"
                    autoFocus
                />
            </FormField>

            <FormField label="No. Karyawan" error={errors.no_karyawan} hint="Format: TOP-XXXXXX (contoh: TOP-123456)">
                <TextField
                    size="sm"
                    value={data.no_karyawan}
                    onChange={(e) => setData('no_karyawan', e.target.value)}
                    placeholder="TOP-123456"
                />
            </FormField>

            <FormField label="Email" error={errors.email} hint="Wajib diisi untuk notifikasi">
                <TextField
                    size="sm"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    placeholder="budi@company.com"
                />
            </FormField>

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

                <FormField label="Department" error={errors.department}>
                    <Select
                        size="sm"
                        value={data.department}
                        onChange={(e) => setData('department', e.target.value)}
                    >
                        <option value="">Pilih department</option>
                        {daftarDepartment.map((d) => (
                            <option key={d} value={d}>{d}</option>
                        ))}
                    </Select>
                </FormField>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                <FormField label="Jabatan" error={errors.jabatan}>
                    <Select
                        size="sm"
                        value={data.jabatan}
                        onChange={(e) => setData('jabatan', e.target.value)}
                    >
                        <option value="">Pilih jabatan</option>
                        <option value="staff">Staff</option>
                        <option value="hod">HOD</option>
                    </Select>
                </FormField>

                <FormField label="Unit Bisnis" error={errors.unit_bisnis}>
                    <TextField
                        size="sm"
                        value={data.unit_bisnis}
                        onChange={(e) => setData('unit_bisnis', e.target.value)}
                        placeholder="Contoh: Operasional"
                    />
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

            <FormField label="No. HP" error={errors.no_hp} hint="Format: 081234567890 atau +6281234567890">
                <TextField
                    size="sm"
                    type="tel"
                    value={data.no_hp}
                    onChange={(e) => setData('no_hp', e.target.value)}
                    placeholder="081234567890"
                />
            </FormField>

            <FormField label="Alamat" error={errors.alamat}>
                <TextField
                    size="sm"
                    as="textarea"
                    rows={3}
                    value={data.alamat}
                    onChange={(e) => setData('alamat', e.target.value)}
                    placeholder="Alamat lengkap"
                />
            </FormField>

            <div className="flex items-center gap-3 mt-2 pt-4 border-t border-slate-100">
                <Button type="submit" variant="primary" disabled={processing}>
                    {processing ? 'Menyimpan...' : 'Simpan Anggota'}
                </Button>
                <Button type="button" variant="ghost" onClick={onClose}>
                    Batal
                </Button>
            </div>
        </form>
    );
}
