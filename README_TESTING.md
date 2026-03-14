# Physical Device Testing Guide - LNU DCS

## Runtime Standard
- Primary local runtime: `docker compose` with `nginx + app + mysql`
- Default external URL for laptop browsers: `http://<your-laptop-ip>:8000`
- Default mobile app base URL for a physical Android device: `http://127.0.0.1:8000/api` through `adb reverse`
- Office portal: `http://<your-laptop-ip>:8000/office/login`
- Admin portal: `http://<your-laptop-ip>:8000/admin/login`
- SQLite is kept only as a fallback mode through `backend/.env.sqlite`

## Prerequisites
- Phone and laptop are on the same WiFi network
- Docker Desktop is running
- USB debugging is enabled if you will use `flutter run` on a physical Android phone
- Flutter is installed on the laptop
- XAMPP, IIS, or any other service bound to ports `80` or `8000` is stopped

## Setup Steps

### 1. Find your local IP
Use the Wi-Fi IPv4 address on your laptop for browser testing only.

**Windows:** run `ipconfig` and use the IPv4 under `Wireless LAN adapter Wi-Fi`

Current detected value in this workspace:
```text
192.168.254.115
```

### 2. Confirm the mobile app connectivity mode
Open `mobile/lib/core/network_config.dart` and confirm:
```dart
static const String androidDebugHost = '127.0.0.1';
static const String laptopLanIp = '192.168.254.115';
```

The student app uses `127.0.0.1` because the phone reaches the laptop backend through `adb reverse`, not direct Wi-Fi routing.

### 3. Confirm the backend default environment
The default backend environment now uses MySQL through Docker:
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=clearance_db
DB_USERNAME=clearance_user
DB_PASSWORD=root
```

If you ever need the fallback direct-Laravel setup, copy `backend/.env.sqlite` over `backend/.env` temporarily.

### 4. Start the canonical Docker stack
From the project root run:
```bash
docker compose up --build -d
```

### 5. Seed the database inside Docker
From the project root run:
```bash
docker compose exec app php artisan migrate:fresh --seed
```

### 6. Verify the portals on your laptop
Open these in your browser:
```text
http://192.168.254.115:8000/admin/login
http://192.168.254.115:8000/office/login
```

### 7. Create the USB reverse tunnel for the phone
With the Android phone connected by USB and USB debugging enabled, run:
```bash
adb reverse tcp:8000 tcp:8000
adb reverse --list
```

You should see a rule that maps `tcp:8000` on the phone to `tcp:8000` on the laptop.

### 8. Run the mobile app
From `mobile/` run:
```bash
flutter pub get
flutter run
```

## Test Credentials

### Student
- Student ID: `2302314`
- Password: `password`

### Super Admin
- Username: `mis.admin`
- Password: `password`

### Office Accounts
- `bsit.treasurer` / `password`
- `bsit.adviser` / `password`
- `year3.treasurer` / `password`
- `librarian.office` / `password`
- `vpsd.office` / `password`

## End-to-End Test Flow
1. Log in on the phone as `2302314`.
2. Verify the profile and active semester load.
3. Tap `Initiate Clearance`.
4. Confirm the app shows 5 routed steps:
   - DIGITS Academic Organization Treasurer
   - DIGITS Academic Organization Adviser
   - 3rd Year Level Organization Treasurer
   - College Chief Librarian
   - Vice President for Student Development
5. On the laptop, log into `bsit.treasurer` and confirm John A. Doe appears in `Pending`.
6. Log into `year3.treasurer` and confirm John A. Doe appears in `Pending`.
7. Flag the student from `year3.treasurer` with a remark.
8. Refresh the phone and confirm only that step becomes `Flagged`.
9. Tap `Re-Submit to This Office` on the phone.
10. Refresh the office portal and confirm the flagged step returns to `Pending`.
11. Approve all 5 routed steps across the office accounts.
12. Refresh the phone and confirm the clearance becomes `Completed`.
13. Confirm a reference number appears.
14. Tap the PDF download button and confirm the file is saved locally on the device.

## Acceptance Checks
- Student login uses student ID, not email.
- Admin and office portals use username/password.
- Exactly 5 steps are generated for the seeded BSIT 3rd-year student.
- Unrelated office accounts do not see the student.
- Resubmitting a flagged step does not reset already approved steps.
- Completing all steps generates the final completed clearance and PDF.

## Troubleshooting
- **Phone cannot connect:** confirm Docker is running, the stack is up, the phone is connected by USB, and `adb reverse tcp:8000 tcp:8000` is active.
- **Port conflict during `docker compose up`:** stop XAMPP, IIS, Skype, or any local service already using port `8000`.
- **Laptop browser works but phone fails:** rerun `adb reverse tcp:8000 tcp:8000`, then reinstall or rerun the Flutter app.
- **Database error in Docker:** rerun `docker compose exec app php artisan migrate:fresh --seed`.
- **Need a quick fallback without Docker:** temporarily copy `backend/.env.sqlite` over `backend/.env`, then run `php artisan serve --host=0.0.0.0 --port=8000` from `backend/`.
- **Old credentials or URLs not working:** ignore the pre-rehaul `/staff/login` flow; use `/office/login`, `/admin/login`, and the username-based credentials above.

## Concurrent Login Notes
- Admin and office portals use browser session auth, so one browser profile normally holds one active portal session at a time.
- You can stay signed in as admin and office at the same time by using different devices or separate browser contexts such as Chrome + Edge or a normal window + incognito.
- Student mobile login is separate from browser portal sessions because the app uses API token auth.
- Logging in the same student account from another phone or reinstall can invalidate the old mobile token because the backend clears previous student tokens on login.
