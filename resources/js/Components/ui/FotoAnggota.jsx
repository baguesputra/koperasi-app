import { useState } from 'react';

const ukuranClass = {
    sm: 'w-9 h-9 text-sm',
    md: 'w-11 h-11 text-base',
    lg: 'w-16 h-16 text-xl',
};

export default function FotoAnggota({ nama, fotoUrl, ukuran = 'sm', className = '' }) {
    const [gagal, setGagal] = useState(false);
    const inisial = (nama ?? '?').charAt(0).toUpperCase();

    if (fotoUrl && !gagal) {
        return (
            <img
                src={fotoUrl}
                alt={`Foto ${nama}`}
                loading="lazy"
                referrerPolicy="no-referrer"
                onError={() => setGagal(true)}
                className={`${ukuranClass[ukuran]} rounded-full object-cover shrink-0 shadow-sm ring-1 ring-slate-200 ${className}`}
            />
        );
    }

    return (
        <div
            aria-hidden="true"
            className={`${ukuranClass[ukuran]} rounded-full bg-gradient-to-br from-brand-green to-brand-green-dark text-white flex items-center justify-center font-bold shrink-0 shadow-sm ${className}`}
        >
            {inisial}
        </div>
    );
}
