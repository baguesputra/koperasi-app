export const KOMPONEN_LABEL = {
    cadangan: 'Cadangan',
    jasa_simpanan: 'Jasa simpanan',
    jasa_transaksi: 'Jasa transaksi',
    pengurus: 'Pengurus',
    pengawas: 'Pengawas',
    pendidikan: 'Pendidikan',
    sosial: 'Sosial',
    wilker: 'Wilayah kerja',
};

export const KOMPONEN_ORDER = Object.keys(KOMPONEN_LABEL);

export const PCT_COL = {
    cadangan: 'pct_cadangan',
    jasa_simpanan: 'pct_jasa_simpanan',
    jasa_transaksi: 'pct_jasa_transaksi',
    pengurus: 'pct_pengurus',
    pengawas: 'pct_pengawas',
    pendidikan: 'pct_pendidikan',
    sosial: 'pct_sosial',
    wilker: 'pct_wilker',
};

/** Jumlahkan 8 persen (bentuk {pct_*: 0.xx}). */
export function totalPersen(persen = {}) {
    return KOMPONEN_ORDER.reduce((a, k) => a + (parseFloat(persen[PCT_COL[k]]) || 0), 0);
}

export function persenValid(persen = {}) {
    return Math.abs(totalPersen(persen) - 1) <= 0.0001;
}

/** Selisih vs 100%: positif = kurang, negatif = lebih. */
export function selisihPersen(persen = {}) {
    return 1 - totalPersen(persen);
}

function formatPersen(desimal) {
    return `${Math.round(desimal * 10000) / 100}%`;
}

/**
 * Pesan validasi eksplisit: komponen apa berubah berapa,
 * dan sebesar apa penyeimbang yang dibutuhkan agar 100%.
 * Return { nada: 'ok'|'lebih'|'kurang', teks }.
 */
export function pesanPenyeimbang(tersimpan = {}, draft = {}, total, valid, selisih) {
    if (valid) {
        return { nada: 'ok', teks: 'Pas 100% — siap simpan.' };
    }
    const berubah = KOMPONEN_ORDER
        .map((k) => {
            const kolom = PCT_COL[k];
            const delta = (parseFloat(draft[kolom]) || 0) - (parseFloat(tersimpan[kolom]) || 0);
            return { label: KOMPONEN_LABEL[k], delta };
        })
        .filter((d) => Math.abs(d.delta) > 0.00001)
        .map((d) => `${d.label} ${d.delta > 0 ? '+' : '−'}${formatPersen(Math.abs(d.delta))}`)
        .join(', ');
    const sebab = berubah ? ` (${berubah})` : '';
    const kurang = formatPersen(Math.abs(selisih));
    if (selisih < 0) {
        return { nada: 'lebih', teks: `Kelebihan ${kurang}${sebab}. Turunkan komponen lain total ${kurang} agar kembali 100%.` };
    }
    return { nada: 'kurang', teks: `Kurang ${kurang}${sebab}. Tambahkan ${kurang} ke salah satu komponen agar 100%.` };
}

/** Nominal per komponen = total_shu × persen (mirror kolom D Excel). */
export function komponenNominal(totalShu = 0, persen = {}) {
    const hasil = {};
    KOMPONEN_ORDER.forEach((k) => {
        hasil[k] = (parseFloat(totalShu) || 0) * (parseFloat(persen[PCT_COL[k]]) || 0);
    });
    return hasil;
}

/** Rumus per anggota G/H/I/J Excel (J = floor ribuan). */
export function barisLive(modal, transaksi, totalModal, totalTransaksi, poolSimpanan, poolTransaksi) {
    const shuModal = totalModal > 0 ? (modal / totalModal) * poolSimpanan : 0;
    const shuTransaksi = totalTransaksi > 0 ? (transaksi / totalTransaksi) * poolTransaksi : 0;
    const bruto = shuModal + shuTransaksi;
    return { shuModal, shuTransaksi, bruto, bersih: Math.floor(bruto / 1000) * 1000 };
}

/** Ringkasan live dari persen draft (total modal/transaksi dari server). */
export function ringkasLive(totalShu, persen, totalModal = 0, totalTransaksi = 0) {
    const komponen = komponenNominal(totalShu, persen);
    const pool = komponen.jasa_simpanan + komponen.jasa_transaksi;
    return {
        komponen,
        pool_anggota: pool,
        dana_dibagi: pool + komponen.pengurus + komponen.pengawas,
        dana_tak_dibagi: komponen.cadangan + komponen.pendidikan + komponen.wilker,
        total_modal: totalModal,
        total_transaksi: totalTransaksi,
    };
}
