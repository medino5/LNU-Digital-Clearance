# Render + Supabase Deployment Runbook

## Summary

Use GitHub as the source repository, Render as the Laravel backend host, and Supabase as the PostgreSQL database.

Delivery paths:

- Backend/web/API: Render web service from `main`
- Database: Supabase PostgreSQL
- Android tester APK: existing GitHub Releases workflow

Render deploys automatically whenever `main` is pushed or merged.

## 1. Push The Repo To GitHub

Repository:

```text
https://github.com/medino5/LNU-Digital-Clearance.git
```

After this setup is pushed, GitHub becomes the repo Render should connect to.

## 2. Create The Supabase Database

1. Open [Supabase Dashboard](https://supabase.com/dashboard).
2. Create a new project.
3. Save the database password.
4. Open `Connect`.
5. Copy the Laravel/Postgres connection string.
6. Prefer the Session Pooler string for Render.

Use this shape in Render:

```env
DB_CONNECTION=pgsql
DB_URL=postgres://postgres.PROJECT_REF:YOUR_PASSWORD@YOUR_POOLER_HOST:5432/postgres
DB_SSLMODE=require
```

Do not commit the real `DB_URL`.
If the database password contains symbols like `@`, `#`, `/`, or `:`, URL-encode the password before pasting it into `DB_URL`.

## 3. Create The Render Web Service

1. Open [Render Dashboard](https://dashboard.render.com).
2. Click `New`.
3. Choose `Blueprint`.
4. Connect the GitHub repo.
5. Select `render.yaml`.
6. Let Render create `lnu-digital-clearance-backend`.

The service uses:

```text
runtime: docker
rootDir: backend
healthCheckPath: /login
```

The Docker image starts Apache, runs migrations, seeds the baseline records, and serves Laravel from `public/`.

## 4. Set Render Environment Variables

In the Render service, open `Environment` and confirm these values.

```env
APP_NAME=Digital Clearance
APP_ENV=production
APP_KEY=base64:PASTE_GENERATED_APP_KEY
APP_DEBUG=false
APP_URL=https://lnu-digital-clearance-backend.onrender.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_URL=postgres://postgres.PROJECT_REF:YOUR_PASSWORD@YOUR_POOLER_HOST:5432/postgres
DB_SSLMODE=require

CACHE_STORE=file
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=120

CLEARANCE_CURRENT_SEMESTER=1st
CLEARANCE_CURRENT_ACADEMIC_YEAR=2025-2026
RUN_DATABASE_SEEDER=true
RUN_DATABASE_SEEDER_CLASS=Database\\Seeders\\UatDatabaseSeeder
```

Generate the Laravel app key locally if needed:

```powershell
cd C:\digital-clearance\backend
php artisan key:generate --show
```

Use the exact HTTPS Render domain for `APP_URL`. If Render gave you a different `.onrender.com` hostname, use that real hostname instead of the example above.

## 5. First Deploy Verification

After Render finishes deploying:

1. Open `https://lnu-digital-clearance-backend.onrender.com/login`.
2. Confirm the login page loads.
3. Log in with the seeded admin account:

```text
username: mis.admin
password: password
```

4. Open Supabase Table Editor and confirm migrated tables exist.
5. Confirm seeded demo records exist, including:

```text
mis.admin
2302314
the generated 2400001-2401400 UAT roster
```

Change default passwords before sharing the deployment outside the team.

## 6. Mobile App Connection

After Render is live, update the GitHub repository variable:

```text
MOBILE_API_BASE_URL=https://lnu-digital-clearance-backend.onrender.com/api
```

Then run the existing `Mobile Release` workflow in GitHub Actions.

Before the first APK release, add these GitHub repository secrets:

```text
ANDROID_KEYSTORE_BASE64
ANDROID_KEYSTORE_PASSWORD
ANDROID_KEY_ALIAS
ANDROID_KEY_PASSWORD
```

And add this GitHub repository variable:

```text
MOBILE_API_BASE_URL=https://your-real-render-domain.onrender.com/api
```

Then trigger the mobile release:

1. Open the GitHub repo.
2. Open `Actions`.
3. Open `Mobile Release`.
4. Click `Run workflow`.
5. Choose branch `main`.
6. Wait for the `latest-testing` release asset to appear.

Tester APK link stays:

```text
https://github.com/medino5/LNU-Digital-Clearance/releases/tag/latest-testing
```

## 7. Ongoing Deploy Flow

Normal sprint flow:

1. Work on a ticket branch.
2. Merge to `main`.
3. Render auto-deploys the backend.
4. GitHub Actions can rebuild the tester APK when mobile config/code changes.

If a deploy fails, Render keeps the previous successful deploy running.

## Notes

- Railway config was removed.
- The old production/deployment notes were replaced by this Render + Supabase runbook.
- `backend/Dockerfile` is now the Render production Dockerfile.
- `backend/Dockerfile.dev` keeps the old local PHP-FPM container for `docker-compose.yml`.
- `RUN_DATABASE_SEEDER_CLASS` lets us switch between `DatabaseSeeder` and `UatDatabaseSeeder` without changing code again.

## Official References

- [Render deploys](https://render.com/docs/deploys/)
- [Render Docker Laravel guide](https://render.com/docs/deploy-php-laravel-docker)
- [Render environment variables](https://render.com/docs/configure-environment-variables)
- [Supabase Laravel quickstart](https://supabase.com/docs/guides/getting-started/quickstarts/laravel)
- [Supabase connection strings](https://supabase.com/docs/reference/postgres/connection-strings)
