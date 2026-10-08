import { HandCoins, PiggyBank, QrCode, UserCog, RefreshCw, Activity } from 'lucide-react';

export const SECTIONS = [
    {
        grup: 'Kebijakan',
        items: [
            { key: 'aturan-pinjaman', label: 'Aturan Pinjaman', desc: 'Bunga, limit & tenor', icon: HandCoins },
            { key: 'dana-operasional', label: 'Dana Operasional', desc: 'Simpanan, kas & chip', icon: PiggyBank },
        ],
    },
    {
        grup: 'Sistem',
        items: [
            { key: 'wa', label: 'WhatsApp', desc: 'Koneksi perangkat & riwayat pesan', icon: QrCode },
            { key: 'akses', label: 'Akses', desc: 'Pengguna, role, dan hak akses', icon: UserCog },
            { key: 'organisasi', label: 'Organisasi GATE', desc: 'Master & sinkron karyawan', icon: RefreshCw },
            { key: 'audit', label: 'Audit Log', desc: 'Jejak aktivitas sistem', icon: Activity },
        ],
    },
];
