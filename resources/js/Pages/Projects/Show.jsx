import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Checkbox from '@/Components/Checkbox';
import CopyButton from '@/Components/CopyButton';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Show({ organization, project }) {
    const { data, setData, put, processing, recentlySuccessful } = useForm({
        name: project.name,
        slug: project.slug,
        public_status_enabled: project.public_status_enabled,
    });

    const statusUrl = route('status.show', project.slug);

    const submit = (e) => {
        e.preventDefault();
        put(
            route('organizations.projects.update', [
                organization.slug,
                project.slug,
            ]),
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {project.name}
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            {organization.name}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
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

                        {project.public_status_enabled && (
                            <div className="mt-4 rounded-md bg-gray-50 p-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Public URL
                                </p>
                                <div className="mt-2 flex flex-wrap items-center gap-2">
                                    <code className="break-all text-sm text-gray-800">
                                        {statusUrl}
                                    </code>
                                    <CopyButton value={statusUrl} />
                                    <a
                                        href={statusUrl}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-sm text-indigo-600 hover:text-indigo-800"
                                    >
                                        Open
                                    </a>
                                </div>
                            </div>
                        )}

                        <div className="mt-6 flex flex-wrap gap-4">
                            <Link
                                href={route(
                                    'organizations.projects.monitors.index',
                                    [organization.slug, project.slug],
                                )}
                                className="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                View monitors →
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

                    {project.can.update && (
                        <form
                            onSubmit={submit}
                            className="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg"
                        >
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Project settings
                            </h3>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="public_status_enabled"
                                    checked={data.public_status_enabled}
                                    onChange={(e) =>
                                        setData(
                                            'public_status_enabled',
                                            e.target.checked,
                                        )
                                    }
                                />
                                <InputLabel
                                    htmlFor="public_status_enabled"
                                    value="Enable public status page"
                                    className="!mb-0"
                                />
                            </div>

                            <div className="flex items-center gap-3">
                                <PrimaryButton disabled={processing}>
                                    Save settings
                                </PrimaryButton>
                                {recentlySuccessful && (
                                    <span className="text-sm text-green-600">
                                        Saved.
                                    </span>
                                )}
                            </div>
                        </form>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
