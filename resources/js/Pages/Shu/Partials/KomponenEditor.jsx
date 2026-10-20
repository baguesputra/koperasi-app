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
import { KOMPONEN_LABEL, KOMPONEN_ORDER, PCT_COL, persenValid, pesanPenyeimbang, selisihPersen, slugKomponen, totalPersen } from '@/Utils/shuLive';
import RingkasanDana from './RingkasanDana';

/**
 * Editor & Viewer 8 persen bawaan (permanen) + N komponen tambahan + rincian pengurus/pengawas.
 * Mode draft: interaktif live edit (mirror Excel), hitung ulang, dan ajukan.
 * Mode readOnly (diajukan / final): data tetap tampil lengkap (komponen, pengurus, pengawas, ringkasan dana).
 */
export default function KomponenEditor({
    periode,
    persenLive,
    setPersenLive,
    customLive,
    setCustomLive,
    preview,
    ringkasan,
    sudahDihitung,
    daftarAnggota = [],
    readOnly = false,
}) {
    const { errors } = usePage().props;
    const [menyimpan, setMenyimpan] = useState(false);
    const total = totalPersen(persenLive, customLive);
    const valid = persenValid(persenLive, customLive);
    const selisih = selisihPersen(persenLive, customLive);
    const pesanValidasi = pesanPenyeimbang(periode.persen, persenLive, total, valid, selisih);

    const [pengurus, setPengurus] = useState(periode.pengurus_rincian ?? []);
    const [pengawas, setPengawas] = useState(periode.pengawas_rincian ?? []);
    const [menyimpanRincian, setMenyimpanRincian] = useState(false);

    function ubahPersen(kunci, persenDesimal) {
        if (readOnly) return;
        const kolom = PCT_COL[kunci];
        setPersenLive({ ...persenLive, [kolom]: persenDesimal });
    }

    function simpanPersen() {
        if (readOnly) return;
        setMenyimpan(true);
        router.put(
            route('shu.persen.update', periode.id),
            {
                persen: persenLive,
                komponen_custom: (customLive ?? []).map((r) => ({ label: r.label, persen: r.persen })),
            },
            { preserveScroll: true, onFinish: () => setMenyimpan(false) }
        );
    }

    function tambahCustom() {
        if (readOnly) return;
        setCustomLive([...(customLive ?? []), { label: '', persen: 0 }]);
    }

    function ubahCustom(i, field, nilai) {
        if (readOnly) return;
        setCustomLive((customLive ?? []).map((r, j) => (j === i ? { ...r, [field]: nilai } : r)));
    }

    function hapusCustom(i) {
        if (readOnly) return;
        setCustomLive((customLive ?? []).filter((_, j) => j !== i));
    }

    function hitung() {
        if (readOnly) return;
        router.post(route('shu.hitung', periode.id), {}, withIdempotencyKey({ preserveScroll: true }));
    }

    function ajukan() {
        if (readOnly) return;
        if (!confirm(`Ajukan SHU ${periode.tahun_buku} untuk dibahas meeting? Periode akan dikunci.`)) return;
        router.post(route('shu.ajukan', periode.id), {}, withIdempotencyKey({ preserveScroll: true }));
    }

    function simpanRincian() {
        if (readOnly) return;
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
                    {readOnly ? (
                        <span className={`text-xs font-bold px-2.5 py-0.5 rounded-full ${periode.status === 'final' ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800'}`}>
                            {periode.status === 'final' ? 'Final — Terkunci (Read-only)' : 'Diajukan — Terkunci (Read-only)'}
                        </span>
                    ) : (
                        <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">
                            Live — angka ikut berubah saat diketik
                        </span>
                    )}
                </div>

                {readOnly ? (
                    <p className="text-sm mb-3 text-slate-600">
                        Total alokasi 100% • Simpanan + Transaksi untuk Anggota <strong className="text-slate-800">{formatRupiah(preview.pool_anggota)}</strong> • Dana yang Dibagikan <strong className="text-slate-800">{formatRupiah(preview.dana_dibagi)}</strong>
                    </p>
                ) : (
                    <p
                        role="status"
                        aria-atomic="true"
                        className={`text-sm mb-3 ${pesanValidasi.nada === 'ok' ? 'text-brand-green-dark font-semibold' : 'text-slate-600'}`}
                    >
                        {pesanValidasi.teks} Simpanan + Transaksi untuk Anggota {formatRupiah(preview.pool_anggota)} •
                        Dana yang Dibagikan {formatRupiah(preview.dana_dibagi)}
                    </p>
                )}

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
                            <div key={kunci} className={`rounded-xl px-3 py-2 ${diubah && !readOnly ? 'bg-amber-50 ring-1 ring-amber-300' : 'bg-slate-50'}`}>
                                <label htmlFor={`persen-${kolom}`} className="block text-xs font-semibold text-slate-600 mb-1 leading-tight min-h-8">
                                    {KOMPONEN_LABEL[kunci]}
                                </label>
                                {readOnly ? (
                                    <div>
                                        <p className="text-lg font-bold text-slate-800 tabular-nums">
                                            {nilai}%
                                        </p>
                                    </div>
                                ) : (
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
                                )}
                                <p className="text-xs tabular-nums text-slate-500 mt-1 truncate" title={formatRupiah(preview.komponen?.[kunci] ?? 0)}>
                                    {formatRupiah(preview.komponen?.[kunci] ?? 0)}
                                </p>
                            </div>
                        );
                    })}
                </div>

                <div className="rounded-xl border border-slate-200 overflow-hidden mb-4">
                    <div className="flex items-center justify-between px-3 py-2 bg-slate-50">
                        <p className="text-sm font-bold text-slate-700">Komponen tambahan <span className="font-normal text-slate-400">(dana tak dibagi)</span></p>
                        {!readOnly && (
                            <button
                                type="button"
                                onClick={tambahCustom}
                                className="text-sm font-semibold text-brand-green-dark inline-flex items-center gap-1 hover:underline"
                            >
                                <Plus size={14} />
                                Tambah pembagian
                            </button>
                        )}
                    </div>
                    {(customLive ?? []).length === 0 && (
                        <p className="text-sm text-slate-400 px-3 py-3">Belum ada komponen tambahan. 8 bawaan di atas permanen dan tidak bisa dihapus.</p>
                    )}
                    {(customLive ?? []).map((r, i) => {
                        const slug = r.slug || slugKomponen(r.label);
                        const nilaiPersen = Math.round((parseFloat(r.persen) || 0) * 10000) / 100;
                        return (
                            <div key={r.slug ?? `baru-${i}`} className="flex items-center gap-2 px-3 py-2 border-t border-slate-50">
                                {readOnly ? (
                                    <>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-semibold text-slate-700">{r.label}</p>
                                        </div>
                                        <div className="w-24 text-right">
                                            <p className="text-sm font-bold text-slate-800 tabular-nums">{nilaiPersen}%</p>
                                        </div>
                                        <div className="w-32 text-right">
                                            <p className="text-sm font-semibold tabular-nums text-slate-600 truncate" title={formatRupiah(preview.komponen?.[slug] ?? 0)}>
                                                {formatRupiah(preview.komponen?.[slug] ?? 0)}
                                            </p>
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <FormField label={i === 0 ? 'Nama pembagian' : ''} className="flex-1">
                                            <TextField
                                                placeholder="cth. Dana Cadangan Khusus"
                                                value={r.label ?? ''}
                                                onChange={(e) => ubahCustom(i, 'label', e.target.value)}
                                                aria-label={`Nama komponen tambahan ${i + 1}`}
                                            />
                                        </FormField>
                                        <FormField label={i === 0 ? '% ' : ''} className="w-28">
                                            <TextField
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                value={nilaiPersen}
                                                onChange={(e) => ubahCustom(i, 'persen', (parseFloat(e.target.value) || 0) / 100)}
                                                className="text-right tabular-nums"
                                                aria-label={`Persen komponen tambahan ${i + 1}`}
                                            />
                                        </FormField>
                                        <div className="w-32 hidden sm:block">
                                            {i === 0 && <p className="text-xs text-slate-400 mb-1">Nominal</p>}
                                            <p className="text-sm tabular-nums text-slate-600 truncate" title={formatRupiah(preview.komponen?.[slug] ?? 0)}>
                                                {formatRupiah(preview.komponen?.[slug] ?? 0)}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => hapusCustom(i)}
                                            aria-label={`Hapus komponen tambahan ${r.label || i + 1}`}
                                            className="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 self-end"
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </>
                                )}
                            </div>
                        );
                    })}
                </div>

                {!readOnly && (
                    <>
                        {errors.persen && <p className="text-sm text-red-600 mb-2">{errors.persen}</p>}
                        {errors.komponen_custom && <p className="text-sm text-red-600 mb-2">{errors.komponen_custom}</p>}

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
                    </>
                )}
            </Card>

            <RingkasanDana preview={preview} ringkasan={ringkasan} />

            <Card>
                <p className="text-base font-bold text-slate-800 mb-1">SHU Pengurus Koperasi &amp; SHU Pengawas</p>
                <p className="text-sm text-slate-500 mb-3">
                    Nominal per orang sesuai keputusan RAT. Saldo yang dialokasikan pengurus {formatRupiah(preview.komponen?.pengurus ?? 0)} •{' '}
                    pengawas {formatRupiah(preview.komponen?.pengawas ?? 0)}
                </p>
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <PengurusTable
                        baris={pengurus}
                        setBaris={setPengurus}
                        alokasi={preview.komponen?.pengurus ?? 0}
                        daftarAnggota={daftarAnggota}
                        readOnly={readOnly}
                    />
                    <RincianTable
                        judul="SHU Pengawas"
                        baris={pengawas}
                        setBaris={setPengawas}
                        alokasi={preview.komponen?.pengawas ?? 0}
                        readOnly={readOnly}
                    />
                </div>
                {!readOnly && (
                    <div className="mt-3">
                        <Button size="sm" variant="secondary" onClick={simpanRincian} disabled={menyimpanRincian}>
                            Simpan Rincian
                        </Button>
                    </div>
                )}
            </Card>
        </div>
    );
}

/** Pengurus dipilih dari anggota (picker ketik-cari) + jabatan teks + nominal. */
function PengurusTable({ baris, setBaris, alokasi, daftarAnggota, readOnly = false }) {
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
        <div className="rounded-xl border border-slate-200 overflow-hidden bg-white">
            <div className="flex items-center justify-between px-3 py-2 bg-slate-50 border-b border-slate-100">
                <p className="text-sm font-bold text-slate-700">SHU Pengurus Koperasi (dari anggota)</p>
                {readOnly && (
                    <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        {baris.length} orang
                    </span>
                )}
            </div>
            {baris.length === 0 && <p className="text-sm text-slate-400 px-3 py-3">Belum ada rincian pengurus.</p>}

            {readOnly ? (
                <div className="divide-y divide-slate-100">
                    {baris.map((r, i) => {
                        const anggota = daftarAnggota.find((a) => a.id === r.anggota_id);
                        const nama = anggota?.nama ?? r.nama ?? `Pengurus ${i + 1}`;
                        const noKaryawan = anggota?.no_karyawan ? ` • NIK: ${anggota.no_karyawan}` : '';
                        const cabang = anggota?.cabang ? ` (${anggota.cabang})` : '';
                        return (
                            <div key={i} className="flex items-center justify-between gap-3 px-3.5 py-2.5 hover:bg-slate-50/50">
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold text-slate-800 truncate">
                                        {nama} <span className="text-xs font-normal text-slate-500">{cabang}{noKaryawan}</span>
                                    </p>
                                    <p className="text-xs text-slate-500 font-medium">{r.jabatan || 'Pengurus'}</p>
                                </div>
                                <div className="text-right shrink-0">
                                    <p className="text-sm font-bold text-slate-800 tabular-nums">
                                        {formatRupiah(parseFloat(r.nominal) || 0)}
                                    </p>
                                </div>
                            </div>
                        );
                    })}
                </div>
            ) : (
                <>
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
                </>
            )}

            <div className="flex items-center justify-between px-3 py-2 border-t border-slate-100 bg-slate-50/60">
                {!readOnly ? (
                    <button
                        type="button"
                        onClick={tambah}
                        className="text-sm font-semibold text-brand-green-dark inline-flex items-center gap-1 hover:underline"
                    >
                        <Plus size={14} />
                        Tambah orang
                    </button>
                ) : (
                    <span className="text-xs font-semibold text-slate-500">Total Dialokasikan</span>
                )}
                <p className="text-sm tabular-nums text-slate-600">
                    <strong className="font-semibold text-slate-800">{formatRupiah(total)}</strong> • Sisa dana <strong className={sisa < 0 ? 'text-red-600 font-bold' : 'text-slate-700 font-semibold'}>{formatRupiah(sisa)}</strong>
                </p>
            </div>
        </div>
    );
}

function RincianTable({ judul, baris, setBaris, alokasi, readOnly = false }) {
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
        <div className="rounded-xl border border-slate-200 overflow-hidden bg-white">
            <div className="flex items-center justify-between px-3 py-2 bg-slate-50 border-b border-slate-100">
                <p className="text-sm font-bold text-slate-700">{judul}</p>
                {readOnly && (
                    <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        {baris.length} orang
                    </span>
                )}
            </div>
            {baris.length === 0 && <p className="text-sm text-slate-400 px-3 py-3">Belum ada rincian {judul.toLowerCase()}.</p>}

            {readOnly ? (
                <div className="divide-y divide-slate-100">
                    {baris.map((r, i) => (
                        <div key={i} className="flex items-center justify-between gap-3 px-3.5 py-2.5 hover:bg-slate-50/50">
                            <div className="min-w-0">
                                <p className="text-sm font-semibold text-slate-800 truncate">{r.nama || `Pengawas ${i + 1}`}</p>
                            </div>
                            <div className="text-right shrink-0">
                                <p className="text-sm font-bold text-slate-800 tabular-nums">
                                    {formatRupiah(parseFloat(r.nominal) || 0)}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <>
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
                </>
            )}

            <div className="flex items-center justify-between px-3 py-2 border-t border-slate-100 bg-slate-50/60">
                {!readOnly ? (
                    <button
                        type="button"
                        onClick={tambah}
                        className="text-sm font-semibold text-brand-green-dark inline-flex items-center gap-1 hover:underline"
                    >
                        <Plus size={14} />
                        Tambah orang
                    </button>
                ) : (
                    <span className="text-xs font-semibold text-slate-500">Total Dialokasikan</span>
                )}
                <p className="text-sm tabular-nums text-slate-600">
                    <strong className="font-semibold text-slate-800">{formatRupiah(total)}</strong> • Sisa dana <strong className={sisa < 0 ? 'text-red-600 font-bold' : 'text-slate-700 font-semibold'}>{formatRupiah(sisa)}</strong>
                </p>
            </div>
        </div>
    );
}
