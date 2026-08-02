# LUMI Web (Laravel)

Administrative and clinician portal for **LUMI**, plus the mobile cloud API.

## Roles

| Role | Surface |
|------|---------|
| **Admin** | Manage/verify clinicians, audit logs, settings |
| **Clinician (Doctor)** | Redeem parent OTP/QR → view child telemetry → PDF export → end session |
| **Guardian / Child** | **Mobile app only** — `/guardian/*` shows a “use the LUMI mobile app” page |

## Stack

- Laravel 12 / PHP 8.2 / MySQL
- Laravel Sanctum (mobile API)
- Gmail SMTP via `PhpMailerService` (guardian email OTP + clinician invite emails)
- Chart.js + DomPDF for clinician reports

## Key mobile APIs

- `POST /api/mobile/guardian/register` → sends email OTP
- `POST /api/mobile/guardian/verify-email` `{ email, otp }`
- `POST /api/mobile/guardian/resend-verification`
- `POST /api/mobile/child/register` (**auth:sanctum**)
- `POST /api/mobile/child/{id}/sync/metrics/batch`
- `PUT /api/mobile/child/{id}/sync/pet|limits`
- Temporary access: `POST /api/mobile/children/{id}/access-tokens`

Prescriptions and permanent clinician–patient links were removed; access is session-based OTP/QR only.

## Setup

```bash
composer install
cp .env.example .env   # configure DB + MAIL_* for Gmail
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan test
```

See `EMAIL_SETUP.md` / `GMAIL_MYSQL_SETUP.md` for SMTP.

## Docs

- [Temp access guide](../docs/TEMP_ACCESS_TOKEN_REFACTOR_UPDATE_AND_TEST_GUIDE.md)
- [2-minute demo](../docs/LUMI_2MIN_DEMO_SCRIPT.md)
- [Thesis limitations](../docs/LUMI_THESIS_LIMITATIONS.md)
