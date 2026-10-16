export const KOMPONEN_LABEL = {
    cadangan: 'Cadangan',
    jasa_simpanan: 'Jasa Partisipasi Atas Simpanan',
    jasa_transaksi: 'Jasa Partisipasi Atas Transaksi',
    pengurus: 'SHU Bagian Pengurus',
    pengawas: 'SHU Bagian Pengawas',
    pendidikan: 'SHU Dana Pendidikan',
    sosial: 'SHU Dana Sosial',
    wilker: 'SHU Pengembangan Wilayah Kerja',
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

/** Slug stabil untuk custom (mirror backend): huruf kecil, non-alnum jadi _. */
export function slugKomponen(label = '') {
    const s = String(label ?? '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    return (s || 'komponen').slice(0, 50);
}

/** Jumlahkan 8 bawaan + custom (custom: [{persen: 0.xx}]). */
export function totalPersen(persen = {}, custom = []) {
    const bawaan = KOMPONEN_ORDER.reduce((a, k) => a + (parseFloat(persen[PCT_COL[k]]) || 0), 0);
    const tambahan = (custom ?? []).reduce((a, r) => a + (parseFloat(r.persen) || 0), 0);
    return bawaan + tambahan;
}

export function persenValid(persen = {}, custom = []) {
    return Math.abs(totalPersen(persen, custom) - 1) <= 0.0001;
}

/** Selisih vs 100%: positif = kurang, negatif = lebih. */
export function selisihPersen(persen = {}, custom = []) {
    return 1 - totalPersen(persen, custom);
}

function formatPersen(desimal) {
    return `${Math.round(desimal * 10000) / 100}%`;
}

/**
 * Pesan validasi eksplisit: komponen apa berubah berapa,
 * dan sebesar apa penyeimbang yang dibutuhkan agar 100%.
 * Return { nada: 'ok'|'lebih'|'kurang', teks }.
 */
export function pesanPenyeimbang(tersimpan = {}, draft = {}, total, valid, selisih, customInfo = null) {
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
        .map((d) => `${d.label} ${d.delta > 0 ? '+' : '−'}${formatPersen(Math.abs(d.delta))}`);
    if (customInfo?.berubah?.length) {
        customInfo.berubah.forEach((c) => berubah.push(`${c} (tambahan)`));
    }
    const sebab = berubah.length ? ` (${berubah.join(', ')})` : '';
    const kurang = formatPersen(Math.abs(selisih));
    if (selisih < 0) {
        return { nada: 'lebih', teks: `Kelebihan ${kurang}${sebab}. Turunkan komponen lain total ${kurang} agar kembali 100%.` };
    }
    return { nada: 'kurang', teks: `Kurang ${kurang}${sebab}. Tambahkan ${kurang} ke salah satu komponen agar 100%.` };
}

/** Nominal per komponen = total_shu × persen (mirror kolom D Excel). Custom keyed by slug. */
export function komponenNominal(totalShu = 0, persen = {}, custom = []) {
    const hasil = {};
    KOMPONEN_ORDER.forEach((k) => {
        hasil[k] = (parseFloat(totalShu) || 0) * (parseFloat(persen[PCT_COL[k]]) || 0);
    });
    (custom ?? []).forEach((r) => {
        const slug = r.slug || slugKomponen(r.label);
        hasil[slug] = (parseFloat(totalShu) || 0) * (parseFloat(r.persen) || 0);
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

/** Ringkasan live dari persen draft (total modal/transaksi dari server). Custom ikut tak dibagi. */
export function ringkasLive(totalShu, persen, totalModal = 0, totalTransaksi = 0, custom = []) {
    const komponen = komponenNominal(totalShu, persen, custom);
    const pool = komponen.jasa_simpanan + komponen.jasa_transaksi;
    const danaDibagi = pool + komponen.pengurus + komponen.pengawas;
    const customNominal = (custom ?? []).map((r) => {
        const slug = r.slug || slugKomponen(r.label);
        return { slug, label: r.label, persen: parseFloat(r.persen) || 0, nominal: komponen[slug] ?? 0 };
    });
    return {
        komponen,
        custom: customNominal,
        pool_anggota: pool,
        dana_dibagi: danaDibagi,
        dana_tak_dibagi: (parseFloat(totalShu) || 0) - danaDibagi,
        total_modal: totalModal,
        total_transaksi: totalTransaksi,
    };
}
