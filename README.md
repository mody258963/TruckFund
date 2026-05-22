# TruckFund

Laravel 12 internal operations platform for truck financing: lead management, customer onboarding, finance applications, and catalog administration.

## Stack

- PHP 8.2+, Laravel 12
- Livewire 4 + Flux UI
- Tailwind CSS 4
- MySQL (UUID primary keys)
- Repository + Service architecture
- Bilingual UI (English / Arabic, RTL)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure MySQL in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=truckfund
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

## Demo logins

| Email | Password | Role |
|-------|----------|------|
| admin@truckfund.test | password | Admin |
| sales@truckfund.test | password | Sales |
| finance@truckfund.test | password | Finance |

## Modules

- **Leads** — AI score stub, priority flag (≥185), assignment, status, communications
- **Customers** — 8-step onboarding wizard
- **Finance** — Application draft → review → accept/reject → booking → documents → completed
- **Catalog** — Merchants, financial products, auto products, suppliers
- **Audit** — Change log (admin)

## Locale

Use the EN / عربي toggle in the header. Session key: `locale` (`en` or `ar`).

## Tests

```bash
php artisan test
```

## Document storage

Private uploads use the `documents` disk (`storage/app/documents`).

## Deploy with Dokploy (Docker)

The repo includes a multi-stage `Dockerfile` (Composer + Vite build, then Nginx + PHP 8.3-FPM).

### Dokploy settings

| Setting | Value |
|--------|--------|
| Build type | Dockerfile |
| Dockerfile path | `Dockerfile` |
| Docker context | `.` |
| Build stage | `production` |
| Port | `80` |
| Health check | `GET /up` |

If the build appears stuck at `package:discover`, pull the latest `Dockerfile` (Composer uses `--no-scripts` at build time; discovery runs on container start).

### Required environment variables

Set these in Dokploy (not in the image):

```
APP_KEY=base64:...          # php artisan key:generate --show
APP_URL=https://your-domain.com
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=truckfund
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Optional:

```
RUN_MIGRATIONS=true         # runs php artisan migrate --force on start
TRUCKFUND_HIGH_VALUE_SCORE=185
```

### Persistent storage

Mount a volume on `/var/www/html/storage/app` so uploaded documents survive redeploys.

### Local Docker test

```bash
docker build -t truckfund .
docker run -p 8080:80 --env-file .env -e APP_KEY=base64:xxx truckfund
```

Or: `docker compose up --build` (see `docker-compose.yml`).
