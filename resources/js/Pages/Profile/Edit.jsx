import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AppLayout>
            <Head title="Profile" />

            <PageHeader title="Profile" subtitle="Kelola informasi akun Anda" />

            <div className="space-y-6">
                <Card>
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-xl"
                    />
                </Card>

                <Card>
                    <UpdatePasswordForm className="max-w-xl" />
                </Card>

                <Card tone="danger">
                    <DeleteUserForm className="max-w-xl" />
                </Card>
            </div>
        </AppLayout>
    );
}
