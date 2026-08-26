// Label & warna untuk tiap aksi audit log.
// Aksi baru tinggal tambah di sini; fallback otomatis dari snake_case bila belum terdaftar.

const KATEGORI = {
    pinjaman: 'Pinjaman',
    percepatan: 'Perubahan Tenor',
    simpanan: 'Simpanan',
    angsuran: 'Angsuran',
    limit: 'Limit',
    pengeluaran: 'Pengeluaran',
    anggota: 'Anggota',
    pengaturan: 'Pengaturan',
    role: 'Role',
    pengguna: 'Pengguna',
};

const KATEGORI_STYLE = {
    pinjaman: 'bg-brand-navy/10 text-brand-navy',
    percepatan: 'bg-indigo-50 text-indigo-700',
    simpanan: 'bg-pink-50 text-pink-700',
    angsuran: 'bg-cyan-50 text-cyan-700',
    limit: 'bg-violet-50 text-violet-700',
    pengeluaran: 'bg-orange-50 text-orange-700',
    anggota: 'bg-teal-50 text-teal-700',
    pengaturan: 'bg-slate-100 text-slate-600',
    role: 'bg-fuchsia-50 text-fuchsia-700',
    pengguna: 'bg-blue-50 text-blue-700',
};

const OUTCOME_STYLE = {
    hijau: 'bg-brand-green-light text-brand-green-dark',
    merah: 'bg-red-50 text-red-600',
    biru: 'bg-blue-50 text-blue-700',
    kuning: 'bg-amber-50 text-amber-700',
    ungu: 'bg-purple-50 text-purple-700',
    abu: 'bg-slate-100 text-slate-600',
};

const AKSI_MAP = {
    // Pinjaman
    pinjaman_diajukan: { label: 'Ajukan Pinjaman', outcome: 'biru', kategori: 'pinjaman' },
    pinjaman_setujui_bendahara: { label: 'Setujui Pinjaman (Bendahara)', outcome: 'hijau', kategori: 'pinjaman' },
    pinjaman_tolak_bendahara: { label: 'Tolak Pinjaman (Bendahara)', outcome: 'merah', kategori: 'pinjaman' },
    pinjaman_cair_bendahara: { label: 'Cairkan Pinjaman (Bendahara)', outcome: 'ungu', kategori: 'pinjaman' },
    pinjaman_setujui_ketua: { label: 'Setujui Pinjaman (Ketua)', outcome: 'hijau', kategori: 'pinjaman' },
    pinjaman_tolak_ketua: { label: 'Tolak Pinjaman (Ketua)', outcome: 'merah', kategori: 'pinjaman' },
    pinjaman_cair: { label: 'Pencairan Pinjaman', outcome: 'ungu', kategori: 'pinjaman' },

    // Perubahan tenor
    percepatan_diajukan: { label: 'Ajukan Perubahan Tenor', outcome: 'biru', kategori: 'percepatan' },
    percepatan_setujui_bendahara: { label: 'Setujui Perubahan Tenor (Bendahara)', outcome: 'hijau', kategori: 'percepatan' },
    percepatan_tolak_bendahara: { label: 'Tolak Perubahan Tenor (Bendahara)', outcome: 'merah', kategori: 'percepatan' },
    percepatan_tolak_ketua: { label: 'Tolak Perubahan Tenor (Ketua)', outcome: 'merah', kategori: 'percepatan' },
    percepatan_setujui_ketua: { label: 'Setujui Perubahan Tenor (Ketua)', outcome: 'hijau', kategori: 'percepatan' },

    // Simpanan & angsuran
    simpanan_konfirmasi_massal: { label: 'Konfirmasi Simpanan Massal', outcome: 'hijau', kategori: 'simpanan' },
    angsuran_konfirmasi_massal: { label: 'Konfirmasi Angsuran Massal', outcome: 'hijau', kategori: 'angsuran' },
    angsuran_konfirmasi_percepatan: { label: 'Konfirmasi Angsuran (Perubahan Tenor)', outcome: 'hijau', kategori: 'angsuran' },

    // Limit
    limit_diajukan: { label: 'Ajukan Kenaikan Limit', outcome: 'biru', kategori: 'limit' },
    setujui_pengajuan_limit: { label: 'Setujui Kenaikan Limit', outcome: 'hijau', kategori: 'limit' },
    limit_ditolak: { label: 'Tolak Kenaikan Limit', outcome: 'merah', kategori: 'limit' },

    // Keuangan
    pengeluaran_dicatat: { label: 'Catat Pengeluaran', outcome: 'kuning', kategori: 'pengeluaran' },

    // Anggota
    anggota_resign: { label: 'Resign Anggota', outcome: 'merah', kategori: 'anggota' },
    anggota_aktifkan_kembali: { label: 'Aktifkan Kembali Anggota', outcome: 'hijau', kategori: 'anggota' },
    update_limit_custom_anggota: { label: 'Update Limit Custom Anggota', outcome: 'kuning', kategori: 'anggota' },

    // Pengaturan
    update_limit_pinjaman: { label: 'Update Limit Pinjaman', outcome: 'kuning', kategori: 'pengaturan' },
    tambah_tenor: { label: 'Tambah Rentang Tenor', outcome: 'biru', kategori: 'pengaturan' },
    update_tenor: { label: 'Update Rentang Tenor', outcome: 'kuning', kategori: 'pengaturan' },
    hapus_tenor: { label: 'Hapus Rentang Tenor', outcome: 'merah', kategori: 'pengaturan' },
    update_bunga: { label: 'Update Persentase Bunga', outcome: 'kuning', kategori: 'pengaturan' },
    update_setting_simpanan: { label: 'Update Nominal Simpanan', outcome: 'kuning', kategori: 'pengaturan' },

    // Role
    tambah_role: { label: 'Tambah Role', outcome: 'biru', kategori: 'role' },
    update_role: { label: 'Update Role', outcome: 'kuning', kategori: 'role' },
    hapus_role: { label: 'Hapus Role', outcome: 'merah', kategori: 'role' },

    // Pengguna
    reset_password_pengguna: { label: 'Reset Password Pengguna', outcome: 'kuning', kategori: 'pengguna' },
    ubah_status_pengguna: { label: 'Ubah Status Pengguna', outcome: 'kuning', kategori: 'pengguna' },
    hapus_pengguna: { label: 'Hapus Pengguna', outcome: 'merah', kategori: 'pengguna' },
};

function fallbackLabel(aksi) {
    return String(aksi ?? '')
        .split('_')
        .map((kata) => kata.charAt(0).toUpperCase() + kata.slice(1))
        .join(' ');
}

export function infoAksi(aksi) {
    const terdaftar = AKSI_MAP[aksi];

    if (terdaftar) {
        return {
            ...terdaftar,
            outcomeStyle: OUTCOME_STYLE[terdaftar.outcome] ?? OUTCOME_STYLE.abu,
            kategoriLabel: KATEGORI[terdaftar.kategori] ?? null,
            kategoriStyle: KATEGORI_STYLE[terdaftar.kategori] ?? 'bg-slate-100 text-slate-600',
        };
    }

    return {
        label: fallbackLabel(aksi),
        outcome: 'abu',
        outcomeStyle: OUTCOME_STYLE.abu,
        kategori: null,
        kategoriLabel: null,
        kategoriStyle: 'bg-slate-100 text-slate-600',
    };
}

export function inisialNama(nama) {
    return String(nama ?? '?')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((kata) => kata.charAt(0).toUpperCase())
        .join('');
}

export function waktuRelatif(tanggal) {
    if (!tanggal) return '';

    const waktu = new Date(tanggal.replace(/(\d{2}) (\w{3}) (\d{4})/, '$2 $1 $3'));
    if (Number.isNaN(waktu.getTime())) return tanggal;

    const selisih = Date.now() - waktu.getTime();
    const menit = Math.floor(selisih / 60000);
    const jam = Math.floor(menit / 60);
    const hari = Math.floor(jam / 24);
    const bulan = Math.floor(hari / 30);

    if (menit < 1) return 'Baru saja';
    if (menit < 60) return `${menit} menit lalu`;
    if (jam < 24) return `${jam} jam lalu`;
    if (hari < 30) return `${hari} hari lalu`;
    if (bulan < 12) return `${bulan} bulan lalu`;
    return `${Math.floor(bulan / 12)} tahun lalu`;
}
