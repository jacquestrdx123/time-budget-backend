# TimeBudget Backend — Environment Variables

## Where the JWT secret lives

The **JWT secret** must be set in the **Laravel `.env` file** as:

```env
JWT_SECRET=your-secret-here
```

- **Locally:** add `JWT_SECRET=...` to your `.env` (do not commit it).
- **Laravel Forge:** in the Forge dashboard, open your site → **Environment** → add `JWT_SECRET` with a strong random value. Forge writes this into `.env` on the server.

Generate a secure value:

```bash
php artisan jwt:secret
```

That command writes `JWT_SECRET=<random-base64>` into your `.env`. On Forge, generate the value locally (or with `openssl rand -base64 32`) and paste it into the Environment editor.

If `JWT_SECRET` is missing or empty, you get: **"Secret is not set"** when logging in or registering.

---

## Mapping from Node/Express env to Laravel .env

| Node / old env              | Laravel .env              | Notes |
|----------------------------|---------------------------|--------|
| `SECRET_KEY`               | **`JWT_SECRET`**          | **Required.** Used to sign JWTs. Set a long random string; generate with `php artisan jwt:secret`. |
| (none)                     | `APP_KEY`                 | Laravel app key. Set with `php artisan key:generate`. Forge often sets this. |
| `DATABASE_URL`             | `DB_*` or `DATABASE_URL`  | Use MySQL on Forge: `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. |
| (none)                     | `APP_ENV`                 | `production` on Forge. |
| (none)                     | `APP_DEBUG`               | `false` in production. |
| (none)                     | `APP_URL`                 | Backend URL, e.g. `https://time-budget-backend-xxx.on-forge.com`. |
| (none)                     | `FRONTEND_URL`            | Frontend URL for CORS/links, e.g. `https://time-budget-frontend-9rn45vjo.on-forge.com`. |
| `FIREBASE_ENABLED`         | `FIREBASE_ENABLED`        | Same. |
| `FIREBASE_CREDENTIALS_PATH` | `FIREBASE_CREDENTIALS_PATH` | Path to service account JSON (e.g. `storage/app/firebase-service-account.json`). |
| `EMAIL_ENABLED`            | `EMAIL_ENABLED`           | Same. |
| `SMTP_*`                   | `MAIL_*`                  | Laravel uses `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`. |
| `SCHEDULER_ENABLED`        | `SCHEDULER_ENABLED`       | Same. |
| `SHIFT_REMINDER_MINUTES`   | `SHIFT_REMINDER_MINUTES` | Same. |
| `SCHEDULER_CHECK_INTERVAL_SECONDS` | `SCHEDULER_CHECK_INTERVAL_SECONDS` | Same. |

---

## Minimal production .env (Forge)

```env
APP_NAME=TimeBudget
APP_ENV=production
APP_KEY=base64:...          # from php artisan key:generate
APP_DEBUG=false
APP_URL=https://time-budget-backend-w3dpfxd1.on-forge.com

# Required for auth
JWT_SECRET=<paste output of: php artisan jwt:secret, or openssl rand -base64 32>

# Database (Forge sets these when you attach a DB)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# Frontend (for CORS; add your frontend URL)
FRONTEND_URL=https://time-budget-frontend-9rn45vjo.on-forge.com

# Optional
FIREBASE_ENABLED=true
FIREBASE_CREDENTIALS_PATH=storage/app/firebase-service-account.json
EMAIL_ENABLED=false
SCHEDULER_ENABLED=true
SHIFT_REMINDER_MINUTES=5
SCHEDULER_CHECK_INTERVAL_SECONDS=60
```

After adding or changing `JWT_SECRET` on Forge, reload the app (e.g. deploy again or restart PHP-FPM) so the new env is picked up.

---

## Firebase (push notifications)

Push notifications use **Firebase Cloud Messaging (FCM)**. Two env vars control this:

| Env var | Purpose |
|--------|---------|
| `FIREBASE_ENABLED` | Set to `true` to enable sending push notifications; `false` to disable (API will return a friendly error instead of sending). |
| `FIREBASE_CREDENTIALS_PATH` | Path to your **Firebase service account JSON** file. Can be relative to the project root or absolute. |

### Where the credentials file lives

- **Path is relative to the Laravel project root** (the directory that contains `artisan`), unless you set an absolute path.
- **Do not commit** the service account JSON to git (it contains a private key). Add it to `.gitignore` (e.g. `firebase-service-account.json` or `storage/app/firebase-service-account.json`).

**Common setups:**

| Setup | `FIREBASE_CREDENTIALS_PATH` | Where to put the file |
|-------|-----------------------------|------------------------|
| Local (project root) | `firebase-service-account.json` | Project root, next to `artisan`. |
| Local (storage) | `storage/app/firebase-service-account.json` | `storage/app/`. |
| Forge (recommended) | `storage/app/firebase-service-account.json` | Upload the JSON to the server under `storage/app/` (e.g. via SFTP, or a deploy script that writes it from a Forge “secret” / env). Ensure the path exists and the web server can read the file. |

### Getting the service account JSON

1. Open [Firebase Console](https://console.firebase.google.com/) → your project → **Project settings** (gear) → **Service accounts**.
2. Click **Generate new private key** and download the JSON.
3. Rename or place it as you like (e.g. `firebase-service-account.json`) and set `FIREBASE_CREDENTIALS_PATH` to that path.

### .env examples

```env
# Disable push (default)
FIREBASE_ENABLED=false

# Enable push, file in project root
FIREBASE_ENABLED=true
FIREBASE_CREDENTIALS_PATH=firebase-service-account.json

# Enable push, file in storage (good for Forge)
FIREBASE_ENABLED=true
FIREBASE_CREDENTIALS_PATH=storage/app/firebase-service-account.json
```

### Forge

1. Add to **Environment**: `FIREBASE_ENABLED=true` and `FIREBASE_CREDENTIALS_PATH=storage/app/firebase-service-account.json`.
2. Upload the service account JSON to the server at `storage/app/firebase-service-account.json` (e.g. SFTP, or a one-off script that writes the content from a secure secret). The file is not in the repo.
3. Ensure `storage/app` is writable by the deploy user if you ever write the file via deploy; for read-only use, the app only needs read access.
