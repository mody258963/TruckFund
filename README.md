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
