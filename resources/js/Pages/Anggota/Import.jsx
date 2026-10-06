import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { Download, Upload, CheckCircle2, XCircle, FileSpreadsheet, Trash2, Loader2 } from 'lucide-react';
import BackLink from '@/Components/ui/BackLink';
import PageHeader from '@/Components/ui/PageHeader';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import ButtonLink from '@/Components/ui/ButtonLink';

export default function Import() {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({ file: null });
    const [seret, setSeret] = useState(false);
    const inputRef = useRef(null);

    function pilihFile(file) {
        if (file) setData('file', file);
    }

    function submit(e) {
        e.preventDefault();
        post(route('anggota.import'), { forceFormData: true });
    }

    return (
        <AppLayout>
            <Head title="Import Anggota" />

            <BackLink href={route('anggota.index')}>Kembali ke daftar anggota</BackLink>
            <PageHeader title="Import Anggota" subtitle="Sinkron data karyawan dari GATE via Excel" />

            <ol className="flex items-center gap-2 mb-4 text-xs font-semibold">
                <li className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-brand-navy text-white">
                    <span className="w-4 h-4 rounded-full bg-white/20 inline-flex items-center justify-center text-[10px]">1</span>
                    Unduh template
                </li>
                <li className="w-6 h-px bg-slate-200" aria-hidden="true" />
                <li className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full transition-colors ${data.file ? 'bg-brand-navy text-white' : 'bg-white border border-slate-200 text-slate-500'}`}>
                    <span className={`w-4 h-4 rounded-full inline-flex items-center justify-center text-[10px] ${data.file ? 'bg-white/20' : 'bg-slate-100'}`}>2</span>
                    Upload file
                </li>
                <li className="w-6 h-px bg-slate-200" aria-hidden="true" />
                <li className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full transition-colors ${flash?.importBerhasil ? 'bg-brand-navy text-white' : 'bg-white border border-slate-200 text-slate-500'}`}>
                    <span className={`w-4 h-4 rounded-full inline-flex items-center justify-center text-[10px] ${flash?.importBerhasil ? 'bg-white/20' : 'bg-slate-100'}`}>3</span>
                    Selesai
                </li>
            </ol>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <Card className="relative overflow-hidden shadow-md border-slate-200/70">
                    <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-navy to-brand-green" aria-hidden="true" />
                    <div className="flex items-center gap-3 mb-2">
                        <span className="w-10 h-10 rounded-2xl bg-gradient-to-br from-brand-navy to-brand-navy-light text-white inline-flex items-center justify-center shadow-md shadow-brand-navy/20">
                            <FileSpreadsheet size={20} />
                        </span>
                        <div>
                            <p className="text-sm font-bold text-slate-800">Template Excel GATE</p>
                            <p className="text-xs text-slate-400">Format tanggal <code>2024-01-15</code></p>
                        </div>
                    </div>
                    <ul className="text-sm text-slate-500 space-y-1 mb-4 list-disc pl-5">
                        <li>Jabatan diisi nama asli dari GATE</li>
                        <li>No. karyawan unik, jadi akun login otomatis</li>
                    </ul>
                    <ButtonLink
                        href={route('anggota.template')}
                        variant="outline"
                        size="sm"
                        className="rounded-full hover:-translate-y-px hover:shadow-md transition-all"
                    >
                        <Download size={16} />
                        Unduh Template
                    </ButtonLink>
                </Card>

                <Card className="relative overflow-hidden shadow-md border-slate-200/70">
                    <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-green to-brand-navy" aria-hidden="true" />
                    <div className="flex items-center gap-3 mb-2">
                        <span className="w-10 h-10 rounded-2xl bg-gradient-to-br from-brand-green to-brand-green-dark text-white inline-flex items-center justify-center shadow-md shadow-brand-green/25">
                            <Upload size={20} />
                        </span>
                        <div>
                            <p className="text-sm font-bold text-slate-800">Upload file Excel</p>
                            <p className="text-xs text-slate-400">Password awal = no. karyawan &bull; Maks 2 MB, 5000 baris</p>
                        </div>
                    </div>

                    <form onSubmit={submit}>
                        <div
                            role="button"
                            tabIndex={0}
                            aria-label="Pilih atau seret file Excel ke sini"
                            onClick={() => inputRef.current?.click()}
                            onKeyDown={(e) => { if (e.key === 'Enter') inputRef.current?.click(); }}
                            onDragOver={(e) => { e.preventDefault(); setSeret(true); }}
                            onDragLeave={() => setSeret(false)}
                            onDrop={(e) => { e.preventDefault(); setSeret(false); pilihFile(e.dataTransfer.files?.[0]); }}
                            className={`rounded-2xl border-2 border-dashed px-4 py-6 text-center cursor-pointer transition-all duration-200 mb-2 ${
                                seret
                                    ? 'border-brand-green bg-brand-green-light/60 scale-[1.01]'
                                    : data.file
                                      ? 'border-brand-green/50 bg-brand-green-light/30'
                                      : 'border-slate-200 bg-slate-50/60 hover:border-brand-green/60 hover:bg-brand-green-light/30'
                            }`}
                        >
                            <input
                                ref={inputRef}
                                type="file"
                                accept=".xlsx,.xls"
                                onChange={(e) => pilihFile(e.target.files[0])}
                                className="hidden"
                            />
                            {data.file ? (
                                <div className="flex items-center justify-center gap-2 text-sm">
                                    <FileSpreadsheet size={18} className="text-brand-green-dark shrink-0" />
                                    <span className="font-semibold text-slate-700 truncate max-w-[220px]">{data.file.name}</span>
                                    <button
                                        type="button"
                                        aria-label="Hapus file"
                                        onClick={(e) => { e.stopPropagation(); setData('file', null); }}
                                        className="w-7 h-7 inline-flex items-center justify-center rounded-full text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors shrink-0"
                                    >
                                        <Trash2 size={14} />
                                    </button>
                                </div>
                            ) : (
                                <>
                                    <Upload size={22} className="mx-auto text-slate-400 mb-1.5" />
                                    <p className="text-sm font-semibold text-slate-600">Seret file ke sini atau klik untuk pilih</p>
                                    <p className="text-xs text-slate-400 mt-0.5">.xlsx / .xls</p>
                                </>
                            )}
                        </div>
                        {errors.file && <p className="text-xs text-red-600 mb-2">{errors.file}</p>}

                        <Button
                            type="submit"
                            size="sm"
                            disabled={processing || !data.file}
                            className="rounded-full bg-gradient-to-r from-brand-green to-brand-green-dark shadow-md shadow-brand-green/25 hover:shadow-lg hover:-translate-y-px active:translate-y-0 disabled:hover:translate-y-0 disabled:hover:shadow-md"
                        >
                            {processing ? <Loader2 size={16} className="animate-spin" /> : <Upload size={16} />}
                            {processing ? 'Memproses...' : 'Upload & Import'}
                        </Button>
                    </form>
                </Card>
            </div>

            {flash?.importBerhasil && (
                <Card className="mt-4 border-brand-green/30 shadow-md">
                    <div className="flex items-center gap-2 mb-2">
                        <span className="w-8 h-8 rounded-full bg-brand-green-light text-brand-green-dark inline-flex items-center justify-center">
                            <CheckCircle2 size={18} />
                        </span>
                        <p className="text-sm font-bold text-slate-800">{flash.importBerhasil.length} berhasil ditambahkan</p>
                    </div>
                    <ul className="space-y-1 list-disc pl-5 max-h-48 overflow-y-auto">
                        {flash.importBerhasil.map((item, i) => (
                            <li key={i} className="text-sm text-slate-600">{item}</li>
                        ))}
                    </ul>
                </Card>
            )}

            {flash?.importGagal && flash.importGagal.length > 0 && (
                <Card className="mt-4 shadow-md" tone="danger">
                    <div className="flex items-center gap-2 mb-2">
                        <span className="w-8 h-8 rounded-full bg-red-100 text-red-600 inline-flex items-center justify-center">
                            <XCircle size={18} />
                        </span>
                        <p className="text-sm font-bold text-red-700">{flash.importGagal.length} baris gagal</p>
                    </div>
                    <ul className="space-y-1 list-disc pl-5 max-h-48 overflow-y-auto">
                        {flash.importGagal.map((item, i) => (
                            <li key={i} className="text-sm text-red-600">{item}</li>
                        ))}
                    </ul>
                </Card>
            )}
        </AppLayout>
    );
}
