import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link } from '@inertiajs/react';

export default function Index({ organizations }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Organizations
                    </h2>
                    <Link href={route('organizations.create')}>
                        <PrimaryButton>New organization</PrimaryButton>
                    </Link>
                </div>
            }
        >
            <Head title="Organizations" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {organizations.length === 0 ? (
                        <div className="bg-white p-8 shadow-sm sm:rounded-lg">
                            <p className="text-gray-600">
                                No organizations yet. Create one to start monitoring.
                            </p>
                            <div className="mt-4">
                                <Link href={route('organizations.create')}>
                                    <PrimaryButton>Create organization</PrimaryButton>
                                </Link>
                            </div>
                        </div>
                    ) : (
                        organizations.map((organization) => (
                            <Link
                                key={organization.id}
                                href={route('organizations.show', organization.slug)}
                                className="block bg-white p-6 shadow-sm transition hover:bg-gray-50 sm:rounded-lg"
                            >
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">
                                            {organization.name}
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-500">
                                            {organization.projects_count}{' '}
                                            {organization.projects_count === 1
                                                ? 'project'
                                                : 'projects'}{' '}
                                            · Role: {organization.role}
                                        </p>
                                    </div>
                                    <span className="text-sm text-indigo-600">
                                        View →
                                    </span>
                                </div>
                            </Link>
                        ))
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
