import { useState } from 'react';
import { formatRupiah } from '@/Utils/formatCurrency';

export function usePrivasiNominal() {
    const [tampil, setTampil] = useState(false);

    function alih() {
        setTampil((v) => !v);
    }

    return [tampil, alih];
}

export default function Nominal({ nilai, tampil, className = '', asDots = 'Rp ••••••' }) {
    if (nilai === null || nilai === undefined || nilai === '') {
        return <span className={className}>-</span>;
    }

    const n = Number(nilai);
    const angka = Number.isFinite(n) ? n : nilai;

    return (
        <span className={className} aria-label={tampil ? undefined : 'Nominal disembunyikan'}>
            {tampil ? formatRupiah(angka) : asDots}
        </span>
    );
}
