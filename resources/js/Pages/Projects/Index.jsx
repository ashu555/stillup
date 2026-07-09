import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ organization, projects }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Projects
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            {organization.name}
                        </p>
                    </div>
                    {organization.can.createProject && (
                        <Link
                            href={route(
                                'organizations.projects.create',
                                organization.slug,
                            )}
                        >
                            <PrimaryButton>New project</PrimaryButton>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title={`Projects · ${organization.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    <Link
                        href={route('organizations.show', organization.slug)}
                        className="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        ← Back to organization
                    </Link>

                    {projects.length === 0 ? (
                        <div className="bg-white p-8 shadow-sm sm:rounded-lg">
                            <p className="text-gray-600">No projects yet.</p>
                        </div>
                    ) : (
                        projects.map((project) => (
                            <Link
                                key={project.id}
                                href={route('organizations.projects.show', [
                                    organization.slug,
                                    project.slug,
                                ])}
                                className="block bg-white p-6 shadow-sm transition hover:bg-gray-50 sm:rounded-lg"
                            >
                                <h3 className="text-lg font-medium text-gray-900">
                                    {project.name}
                                </h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Public status:{' '}
                                    {project.public_status_enabled
                                        ? 'Enabled'
                                        : 'Disabled'}
                                </p>
                            </Link>
                        ))
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
