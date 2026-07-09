import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link } from '@inertiajs/react';

const statusStyles = {
    up: 'bg-green-100 text-green-800',
    down: 'bg-red-100 text-red-800',
    degraded: 'bg-orange-100 text-orange-800',
    paused: 'bg-gray-100 text-gray-700',
    pending: 'bg-amber-100 text-amber-800',
};

function StatusBadge({ status }) {
    return (
        <span
            className={`inline-flex rounded px-2 py-0.5 text-xs font-medium uppercase tracking-wide ${statusStyles[status] ?? 'bg-gray-100 text-gray-700'}`}
        >
            {status}
        </span>
    );
}

function formatInterval(seconds) {
    if (seconds < 60) return `${seconds}s`;
    if (seconds % 3600 === 0) return `${seconds / 3600}h`;
    if (seconds % 60 === 0) return `${seconds / 60}m`;
    return `${seconds}s`;
}

function formatCheckedAt(value) {
    if (!value) return 'Never';
    return new Date(value).toLocaleString();
}

export default function Index({ organization, project, monitors, can }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Monitors
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            {organization.name} / {project.name}
                        </p>
                    </div>
                    {can.create && (
                        <Link
                            href={route(
                                'organizations.projects.monitors.create',
                                [organization.slug, project.slug],
                            )}
                        >
                            <PrimaryButton>New HTTP monitor</PrimaryButton>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title={`Monitors · ${project.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    <Link
                        href={route('organizations.projects.show', [
                            organization.slug,
                            project.slug,
                        ])}
                        className="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        ← Back to project
                    </Link>

                    {monitors.length === 0 ? (
                        <div className="bg-white p-8 shadow-sm sm:rounded-lg">
                            <p className="text-gray-600">
                                No monitors yet. Create an HTTP monitor to start
                                checking endpoints.
                            </p>
                            {can.create && (
                                <div className="mt-4">
                                    <Link
                                        href={route(
                                            'organizations.projects.monitors.create',
                                            [organization.slug, project.slug],
                                        )}
                                    >
                                        <PrimaryButton>
                                            Create HTTP monitor
                                        </PrimaryButton>
                                    </Link>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Name
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            URL
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Interval
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Last checked
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {monitors.map((monitor) => (
                                        <tr
                                            key={monitor.id}
                                            className="hover:bg-gray-50"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={route(
                                                        'organizations.projects.monitors.show',
                                                        [
                                                            organization.slug,
                                                            project.slug,
                                                            monitor.id,
                                                        ],
                                                    )}
                                                    className="font-medium text-indigo-600 hover:text-indigo-800"
                                                >
                                                    {monitor.name}
                                                </Link>
                                            </td>
                                            <td className="max-w-xs truncate px-4 py-3 text-sm text-gray-600">
                                                {monitor.url}
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge
                                                    status={monitor.status}
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {formatInterval(
                                                    monitor.interval_seconds,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {formatCheckedAt(
                                                    monitor.last_checked_at,
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
