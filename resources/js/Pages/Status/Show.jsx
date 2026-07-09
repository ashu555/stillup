import PublicLayout from '@/Layouts/PublicLayout';
import {
    StatusBadge,
    TypeBadge,
    formatRelativeTime,
    formatDateTime,
} from '@/Components/StatusBadge';
import { Head } from '@inertiajs/react';

const overallBanner = {
    operational: 'border-green-200 bg-green-50 text-green-900',
    degraded: 'border-amber-200 bg-amber-50 text-amber-900',
    major_outage: 'border-red-200 bg-red-50 text-red-900',
    pending: 'border-slate-200 bg-slate-50 text-slate-800',
};

export default function Show({
    project,
    overall_status,
    overall_status_label,
    monitors,
    incidents,
    refreshed_at,
}) {
    return (
        <PublicLayout>
            <Head title={`${project.name} Status`} />

            <div className="mx-auto max-w-5xl space-y-8 px-4 py-10 sm:px-6">
                <div>
                    <p className="text-sm font-medium uppercase tracking-wide text-slate-500">
                        Status page
                    </p>
                    <h1 className="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
                        {project.name}
                    </h1>
                </div>

                <div
                    className={`rounded-lg border p-6 ${overallBanner[overall_status] ?? overallBanner.pending}`}
                >
                    <div className="flex flex-wrap items-center gap-3">
                        <StatusBadge status={overall_status} kind="overall" />
                        <p className="text-xl font-semibold">
                            {overall_status_label}
                        </p>
                    </div>
                    <p className="mt-2 text-sm opacity-80">
                        Updated {formatRelativeTime(refreshed_at)}
                    </p>
                </div>

                <section className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 px-4 py-3">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-500">
                            Monitors
                        </h2>
                    </div>
                    {monitors.length === 0 ? (
                        <p className="p-6 text-sm text-slate-500">
                            No public monitors yet.
                        </p>
                    ) : (
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                                        Type
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                                        Last check
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {monitors.map((monitor, index) => (
                                    <tr key={`${monitor.name}-${index}`}>
                                        <td className="px-4 py-3 text-sm font-medium text-slate-900">
                                            {monitor.name}
                                        </td>
                                        <td className="px-4 py-3">
                                            <TypeBadge type={monitor.type} />
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge
                                                status={monitor.status}
                                            />
                                        </td>
                                        <td className="px-4 py-3 text-sm text-slate-600">
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
                    )}
                </section>

                <section className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 px-4 py-3">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-500">
                            Active incidents
                        </h2>
                    </div>
                    {incidents.length === 0 ? (
                        <p className="p-6 text-sm text-slate-500">
                            No active incidents. All clear.
                        </p>
                    ) : (
                        <ul className="divide-y divide-slate-200">
                            {incidents.map((incident, index) => (
                                <li
                                    key={`${incident.summary}-${index}`}
                                    className="px-4 py-4"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <StatusBadge
                                            status={incident.status}
                                            kind="incident"
                                        />
                                        <p className="text-sm font-medium text-slate-900">
                                            {incident.summary}
                                        </p>
                                    </div>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {incident.monitor_name
                                            ? `${incident.monitor_name} · `
                                            : ''}
                                        Opened{' '}
                                        {formatDateTime(incident.opened_at)}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <footer className="border-t border-slate-200 pt-6 text-center text-sm text-slate-500">
                    Powered by Stillup · Last refreshed{' '}
                    {formatDateTime(refreshed_at)}
                </footer>
            </div>
        </PublicLayout>
    );
}
