import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link } from '@inertiajs/react';

export default function Show({ organization, project }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {project.name}
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            {organization.name}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link
                            href={route(
                                'organizations.projects.incidents.index',
                                [organization.slug, project.slug],
                            )}
                            className="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            Incidents
                        </Link>
                        <Link
                            href={route(
                                'organizations.projects.monitors.index',
                                [organization.slug, project.slug],
                            )}
                        >
                            <PrimaryButton>Monitors</PrimaryButton>
                        </Link>
                    </div>
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

                        <div className="mt-6 flex flex-wrap gap-4">
                            <Link
                                href={route(
                                    'organizations.projects.monitors.index',
                                    [organization.slug, project.slug],
                                )}
                                className="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                View HTTP monitors →
                            </Link>
                            <Link
                                href={route(
                                    'organizations.projects.incidents.index',
                                    [organization.slug, project.slug],
                                )}
                                className="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                View incidents →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
