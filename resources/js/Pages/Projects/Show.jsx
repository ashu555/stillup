import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ organization, project }) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {project.name}
                    </h2>
                    <p className="mt-1 text-sm text-gray-500">
                        {organization.name}
                    </p>
                </div>
            }
        >
            <Head title={project.name} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    <Link
                        href={route('organizations.show', organization.slug)}
                        className="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        ← Back to organization
                    </Link>

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Slug
                                </dt>
                                <dd className="mt-1 text-gray-900">
                                    {project.slug}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Public status page
                                </dt>
                                <dd className="mt-1 text-gray-900">
                                    {project.public_status_enabled
                                        ? 'Enabled'
                                        : 'Disabled'}
                                </dd>
                            </div>
                        </dl>

                        <p className="mt-6 text-sm text-gray-500">
                            Monitors and incidents will land here in Step 2.
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
