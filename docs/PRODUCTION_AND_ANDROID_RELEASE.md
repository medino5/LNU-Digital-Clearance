# Production Deploy and Android Tester Releases

This repository now supports two separate delivery paths:

- `backend/` deploys the Laravel web app to Railway
- `mobile/` builds a signed Android APK and publishes it to GitHub Releases for testers

## Railway backend setup

1. In Railway, point the web service root directory to `backend/`.
2. In the Railway service settings, set the config file path to `/backend/railway.json` if Railway does not detect it automatically.
3. Set these production variables in Railway:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://<your-railway-domain>`
   - keep the Railway-provided MySQL variables
4. Open the Railway web service shell and run:
   - `php artisan config:clear`
   - `php artisan migrate --force`
5. If the database is brand new and you want the default admin/program/office/demo records, run once:
   - `php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder --force`
6. Verify the deployment with:
   - `php artisan migrate:status`
   - the Railway public URL
   - Railway deployment logs

## Android tester release setup

### Required one-time secrets in GitHub

Add these GitHub Actions secrets:

- `ANDROID_KEYSTORE_BASE64`
- `ANDROID_KEYSTORE_PASSWORD`
- `ANDROID_KEY_ALIAS`
- `ANDROID_KEY_PASSWORD`

Add this GitHub repository variable:

- `MOBILE_API_BASE_URL`

`MOBILE_API_BASE_URL` should be the live Railway API root, for example:

```text
https://your-railway-domain/api
```

### Local signing file for manual release builds

If you want to build a signed APK locally, copy:

```text
mobile/android/key.properties.example
```

to:

```text
mobile/android/key.properties
```

and point `storeFile` to your real keystore path.

### Release pipeline behavior

The workflow file is:

```text
.github/workflows/mobile-release.yml
```

It does the following on every push to `main`:

1. Builds a signed Android release APK
2. Injects the live Railway API URL with `--dart-define`
3. Updates the rolling GitHub prerelease tag `latest-testing`
4. Uploads the newest tester APK as:
   - `digital-clearance-latest.apk`

## Tester download link

Once the workflow has run at least once on `main`, testers can use:

- Release page:
  - `https://github.com/rapozdota-dot/Digital-Clearance/releases/tag/latest-testing`
- Direct APK download:
  - `https://github.com/rapozdota-dot/Digital-Clearance/releases/download/latest-testing/digital-clearance-latest.apk`

Testers must download and install each new APK manually. GitHub Releases does not provide Play Store-style auto-updates.

## Notes

- The Android package ID is now `com.digitalclearance.mobile`. Do not change it after testers install the app, or future updates will install as a separate app.
- Release builds no longer fall back to the Android debug signing key.
- The mobile debug-only connection test button is hidden in release builds unless `ENABLE_CONNECTION_TEST=true` is passed as a `--dart-define`.
