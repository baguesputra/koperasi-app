import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Download, Upload, ArrowLeft, CheckCircle2, XCircle } from 'lucide-react';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';

export default function Import() {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({ file: null });

    function submit(e) {
        e.preventDefault();
        post(route('anggota.import'), { forceFormData: true });
    }

    return (
        <AppLayout>
            <Head title="Import Anggota" />

            <Link href={route('anggota.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-4">
                <ArrowLeft size={16} />
                Kembali ke daftar anggota
            </Link>

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-800">Import Anggota</h1>
                <p className="text-base text-slate-400 mt-1">Tambahkan banyak anggota sekaligus lewat file Excel</p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <Card>
                    <p className="text-base font-bold text-slate-800 mb-2">1. Unduh Template</p>
                    <p className="text-sm text-slate-500 mb-4">
                        Gunakan template ini agar format data sesuai dan bisa diproses sistem.
                    </p>
                    <div className="bg-slate-50 rounded-xl p-4 mb-4 text-sm text-slate-600 space-y-1">
                        <p>&bull; Kolom <strong>Jabatan</strong> diisi: <code>staff</code> atau <code>hod</code></p>
                        <p>&bull; Kolom <strong>Tanggal</strong> diisi format: <code>2024-01-15</code> (Tahun-Bulan-Tanggal)</p>
                    </div>
                    <a href={route('anggota.template')}>
                        <Button variant="outline">
                            <Download size={18} />
                            Unduh Template Excel
                        </Button>
                    </a>
                </Card>

                <Card>
                    <p className="text-base font-bold text-slate-800 mb-2">2. Upload File</p>
                    <p className="text-sm text-slate-500 mb-4">
                        Setiap anggota otomatis mendapat akun login. Password awal = No. Karyawan, wajib diganti saat login pertama.
                    </p>

                    <form onSubmit={submit}>
                        <input
                            type="file"
                            accept=".xlsx,.xls"
                            onChange={(e) => setData('file', e.target.files[0])}
                            className="w-full text-sm text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-brand-green-light file:text-brand-green-dark file:font-semibold file:text-sm mb-3"
                        />
                        {errors.file && <p className="text-sm text-red-600 mb-3">{errors.file}</p>}

                        <Button type="submit" variant="primary" disabled={processing || !data.file}>
                            <Upload size={18} />
                            {processing ? 'Memproses...' : 'Upload & Import'}
                        </Button>
                    </form>
                </Card>
            </div>

            {flash?.importBerhasil && (
                <Card className="mt-5">
                    <div className="flex items-center gap-2 mb-3">
                        <CheckCircle2 size={20} className="text-brand-green" />
                        <p className="text-base font-bold text-slate-800">
                            {flash.importBerhasil.length} Anggota Berhasil Ditambahkan
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