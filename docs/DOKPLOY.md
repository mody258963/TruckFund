# Deploy AutoFund on Dokploy

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

Also set: `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_DRIVER=database`, `CACHE_STORE=database`.

### `APP_KEY` (optional — auto-generated)

You do **not** need to set `APP_KEY` manually. On container start, `docker/generate-app-key.sh`:

1. Uses `APP_KEY` from Dokploy if you set it  
2. Otherwise loads the key from `storage/app/.app_key` (persisted volume)  
3. Otherwise generates `base64:...` and prints it in deploy logs  

Copy the key from logs into Dokploy **once** so it stays the same across redeploys without a volume:

```text
APP_KEY=base64:xxxxxxxx
```

Generate locally (optional): `php artisan key:generate --show`

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
| `aiman@autofund.test` | `Ay#7mNq4Zt$Rv2Kx` | Admin |
| `mohammed@autofund.test` | `Mh$9dRk6Xw#Pb3Tq` | Admin |

Set `TRUCKFUND_SEED_AIMAN_PASSWORD` and `TRUCKFUND_SEED_MOHAMMED_PASSWORD` in the Dokploy environment before seeding to use your own passwords.

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

**Push latest code to Git and Rebuild** in Dokploy first — otherwise `truckfund:seed-demo` does not exist in the container.

```bash
php artisan truckfund:seed-demo
# or full reset (after redeploy):
php artisan migrate:fresh --force && php artisan truckfund:seed-demo
```

If you see `Call to undefined function fake()`, the running image is **old**. Either **redeploy** from latest Git, or create the admin user immediately:

```bash
php artisan truckfund:seed-demo
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

1. Set `APP_URL` to your **HTTPS** URL, e.g. `https://autofund.example.com` (no trailing slash).
2. Redeploy after saving (config is cached on boot).
3. Optional: `ASSET_URL` — same as `APP_URL` (entrypoint sets it automatically).
4. `APP_FORCE_HTTPS=true` (default in production via entrypoint).

The app trusts `X-Forwarded-Proto` only (not empty `X-Forwarded-Host`, which crashes Symfony `getPort()`). URLs are forced to `https` via `APP_URL` / `APP_FORCE_HTTPS`. If the page is still plain HTML, open DevTools → **Network** and confirm `build/assets/app-*.css` returns **200** over **https**. If those requests are red/blocked, fix `APP_URL` and redeploy.
