export function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(angka);
}

export function formatRupiahSingkat(angka) {
    if (angka >= 1_000_000) return `${(angka / 1_000_000).toFixed(1)}jt`;
    if (angka >= 1_000) return `${(angka / 1_000).toFixed(0)}rb`;
    return angka;
}