import { Plus, Trash2 } from 'lucide-react';
import Card from '@/Components/ui/Card';
import AnggotaPicker from './AnggotaPicker';
import Button from '@/Components/ui/Button';
import TextField from '@/Components/ui/TextField';
import FormField from '@/Components/ui/FormField';
import { formatRupiah } from '@/Utils/formatCurrency';

/**
 * Card gabungan "SHU Pengurus Koperasi & SHU Pengawas".
 * Dipisah dari tab Komponen SHU agar lebih mudah dibaca.
 * State pengurus/pengawas & aksi simpan dipegang oleh parent (Show) supaya
 * input tidak hilang saat berpindah tab, dan satu tombol menyimpan keduanya.
 */
export default function PengurusPengawas({
    periode,
    pengurus,
    setPengurus,
    pengawas,
    setPengawas,
    preview,
    daftarAnggota = [],
    readOnly = false,
    onSimpan,
    menyimpan = false,
}) {
    const anggotaArr = Array.isArray(daftarAnggota) ? daftarAnggota : Object.values(daftarAnggota ?? {});
    return (
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
                    daftarAnggota={anggotaArr}
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
                    <Button size="sm" variant="secondary" onClick={onSimpan} disabled={menyimpan}>
                        Simpan Rincian
                    </Button>
                </div>
            )}
        </Card>
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
