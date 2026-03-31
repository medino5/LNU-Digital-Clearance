# Mobile App

This Flutter app is distributed to testers as a signed Android APK through GitHub Releases.

## Release-ready defaults

- Android package ID: `com.digitalclearance.mobile`
- Release signing: `mobile/android/key.properties` or CI secrets
- Live API URL: injected at build time with `--dart-define=APP_API_BASE_URL=...`
- Rolling tester release tag: `latest-testing`

## Local development

By default, debug builds still point to the local Laravel backend on:

```text
http://127.0.0.1:8000/api
```

For phone testing, keep using:

```text
adb reverse tcp:8000 tcp:8000
```

## Local signed release build

1. Create a keystore outside git.
2. Copy `mobile/android/key.properties.example` to `mobile/android/key.properties`.
3. Update the file with your real keystore path and passwords.
4. Build with:

```text
flutter build apk --release --dart-define=APP_API_BASE_URL=https://your-railway-domain/api
```

## Automated tester releases

The repository workflow at `.github/workflows/mobile-release.yml` builds and publishes `digital-clearance-latest.apk` to the `latest-testing` GitHub prerelease whenever code is pushed to `main`.

The full deploy and distribution checklist lives in:

```text
docs/PRODUCTION_AND_ANDROID_RELEASE.md
```
