import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Pencil, Check, X } from 'lucide-react';
import { formatRupiah, formatRupiahSingkat } from '@/Utils/formatCurrency';
import TextField from '@/Components/ui/TextField';

const LABEL_GRUP = {
    santunan: 'Santunan Dana Sosial',
    pengeluaran: 'Pengeluaran',
    topup: 'Topup Bank',
    sisih: 'Sisih Kas Kecil',
    pinjaman_usulan: 'Usulan Pinjaman (Bendahara/Ketua)',
    limit_baru: 'Limit Baru (Bendahara/Ketua)',
};

function parseInput(teks) {
    return String(teks ?? '')
        .split(/[\s,;]+/)
        .map((s) => s.replace(/[^\d]/g, ''))
        .filter(Boolean)
        .map(Number)
        .filter((n) => n >= 1);
}

export default function TabChip({ chipNominal = {} }) {
    const grupList = Object.keys(LABEL_GRUP);
    const [editGrup, setEditGrup] = useState(null);
    const [draft, setDraft] = useState('');
    const { data, setData, post, processing, errors, clearErrors } = useForm({ grup: '', daftar: [] });

    function mulaiEdit(grup) {
        setEditGrup(grup);
        clearErrors();
        const isi = (chipNominal[grup] ?? []).join(', ');
        setDraft(isi);
        setData({ grup, daftar: chipNominal[grup] ?? [] });
    }

    function batal() {
        setEditGrup(null);
        setDraft('');
        clearErrors();
    }

    function sinkron(teks) {
        setDraft(teks);
        setData('daftar', [...new Set(parseInput(teks))].slice(0, 8));
    }

    function simpan() {
        post(route('pengaturan.chip.update'), { onSuccess: batal, preserveScroll: true });
    }

    return (
        <div>
            <p className="text-sm text-slate-400 mb-4">
                Tombol cepat nominal di bawah input angka pada form persetujuan. Ketik beberapa nominal
                dipisahkan koma atau spasi (maks 8 per grup). Ketik manual di form tetap bisa.
            </p>
            <ul className="divide-y divide-slate-100">
                {grupList.map((grup) => {
                    const daftar = chipNominal[grup] ?? [];
                    const sedangEdit = editGrup === grup;
                    return (
                        <li key={grup} className="py-3.5 first:pt-0 last:pb-0">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-slate-700 min-w-0">{LABEL_GRUP[grup]}</p>
                                {!sedangEdit && (
                                    <div className="flex items-center gap-2 shrink-0">
                                        <span className="text-sm font-bold text-slate-800 tabular-nums">
                                            {daftar.map((n) => formatRupiahSingkat(n)).join(' • ') || '-'}
                                        </span>
                                        <button onClick={() => mulaiEdit(grup)} aria-label={`Ubah ${LABEL_GRUP[grup]}`} title="Ubah" className="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-brand-navy hover:bg-slate-100 transition-colors">
                                            <Pencil size={15} />
                                        </button>
                                    </div>
                                )}
                            </div>
                            {sedangEdit ? (
                                <div className="mt-2">
                                    <div className="flex items-center gap-1.5">
                                        <TextField
                                            size="sm"
                                            value={draft}
                                            onChange={(e) => sinkron(e.target.value)}
                                            placeholder="100000, 300000, 500000"
                                            aria-label={`Daftar chip ${LABEL_GRUP[grup]}`}
                                            className="flex-1 tabular-nums"
                                            autoFocus
                                        />
                                        <button onClick={simpan} disabled={processing} aria-label="Simpan" title="Simpan" className="w-8 h-8 inline-flex items-center justify-center rounded-lg text-brand-green-dark bg-brand-green-light hover:shadow-sm transition-all">
                                            <Check size={16} />
                                        </button>
                                        <button onClick={batal} aria-label="Batal" title="Batal" className="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                                            <X size={16} />
                                        </button>
                                    </div>
                                    {(data.daftar ?? []).length > 0 && (
                                        <div className="flex flex-wrap gap-1.5 mt-2">
                                            {(data.daftar ?? []).map((n) => (
                                                <span key={n} className="px-2.5 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-600 tabular-nums">
                                                    {formatRupiah(n)}
                                                </span>
                                            ))}
                                        </div>
                                    )}
                                    {errors.daftar && <p className="text-xs text-red-600 mt-1">{errors.daftar}</p>}
                                    {errors.grup && <p className="text-xs text-red-600 mt-1">{errors.grup}</p>}
                                </div>
                            ) : (
                                daftar.length > 0 && (
                                    <div className="flex flex-wrap gap-1.5 mt-2">
                                        {daftar.map((n) => (
                                            <span key={n} className="px-2.5 py-1 text-xs font-bold rounded-full bg-slate-50 text-slate-600 border border-slate-200 tabular-nums">
                                                {formatRupiahSingkat(n)}
                                            </span>
                                        ))}
                                    </div>
                                )
                            )}
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
