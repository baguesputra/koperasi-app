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

export default function SlipResign({ anggota, settlement }) {
    function handlePrint() {
        window.print();
    }

    return (
        <>
            <Head title={`Slip Resign - ${anggota.nama}`} />

            <style>{`
                @media print {
                    body { background: white !important; }
                    .no-print { display: none !important; }
                    .print-area { box-shadow: none !important; border: none !important; }
                }
            `}</style>

            <div className="min-h-screen bg-slate-100 py-8 px-4">
                <div className="max-w-2xl mx-auto no-print mb-4 flex justify-between items-center">
                    <h1 className="text-lg font-bold text-slate-700">Slip Pengembalian Simpanan</h1>
                    <button
                        onClick={handlePrint}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-brand-green text-white text-sm font-semibold rounded-xl hover:bg-brand-green/90"
                    >
                        <Printer size={16} />
                        Cetak / Simpan PDF
                    </button>
                </div>

                <div className="print-area max-w-2xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
                    <div className="text-center border-b border-slate-200 pb-5 mb-6">
                        <h2 className="text-2xl font-bold text-slate-800">KOPERASI KARYAWAN</h2>
                        <p className="text-sm text-slate-500 mt-1">Slip Pengembalian Simpanan & Pelunasan Pinjaman</p>
                        <p className="text-xs text-slate-400 mt-1">Nomor: SLIP-RESIGN/{anggota.no_anggota}/{settlement.tanggal_proses ?? '-'}</p>
                    </div>

                    <section className="mb-6">
                        <h3 className="text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Data Anggota</h3>
                        <table className="w-full text-sm">
                            <tbody>
                                <Row label="No. Anggota" value={anggota.no_anggota} />
                                <Row label="No. Karyawan" value={anggota.no_karyawan} />
                                <Row label="Nama" value={anggota.nama} />
                                <Row label="Cabang" value={anggota.cabang} />
                                <Row label="Unit Bisnis" value={anggota.unit_bisnis} />
                                <Row label="Jabatan" value={anggota.jabatan} />
                                <Row label="Tanggal Jadi Anggota" value={formatTanggal(anggota.tanggal_jadi_anggota)} />
                                <Row label="Tanggal Resign" value={formatTanggal(anggota.tanggal_resign)} />
                                <Row label="Alasan" value={anggota.alasan_resign ?? '-'} multiline />
                            </tbody>
                        </table>
                    </section>

                    <section className="mb-6">
                        <h3 className="text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Ringkasan Simpanan</h3>
                        <table className="w-full text-sm">
                            <tbody>
                                <Row label="Total Simpanan Pokok" value={formatRupiah(settlement.simpanan_pokok_total)} />
                                <Row label="Total Simpanan Wajib" value={formatRupiah(settlement.simpanan_wajib_total)} />
                                <Row label="Dana Sosial (hangus)" value={formatRupiah(settlement.dana_sosial_hangus)} />
                            </tbody>
                        </table>
                    </section>

                    <section className="mb-6">
                        <h3 className="text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Pelunasan Pinjaman</h3>
                        <table className="w-full text-sm">
                            <tbody>
                                <Row label="Total Tagihan Pelunasan" value={formatRupiah(settlement.tagihan_pelunasan)} />
                                <Row label="Alokasi dari Simpanan Pokok" value={formatRupiah(settlement.alokasi_dari_pokok)} />
                                <Row label="Alokasi dari Simpanan Wajib" value={formatRupiah(settlement.alokasi_dari_wajib)} />
                            </tbody>
                        </table>
                    </section>

                    <section className="rounded-xl bg-brand-green-light p-4 mb-6">
                        <h3 className="text-sm font-bold text-brand-green-dark mb-3 uppercase tracking-wider">Pengembalian ke Anggota</h3>
                        <table className="w-full text-sm">
                            <tbody>
                                <Row label="Kembali ke Simpanan Pokok" value={formatRupiah(settlement.kembali_pokok)} />
                                <Row label="Kembali ke Simpanan Wajib" value={formatRupiah(settlement.kembali_wajib)} />
                                <tr>
                                    <td className="py-2 text-slate-700 font-bold">TOTAL DIKEMBALIKAN</td>
                                    <td className="py-2 text-right text-xl font-bold text-brand-green-dark">
                                        {formatRupiah(settlement.total_dikembalikan)}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section className="mb-6 text-xs text-slate-500">
                        <p>
                            <strong>Catatan:</strong> Dana sosial bersifat hangus dan tidak dikembalikan.
                            Pengembalian telah dicatat sebagai jurnal keluar kas koperasi.
                            Akun login anggota telah dinonaktifkan per tanggal resign.
                        </p>
                    </section>

                    <div className="grid grid-cols-2 gap-8 pt-6 border-t border-slate-200">
                        <div className="text-center">
                            <p className="text-sm text-slate-600 mb-20">Anggota</p>
                            <p className="text-sm font-semibold text-slate-800 border-t border-slate-300 pt-1">
                                {anggota.nama}
                            </p>
                        </div>
                        <div className="text-center">
                            <p className="text-sm text-slate-600 mb-20">Bendahara</p>
                            <p className="text-sm font-semibold text-slate-800 border-t border-slate-300 pt-1">
                                {settlement.aktor ?? '________________'}
                            </p>
                        </div>
                    </div>

                    <p className="text-center text-xs text-slate-400 mt-8">
                        Slip ini dicetak otomatis oleh sistem pada {formatTanggal(new Date().toISOString().slice(0, 10))}
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
