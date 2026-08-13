import { ShieldX, FileQuestion, Hourglass, ServerCrash } from 'lucide-react';

export const errorStatusConfig = {
    403: {
        icon: ShieldX,
        warna: 'bg-red-50 text-red-600',
        title: 'Akses Ditolak',
        deskripsi: 'Halaman ini khusus untuk peran tertentu. Anda tidak memiliki izin untuk membukanya.',
        langkah: [
            'Periksa kembali akun yang sedang Anda gunakan.',
            'Hubungi admin koperasi jika Anda merasa seharusnya punya akses.',
        ],
        aksi: { tipe: 'link', label: 'Kembali ke Dashboard' },
    },
    404: {
        icon: FileQuestion,
        warna: 'bg-amber-50 text-amber-600',
        title: 'Halaman Tidak Ditemukan',
        deskripsi: 'Halaman yang Anda cari tidak tersedia atau alamatnya salah.',
        langkah: [
            'Periksa kembali alamat yang Anda ketik.',
            'Gunakan menu untuk kembali ke halaman yang benar.',
        ],
        aksi: { tipe: 'link', label: 'Kembali ke Dashboard' },
    },
    419: {
        icon: Hourglass,
        warna: 'bg-blue-50 text-blue-600',
        title: 'Sesi Berakhir',
        deskripsi: 'Terlalu lama tidak ada aktivitas, sesi Anda telah berakhir demi keamanan.',
        langkah: [
            'Masuk kembali untuk melanjutkan pekerjaan Anda.',
            'Data yang sudah tersimpan sebelumnya tidak akan hilang.',
        ],
        aksi: { tipe: 'link', label: 'Masuk Kembali' },
    },
    500: {
        icon: ServerCrash,
        warna: 'bg-red-50 text-red-600',
        title: 'Terjadi Kesalahan',
        deskripsi: 'Ada kendala pada server. Tenang, data Anda aman.',
        langkah: [
            'Coba muat ulang halaman ini.',
            'Jika tetap terjadi, coba lagi beberapa saat kemudian.',
            'Hubungi admin koperasi jika masalah terus berlanjut.',
        ],
        aksi: { tipe: 'reload', label: 'Coba Lagi' },
    },
};

export function statusAksiHref(status) {
    return status === 419 ? route('login') : route('dashboard');
}