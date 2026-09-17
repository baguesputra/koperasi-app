import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Download, Upload, CheckCircle2, XCircle } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';

function BlokImport({ judul, deskripsi, catatan, templateRoute, importRoute, progresRoute, form }) {
    function submit(e) {
        e.preventDefault();
        form.post(route(importRoute), { forceFormData: true });
    }

    return (
        <Card>
            <p className="text-base font-bold text-slate-800 mb-1">{judul}</p>
            <p className="text-sm text-slate-500 mb-4">{deskripsi}</p>

            <div className="bg-slate-50 rounded-xl p-4 mb-4 text-sm text-slate-600 space-y-1">
                {catatan.map((c, i) => (
                    <p key={i}>&bull; <span dangerouslySetInnerHTML={{ __html: c }} /></p>
                ))}
            </div>

            <div className="flex flex-wrap gap-2 mb-4">
                <a href={route(templateRoute)}>
                    <Button variant="outline">
                        <Download size={18} />
                        Unduh Template
                    </Button>
                </a>
                <a href={route(progresRoute)}>
                    <Button variant="outline">
                        <Download size={18} />
                        Unduh Progres
                    </Button>
                </a>
            </div>

            <form onSubmit={submit}>
                <input
                    type="file"
                    accept=".xlsx,.xls"
                    onChange={(e) => form.setData('file', e.target.files[0])}
                    className="w-full text-sm text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-brand-green-light file:text-brand-green-dark file:font-semibold file:text-sm mb-3"
                />
                {form.errors.file && <p className="text-sm text-red-600 mb-3">{form.errors.file}</p>}

                <Button type="submit" variant="primary" disabled={form.processing || !form.data.file}>
                    <Upload size={18} />
                    {form.processing ? 'Memproses...' : 'Upload & Import'}
                </Button>
            </form>
        </Card>
    );
}

export default function Index() {
    const { flash } = usePage().props;
    const formPinjaman = useForm({ file: null });
    const formSimpanan = useForm({ file: null });

    return (
        <AppLayout>
            <Head title="Migrasi Data" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Migrasi Data Manual</h1>
                <p className="text-base text-slate-400 mt-1">Import sekali pakai untuk pinjaman dan simpanan dari catatan manual koperasi</p>
            </div>

            <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 text-sm text-amber-800">
                Topup kantong <strong>Dana Pinjaman</strong> dan <strong>Simpanan Anggota</strong> dulu di halaman Kas Koperasi sebelum import,
                supaya jurnal pencairan historis tidak gagal karena saldo kurang.
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <BlokImport
                    judul="1. Migrasi Pinjaman"
                    deskripsi="Satu baris = satu pinjaman. Jadwal angsuran dibuat otomatis, cicilan yang sudah dibayar ditandai lunas."
                    catatan={[
                        'Kunci anggota: <code>No Karyawan</code> (harus sudah terdaftar & aktif), kolom <code>Nama</code> opsional sebagai pembanding',
                        'Typo ringan 1 huruf (misal <code>Eka Yogi</code> vs <code>Eka Yogie</code>) otomatis ditempel bila kandidat tunggal',
                        'Kolom <code>Sudah Bayar Cicilan Ke</code>: jumlah cicilan yang sudah lunas, sisa dihitung otomatis',
                        'Tanggal: format <code>2024-01-15</code>, tanggal bayar tiap cicilan mengikuti jatuh tempo',
                    ]}
                    templateRoute="migrasi.template-pinjaman"
                    importRoute="migrasi.import-pinjaman"
                    progresRoute="migrasi.progres-pinjaman"
                    form={formPinjaman}
                />

                <BlokImport
                    judul="2. Migrasi Simpanan"
                    deskripsi="Satu baris = satu setoran. Duplikat anggota + jenis + periode ditolak."
                    catatan={[
                        'Kunci anggota: <code>No Karyawan</code> (harus sudah terdaftar & aktif)',
                        'Kolom <code>Jenis</code>: <code>pokok</code>, <code>wajib</code>, atau <code>dana_sosial</code>',
                        'Kolom <code>Bulan Periode</code>: format <code>2024-01</code> (Tahun-Bulan)',
                    ]}
                    templateRoute="migrasi.template-simpanan"
                    importRoute="migrasi.import-simpanan"
                    progresRoute="migrasi.progres-simpanan"
                    form={formSimpanan}
                />
            </div>

            {flash?.importBerhasil && (
                <Card className="mt-5">
                    <div className="flex items-center gap-2 mb-3">
                        <CheckCircle2 size={20} className="text-brand-green" />
                        <p className="text-base font-bold text-slate-800">
                            {flash.importBerhasil.length} Baris Berhasil Diproses
                        </p>
                    </div>
                    <div className="space-y-1">
                        {flash.importBerhasil.map((item, i) => (
                            <p key={i} className="text-sm text-slate-600">{item}</p>
                        ))}
                    </div>
                </Card>
            )}

            {flash?.importGagal && flash.importGagal.length > 0 && (
                <Card className="mt-5 bg-red-50 border-red-100">
                    <div className="flex items-center gap-2 mb-3">
                        <XCircle size={20} className="text-red-600" />
                        <p className="text-base font-bold text-red-700">
                            {flash.importGagal.length} Baris Gagal Diproses
                        </p>
                    </div>
                    <div className="space-y-1">
                        {flash.importGagal.map((item, i) => (
                            <p key={i} className="text-sm text-red-600">{item}</p>
                        ))}
                    </div>
                </Card>
            )}
        </AppLayout>
    );
}
