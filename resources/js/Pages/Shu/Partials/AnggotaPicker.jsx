import { useEffect, useId, useRef, useState } from 'react';
import { Search, X } from 'lucide-react';

/**
 * Combobox pilih anggota: saran muncul sesuai ketikan (maks 8),
 * navigasi keyboard panah + Enter, Esc tutup.
 */
export default function AnggotaPicker({ daftar = [], nilai = null, onPilih, placeholder = 'Ketik nama anggota...' }) {
    const idInput = useId();
    const idList = useId();
    const [teks, setTeks] = useState('');
    const [buka, setBuka] = useState(false);
    const [sorot, setSorot] = useState(0);
    const wadah = useRef(null);

    const terpilih = nilai ? daftar.find((a) => a.id === nilai) ?? null : null;

    const saran = teks.trim().length >= 2
        ? daftar
            .filter((a) => {
                const kunci = `${a.nama} ${a.no_karyawan ?? ''}`.toLowerCase();
                return kunci.includes(teks.trim().toLowerCase());
            })
            .slice(0, 8)
        : [];

    useEffect(() => {
        function tutupLuar(e) {
            if (wadah.current && !wadah.current.contains(e.target)) setBuka(false);
        }
        document.addEventListener('mousedown', tutupLuar);
        return () => document.removeEventListener('mousedown', tutupLuar);
    }, []);

    function pilih(anggota) {
        onPilih?.(anggota.id);
        setTeks('');
        setBuka(false);
        setSorot(0);
    }

    function tekanTombol(e) {
        if (e.key === 'ArrowDown' && saran.length > 0) {
            e.preventDefault();
            setBuka(true);
            setSorot((s) => (s + 1) % saran.length);
        } else if (e.key === 'ArrowUp' && saran.length > 0) {
            e.preventDefault();
            setSorot((s) => (s - 1 + saran.length) % saran.length);
        } else if (e.key === 'Enter' && buka && saran[sorot]) {
            e.preventDefault();
            pilih(saran[sorot]);
        } else if (e.key === 'Escape') {
            setBuka(false);
        }
    }

    if (terpilih) {
        return (
            <div className="flex items-center gap-1.5 rounded-xl border border-brand-green/40 bg-brand-green-light/40 pl-2.5 pr-1 py-1.5">
                <p className="min-w-0 flex-1 text-sm font-semibold text-slate-800 truncate" title={terpilih.nama}>
                    {terpilih.nama}
                    <span className="block text-xs font-normal text-slate-500">{terpilih.no_karyawan} • {terpilih.cabang}</span>
                </p>
                <button
                    type="button"
                    onClick={() => onPilih?.(null)}
                    aria-label={`Ganti pilihan ${terpilih.nama}`}
                    className="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/40"
                >
                    <X size={14} />
                </button>
            </div>
        );
    }

    return (
        <div ref={wadah} className="relative">
            <Search size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" aria-hidden="true" />
            <input
                id={idInput}
                role="combobox"
                aria-expanded={buka && saran.length > 0}
                aria-controls={idList}
                aria-activedescendant={buka && saran[sorot] ? `${idList}-${saran[sorot].id}` : undefined}
                autoComplete="off"
                value={teks}
                placeholder={placeholder}
                onChange={(e) => { setTeks(e.target.value); setBuka(true); setSorot(0); }}
                onFocus={() => teks.trim().length >= 2 && setBuka(true)}
                onKeyDown={tekanTombol}
                className="w-full rounded-xl border border-slate-300 pl-8 pr-2.5 py-2.5 text-sm focus:border-brand-green focus:ring-2 focus:ring-brand-green/20 outline-none"
            />
            {buka && saran.length > 0 && (
                <ul
                    id={idList}
                    role="listbox"
                    aria-label="Saran anggota"
                    className="absolute z-20 mt-1 w-full max-h-56 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg py-1"
                >
                    {saran.map((a, i) => (
                        <li
                            key={a.id}
                            id={`${idList}-${a.id}`}
                            role="option"
                            aria-selected={i === sorot}
                            onMouseDown={(e) => { e.preventDefault(); pilih(a); }}
                            onMouseEnter={() => setSorot(i)}
                            className={`px-3 py-2 text-sm cursor-pointer ${i === sorot ? 'bg-brand-green-light/60' : ''}`}
                        >
                            <span className="block font-semibold text-slate-800">{a.nama}</span>
                            <span className="block text-xs text-slate-400">{a.no_karyawan} • {a.cabang}</span>
                        </li>
                    ))}
                </ul>
            )}
            {buka && teks.trim().length >= 2 && saran.length === 0 && (
                <p className="absolute z-20 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-lg px-3 py-2 text-sm text-slate-400">
                    Tidak ada anggota cocok.
                </p>
            )}
        </div>
    );
}
