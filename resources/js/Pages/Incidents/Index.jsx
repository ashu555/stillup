import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { StatusBadge, formatDateTime } from '@/Components/StatusBadge';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ organization, project, incidents, filters }) {
    const setStatus = (status) => {
        router.get(
            route('organizations.projects.incidents.index', [
                organization.slug,
                project.slug,
            ]),
            status ? { status } : {},
            { preserveState: true, replace: true },
        );
    };

    const tabs = [
        { label: 'All', value: null },
        { label: 'Open', value: 'open' },
        { label: 'Acknowledged', value: 'acknowledged' },
        { label: 'Resolved', value: 'resolved' },
    ];

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Incidents
                    </h2>
                    <p className="mt-1 text-sm text-gray-500">
                        {organization.name} / {project.name}
                    </p>
                </div>
            }
        >
            <Head title={`Incidents · ${project.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <Link
                            href={route('organizations.projects.show', [
                                organization.slug,
                                project.slug,
                            ])}
                            className="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            ← Back to project
                        </Link>

                        <div className="flex gap-2">
                            {tabs.map((tab) => {
                                const active =
                                    (tab.value === null && !filters.status) ||
                                    filters.status === tab.value;

                                return (
                                    <button
                                        key={tab.label}
                                        type="button"
                                        onClick={() => setStatus(tab.value)}
                                        className={`rounded px-3 py-1 text-sm ${
                                            active
                                                ? 'bg-gray-900 text-white'
                                                : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'
                                        }`}
                                    >
                                        {tab.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {incidents.length === 0 ? (
                        <div className="bg-white p-8 shadow-sm sm:rounded-lg">
                            <p className="text-gray-600">
                                {filters.status
                                    ? 'No incidents for this filter.'
                                    : 'All clear. No incidents yet.'}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Monitor
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Summary
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Opened
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {incidents.map((incident) => (
                                        <tr
                                            key={incident.id}
                                            className="hover:bg-gray-50"
                                        >
                                            <td className="px-4 py-3">
                                                <StatusBadge
                                                    status={incident.status}
                                                    kind="incident"
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                {incident.monitor.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={route(
                                                        'organizations.projects.incidents.show',
                                                        [
                                                            organization.slug,
                                                            project.slug,
                                                            incident.id,
                                                        ],
                                                    )}
                                                    className="text-sm text-indigo-600 hover:text-indigo-800"
                                                >
                                                    {incident.summary}
                                                </Link>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                                {formatDateTime(
                                                    incident.opened_at,
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
