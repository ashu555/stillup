const monitorStatusStyles = {
    up: 'bg-green-100 text-green-800',
    down: 'bg-red-100 text-red-800',
    degraded: 'bg-orange-100 text-orange-800',
    paused: 'bg-gray-100 text-gray-700',
    pending: 'bg-amber-100 text-amber-800',
};

const incidentStatusStyles = {
    open: 'bg-red-100 text-red-800',
    acknowledged: 'bg-amber-100 text-amber-800',
    resolved: 'bg-green-100 text-green-800',
};

const overallStatusStyles = {
    operational: 'bg-green-100 text-green-800',
    degraded: 'bg-amber-100 text-amber-800',
    major_outage: 'bg-red-100 text-red-800',
    pending: 'bg-gray-100 text-gray-700',
};

const typeStyles = {
    http: 'bg-sky-100 text-sky-800',
    heartbeat: 'bg-violet-100 text-violet-800',
};

export function StatusBadge({ status, kind = 'monitor' }) {
    const styles =
        kind === 'incident'
            ? incidentStatusStyles
            : kind === 'overall'
              ? overallStatusStyles
              : monitorStatusStyles;

    return (
        <span
            className={`inline-flex rounded px-2 py-0.5 text-xs font-medium uppercase tracking-wide ${styles[status] ?? 'bg-gray-100 text-gray-700'}`}
        >
            {String(status).replaceAll('_', ' ')}
        </span>
    );
}

export function TypeBadge({ type }) {
    return (
        <span
            className={`inline-flex rounded px-2 py-0.5 text-xs font-medium uppercase tracking-wide ${typeStyles[type] ?? 'bg-gray-100 text-gray-700'}`}
        >
            {type}
        </span>
    );
}

export function formatRelativeTime(value) {
    if (!value) return 'Never';

    const date = new Date(value);
    const seconds = Math.round((date.getTime() - Date.now()) / 1000);
    const abs = Math.abs(seconds);
    const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

    if (abs < 60) return rtf.format(Math.round(seconds), 'second');
    if (abs < 3600) return rtf.format(Math.round(seconds / 60), 'minute');
    if (abs < 86400) return rtf.format(Math.round(seconds / 3600), 'hour');
    return rtf.format(Math.round(seconds / 86400), 'day');
}

export function formatDateTime(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}
