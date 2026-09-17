import { useMemo, useState } from 'react';
import { Eye, EyeOff, Lock } from 'lucide-react';
import TextField from './TextField';

function skorKekuatan(nilai) {
    if (!nilai) return 0;
    let skor = 0;
    if (nilai.length >= 8) skor += 1;
    if (nilai.length >= 12) skor += 1;
    if (/[a-z]/.test(nilai) && /[A-Z]/.test(nilai)) skor += 1;
    if (/\d/.test(nilai)) skor += 1;
    if (/[^a-zA-Z0-9]/.test(nilai)) skor += 1;
    return Math.min(skor, 4);
}

const meterLabel = ['', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'];
const meterWarna = ['', 'bg-red-500', 'bg-amber-500', 'bg-brand-green', 'bg-brand-green-dark'];

export default function KataSandi({ value, onChange, meter = false, ...props }) {
    const [lihat, setLihat] = useState(false);
    const skor = useMemo(() => skorKekuatan(value), [value]);

    return (
        <div>
            <div className="relative">
                <Lock size={15} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                <TextField
                    type={lihat ? 'text' : 'password'}
                    value={value}
                    onChange={onChange}
                    autoComplete="new-password"
                    {...props}
                    className={`pl-10 pr-11 ${props.className ?? ''}`}
                />
                <button
                    type="button"
                    onClick={() => setLihat((v) => !v)}
                    aria-label={lihat ? 'Sembunyikan password' : 'Tampilkan password'}
                    aria-pressed={lihat}
                    title={lihat ? 'Sembunyikan' : 'Tampilkan'}
                    className="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                >
                    {lihat ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
            </div>
            {meter && value && (
                <div className="mt-2" aria-live="polite">
                    <div className="flex gap-1" aria-hidden="true">
                        {[1, 2, 3, 4].map((i) => (
                            <span key={i} className={`h-1.5 flex-1 rounded-full transition-colors ${i <= skor ? meterWarna[skor] : 'bg-slate-100'}`} />
                        ))}
                    </div>
                    <p className="text-xs text-slate-400 mt-1">Kekuatan: {meterLabel[skor]}</p>
                </div>
            )}
        </div>
    );
}
