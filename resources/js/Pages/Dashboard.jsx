import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { StatusBadge, TypeBadge, formatRelativeTime } from '@/Components/StatusBadge';
import { Head, Link } from '@inertiajs/react';

function StatCard({ label, value, tone = 'default' }) {
    const tones = {
        default: 'bg-white text-gray-900',
        danger: 'bg-red-50 text-red-800',
        warn: 'bg-amber-50 text-amber-800',
        ok: 'bg-green-50 text-green-800',
    };

    return (
        <div className={`rounded-lg p-4 shadow-sm ${tones[tone]}`}>
            <p className="text-xs font-medium uppercase tracking-wide opacity-70">
                {label}
            </p>
            <p className="mt-2 text-3xl font-semibold">{value}</p>
        </div>
    );
}

export default function Dashboard({
    counts,
    needs_attention,
    recent_incidents,
    recent_failures,
    quick_links,
}) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Dashboard
                    </h2>
                    {quick_links && (
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href={route(
                                    'organizations.projects.monitors.create',
                                    [
                                        quick_links.organization_slug,
                                        quick_links.project_slug,
                                    ],
                                )}
                            >
                                <PrimaryButton>New HTTP</PrimaryButton>
                            </Link>
                            <Link
                                href={route(
                                    'organizations.projects.monitors.create-heartbeat',
                                    [
                                        quick_links.organization_slug,
                                        quick_links.project_slug,
                                    ],
                                )}
                                className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                            >
                                New heartbeat
                            </Link>
                            <Link
                                href={route(
                                    'organizations.projects.incidents.index',
                                    [
                                        quick_links.organization_slug,
                                        quick_links.project_slug,
                                    ],
                                )}
                                className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                            >
                                Incidents
                            </Link>
                        </div>
                    )}
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard label="Up" value={counts.monitors.up} tone="ok" />
                        <StatCard
                            label="Down"
                            value={counts.monitors.down}
                            tone="danger"
                        />
                        <StatCard
                            label="Open incidents"
                            value={counts.incidents.open}
                            tone="danger"
                        />
                        <StatCard
                            label="Acknowledged"
                            value={counts.incidents.acknowledged}
                            tone="warn"
                        />
                    </div>

                    <div className="grid gap-4 text-sm text-gray-600 sm:grid-cols-3">
                        <div className="rounded-lg bg-white p-4 shadow-sm">
                            Paused: {counts.monitors.paused}
                        </div>
                        <div className="rounded-lg bg-white p-4 shadow-sm">
                            Pending: {counts.monitors.pending}
                        </div>
                        <div className="rounded-lg bg-white p-4 shadow-sm">
                            Total monitors: {counts.monitors.total}
                        </div>
                    </div>

                    <section className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="border-b border-gray-200 px-4 py-3">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Needs attention
                            </h3>
                        </div>
                        {needs_attention.monitors.length === 0 &&
                        needs_attention.incidents.length === 0 ? (
                            <p className="p-6 text-sm text-gray-500">
                                All clear. No down monitors or open incidents.
                            </p>
                        ) : (
                            <div className="divide-y divide-gray-200">
                                {needs_attention.monitors.map((monitor) => (
                                    <div
                                        key={`m-${monitor.id}`}
                                        className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                                    >
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <TypeBadge type={monitor.type} />
                                                <StatusBadge
                                                    status={monitor.status}
                                                />
                                                <Link
                                                    href={route(
                                                        'organizations.projects.monitors.show',
                                                        [
                                                            monitor.organization
                                                                .slug,
                                                            monitor.project.slug,
                                                            monitor.id,
                                                        ],
                                                    )}
                                                    className="font-medium text-indigo-600 hover:text-indigo-800"
                                                >
                                                    {monitor.name}
                                                </Link>
                                            </div>
                                            <p className="mt-1 text-xs text-gray-500">
                                                {monitor.organization.name} /{' '}
                                                {monitor.project.name}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                                {needs_attention.incidents.map((incident) => (
                                    <div
                                        key={`i-${incident.id}`}
                                        className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                                    >
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <StatusBadge
                                                    status={incident.status}
                                                    kind="incident"
                                                />
                                                <Link
                                                    href={route(
                                                        'organizations.projects.incidents.show',
                                                        [
                                                            incident.organization
                                                                .slug,
                                                            incident.project.slug,
                                                            incident.id,
                                                        ],
                                                    )}
                                                    className="font-medium text-indigo-600 hover:text-indigo-800"
                                                >
                                                    {incident.summary}
                                                </Link>
                                            </div>
                                            <p className="mt-1 text-xs text-gray-500">
                                                {incident.project.name}
                                                {incident.monitor?.name
                                                    ? ` · ${incident.monitor.name}`
                                                    : ''}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <section className="overflow-hidden rounded-lg bg-white shadow-sm">
                            <div className="border-b border-gray-200 px-4 py-3">
                                <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                    Recent incidents
                                </h3>
                            </div>
                            {recent_incidents.length === 0 ? (
                                <p className="p-6 text-sm text-gray-500">
                                    No incidents yet.
                                </p>
                            ) : (
                                <ul className="divide-y divide-gray-200">
                                    {recent_incidents.map((incident) => (
                                        <li key={incident.id} className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <StatusBadge
                                                    status={incident.status}
                                                    kind="incident"
                                                />
                                                <Link
                                                    href={route(
                                                        'organizations.projects.incidents.show',
                                                        [
                                                            incident.organization
                                                                .slug,
                                                            incident.project.slug,
                                                            incident.id,
                                                        ],
                                                    )}
                                                    className="text-sm text-indigo-600 hover:text-indigo-800"
                                                >
                                                    {incident.summary}
                                                </Link>
                                            </div>
                                            <p className="mt-1 text-xs text-gray-500">
                                                {formatRelativeTime(
                                                    incident.opened_at,
                                                )}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section className="overflow-hidden rounded-lg bg-white shadow-sm">
                            <div className="border-b border-gray-200 px-4 py-3">
                                <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                    Recent check failures
                                </h3>
                            </div>
                            {recent_failures.length === 0 ? (
                                <p className="p-6 text-sm text-gray-500">
                                    No recent failures.
                                </p>
                            ) : (
                                <ul className="divide-y divide-gray-200">
                                    {recent_failures.map((failure) => (
                                        <li key={failure.id} className="px-4 py-3">
                                            <Link
                                                href={route(
                                                    'organizations.projects.monitors.show',
                                                    [
                                                        failure.organization.slug,
                                                        failure.project.slug,
                                                        failure.monitor.id,
                                                    ],
                                                )}
                                                className="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                {failure.monitor.name}
                                            </Link>
                                            <p className="mt-1 truncate text-xs text-gray-500">
                                                {failure.error_message || 'Failed'}{' '}
                                                ·{' '}
                                                {formatRelativeTime(
                                                    failure.checked_at,
                                                )}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
