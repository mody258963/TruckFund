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
| `APP_URL` | `https://truckfund-production.up.railway.app` |
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
