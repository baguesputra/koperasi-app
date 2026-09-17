import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import Breadcrumb from '@/Components/ui/Breadcrumb';
import KelolaPengguna from '@/Components/pengguna/KelolaPengguna';

export default function Index({ pengguna, daftarRole, filters }) {
    return (
        <AppLayout>
            <Head title="Kelola Pengguna" />

            <Breadcrumb
                items={[
                    { label: 'Pengaturan', href: route('pengaturan.index') },
                    { label: 'Kelola Pengguna' },
                ]}
            />

            <PageHeader title="Kelola Pengguna" subtitle="Atur akun login, role, dan status pengguna sistem" className="mt-3" />

            <Card padding="sm" className="shadow-md border-slate-200/70">
                <KelolaPengguna
                    pengguna={pengguna}
                    filterPengguna={filters}
                    daftarRole={daftarRole}
                    mode="halaman"
                    ruteIndex="pengaturan.pengguna.index"
                />
            </Card>
        </AppLayout>
    );
}
