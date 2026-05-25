# Deploy TruckFund on Dokploy

## Why you see 502 / crash loop

The container **exits on start** when environment variables are missing. Dokploy shows **502 Bad Gateway** because PHP/Nginx never stays running.

Logs like `ERROR: DB_CONNECTION is sqlite` mean **Dokploy did not pass** `DB_CONNECTION=mysql` into the container.

---

## Step 1: Railway MySQL — copy real values

In **Railway → MySQL → Connect**, copy the **public** URL (TCP proxy), for example:

```text
mysql://root:YOUR_PASSWORD@monorail.proxy.rlwy.net:12345/truckfund
```

Do **not** use `mysql.railway.internal` or `RAILWAY_PRIVATE_DOMAIN`.

---

## Step 2: Dokploy Environment variables

Open **Application → Environment** (runtime env, not build-only).

### Option A — one variable (easiest)

| Name | Value |
|------|--------|
| `MYSQL_PUBLIC_URL` | Paste the full `mysql://root:...@host:port/truckfund` from Railway |
| `APP_KEY` | `base64:...` from `php artisan key:generate --show` |
| `APP_URL` | `https://your-app.sslip.io` (exact public HTTPS URL — **not** `http://`) |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |

### Option B — separate DB variables

| Name | Example |
|------|---------|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `monorail.proxy.rlwy.net` |
| `DB_PORT` | `12345` |
| `DB_DATABASE` | `truckfund` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | your real password |

Also set: `APP_KEY`, `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_DRIVER=database`, `CACHE_STORE=database`.

---

## Common mistakes

| Mistake | Fix |
|---------|-----|
| Pasting `DB_HOST=<RAILWAY_TCP_PROXY_DOMAIN>` | Replace with the **actual host** from Railway |
| Using `${{MYSQL_ROOT_PASSWORD}}` | Use the **resolved password** from Railway UI |
| Variables only in Build, not Environment | Use **runtime** Environment tab |
| Private host `mysql.railway.internal` | Use **TCP proxy** host from `MYSQL_PUBLIC_URL` |

---

## Seed demo users (no users in DB)

Default logins after seeding:

| Email | Password | Role |
|-------|----------|------|
| `admin@truckfund.test` | `password` | Admin |
| `sales@truckfund.test` | `password` | Sales |
| `finance@truckfund.test` | `password` | Finance |

**Option A — seed only** (keeps existing data): add to Environment, redeploy once, then remove:

```text
RUN_SEED_DATABASE=true
```

**Option B — full reset** (drops all tables, then migrate + seed): use only on a new/empty DB:

```text
RUN_MIGRATE_FRESH=true
```

Redeploy, confirm login works, then **delete** `RUN_MIGRATE_FRESH` or set it to `false` so the next deploy does not wipe data again.

Or run in the container shell:

```bash
php artisan migrate:fresh --seed --force
```

---

## Step 3: Save and redeploy

1. **Save** environment  
2. **Redeploy** (rebuild if Dockerfile changed)  
3. Check logs — you should see migrations run, not repeated `ERROR: DB_CONNECTION`

---

## Verify inside container

```bash
cat /var/www/html/.env | grep DB_
php artisan migrate:status
```

---

## Port

Container listens on **80**. Dokploy domain should target port **80**.

---

## Mixed content (CSS/JS blocked on login)

The browser loads the page over **HTTPS** but Laravel emitted asset URLs as **HTTP** (`http://.../build/assets/...`). Browsers block those scripts and styles.

**Fix in Dokploy Environment:**

1. Set `APP_URL` to your **HTTPS** URL, e.g. `https://truckfund-truck-fund-epb8fr-c25c32-72-62-16-40.sslip.io` (no trailing slash).
2. Redeploy after saving (config is cached on boot).
3. Optional: `ASSET_URL` — same as `APP_URL` (entrypoint sets it automatically).
4. `APP_FORCE_HTTPS=true` (default in production via entrypoint).

The app trusts reverse-proxy headers and forces `https` for generated URLs in production. If the page is still plain HTML, open DevTools → **Network** and confirm `build/assets/app-*.css` returns **200** over **https**. If those requests are red/blocked, fix `APP_URL` and redeploy.
