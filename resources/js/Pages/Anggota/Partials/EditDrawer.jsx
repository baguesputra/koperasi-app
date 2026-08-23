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

export default function EditDrawer({ anggota, daftarCabang, onClose }) {
    const { data, setData, put, processing, errors } = useForm({
        nama: anggota.nama,
        no_karyawan: anggota.no_karyawan ?? '',
        email: anggota.user?.email ?? '',
        cabang: anggota.cabang,
        unit_bisnis: anggota.unit_bisnis,
        department: anggota.department ?? '',
        jabatan: anggota.jabatan,
        tanggal_mulai_kerja: anggota.tanggal_mulai_kerja ?? '',
        tanggal_jadi_anggota: anggota.tanggal_jadi_anggota ?? '',
        status: anggota.status,
        limit_custom: anggota.limit_custom ?? '',
        limit_custom_keterangan: anggota.limit_custom_keterangan ?? '',
        no_hp: anggota.no_hp ?? '',
        alamat: anggota.alamat ?? '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('anggota.update', anggota.id), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    }

    return (
        <form onSubmit={submit}>
            <FormField label="Nama Lengkap" error={errors.nama}>
                <TextField
                    size="sm"
                    value={data.nama}
                    onChange={(e) => setData('nama', e.target.value)}
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
                        <option value="staff">Staff</option>
                        <option value="hod">HOD</option>
                    </Select>
                </FormField>

                <FormField label="Unit Bisnis" error={errors.unit_bisnis}>
                    <TextField
                        size="sm"
                        value={data.unit_bisnis}
                        onChange={(e) => setData('unit_bisnis', e.target.value)}
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

            <div className="mt-5 pt-5 border-t border-slate-100">
                <p className="text-base font-bold text-slate-700 mb-1">Limit Pinjaman Khusus (Opsional)</p>
                <p className="text-sm text-slate-400 mb-4">
                    Isi jika anggota ini punya kebijakan limit berbeda dari aturan umum. Kosongkan untuk memakai aturan otomatis berdasarkan jabatan & lama keanggotaan.
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

            <div className="flex items-center gap-3 mt-2 pt-4 border-t border-slate-100">
                <Button type="submit" variant="primary" disabled={processing}>
                    {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                </Button>
                <Button type="button" variant="ghost" onClick={onClose}>
                    Batal
                </Button>
            </div>
        </form>
    );
}