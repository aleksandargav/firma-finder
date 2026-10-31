# Firma Finder

Firma Finder is a Laravel application for finding and qualifying Serbian
companies as sales leads for website development and redesign services.
It imports the open company register of the Serbian Business Registers
Agency (APR), adds a lightweight CRM on top of it, and runs technical
checks of company websites.

The user interface is in Serbian, because the tool is built for the
Serbian market. Code, documentation, and this README are in English.

## Features

- **APR catalog**: 134,374 companies imported from the APR Open Data API,
  with search by name or registration number and filters by
  municipality, activity code, and status. Search is case-insensitive in
  both Latin and Cyrillic and ignores diacritics (`cacak` finds `Čačak`),
  but it does not match across scripts: `beograd` does not find
  `БЕОГРАД`.
- **CRM**: one lead per company with website, contact details, lead
  status, and score; a lead list with filters and sorting. The first
  contact date and the last website check date are set automatically.
- **Website checks**: an HTTP check of a company's website that records
  the status code, final URL after redirects, HTTPS, response time, page
  title, and meta description. Every check is kept as history.
- **Data enrichment**: website, email, phone, and address candidates with
  source, confidence, and license status. Candidates are verified
  manually; a verified website can be applied to the CRM only by an
  explicit user action.

There is no authentication. The application is meant to run locally.

## Tech stack

- PHP 8.5, Laravel 13
- MySQL 8.4
- Blade views
- Docker Compose: PHP-FPM, Nginx, MySQL, Redis
- PHPUnit, running against MySQL

## Running locally

Requirements: Docker with the Compose plugin.

```bash
git clone https://github.com/aleksandargav/firma-finder.git
cd firma-finder
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

The application is now available at http://localhost:8080. The company
list stays empty until the APR data is imported.

The `app` container runs as user and group ID 1000. If your IDs differ
(`id -u`, `id -g`), add `UID` and `GID` with your values to `.env` before
building, so that files created in `storage/` stay editable.

### Importing the APR catalog

The import has two steps: download a snapshot, then write it to the
database.

`apr:sync` needs a CA certificate bundle. The APR API server sends only
its own certificate and leaves out the intermediate certificate (SSL2BUY
EMEA RSA Domain Validation Secure Server CA), so a standard TLS client
cannot verify the connection. The bundle contains that intermediate and
its root, Sectigo Public Server Authentication Root R46. Build it once:

```bash
mkdir -p storage/app/apr/ca
curl -sS http://crt.sectigo.com/SSL2BUYEMEARSADomainValidationSecureServerCA.crt \
  | openssl x509 -inform DER -out storage/app/apr/ca/apr-ca.pem
cat /etc/ssl/certs/Sectigo_Public_Server_Authentication_Root_R46.pem \
  >> storage/app/apr/ca/apr-ca.pem
```

The download URL of the intermediate is listed in the APR server
certificate itself. The root certificate path above is the one on Debian
and Ubuntu; on other systems, take the same root from the system trust
store. To use a different location, set `APR_CA_BUNDLE`.

Then download and import the data:

```bash
docker compose exec app php artisan apr:sync
docker compose exec app php -d memory_limit=512M artisan apr:import \
  --file=storage/app/apr/snapshots/YYYYMMDD/companies.json \
  --limit=134374 \
  --write
```

- `apr:sync` prints the path of the saved snapshot; use it for `--file`.
- `apr:import` reads the whole JSON file into memory (about 200 MB),
  hence `-d memory_limit=512M`. It asks for confirmation before writing.

`apr:import` has been run inside Docker; `apr:sync` has so far only been
run outside the container. Details: [APR_DATA_SYNC.md](APR_DATA_SYNC.md).

## Running the tests

```bash
docker compose exec app php artisan test --compact
```

The tests run against `firma_finder_testing`, a separate MySQL database
that the container creates on first start. Because `RefreshDatabase`
drops every table, `tests/TestDatabaseGuard.php` stops the test run
before any migration if the configured database is anything else. The
guard has its own unit tests.

## Key decisions

- **APR data is never changed.** CRM, enrichment, and audit data live in
  separate tables, and company names are stored exactly as in the
  register.
- **MySQL instead of SQLite.** SQLite accepted values that MySQL rejects,
  such as URLs longer than the column; tests now run on MySQL to catch
  this.
- **Collation chosen by measurement.** `utf8mb4_0900_ai_ci` was compared
  with `utf8mb4_unicode_ci` and the Serbian Latin collation on the real
  data; it finds the most results for searches typed without diacritics.
- **Manual confirmation.** Enrichment candidates and future AI findings
  never change the CRM without an explicit user action.

All decisions with their reasons: [DECISIONS.md](DECISIONS.md).

## Project status

Done: the APR catalog, the CRM, manual website checks, manual
enrichment, and a Docker and MySQL environment.

Next: running website checks as queued jobs (Redis and Horizon), CI with
GitHub Actions, a Vue 3 + Inertia frontend, and deployment.

Plan and progress: [ROADMAP.md](ROADMAP.md) ·
Current state: [PROJECT_STATUS.md](PROJECT_STATUS.md)

## More documentation

- [APR_DATA_SYNC.md](APR_DATA_SYNC.md): APR download and import
- [docs/crm.md](docs/crm.md): CRM model and statuses
- [docs/website-audits.md](docs/website-audits.md): website audit model
  and flow
- [docs/company-enrichment.md](docs/company-enrichment.md): enrichment
  model and rules
