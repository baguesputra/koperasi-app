import AnggotaLayout from '@/Layouts/AnggotaLayout';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';

export default function Berhasil() {
    return (
        <AnggotaLayout>
            <Head title="Pengajuan Terkirim" />

            <div className="max-w-xl mx-auto text-center py-8">
                <div className="w-20 h-20 bg-brand-green-light rounded-full flex items-center justify-center mx-auto mb-5">
                    <CheckCircle2 size={40} className="text-brand-green" />
                </div>
                <h1 className="text-2xl font-bold text-slate-800 mb-2">Pengajuan Terkirim!</h1>
                <p className="text-base text-slate-500 mb-8">
                    Pengajuan pinjaman Anda sedang ditinjau oleh Bendahara. Anda akan melihat status terbaru di halaman Riwayat.
                </p>

                <Link
                    href={route('portal.dashboard')}
                    className="inline-block px-8 py-3.5 text-base font-bold rounded-2xl bg-brand-green text-white hover:bg-brand-green-dark transition-colors"
                >
                    Kembali ke Beranda
                </Link>
            </div>
        </AnggotaLayout>
    );
}