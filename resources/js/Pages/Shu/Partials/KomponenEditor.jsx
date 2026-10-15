import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Calculator, Plus, Send, Trash2 } from 'lucide-react';
import Card from '@/Components/ui/Card';
import AnggotaPicker from './AnggotaPicker';
import Button from '@/Components/ui/Button';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import { formatRupiah } from '@/Utils/formatCurrency';
import { withIdempotencyKey } from '@/Utils/idempotency';
import { KOMPONEN_LABEL, KOMPONEN_ORDER, PCT_COL, persenValid, pesanPenyeimbang, selisihPersen, totalPersen } from '@/Utils/shuLive';
import RingkasanDana from './RingkasanDana';

/**
 * Editor 8 persen + rincian pengurus/pengawas.
 * Semua angka preview dihitung live di browser (mirror Excel);
 * Simpan = persist ke server, Hitung Ulang = recalc rincian anggota.
 */
export default function KomponenEditor({ periode, persenLive, setPersenLive, preview, sudahDihitung, daftarAnggota = [] }) {
    const { errors } = usePage().props;
    const [menyimpan, setMenyimpan] = useState(false);
    const total = totalPersen(persenLive);
    const valid = persenValid(persenLive);
    const selisih = selisihPersen(persenLive);
    const pesanValidasi = pesanPenyeimbang(periode.persen, persenLive, total, valid, selisih);

    const [pengurus, setPengurus] = useState(periode.pengurus_rincian ?? []);
    const [pengawas, setPengawas] = useState(periode.pengawas_rincian ?? []);
    const [menyimpanRincian, setMenyimpanRincian] = useState(false);

    function ubahPersen(kunci, persenDesimal) {
        const kolom = PCT_COL[kunci];
        setPersenLive({ ...persenLive, [kolom]: persenDesimal });
    }

    function simpanPersen() {
        setMenyimpan(true);
        router.put(
            route('shu.persen.update', periode.id),
            { persen: persenLive },
            { preserveScroll: true, onFinish: () => setMenyimpan(false) }
        );
    }

    function hitung() {
        router.post(route('shu.hitung', periode.id), {}, withIdempotencyKey({ preserveScroll: true }));
    }

    function ajukan() {
        if (!confirm(`Ajukan SHU ${periode.tahun_buku} untuk dibahas meeting? Periode akan dikunci.`)) return;
        router.post(route('shu.ajukan', periode.id), {}, withIdempotencyKey({ preserveScroll: true }));
    }

    function simpanRincian() {
        setMenyimpanRincian(true);
        router.post(
            route('shu.rincian-pengelola', periode.id),
            { pengurus, pengawas },
            {
                preserveScroll: true,
                onFinish: () => setMenyimpanRincian(false),
            }
        );
    }

    return (
        <div className="space-y-4">
            <Card>
                <div className="flex flex-wrap items-center gap-2 mb-1">
                    <p className="text-base font-bold text-slate-800">Besaran Persentase</p>
                    <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">
                        Live — angka ikut berubah saat diketik
                    </span>
                </div>

                <p
                    role="status"
                    aria-atomic="true"
                    className={`text-sm mb-3 ${pesanValidasi.nada === 'ok' ? 'text-brand-green-dark font-semibold' : 'text-slate-600'}`}
                >
                    {pesanValidasi.teks} Simpanan + Transaksi untuk Anggota {formatRupiah(preview.pool_anggota)} •
                    Dana yang Dibagikan {formatRupiah(preview.dana_dibagi)}
                </p>

                <div className="w-full h-2 rounded-full bg-slate-100 overflow-hidden mb-4" aria-hidden="true">
                    <div
                        className={`h-full rounded-full transition-all ${valid ? 'bg-brand-green' : 'bg-red-500'}`}
                        style={{ width: `${Math.min(total * 100, 100)}%` }}
                    />
                </div>

                <div className="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
                    {KOMPONEN_ORDER.map((kunci) => {
                        const kolom = PCT_COL[kunci];
                        const nilai = Math.round((persenLive?.[kolom] ?? 0) * 10000) / 100;
                        const awal = Math.round((periode.persen?.[kolom] ?? 0) * 10000) / 100;
                        const diubah = nilai !== awal;
                        return (
                            <div key={kunci} className={`rounded-xl px-3 py-2 ${diubah ? 'bg-amber-50 ring-1 ring-amber-300' : 'bg-slate-50'}`}>
                                <label htmlFor={`persen-${kolom}`} className="block text-xs font-semibold text-slate-600 mb-1 leading-tight min-h-8">
                                    {KOMPONEN_LABEL[kunci]}
                                </label>
                                <div className="flex items-baseline gap-1">
                                    <TextField
                                        id={`persen-${kolom}`}
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={nilai}
                                        onChange={(e) => ubahPersen(kunci, (parseFloat(e.target.value) || 0) / 100)}
                                        className="!px-2 !py-1.5 text-right tabular-nums font-bold"
                                        aria-label={`${KOMPONEN_LABEL[kunci]} persen`}
                                    />
                                    <span className="text-xs text-slate-400 shrink-0">%</span>
                                </div>
                                <p className="text-xs tabular-nums text-slate-500 mt-1 truncate" title={formatRupiah(preview.komponen?.[kunci] ?? 0)}>
                                    {formatRupiah(preview.komponen?.[kunci] ?? 0)}
                                </p>
                            </div>
                        );
                    })}
                </div>

                {errors.persen && <p className="text-sm text-red-600 mb-2">{errors.persen}</p>}

                <div className="flex flex-wrap gap-2">
                    <Button size="sm" onClick={simpanPersen} disabled={menyimpan || !valid}>
                        {menyimpan ? 'Menyimpan...' : 'Simpan Persen + Hitung Ulang'}
                    </Button>
                    <Button size="sm" variant="secondary" onClick={hitung}>
                        <Calculator size={16} />
                        Hitung Ulang
                    </Button>
                    <Button size="sm" variant="secondary" onClick={ajukan} disabled={!sudahDihitung}>
                        <Send size={16} />
                        Ajukan Pembahasan
                    </Button>
                </div>
                {!sudahDihitung && (
                    <p className="text-xs text-slate-400 mt-2">Hitung dulu rincian anggota sebelum bisa diajukan.</p>
                )}
            </Card>

            <RingkasanDana preview={preview} />

            <Card>
                <p className="text-base font-bold text-slate-800 mb-1">SHU Pengurus Koperasi & SHU Pengawas</p>
                <p className="text-sm text-slate-500 mb-3">
                    Nominal per orang sesuai keputusan RAT. Saldo yang dialokasikan pengurus {formatRupiah(preview.komponen?.pengurus ?? 0)} •
                    pengawas {formatRupiah(preview.komponen?.pengawas ?? 0)}
                </p>
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <PengurusTable
                        baris={pengurus}
                        setBaris={setPengurus}
                        alokasi={preview.komponen?.pengurus ?? 0}
                        daftarAnggota={daftarAnggota}
                    />
                    <RincianTable
                        judul="SHU Pengawas"
                        baris={pengawas}
                        setBaris={setPengawas}
                        alokasi={preview.komponen?.pengawas ?? 0}
                    />
                </div>
                <div className="mt-3">
                    <Button size="sm" variant="secondary" onClick={simpanRincian} disabled={menyimpanRincian}>
                        Simpan Rincian
                    </Button>
                </div>
            </Card>
        </div>
    );
}

/** Pengurus dipilih dari anggota (picker ketik-cari) + jabatan teks + nominal. */
function PengurusTable({ baris, setBaris, alokasi, daftarAnggota }) {
    const total = baris.reduce((a, r) => a + (parseFloat(r.nominal) || 0), 0);
    const sisa = alokasi - total;

    function tambah() {
        setBaris([...baris, { anggota_id: null, jabatan: '', nominal: '' }]);
    }

    function ubah(i, field, nilai) {
        setBaris(baris.map((r, j) => (j === i ? { ...r, [field]: nilai } : r)));
    }

    function hapus(i) {
        setBaris(baris.filter((_, j) => j !== i));
    }

    return (
        <div className="rounded-xl border border-slate-100 overflow-hidden">
            <p className="text-sm font-bold text-slate-700 px-3 py-2 bg-slate-50">SHU Pengurus Koperasi (dari anggota)</p>
            {baris.length === 0 && <p className="text-sm text-slate-400 px-3 py-3">Belum ada rincian.</p>}
            {baris.map((r, i) => (
                <div key={i} className="px-3 py-2 border-t border-slate-50 space-y-2">
                    <div className="flex items-start gap-2">
                        <div className="flex-1 min-w-0">
                            <AnggotaPicker
                                daftar={daftarAnggota}
                                nilai={r.anggota_id ?? null}
                                onPilih={(id) => ubah(i, 'anggota_id', id)}
                            />
                            {!r.anggota_id && r.nama && (
                                <p className="text-xs text-slate-400 mt-1">
                                    Input lama: {r.nama} <span className="font-semibold">(pilih anggota untuk memetakan)</span>
                                </p>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={() => hapus(i)}
                            aria-label={`Hapus pengurus ${i + 1}`}
                            className="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 shrink-0"
                        >
                            <Trash2 size={16} />
                        </button>
                    </div>
                    <div className="flex items-center gap-2">
                        <FormField label="Jabatan" className="flex-1">
                            <TextField
                                placeholder="cth. Ketua, Sekretaris DM"
                                value={r.jabatan ?? ''}
                                onChange={(e) => ubah(i, 'jabatan', e.target.value)}
                            />
                        </FormField>
                        <FormField label="Nominal" className="w-36">
                            <TextField
                                type="number"
                                min="0"
                                placeholder="Nominal"
                                value={r.nominal}
                                onChange={(e) => ubah(i, 'nominal', e.target.value)}
                                className="text-right"
                            />
                        </FormField>
                    </div>
                </div>
            ))}
            <div className="flex items-center justify-between px-3 py-2 border-t border-slate-100 bg-slate-50/60">
                <button
                    type="button"
                    onClick={tambah}
                    className="text-sm font-semibold text-brand-green-dark inline-flex items-center gap-1 hover:underline"
                >
                    <Plus size={14} />
                    Tambah orang
                </button>
                <p className="text-sm tabular-nums text-slate-600">
                    {formatRupiah(total)} • Sisa dana <strong className={sisa < 0 ? 'text-red-600' : ''}>{formatRupiah(sisa)}</strong>
                </p>
            </div>
        </div>
    );
}

function RincianTable({ judul, baris, setBaris, alokasi }) {
    const total = baris.reduce((a, r) => a + (parseFloat(r.nominal) || 0), 0);
    const sisa = alokasi - total;

    function tambah() {
        setBaris([...baris, { nama: '', nominal: '' }]);
    }

    function ubah(i, field, nilai) {
        setBaris(baris.map((r, j) => (j === i ? { ...r, [field]: nilai } : r)));
    }

    function hapus(i) {
        setBaris(baris.filter((_, j) => j !== i));
    }

    return (
        <div className="rounded-xl border border-slate-100 overflow-hidden">
            <p className="text-sm font-bold text-slate-700 px-3 py-2 bg-slate-50">{judul}</p>
            {baris.length === 0 && <p className="text-sm text-slate-400 px-3 py-3">Belum ada rincian.</p>}
            {baris.map((r, i) => (
                <div key={i} className="flex items-center gap-2 px-3 py-2 border-t border-slate-50">
                    <FormField className="flex-1">
                        <TextField
                            placeholder="Nama"
                            value={r.nama}
                            onChange={(e) => ubah(i, 'nama', e.target.value)}
                        />
                    </FormField>
                    <FormField className="w-32">
                        <TextField
                            type="number"
                            min="0"
                            placeholder="Nominal"
                            value={r.nominal}
                            onChange={(e) => ubah(i, 'nominal', e.target.value)}
                            className="text-right"
                        />
                    </FormField>
                    <button
                        type="button"
                        onClick={() => hapus(i)}
                        aria-label={`Hapus ${judul} ${i + 1}`}
                        className="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50"
                    >
                        <Trash2 size={16} />
                    </button>
                </div>
            ))}
            <div className="flex items-center justify-between px-3 py-2 border-t border-slate-100 bg-slate-50/60">
                <button
                    type="button"
                    onClick={tambah}
                    className="text-sm font-semibold text-brand-green-dark inline-flex items-center gap-1 hover:underline"
                >
                    <Plus size={14} />
                    Tambah orang
                </button>
                <p className="text-sm tabular-nums text-slate-600">
                    {formatRupiah(total)} • Sisa dana <strong className={sisa < 0 ? 'text-red-600' : ''}>{formatRupiah(sisa)}</strong>
                </p>
            </div>
        </div>
    );
}
