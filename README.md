# White-Label Email Automation System

This is a private PHP/MySQL email automation dashboard for compliant B2B outreach, RFQs, lead tracking, queues, unsubscribe handling, and reports. Atlas BioLabs is included as the first default business profile, but the app can now run multiple businesses from the same installation.

## Requirements

- PHP 8.1+ with `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `curl`, `json`, `fileinfo`, and `session`
- Composer
- MySQL or MariaDB

This Windows workspace currently uses PHP 8.2, `tools/composer.phar`, and MariaDB 12.2.

## Setup

```powershell
php tools/composer.phar install
copy .env.example .env
```

Configure `.env` for app URL, database credentials, environment, and secrets only. Business sender details, branding, address, compliance footer, categories, signatures, daily limits, follow-up delays, and non-sensitive SMTP values are configured from `/settings.php`.

Create and load the database:

```sql
CREATE DATABASE IF NOT EXISTS atlas_biolabs_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```powershell
mysql -u root atlas_biolabs_bot < database/schema.sql
mysql -u root atlas_biolabs_bot < database/seed.sql
php scripts/create_admin.php
php -S localhost:8000 router.php
```

Open `http://localhost:8000/login.php`.

## Railway Deployment

This project includes a `Dockerfile` for Railway. It uses PHP 8.2, installs the required PHP extensions, runs `composer install` during the image build, and starts the app with the PHP built-in server:

```sh
php -S 0.0.0.0:${PORT:-8080} router.php
```

Railway sets `PORT` automatically. Do not add `PORT` manually unless Railway asks for it.

Deploy steps:

1. Create a new Railway project from this repository.
2. Add a Railway MySQL database service.
3. Add the environment variables listed below to the web service.
4. Deploy the web service. Railway will build from the `Dockerfile`.
5. Import `database/schema.sql` and `database/seed.sql` into the Railway MySQL database.
6. Run `php scripts/create_admin.php` in a Railway shell or create the first admin from a secure one-off CLI session.
7. Open `/login.php`, sign in, confirm the Atlas BioLabs profile is active, and keep `MAIL_PROVIDER=log` until you are ready to test a real provider.

The app supports both local database variables and Railway MySQL variables. Railway values are read from `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, or `MYSQL_URL`.

Add these Railway variables to the PHP web service:

```env
APP_ENV=production
APP_URL=https://your-railway-domain.up.railway.app

MYSQLHOST=${{MySQL.MYSQLHOST}}
MYSQLPORT=${{MySQL.MYSQLPORT}}
MYSQLUSER=${{MySQL.MYSQLUSER}}
MYSQLPASSWORD=${{MySQL.MYSQLPASSWORD}}
MYSQLDATABASE=${{MySQL.MYSQLDATABASE}}
MYSQL_URL=${{MySQL.MYSQL_URL}}

RFQ_API_TOKEN=change_me_to_a_long_private_random_secret
MAIL_API_KEY=
MAIL_SMTP_PASS=
```

Keep business-specific sender settings, `MAIL_PROVIDER`, SMTP host/user/port, daily limits, follow-up delays, signatures, and compliance footer in `/settings.php`. Keep `MAIL_PROVIDER=log` for initial Railway testing so no real emails are sent. If Railway SMTP connectivity fails, switch to `MAIL_PROVIDER=brevo_api` in `/settings.php` and set `MAIL_API_KEY` in Railway variables.

Security notes:

- `.railwayignore` excludes `.env`, `storage/mail/*`, `database/backups/*`, `node_modules`, and `vendor`.
- `router.php` blocks direct browser access to `.env`, `app/`, `config/`, `database/`, `storage/`, `tools/`, `vendor/`, and Composer files.
- Do not use `NEXT_PUBLIC_` variables for the bot API token on the website.

## Atlas BioLabs

Atlas BioLabs is seeded as business profile `1` with its existing colors, compliant footer, signature, lead categories, and default templates. Existing leads, templates, queue items, logs, settings, and RFQs are migrated to that profile.

## Adding Another Business

Go to `/businesses/create.php` and configure:

- business and brand name
- sender name/email and reply-to
- admin notification email
- address, website, logo URL
- brand colors
- compliance footer and default signature
- daily send limit, follow-up delays, and lead categories

New business profiles receive a generic editable template set automatically.

## Local Test Business

Create a profile such as `Local Test Business` and set `MAIL_PROVIDER=log` on `/settings.php`. Add a lead, queue an email, and run the sender. Emails are written to `storage/mail` and include the selected business profile ID, sender, signature, footer, and unsubscribe link.

## Email Testing

Dry-run mode:

Set `MAIL_PROVIDER=log` on `/settings.php`. This is the default testing mode.

SMTP mode:

Set `MAIL_PROVIDER=smtp`, sender, SMTP host, port, and SMTP user on `/settings.php`.

`MAIL_SMTP_PASS`, `MAIL_API_KEY`, and `RFQ_API_TOKEN` stay in `.env` and are never shown in the admin UI.

Brevo API mode:

Set `MAIL_PROVIDER=brevo_api` on `/settings.php` and keep `MAIL_API_KEY` in `.env`.

Railway Free/Hobby commonly blocks outbound SMTP connections. For Railway deployments, `brevo_api` is the recommended provider because it sends over HTTPS instead of SMTP.

Use `/tools/mail_diagnostics.php` for a safe mail readiness check. It shows the active business mail settings, whether `MAIL_API_KEY` exists, whether cURL is enabled, and it can run a Brevo account connectivity check without sending a campaign.

## Settings

Keep these in `.env`:

- `APP_URL`
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `MAIL_API_KEY`
- `MAIL_SMTP_PASS`
- `RFQ_API_TOKEN`
- `APP_ENV`

Control these from `/settings.php`:

- business name, brand name, tagline, address, default signature
- mail provider, sender name/email, reply-to, admin notification email
- SMTP host, port, and user
- daily send limit
- follow-up delays
- compliance and unsubscribe footer text
- one-off provider test email

If `MAIL_PROVIDER=smtp`, sender name, sender email, SMTP host, SMTP port, and SMTP user are required. If `MAIL_PROVIDER=brevo_api`, sender name and sender email are required and `MAIL_API_KEY` must exist in `.env`. If `MAIL_PROVIDER=log`, SMTP credentials are not required.

RFQ API token support reads `RFQ_API_TOKEN` first and also accepts `ATLAS_BOT_API_TOKEN` as a compatible alias for website-to-bot integrations.

## Campaign Queues

All manual sending goes through `email_queue`.

- Single lead: open a lead and use `Queue Email to This Lead`.
- Selected leads: select checkboxes on `/leads/index.php`, choose a template, and preview.
- Filtered leads: filter `/leads/index.php`, then use `Queue Email to Filtered Leads`.

Every campaign shows a preview with a rendered sample email, eligible count, skipped count, skipped reasons, schedule, and daily limit warning. The queue prevents duplicate pending rows for the same lead/template and skips unsubscribed, bounced, not-interested, complained, invalid, and other stopped-status leads.

Campaigns are stored in `campaigns`, and every queued email keeps its selected `template_id`. The sender fails queue rows with missing or inactive templates instead of falling back to a hardcoded message.

## Cron Jobs

Run all active business profiles:

```powershell
php cron/send_daily_emails.php
php cron/send_daily_report.php
```

Run one business profile:

```powershell
php cron/send_daily_emails.php --business=1
php cron/send_daily_report.php --business=1
```

Sending is Monday-Friday only. Each profile uses its own daily sending limit and compliance fields. Sending is blocked for a profile if sender name, sender email, business address, or compliance footer is missing.

The queue sender continues to respect daily limits, duplicate prevention, unsubscribes, bounces, and stopped lead statuses regardless of whether the actual provider is `log`, `smtp`, or `brevo_api`.

## RFQ Forms

Use a business-specific RFQ URL:

```text
/rfq-submit.php?business_id=1
```

RFQs create or update a lead only under that business profile and send notifications using that profile’s sender and compliance settings.

Website-to-bot RFQ API posts can send JSON with a secure header:

```powershell
curl -X POST http://localhost:8000/api/rfqs/create.php?business_id=1 `
  -H "Content-Type: application/json" `
  -H "X-ATLAS-RFQ-TOKEN: your-env-token" `
  -d "{\"name\":\"Test Buyer\",\"company\":\"Example Co\",\"email\":\"buyer@example.com\",\"product_interest\":\"Sourcing support\",\"source\":\"website_rfq\"}"
```

The public `/rfq-submit.php` form remains available without the API token and is rate limited. API posts require `X-ATLAS-RFQ-TOKEN`.

The website integration endpoint is:

```text
/api/rfqs/create.php
```

It accepts JSON only, validates `X-ATLAS-RFQ-TOKEN` against `RFQ_API_TOKEN` in `.env`, rate-limits requests, creates or updates the lead as `interested`, stores the `source`, and stores cart `items` JSON when provided.

## Database Changes

Before altering the live database, a backup was written under `database/backups/`. The white-label migration is in:

```text
database/migrations/2026_05_12_business_profiles.sql
database/migrations/2026_05_12_generic_templates.sql
database/migrations/2026_05_12_campaigns_segments_rfq.sql
database/migrations/2026_05_12_rfq_api_fields.sql
```

These scripts add profile IDs safely and assign existing data to Atlas BioLabs profile `1`.
