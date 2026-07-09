# Stillup

Uptime + cron heartbeat monitoring with incidents, email alerts, and public status pages.

## Stack

Laravel 11 · PHP 8.3 · MySQL 8 · Redis · Sanctum · Inertia React + Tailwind · Docker · Mailhog

## Quick start

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
npm install && npm run build
```

- App: http://localhost:8080  
- Mailhog: http://localhost:8025  
- Demo login: `demo@stillup.test` / `password`

## How it works

### HTTP monitors
Stillup checks a URL on a schedule. Success = expected status (+ optional keyword). Failures flip the monitor to **down** and open an incident.

### Heartbeat monitors
Your cron/job pings a unique URL. If Stillup does not hear from it within `expected_every + grace`, the monitor goes **down**.

```bash
curl -X POST http://localhost:8080/heartbeat/YOUR_TOKEN
```

Never-pinged heartbeats stay **pending** (no incident until the first successful ping, then a later miss).

### Incidents + alerts
- Transition to down → open incident + email owners/admins (Mailhog locally)
- Recovery → auto-resolve + recovery email
- Acknowledge from the UI (member+)
- One active incident per monitor (no spam duplicates)

### Public status page
Enable on the project settings page, then open:

```
http://localhost:8080/status/{project-slug}
http://localhost:8080/status/{project-slug}.json
```

Public pages never expose heartbeat tokens or HTTP URLs.

### Dashboard
After login, `/dashboard` shows monitor counts, open/ack incidents, needs-attention lists, and quick actions.

## Verify

```bash
php artisan test --filter='PublicStatusPageTest|DashboardTest|HeartbeatMonitorTest|HttpMonitorTest|IncidentTest'
docker compose exec app php artisan schedule:run
```

## Scope

**Done (Steps 1–5):** auth, orgs/projects, HTTP + heartbeat monitors, incidents/email, public status, dashboard polish.

**Not yet (Step 6+):** Slack/SMS, billing, maintenance windows, custom status domains, subscribers.
