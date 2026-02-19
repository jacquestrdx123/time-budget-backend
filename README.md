# TimeBudget Laravel API

Laravel 12 backend for TimeBudget. API-compatible with the existing Express backend.

## Requirements

- PHP 8.2+
- Composer
- MySQL (same schema as Express backend)

## Setup

1. Copy `.env.example` to `.env` and set:
   - `DB_*` (host, port, database, username, password) to match your MySQL
   - `FRONTEND_URL` (e.g. `http://localhost:8081`)
   - Optional: `FIREBASE_*`, `EMAIL_*`, `SCHEDULER_*` (see `config/timebudget.php`)

2. Generate keys:
   ```bash
   php artisan key:generate
   php artisan jwt:secret
   ```

3. Run migrations (fresh DB):
   ```bash
   php artisan migrate
   ```

4. (Optional) Seed default system settings for existing tenants:
   ```bash
   php artisan db:seed --class=SystemSettingsSeeder
   ```

## Running

```bash
php artisan serve
```

API is at `http://localhost:8000` (no `/api` prefix: routes are `/auth/login`, `/users`, etc.).

## Frontend

Point the Vue frontend to this API by setting in the frontend `.env`:

```
VITE_API_BASE_URL=http://localhost:8000
```

Then rebuild or run the frontend dev server.

## Scheduler

Reminder jobs run every minute when the Laravel scheduler is executed. Add to crontab:

```cron
* * * * * cd /path/to/backend-laravel && php artisan schedule:run >> /dev/null 2>&1
```

## Artisan commands

- `reminders:shift` – Send notifications for shifts starting within the configured window
- `reminders:custom` – Send due custom reminders

These are scheduled automatically when `schedule:run` runs.
