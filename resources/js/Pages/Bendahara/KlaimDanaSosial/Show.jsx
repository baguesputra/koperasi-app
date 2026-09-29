import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Card from '@/Components/ui/Card';
import KeputusanDrawer from '@/Pages/KlaimDanaSosial/Partials/KeputusanDrawer';

export default function Show({ klaim }) {
    return (
        <AppLayout>
            <Head title="Detail Santunan Dana Sosial" />

            <Link href={route('bendahara.klaim-dana-sosial.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-navy mb-5">
                <ArrowLeft size={16} />
                Kembali ke Verifikasi Santunan
            </Link>

            <Card>
                <KeputusanDrawer klaim={klaim} tahap="bendahara" onClose={() => window.history.back()} />
            </Card>
        </AppLayout>
    );
}
