import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

function formatTime(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

export default function Show({ organization, project, incident, events }) {
    const acknowledge = () => {
        router.post(
            route('organizations.projects.incidents.acknowledge', [
                organization.slug,
                project.slug,
                incident.id,
            ]),
        );
    };

    const resolve = () => {
        router.post(
            route('organizations.projects.incidents.resolve', [
                organization.slug,
                project.slug,
                incident.id,
            ]),
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Incident #{incident.id}
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            {organization.name} / {project.name}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <StatusBadge status={incident.status} />
                        {incident.can.acknowledge && (
                            <PrimaryButton onClick={acknowledge}>
                                Acknowledge
                            </PrimaryButton>
                        )}
                        {incident.can.resolve && (
                            <SecondaryButton onClick={resolve}>
                                Resolve
                            </SecondaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Incident #${incident.id}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <Link
                        href={route('organizations.projects.incidents.index', [
                            organization.slug,
                            project.slug,
                        ])}
                        className="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        ← Back to incidents
                    </Link>

                    {(incident.status === 'open' ||
                        incident.status === 'acknowledged') && (
                        <div
                            className={`rounded-lg border px-4 py-3 ${
                                incident.status === 'open'
                                    ? 'border-red-300 bg-red-50 text-red-900'
                                    : 'border-amber-300 bg-amber-50 text-amber-900'
                            }`}
                        >
                            <p className="font-semibold">
                                {incident.status === 'open'
                                    ? 'Active incident — needs attention'
                                    : 'Acknowledged — still unresolved'}
                            </p>
                            <p className="mt-1 text-sm opacity-90">
                                {incident.summary}
                            </p>
                        </div>
                    )}

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <p className="text-lg text-gray-900">{incident.summary}</p>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm text-gray-500">Monitor</dt>
                                <dd className="mt-1">
                                    <Link
                                        href={route(
                                            'organizations.projects.monitors.show',
                                            [
                                                organization.slug,
                                                project.slug,
                                                incident.monitor.id,
                                            ],
                                        )}
                                        className="text-indigo-600 hover:text-indigo-800"
                                    >
                                        {incident.monitor.name}
                                    </Link>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">URL</dt>
                                <dd className="mt-1 break-all text-gray-900">
                                    {incident.monitor.url || '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">Cause</dt>
                                <dd className="mt-1 text-gray-900">
                                    {incident.cause}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">
                                    Monitor status
                                </dt>
                                <dd className="mt-1">
                                    <StatusBadge status={incident.monitor.status} />
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">Opened</dt>
                                <dd className="mt-1 text-gray-900">
                                    {formatTime(incident.opened_at)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">
                                    Acknowledged
                                </dt>
                                <dd className="mt-1 text-gray-900">
                                    {incident.acknowledged_by
                                        ? `${incident.acknowledged_by.name} · ${formatTime(incident.acknowledged_at)}`
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">Resolved</dt>
                                <dd className="mt-1 text-gray-900">
                                    {incident.resolved_at
                                        ? `${incident.resolved_by?.name ?? 'Auto'} · ${formatTime(incident.resolved_at)}`
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">
                                    Last notified
                                </dt>
                                <dd className="mt-1 text-gray-900">
                                    {formatTime(incident.last_notified_at)}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                            Timeline
                        </h3>
                        <ol className="mt-4 space-y-4">
                            {events.map((event) => (
                                <li
                                    key={event.id}
                                    className="border-l-2 border-gray-200 pl-4"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                            {event.type}
                                        </span>
                                        <span className="text-xs text-gray-400">
                                            {formatTime(event.created_at)}
                                        </span>
                                        {event.user && (
                                            <span className="text-xs text-gray-500">
                                                · {event.user.name}
                                            </span>
                                        )}
                                    </div>
                                    <p className="mt-1 text-sm text-gray-800">
                                        {event.message}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
