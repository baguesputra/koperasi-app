import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import BackLink from '@/Components/ui/BackLink';
import PageHeader from '@/Components/ui/PageHeader';
import StatusBadge from '@/Components/ui/StatusBadge';
import DetailDrawer from './Partials/DetailDrawer';

export default function Show({ pinjaman, angsuran, pelunasan_resign, jurnal_pelunasan }) {
    return (
        <AppLayout>
            <Head title={`Pinjaman #${pinjaman.id}`} />

            <BackLink href={route('pinjaman.index')}>Kembali ke daftar pinjaman</BackLink>
            <PageHeader title={`Pinjaman #${pinjaman.id}`} subtitle={`${pinjaman.nama} • ${pinjaman.no_karyawan}`}>
                <StatusBadge status={pinjaman.status} />
            </PageHeader>

            <DetailDrawer
                pinjaman={pinjaman}
                angsuran={angsuran}
                pelunasan_resign={pelunasan_resign}
                jurnal_pelunasan={jurnal_pelunasan}
            />
        </AppLayout>
    );
}
