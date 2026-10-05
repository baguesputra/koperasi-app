import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { FileSpreadsheet, Printer, CalendarDays, Loader2, RotateCcw } from 'lucide-react';
import Card from '@/Components/ui/Card';
import BackLink from '@/Components/ui/BackLink';
import PageHeader from '@/Components/ui/PageHeader';
import FormField from '@/Components/ui/FormField';
import TextField from '@/Components/ui/TextField';
import Select from '@/Components/ui/Select';
import { formatRupiah } from '@/Utils/formatCurrency';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

export default function Show({ laporan, filter, opsi, periodeLabel, hasil }) {
    const tipeFilter = 'bulan' in filter ? 'bulan' : 'tahun' in filter ? 'tahun' : 'tanggal' in filter ? 'tanggal' : 'dari' in filter ? 'rentang' : null;
    const [memuat, setMemuat] = useState(false);
    const [tahun, setTahun] = useState(String(filter.tahun ?? ''));

    // Ikuti nilai server saat navigasi (mis. dari tombol back browser).
    useEffect(() => setTahun(String(filter.tahun ?? '')), [filter.tahun]);

    function terapkan(perubahan) {
        router.get(route('laporan.show', laporan.slug), { ...filter, ...perubahan }, {
            preserveState: true,
            onStart: () => setMemuat(true),
            onFinish: () => setMemuat(false),
        });
    }

    function komitTahun() {
        const n = parseInt(tahun, 10);
        if (tahun.length === 4 && n >= 2000 && n <= 2100 && n !== filter.tahun) {
            terapkan({ tahun: n });
        } else {
            setTahun(String(filter.tahun ?? ''));
        }
    }

    function queryString() {
        const params = Object.fromEntries(Object.entries(filter).filter(([, v]) => v !== '' && v != null));
        return new URLSearchParams(params).toString();
    }

    const sel = (i) => (hasil.rataKanan.includes(i) ? 'text-right whitespace-nowrap tabular-nums' : '');

    const tampilCell = (i, cell) => (hasil.rataKanan.includes(i) && typeof cell === 'number' ? formatRupiah(cell) : cell);

    const gayaBaris = (ri) => (hasil.gayaBaris?.[ri] === 'section'
        ? 'bg-slate-100'
        : hasil.gayaBaris?.[ri] === 'subtotal'
            ? 'bg-slate-50 font-bold'
            : 'border-b border-slate-50 last:border-0 hover:bg-slate-50 transition-colors');

    return (
        <AppLayout>
            <Head title={laporan.judul} />

            <BackLink href={route('laporan.index')}>Semua Laporan</BackLink>

            <PageHeader title={laporan.judul} subtitle={laporan.deskripsi}>
                <div className="flex flex-wrap items-center gap-2">
                    <Link
                        href={route('laporan.show', laporan.slug)}
                        className={`inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-full text-slate-600 hover:bg-slate-100 transition-colors ${fokusRing}`}
                    >
                        <RotateCcw size={16} aria-hidden="true" />
                        Reset periode
                    </Link>
                    <a
                        href={`${route('laporan.export', laporan.slug)}?${queryString()}`}
                        className={`inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-full border-2 border-brand-navy text-brand-navy hover:bg-slate-50 hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all ${fokusRing}`}
                    >
                        <FileSpreadsheet size={16} aria-hidden="true" />
                        Excel
                    </a>
                    <a
                        href={`${route('laporan.pdf', laporan.slug)}?${queryString()}`}
                        className={`inline-flex items-center justify-center gap-2 px-5 py-2 text-sm font-semibold rounded-full text-white bg-gradient-to-r from-brand-green to-brand-green-dark shadow-md shadow-brand-green/25 hover:shadow-lg hover:-translate-y-px active:translate-y-0 transition-all ${fokusRing}`}
                    >
                        <Printer size={16} aria-hidden="true" />
                        Cetak / PDF
                    </a>
                </div>
            </PageHeader>

            {/* Filter periode */}
            <Card padding="sm" className="shadow-md border-slate-200/70 mb-4">
                <div className={`flex flex-wrap items-end gap-x-3 gap-y-1 transition-opacity ${memuat ? 'opacity-50 pointer-events-none' : ''}`}>
                    {tipeFilter === 'bulan' && (
                        <FormField label="Bulan">
                            <TextField
                                size="sm"
                                type="month"
                                value={filter.bulan}
                                onChange={(e) => e.target.value && terapkan({ bulan: e.target.value })}
                                aria-label="Pilih bulan"
                                className="md:w-48 rounded-full border-slate-200 bg-slate-50/60"
                            />
                        </FormField>
                    )}
                    {tipeFilter === 'tahun' && (
                        <FormField label="Tahun" hint="Tekan Enter untuk Terapkan">
                            <TextField
                                size="sm"
                                type="number"
                                inputMode="numeric"
                                min="2000"
                                max="2100"
                                value={tahun}
                                onChange={(e) => setTahun(e.target.value)}
                                onBlur={komitTahun}
                                onKeyDown={(e) => e.key === 'Enter' && e.currentTarget.blur()}
                                aria-label="Pilih tahun"
                                className="w-28 rounded-full border-slate-200 bg-slate-50/60 tabular-nums"
                            />
                        </FormField>
                    )}
                    {tipeFilter === 'tanggal' && (
                        <FormField label="Tanggal Cut-off">
                            <TextField
                                size="sm"
                                type="date"
                                value={filter.tanggal}
                                onChange={(e) => e.target.value && terapkan({ tanggal: e.target.value })}
                                aria-label="Pilih tanggal"
                                className="md:w-48 rounded-full border-slate-200 bg-slate-50/60"
                            />
                        </FormField>
                    )}
                    {tipeFilter === 'rentang' && (
                        <>
                            <FormField label="Dari">
                                <TextField
                                    size="sm"
                                    type="month"
                                    value={filter.dari}
                                    onChange={(e) => e.target.value && terapkan({ dari: e.target.value })}
                                    aria-label="Bulan mulai"
                                    className="md:w-44 rounded-full border-slate-200 bg-slate-50/60"
                                />
                            </FormField>
                            <span className="pb-4 text-slate-400 text-sm">s/d</span>
                            <FormField label="Sampai">
                                <TextField
                                    size="sm"
                                    type="month"
                                    value={filter.sampai}
                                    onChange={(e) => e.target.value && terapkan({ sampai: e.target.value })}
                                    aria-label="Bulan selesai"
                                    className="md:w-44 rounded-full border-slate-200 bg-slate-50/60"
                                />
                            </FormField>
                        </>
                    )}
                    {'cabang' in filter && opsi.cabang && (
                        <FormField label="Cabang">
                            <Select
                                size="sm"
                                value={filter.cabang}
                                onChange={(e) => terapkan({ cabang: e.target.value })}
                                className="md:w-40 rounded-full border-slate-200 bg-slate-50/60"
                            >
                                <option value="">Semua Cabang</option>
                                {opsi.cabang.map((c) => <option key={c} value={c}>{c}</option>)}
                            </Select>
                        </FormField>
                    )}
                    {'kantong' in filter && opsi.kantong && (
                        <FormField label="Kantong">
                            <Select
                                size="sm"
                                value={filter.kantong}
                                onChange={(e) => terapkan({ kantong: e.target.value })}
                                className="md:w-44 rounded-full border-slate-200 bg-slate-50/60"
                            >
                                <option value="">Semua Kantong</option>
                                {Object.entries(opsi.kantong).map(([nilai, label]) => <option key={nilai} value={nilai}>{label}</option>)}
                            </Select>
                        </FormField>
                    )}
                    {'status_anggota' in filter && (
                        <FormField label="Status Anggota">
                            <Select
                                size="sm"
                                value={filter.status_anggota}
                                onChange={(e) => terapkan({ status_anggota: e.target.value })}
                                className="md:w-40 rounded-full border-slate-200 bg-slate-50/60"
                            >
                                <option value="">Semua Status</option>
                                <option value="aktif">Aktif</option>
                                <option value="resign">Resign</option>
                            </Select>
                        </FormField>
                    )}
                    <div className="pb-4">
                        <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-brand-navy/5 text-brand-navy whitespace-nowrap">
                            {memuat
                                ? <Loader2 size={13} aria-hidden="true" className="animate-spin" />
                                : <CalendarDays size={13} aria-hidden="true" />}
                            {periodeLabel}
                        </span>
                    </div>
                </div>
            </Card>

            {/* Hasil */}
            <Card padding="none" className="shadow-md border-slate-200/70">
                <div className="px-4 py-3 border-b border-slate-100 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 className="text-sm font-bold text-slate-800 tabular-nums">{hasil.rows.length} baris data</h2>
                    <p className="text-xs text-slate-400">Periode: <span className="font-bold text-slate-600">{periodeLabel}</span></p>
                </div>

                {hasil.rows.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <p className="text-sm font-semibold text-slate-600">Tidak ada data pada periode ini.</p>
                        <p className="text-sm text-slate-400 mt-1">Coba perlebar rentang periode atau ubah filter.</p>
                        <Link
                            href={route('laporan.show', laporan.slug)}
                            className="inline-flex items-center justify-center gap-2 mt-3 px-4 py-2 text-sm font-semibold rounded-full text-brand-navy border-2 border-brand-navy hover:bg-slate-50 transition-colors"
                        >
                            <RotateCcw size={14} aria-hidden="true" />
                            Kembali ke periode default
                        </Link>
                    </div>
                ) : (
                    <div className={`overflow-x-auto transition-opacity ${memuat ? 'opacity-50' : ''}`} aria-busy={memuat}>
                        <table className="w-full text-sm table-sticky-first">
                            {/* .table-sticky-first thead th memaksa background putih (app.css), jadi header tanpa gradient. */}
                            <thead>
                                <tr className="text-left border-b border-slate-200/80">
                                    {hasil.kolom.map((label, i) => (
                                        <th key={i} scope="col" className={`px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap ${sel(i)}`}>{label}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {hasil.rows.map((row, ri) => (
                                    hasil.gayaBaris?.[ri] === 'section' ? (
                                        <tr key={ri} className="bg-slate-100">
                                            <td colSpan={hasil.kolom.length} className="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-600">{row[0]}</td>
                                        </tr>
                                    ) : (
                                        <tr key={ri} className={gayaBaris(ri)}>
                                            {row.map((cell, ci) => (
                                                <td key={ci} className={`px-4 py-2.5 text-slate-700 ${sel(ci)}`}>{tampilCell(ci, cell)}</td>
                                            ))}
                                        </tr>
                                    )
                                ))}
                            </tbody>
                            {hasil.totals && (
                                <tfoot>
                                    <tr className="border-t-2 border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                                        {hasil.totals.map((cell, i) => (
                                            <td key={i} className={`px-4 py-3 font-bold text-slate-800 ${sel(i)}`}>{cell == null ? '' : tampilCell(i, cell)}</td>
                                        ))}
                                    </tr>
                                </tfoot>
                            )}
                        </table>
                    </div>
                )}

                {(hasil.ringkasan?.length > 0 || hasil.catatan) && (
                    <div className="p-4 border-t border-slate-100">
                        {hasil.ringkasan?.length > 0 && (
                            <dl className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mb-2">
                                {hasil.ringkasan?.map(([label, nilai]) => (
                                    <div key={label} className="rounded-xl bg-slate-50/80 border border-slate-100 px-3 py-2">
                                        <dt className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{label}</dt>
                                        <dd className="text-sm font-bold text-slate-800 tabular-nums mt-0.5">{nilai}</dd>
                                    </div>
                                ))}
                            </dl>
                        )}
                        {hasil.catatan && <p className="text-xs italic text-slate-400">{hasil.catatan}</p>}
                    </div>
                )}
            </Card>
        </AppLayout>
    );
}
