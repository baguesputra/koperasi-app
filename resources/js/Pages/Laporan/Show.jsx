import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { FileSpreadsheet, Printer } from 'lucide-react';
import Card from '@/Components/ui/Card';
import BackLink from '@/Components/ui/BackLink';
import Button from '@/Components/ui/Button';
import { formatRupiah } from '@/Utils/formatCurrency';

const fokusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40';

export default function Show({ laporan, filter, opsi, periodeLabel, hasil }) {
    const tipeFilter = 'bulan' in filter ? 'bulan' : 'tahun' in filter ? 'tahun' : 'tanggal' in filter ? 'tanggal' : 'dari' in filter ? 'rentang' : null;

    function terapkan(perubahan) {
        router.get(route('laporan.show', laporan.slug), { ...filter, ...perubahan }, { preserveState: true });
    }

    function queryString() {
        const params = Object.fromEntries(Object.entries(filter).filter(([, v]) => v !== '' && v != null));
        return new URLSearchParams(params).toString();
    }

    const sel = (i) => (hasil.rataKanan.includes(i) ? 'text-right whitespace-nowrap' : '');

    const tampilCell = (i, cell) => (hasil.rataKanan.includes(i) && typeof cell === 'number' ? formatRupiah(cell) : cell);

    return (
        <AppLayout>
            <Head title={laporan.judul} />

            <BackLink href={route('laporan.index')}>Semua Laporan</BackLink>

            <div className="mb-6 mt-3">
                <h1 className="text-2xl font-bold text-slate-800">{laporan.judul}</h1>
                <p className="text-base text-slate-400 mt-1">{laporan.deskripsi}</p>
            </div>

            {/* Filter + tombol keluaran */}
            <Card className="mb-5">
                <div className="flex flex-col lg:flex-row lg:items-end gap-4">
                    <div className="flex flex-wrap items-end gap-3 flex-1">
                        {tipeFilter === 'bulan' && (
                            <div>
                                <label htmlFor="f-bulan" className="block text-sm font-semibold text-slate-600 mb-1.5">Bulan</label>
                                <input
                                    id="f-bulan" type="month" value={filter.bulan}
                                    onChange={(e) => e.target.value && terapkan({ bulan: e.target.value })}
                                    aria-label="Pilih bulan"
                                    className={`px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                                />
                            </div>
                        )}
                        {tipeFilter === 'tahun' && (
                            <div>
                                <label htmlFor="f-tahun" className="block text-sm font-semibold text-slate-600 mb-1.5">Tahun</label>
                                <input
                                    id="f-tahun" type="number" min="2000" max="2100" value={filter.tahun}
                                    onChange={(e) => e.target.value.length === 4 && terapkan({ tahun: Number(e.target.value) })}
                                    aria-label="Pilih tahun"
                                    className={`w-24 px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                                />
                            </div>
                        )}
                        {tipeFilter === 'tanggal' && (
                            <div>
                                <label htmlFor="f-tanggal" className="block text-sm font-semibold text-slate-600 mb-1.5">Tanggal Cut-off</label>
                                <input
                                    id="f-tanggal" type="date" value={filter.tanggal}
                                    onChange={(e) => e.target.value && terapkan({ tanggal: e.target.value })}
                                    aria-label="Pilih tanggal"
                                    className={`px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                                />
                            </div>
                        )}
                        {tipeFilter === 'rentang' && (
                            <>
                                <div>
                                    <label htmlFor="f-dari" className="block text-sm font-semibold text-slate-600 mb-1.5">Dari</label>
                                    <input
                                        id="f-dari" type="month" value={filter.dari}
                                        onChange={(e) => e.target.value && terapkan({ dari: e.target.value })}
                                        aria-label="Bulan mulai"
                                        className={`px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                                    />
                                </div>
                                <span className="pb-2.5 text-slate-400">s/d</span>
                                <div>
                                    <label htmlFor="f-sampai" className="sr-only">Sampai</label>
                                    <input
                                        id="f-sampai" type="month" value={filter.sampai}
                                        onChange={(e) => e.target.value && terapkan({ sampai: e.target.value })}
                                        aria-label="Bulan selesai"
                                        className={`px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                                    />
                                </div>
                            </>
                        )}
                        {'cabang' in filter && opsi.cabang && (
                            <div>
                                <label htmlFor="f-cabang" className="block text-sm font-semibold text-slate-600 mb-1.5">Cabang</label>
                                <select
                                    id="f-cabang" value={filter.cabang}
                                    onChange={(e) => terapkan({ cabang: e.target.value })}
                                    className={`px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                                >
                                    <option value="">Semua Cabang</option>
                                    {opsi.cabang.map((c) => <option key={c} value={c}>{c}</option>)}
                                </select>
                            </div>
                        )}
                        {'status_anggota' in filter && (
                            <div>
                                <label htmlFor="f-status" className="block text-sm font-semibold text-slate-600 mb-1.5">Status Anggota</label>
                                <select
                                    id="f-status" value={filter.status_anggota}
                                    onChange={(e) => terapkan({ status_anggota: e.target.value })}
                                    className={`px-3 py-2.5 text-base rounded-xl border border-slate-300 bg-white focus:border-brand-green outline-none ${fokusRing}`}
                                >
                                    <option value="">Semua Status</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="resign">Resign</option>
                                </select>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <a
                            href={`${route('laporan.export', laporan.slug)}?${queryString()}`}
                            className={`inline-flex items-center justify-center gap-2 min-h-[44px] px-4 py-2 text-sm font-semibold rounded-xl border-2 border-brand-navy text-brand-navy hover:bg-slate-50 transition-colors ${fokusRing}`}
                        >
                            <FileSpreadsheet size={16} aria-hidden="true" />
                            Excel
                        </a>
                        <a
                            href={`${route('laporan.pdf', laporan.slug)}?${queryString()}`}
                            className={`inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-2 text-sm font-semibold rounded-xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors ${fokusRing}`}
                        >
                            <Printer size={16} aria-hidden="true" />
                            Cetak / PDF
                        </a>
                    </div>
                </div>
            </Card>

            {/* Hasil */}
            <Card padding="none">
                <div className="p-5 border-b border-slate-100 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 className="text-base font-semibold text-slate-700">{hasil.rows.length} baris data</h2>
                    <p className="text-sm text-slate-400">Periode: <span className="font-semibold text-slate-600">{periodeLabel}</span></p>
                </div>

                {hasil.rows.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <p className="text-base text-slate-500">Tidak ada data pada periode ini.</p>
                        <p className="text-sm text-slate-400 mt-1">Coba perlebar rentang periode atau ubah filter.</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm table-sticky-first">
                            <thead>
                                <tr className="bg-slate-50 border-y border-slate-200">
                                    {hasil.kolom.map((label, i) => (
                                        <th key={i} scope="col" className={`px-4 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 whitespace-nowrap ${sel(i)}`}>{label}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {hasil.rows.map((row, ri) => (
                                    <tr key={ri} className="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                        {row.map((cell, ci) => (
                                            <td key={ci} className={`px-4 py-2.5 text-slate-700 ${sel(ci)}`}>{tampilCell(ci, cell)}</td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                            {hasil.totals && (
                                <tfoot>
                                    <tr className="border-t-2 border-slate-200 bg-slate-50">
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
                    <div className="p-5 border-t border-slate-100 space-y-2">
                        {hasil.ringkasan?.map(([label, nilai]) => (
                            <p key={label} className="text-sm text-slate-500">
                                {label}: <span className="font-bold text-slate-800">{nilai}</span>
                            </p>
                        ))}
                        {hasil.catatan && <p className="text-xs italic text-slate-400">{hasil.catatan}</p>}
                    </div>
                )}
            </Card>
        </AppLayout>
    );
}
