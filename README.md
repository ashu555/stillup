# Stillup

Uptime + cron heartbeat monitoring platform.

**Step 1:** foundation (auth, organizations, projects, Docker)  
**Step 2:** HTTP monitors + scheduled checks

## Stack

- Laravel 11 / PHP 8.3
- MySQL 8
- Redis (queues + cache)
- Laravel Sanctum
- Inertia.js + React + Tailwind CSS
- Docker Compose

## Quick start (Docker)

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
npm install && npm run build
```

App: http://localhost:8080  
Mailhog UI: http://localhost:8025

### Demo login

- Email: `demo@stillup.test`
- Password: `password`

Demo project includes two HTTP monitors (example.com + an intentionally broken URL).

## Verify HTTP monitors

```bash
# Migrate + seed
docker compose exec app php artisan migrate --seed

# Feature tests
php artisan test --filter=HttpMonitorTest

# Manually run scheduler tick (also runs every minute in the scheduler container)
docker compose exec app php artisan schedule:run

# UI path
# Login → Organizations → Acme → Production → Monitors
```

## Local (without Docker)

Requires PHP 8.3, Composer, MySQL, Redis, Node 18+.

```bash
cp .env.example .env
# Point DB_HOST / REDIS_HOST / MAIL_HOST to localhost
composer install
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

## Useful commands

```bash
# App shell
docker compose exec app bash

# Queue / scheduler are already running as containers
docker compose ps

# Rebuild assets while developing (on the host)
npm run dev
```

## API auth (Sanctum tokens)

```bash
# Register
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Ada","email":"ada@example.com","password":"password","password_confirmation":"password"}'

# Login
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"demo@stillup.test","password":"password"}'

# Me
curl http://localhost:8080/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## Roles

`owner` · `admin` · `member` · `viewer`

- viewer: read-only monitors
- member+: create/update/pause/resume
- admin/owner: delete (policy ready; UI delete not in Step 2)

## Project layout

```
app/
  Actions/          CreateHttpMonitorAction, RecordCheckResultAction, …
  DTOs/             HttpCheckResultDto
  Enums/            MonitorType, MonitorStatus, OrganizationRole
  Jobs/             DispatchDueHttpMonitorsJob, RunHttpMonitorCheckJob
  Models/           Monitor, HttpMonitorConfig, CheckResult, …
  Policies/         MonitorPolicy, …
  Services/         HttpMonitorChecker, AuditLogger
```

## Scope

**Done (Step 1–2):** Docker, auth, orgs/projects, HTTP monitors, check jobs, scheduler, check history UI.

**Not yet:** Heartbeats, incidents, email alerts, public status pages.
