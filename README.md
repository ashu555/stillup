# Stillup

Uptime + cron heartbeat monitoring platform.

**Step 1:** foundation (auth, organizations, projects, Docker)  
**Step 2:** HTTP monitors + scheduled checks  
**Step 3:** Incidents + email alerts

## Stack

- Laravel 11 / PHP 8.3
- MySQL 8
- Redis (queues + cache)
- Laravel Sanctum
- Inertia.js + React + Tailwind CSS
- Docker Compose + Mailhog

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

## Verify incidents + email (Step 3)

```bash
docker compose exec app php artisan migrate
php artisan test --filter='IncidentTest|HttpMonitorTest'

# Force checks (scheduler also runs every minute)
docker compose exec app php artisan schedule:run

# Or run a single due check cycle via queue
docker compose exec app php artisan queue:work redis --once
```

**Demo path**

1. Login → Organizations → Acme → Production → Monitors  
2. Open **Broken endpoint (demo)** (or create a monitor with a bad URL)  
3. Wait for scheduler/queue → monitor goes **DOWN** → incident opens  
4. Check Mailhog for “Incident opened” email  
5. Acknowledge from Incidents UI  
6. Point monitor at a healthy URL / wait for recovery → auto-resolve + recovery email  

## Useful commands

```bash
docker compose exec app bash
docker compose ps
npm run dev
```

## Roles (incidents)

- viewer: view only  
- member+: acknowledge + manual resolve  
- admin/owner: receive opened/resolved emails  

## Scope

**Done:** Docker, auth, orgs/projects, HTTP monitors, incidents, email alerts (Mailhog).

**Not yet:** Heartbeats, public status pages, Slack/SMS, escalation.
