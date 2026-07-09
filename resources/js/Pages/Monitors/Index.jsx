import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import {
    StatusBadge,
    TypeBadge,
    formatRelativeTime,
} from '@/Components/StatusBadge';
import { Head, Link } from '@inertiajs/react';

function formatInterval(seconds) {
    if (seconds < 60) return `${seconds}s`;
    if (seconds % 3600 === 0) return `${seconds / 3600}h`;
    if (seconds % 60 === 0) return `${seconds / 60}m`;
    return `${seconds}s`;
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
                    <div className="flex items-center gap-3">
                        {project.public_status_enabled && (
                            <a
                                href={route('status.show', project.slug)}
                                target="_blank"
                                rel="noreferrer"
                                className="text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                Public status
                            </a>
                        )}
                        <Link
                            href={route(
                                'organizations.projects.incidents.index',
                                [organization.slug, project.slug],
                            )}
                            className="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            Incidents
                        </Link>
                        {can.create && (
                            <>
                                <Link
                                    href={route(
                                        'organizations.projects.monitors.create',
                                        [organization.slug, project.slug],
                                    )}
                                >
                                    <PrimaryButton>New HTTP</PrimaryButton>
                                </Link>
                                <Link
                                    href={route(
                                        'organizations.projects.monitors.create-heartbeat',
                                        [organization.slug, project.slug],
                                    )}
                                    className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                >
                                    New heartbeat
                                </Link>
                            </>
                        )}
                    </div>
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
                                No monitors yet. Create an HTTP or heartbeat
                                monitor to start watching production.
                            </p>
                            {can.create && (
                                <div className="mt-4 flex flex-wrap gap-3">
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
                                    <Link
                                        href={route(
                                            'organizations.projects.monitors.create-heartbeat',
                                            [organization.slug, project.slug],
                                        )}
                                        className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                    >
                                        Create heartbeat
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
                                            Type
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Target
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Incident
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Interval
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Last check
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
                                            <td className="px-4 py-3">
                                                <TypeBadge type={monitor.type} />
                                            </td>
                                            <td className="max-w-xs truncate px-4 py-3 text-sm text-gray-600">
                                                {monitor.type === 'http'
                                                    ? monitor.url
                                                    : `every ${formatInterval(monitor.interval_seconds)}`}
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge
                                                    status={monitor.status}
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {monitor.active_incident ? (
                                                    <Link
                                                        href={route(
                                                            'organizations.projects.incidents.show',
                                                            [
                                                                organization.slug,
                                                                project.slug,
                                                                monitor
                                                                    .active_incident
                                                                    .id,
                                                            ],
                                                        )}
                                                        className="font-medium text-red-600 hover:text-red-800"
                                                    >
                                                        {
                                                            monitor
                                                                .active_incident
                                                                .status
                                                        }
                                                    </Link>
                                                ) : (
                                                    <span className="text-gray-400">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {formatInterval(
                                                    monitor.interval_seconds,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {formatRelativeTime(
                                                    monitor.type === 'heartbeat'
                                                        ? monitor.last_heartbeat_at ||
                                                              monitor.last_checked_at
                                                        : monitor.last_checked_at,
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
