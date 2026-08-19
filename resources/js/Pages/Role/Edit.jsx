import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Lock } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';

const kelompokLabel = {
    anggota: 'Anggota',
    simpanan: 'Simpanan',
    pinjaman: 'Pinjaman',
    percepatan: 'Percepatan Pinjaman',
    angsuran: 'Angsuran',
    kas: 'Kas Koperasi',
    laporan: 'Laporan',
    pengaturan: 'Pengaturan',
    portal: 'Portal Anggota',
};

const permissionLabel = {
    'anggota.lihat': 'Lihat data anggota',
    'anggota.kelola': 'Tambah / ubah data anggota',
    'simpanan.lihat': 'Lihat data simpanan',
    'simpanan.konfirmasi': 'Konfirmasi simpanan bulanan',
    'pinjaman.lihat': 'Lihat semua data pinjaman',
    'pinjaman.tinjau-bendahara': 'Tinjau & setujui pinjaman (tahap Bendahara)',
    'pinjaman.approve-ketua': 'Setujui final pinjaman (tahap Ketua)',
    'percepatan.tinjau-bendahara': 'Tinjau pengajuan percepatan (Bendahara)',
    'percepatan.approve-ketua': 'Approval final percepatan (Ketua)',
    'angsuran.konfirmasi': 'Konfirmasi pembayaran angsuran',
    'kas.lihat': 'Lihat saldo & riwayat kas',
    'kas.topup': 'Tambah saldo kas koperasi',
    'laporan.lihat': 'Akses halaman laporan',
    'pengaturan.kelola': 'Kelola pengaturan sistem',
    'portal.akses': 'Akses portal anggota',
};

export default function Edit({ role, permissionTerpilih, semuaPermission }) {
    const { data, setData, put, processing } = useForm({
        permissions: permissionTerpilih,
    });

    function toggle(name) {
        setData('permissions',
            data.permissions.includes(name)
                ? data.permissions.filter((p) => p !== name)
                : [...data.permissions, name]
        );
    }

    function submit(e) {
        e.preventDefault();
        put(route('role.update', role.id));
    }

    return (
        <AppLayout>
            <Head title={`Akses Role - ${role.name}`} />

            <Link href={route('role.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-4">
                <ArrowLeft size={16} />
                Kembali ke daftar role
            </Link>

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800 capitalize flex items-center gap-2">
                    {role.name.replace('_', ' ')}
                    {role.dilindungi && <Lock size={18} className="text-slate-300" />}
                </h1>
                <p className="text-base text-slate-400 mt-1">
                    Pilih hak akses yang dimiliki role ini
                </p>
            </div>

            <form onSubmit={submit}>
                <div className="space-y-4 mb-6">
                    {Object.entries(semuaPermission).map(([kelompok, items]) => (
                        <Card key={kelompok} padding="normal">
                            <p className="text-base font-bold text-slate-700 mb-3">
                                {kelompokLabel[kelompok] ?? kelompok}
                            </p>
                            <div className="space-y-2.5">
                                {items.map((permission) => (
                                    <label key={permission.name} className="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.permissions.includes(permission.name)}
                                            onChange={() => toggle(permission.name)}
                                            className="w-5 h-5 rounded border-slate-300 text-brand-green focus:ring-brand-green/30"
                                        />
                                        <span className="text-base text-slate-700">
                                            {permissionLabel[permission.name] ?? permission.name}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </Card>
                    ))}
                </div>

                <div className="sticky bottom-6">
                    <Button type="submit" variant="primary" disabled={processing}>
                        {processing ? 'Menyimpan...' : 'Simpan Hak Akses'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}