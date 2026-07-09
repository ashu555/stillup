# Stillup

Uptime + cron heartbeat monitoring platform. Step 1: foundation (auth, organizations, projects, Docker).

## Stack

- Laravel 11 / PHP 8.4
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

## Local (without Docker)

Requires PHP 8.4, Composer, MySQL, Redis, Node 18+.

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

Organization creators become `owner`. Policies gate org/project access accordingly.

## Project layout (Step 1)

```
app/
  Actions/          CreateOrganizationAction, CreateProjectAction
  Enums/            OrganizationRole
  Models/           User, Organization, Project, AuditLog
  Policies/         OrganizationPolicy, ProjectPolicy
  Services/         AuditLogger
docker/
  nginx/            reverse proxy
  php/              PHP-FPM image + entrypoint
```

## Step 1 scope

Done: Docker stack, Sanctum session + token auth, orgs/projects CRUD foundation, roles/policies, audit logs, Inertia shell.

Not yet: HTTP monitors, heartbeats, incidents, alerts, public status pages.
