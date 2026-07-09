import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

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

function formatCheckedAt(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

export default function Show({
    organization,
    project,
    monitor,
    activeIncident,
    checkResults,
}) {
    const pause = () => {
        router.post(
            route('organizations.projects.monitors.pause', [
                organization.slug,
                project.slug,
                monitor.id,
            ]),
        );
    };

    const resume = () => {
        router.post(
            route('organizations.projects.monitors.resume', [
                organization.slug,
                project.slug,
                monitor.id,
            ]),
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {monitor.name}
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            {organization.name} / {project.name}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <StatusBadge status={monitor.status} />
                        {monitor.status === 'paused'
                            ? monitor.can.resume && (
                                  <PrimaryButton onClick={resume}>
                                      Resume
                                  </PrimaryButton>
                              )
                            : monitor.can.pause && (
                                  <SecondaryButton onClick={pause}>
                                      Pause
                                  </SecondaryButton>
                              )}
                    </div>
                </div>
            }
        >
            <Head title={monitor.name} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <Link
                        href={route('organizations.projects.monitors.index', [
                            organization.slug,
                            project.slug,
                        ])}
                        className="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        ← Back to monitors
                    </Link>

                    {activeIncident && (
                        <div className="border border-red-200 bg-red-50 p-4 shadow-sm sm:rounded-lg">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p className="text-sm font-semibold uppercase tracking-wide text-red-700">
                                        Active incident · {activeIncident.status}
                                    </p>
                                    <p className="mt-1 text-sm text-red-900">
                                        {activeIncident.summary}
                                    </p>
                                </div>
                                <Link
                                    href={route(
                                        'organizations.projects.incidents.show',
                                        [
                                            organization.slug,
                                            project.slug,
                                            activeIncident.id,
                                        ],
                                    )}
                                    className="text-sm font-medium text-red-700 underline hover:text-red-900"
                                >
                                    View incident →
                                </Link>
                            </div>
                        </div>
                    )}

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                            Configuration
                        </h3>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm text-gray-500">URL</dt>
                                <dd className="mt-1 break-all text-gray-900">
                                    {monitor.config?.url}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">Method</dt>
                                <dd className="mt-1 text-gray-900">
                                    {monitor.config?.method}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">
                                    Expected status
                                </dt>
                                <dd className="mt-1 text-gray-900">
                                    {monitor.config?.expected_status}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">
                                    Timeout
                                </dt>
                                <dd className="mt-1 text-gray-900">
                                    {monitor.config?.timeout_seconds}s
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">
                                    Interval
                                </dt>
                                <dd className="mt-1 text-gray-900">
                                    {monitor.interval_seconds}s
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">
                                    Keyword
                                </dt>
                                <dd className="mt-1 text-gray-900">
                                    {monitor.config?.keyword || '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">
                                    Last checked
                                </dt>
                                <dd className="mt-1 text-gray-900">
                                    {formatCheckedAt(monitor.last_checked_at)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500">
                                    Last status change
                                </dt>
                                <dd className="mt-1 text-gray-900">
                                    {formatCheckedAt(
                                        monitor.last_status_change_at,
                                    )}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-200 px-4 py-3">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Recent checks
                            </h3>
                        </div>
                        {checkResults.length === 0 ? (
                            <p className="p-6 text-sm text-gray-500">
                                No checks yet. The scheduler will run this
                                monitor shortly.
                            </p>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Checked at
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Result
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Time
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Error
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {checkResults.map((result) => (
                                        <tr key={result.id}>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                                {formatCheckedAt(
                                                    result.checked_at,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <span
                                                    className={
                                                        result.success
                                                            ? 'font-medium text-green-700'
                                                            : 'font-medium text-red-700'
                                                    }
                                                >
                                                    {result.success
                                                        ? 'OK'
                                                        : 'FAIL'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-700">
                                                {result.status_code ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-700">
                                                {result.response_time_ms != null
                                                    ? `${result.response_time_ms} ms`
                                                    : '—'}
                                            </td>
                                            <td className="max-w-md truncate px-4 py-3 text-sm text-gray-500">
                                                {result.error_message || '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
