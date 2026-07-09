import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ organization }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {organization.name}
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Role: {organization.role}
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
            <Head title={organization.name} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <h3 className="text-lg font-medium text-gray-900">
                            Projects
                        </h3>
                        <Link
                            href={route(
                                'organizations.projects.index',
                                organization.slug,
                            )}
                            className="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            View all
                        </Link>
                    </div>

                    {organization.projects.length === 0 ? (
                        <div className="bg-white p-8 shadow-sm sm:rounded-lg">
                            <p className="text-gray-600">
                                No projects yet.
                            </p>
                            {organization.can.createProject && (
                                <div className="mt-4">
                                    <Link
                                        href={route(
                                            'organizations.projects.create',
                                            organization.slug,
                                        )}
                                    >
                                        <PrimaryButton>
                                            Create project
                                        </PrimaryButton>
                                    </Link>
                                </div>
                            )}
                        </div>
                    ) : (
                        organization.projects.map((project) => (
                            <Link
                                key={project.id}
                                href={route('organizations.projects.show', [
                                    organization.slug,
                                    project.slug,
                                ])}
                                className="block bg-white p-6 shadow-sm transition hover:bg-gray-50 sm:rounded-lg"
                            >
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h4 className="font-medium text-gray-900">
                                            {project.name}
                                        </h4>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Public status:{' '}
                                            {project.public_status_enabled
                                                ? 'Enabled'
                                                : 'Disabled'}
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
