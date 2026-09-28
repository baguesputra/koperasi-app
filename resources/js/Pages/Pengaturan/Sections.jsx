import { Percent, HandCoins, CalendarRange, PiggyBank, Wallet, QrCode, UserCog, RefreshCw, Activity } from 'lucide-react';

export const SECTIONS = [
    {
        grup: 'Aturan Pinjaman',
        items: [
            { key: 'bunga', label: 'Bunga', desc: 'Persentase bunga menurun per bulan', icon: Percent },
            { key: 'limit', label: 'Limit Pinjaman', desc: 'Batas nominal per lama keanggotaan', icon: HandCoins },
            { key: 'tenor', label: 'Tenor', desc: 'Tenor maksimal per rentang nominal', icon: CalendarRange },
        ],
    },
    {
        grup: 'Operasional',
        items: [
            { key: 'simpanan', label: 'Simpanan', desc: 'Nominal pokok, wajib, dana sosial', icon: PiggyBank },
            { key: 'kas', label: 'Kas', desc: 'Pagu pinjaman & cadangan sosial bulanan', icon: Wallet },
            { key: 'wa', label: 'WhatsApp', desc: 'Koneksi perangkat & riwayat pesan', icon: QrCode },
        ],
    },
    {
        grup: 'Sistem',
        items: [
            { key: 'akses', label: 'Akses', desc: 'Pengguna, role, dan hak akses', icon: UserCog },
            { key: 'organisasi', label: 'Organisasi GATE', desc: 'Master & sinkron karyawan', icon: RefreshCw },
            { key: 'audit', label: 'Audit Log', desc: 'Jejak aktivitas sistem', icon: Activity },
        ],
    },
];
