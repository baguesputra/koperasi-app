import { Head } from '@inertiajs/react';
import { Printer } from 'lucide-react';

function formatRupiah(n) {
    return 'Rp ' + Number(n ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

function formatTanggal(iso) {
    if (!iso) return '-';
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

export default function CetakBukti({ pinjaman, angsuran, totals }) {
    function handlePrint() {
        window.print();
    }

    return (
        <>
            <Head title={`Bukti Peminjaman - ${pinjaman.anggota.nama}`} />

            <style>{`
                @media print {
                    body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .no-print { display: none !important; }
                    .print-area { box-shadow: none !important; border: none !important; }
                    @page { margin: 1.5cm; }
                }
            `}</style>

            <div className="min-h-screen bg-slate-100 py-8 px-4">
                <div className="max-w-3xl mx-auto no-print mb-4 flex justify-between items-center">
                    <h1 className="text-lg font-bold text-slate-700">Bukti Peminjaman</h1>
                    <button
                        onClick={handlePrint}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-brand-green text-white text-sm font-semibold rounded-xl hover:bg-brand-green/90"
                    >
                        <Printer size={16} />
                        Cetak / Simpan PDF
                    </button>
                </div>

                <div className="print-area max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
<div className="flex items-center border-b border-slate-200 pb-5 mb-6">
    <img src="/images/logo.png" alt="Logo" className="h-10 w-auto mr-4" />
    <div>
        <h2 className="text-2xl font-bold text-slate-800">KOPERASI KARYAWAN</h2>
        <p className="text-sm text-slate-500 mt-1">Bukti Peminjaman</p>
        <p className="text-xs text-slate-400 mt-1">
            Nomor: BUKTI-PJM/{pinjaman.anggota.no_anggota}/{pinjaman.id}
        </p>
    </div>
</div>

                    <section className="mb-6">
                        <h3 className="text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Data Anggota</h3>
                        <table className="w-full text-sm">
                            <tbody>
                                <Row label="No. Anggota" value={pinjaman.anggota.no_anggota} />
                                <Row label="No. Karyawan" value={pinjaman.anggota.no_karyawan} />
                                <Row label="Nama" value={pinjaman.anggota.nama} />
                                <Row label="Cabang" value={pinjaman.anggota.cabang} />
                                <Row label="Unit Bisnis" value={pinjaman.anggota.unit_bisnis} />
                                <Row label="Jabatan" value={pinjaman.anggota.jabatan} />
                            </tbody>
                        </table>
                    </section>

                    <section className="mb-6">
                        <h3 className="text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Detail Pinjaman</h3>
                        <table className="w-full text-sm">
                            <tbody>
                                <Row label="Nominal Pinjaman" value={formatRupiah(pinjaman.nominal)} />
                                <Row label="Terbilang" value={pinjaman.terbilang} />
                                <Row label="Tenor" value={`${pinjaman.tenor_bulan} bulan`} />
                                <Row label="Bunga" value={`${pinjaman.persentase_bunga}% / bulan (menurun)`} />
                                <Row label="Tanggal Pengajuan" value={formatTanggal(pinjaman.tanggal_pengajuan)} />
                                <Row label="Tanggal Cair" value={formatTanggal(pinjaman.tanggal_cair)} />
                                <Row label="Keperluan" value={pinjaman.keperluan || '-'} multiline />
                            </tbody>
                        </table>
                    </section>

                    <section className="mb-6">
                        <h3 className="text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Rekening Tujuan Pencairan</h3>
                        <div className="rounded-xl bg-slate-50 p-4">
                            <p className="text-sm font-bold text-slate-800">{pinjaman.rekening.bank}</p>
                            <p className="text-sm text-slate-600">{pinjaman.rekening.no_rekening}</p>
                            <p className="text-xs text-slate-500 mt-1">a.n. {pinjaman.rekening.atas_nama}</p>
                        </div>
                    </section>

                    <section className="mb-6">
                        <h3 className="text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Jadwal Angsuran</h3>
                        <table className="w-full text-sm border-collapse">
                            <thead>
                                <tr className="border-y border-slate-300 text-left">
                                    <th className="py-2 px-2 text-xs font-bold text-slate-600">#</th>
                                    <th className="py-2 px-2 text-xs font-bold text-slate-600">Jatuh Tempo</th>
                                    <th className="py-2 px-2 text-xs font-bold text-slate-600 text-right">Pokok</th>
                                    <th className="py-2 px-2 text-xs font-bold text-slate-600 text-right">Bunga</th>
                                    <th className="py-2 px-2 text-xs font-bold text-slate-600 text-right">Total Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                {angsuran.map((a) => (
                                    <tr key={a.cicilan_ke} className="border-b border-slate-100">
                                        <td className="py-2 px-2 text-slate-700">{a.cicilan_ke}</td>
                                        <td className="py-2 px-2 text-slate-600">{a.tanggal_jatuh_tempo}</td>
                                        <td className="py-2 px-2 text-slate-700 text-right">{formatRupiah(a.nominal_pokok)}</td>
                                        <td className="py-2 px-2 text-slate-700 text-right">{formatRupiah(a.nominal_bunga)}</td>
                                        <td className="py-2 px-2 text-slate-800 text-right font-semibold">{formatRupiah(a.total_bayar)}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 border-slate-300">
                                    <td colSpan={2} className="py-2 px-2 text-sm font-bold text-slate-700">TOTAL</td>
                                    <td className="py-2 px-2 text-sm font-bold text-slate-800 text-right">{formatRupiah(totals.pokok)}</td>
                                    <td className="py-2 px-2 text-sm font-bold text-slate-800 text-right">{formatRupiah(totals.bunga)}</td>
                                    <td className="py-2 px-2 text-sm font-bold text-brand-green-dark text-right">{formatRupiah(totals.angsuran)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </section>

                    <section className="mb-6 text-xs text-slate-500 italic">
                        <p>
                            Catatan: Angsuran dibayar setiap tanggal jatuh tempo. Keterlambatan akan dikenai
                            kebijakan internal koperasi. Peminjaman ini dilindungi oleh aturan simpan &
                            pinjam koperasi yang berlaku.
                        </p>
                    </section>

                    <div className="grid grid-cols-2 gap-8 pt-6 border-t border-slate-200">
                        <div className="text-center">
                            <p className="text-sm text-slate-600 mb-20">Anggota</p>
                            <p className="text-sm font-semibold text-slate-800 border-t border-slate-300 pt-1">
                                {pinjaman.anggota.nama}
                            </p>
                        </div>
                        <div className="text-center">
                            <p className="text-sm text-slate-600 mb-20">Bendahara</p>
                            <p className="text-sm font-semibold text-slate-800 border-t border-slate-300 pt-1">
                                ________________
                            </p>
                        </div>
                    </div>

                    <p className="text-center text-xs text-slate-400 mt-8">
                        Dokumen ini dicetak otomatis oleh sistem pada {formatTanggal(new Date().toISOString().slice(0, 10))}
                    </p>
                </div>
            </div>
        </>
    );
}

function Row({ label, value, multiline = false }) {
    return (
        <tr>
            <td className="py-2 text-slate-600 align-top w-1/2">{label}</td>
            <td className={`py-2 text-right align-top font-medium text-slate-800 ${multiline ? 'whitespace-pre-line text-left' : ''}`}>
                {value}
            </td>
        </tr>
    );
}
