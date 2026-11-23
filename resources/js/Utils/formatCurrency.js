const MAKS_DIGIT_NOMINAL = 15;

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

/**
 * Membersihkan ketikan nominal menjadi string digit integer murni tanpa leading zero.
 */
export function bersihkanNominalKetikan(raw) {
    if (raw === null || raw === undefined) {
        return '';
    }
    const digits = String(raw).replace(/\D/g, '').slice(0, MAKS_DIGIT_NOMINAL);
    if (digits === '') {
        return '';
    }
    const trimmed = digits.replace(/^0+(?=\d)/, '');
    return trimmed === '' ? '0' : trimmed;
}

/**
 * Membersihkan hasil tempel nominal (mis. "Rp 1.500.000,00" atau "2,500,000.00")
 * dengan memangkas bagian sen (1-2 digit desimal di akhir) agar tidak terkalikan 100x.
 */
export function bersihkanNominalTempel(raw) {
    if (raw === null || raw === undefined) {
        return '';
    }
    const tanpaAkhiran = String(raw)
        .trim()
        .replace(/(?:\s*(?:idr|rp|\.-|-))+$/i, '')
        .trim();
    const tanpaSen = tanpaAkhiran.replace(/(?:[.,]\d{1,2})$/, '');
    return bersihkanNominalKetikan(tanpaSen);
}

/**
 * Memformat angka / string digit menjadi string pemisah ribuan bertitik (id-ID).
 */
export function formatRibuan(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    let clean = '';
    if (typeof value === 'number') {
        if (!Number.isFinite(value) || value < 0) {
            return '';
        }
        clean = bersihkanNominalKetikan(String(Math.trunc(value)));
    } else {
        const teks = String(value).trim();
        if (teks === '') {
            return '';
        }
        // Tangani string decimal dari database (mis. "250000.00") tanpa membiarkan ".000" ribuan dianggap desimal.
        if (/^-?\d+\.\d{1,2}$/.test(teks)) {
            const num = Math.trunc(Number(teks));
            if (!Number.isFinite(num) || num < 0) {
                return '';
            }
            clean = bersihkanNominalKetikan(String(num));
        } else {
            clean = bersihkanNominalKetikan(teks);
        }
    }

    if (clean === '') {
        return '';
    }
    return clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/**
 * Menghitung posisi kursor yang ekuivalen di dalam string ber-titik berdasarkan jumlah digit di kiri kursor.
 */
export function hitungPosisiKursorRibuan(formatted, digitsBefore) {
    if (!formatted || digitsBefore <= 0) {
        return 0;
    }
    let count = 0;
    for (let i = 0; i < formatted.length; i += 1) {
        if (/\d/.test(formatted[i])) {
            count += 1;
            if (count === digitsBefore) {
                return i + 1;
            }
        }
    }
    return formatted.length;
}
