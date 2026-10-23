# Firma Finder

Firma Finder is a local Laravel application for finding, reviewing, and
qualifying companies from the Serbian Business Registers Agency (APR)
register. It uses a local APR catalog to organize the sales process for
website development, redesign, and improvement services. The user
interface is written in Serbian, because the tool is built for the Serbian
market. Documentation, code messages and this README are in English; button and label names quoted in the documentation are English translations of the Serbian interface text.

## Technology

- Laravel
- PHP 8.3 or newer (developed on 8.5)
- Local SQLite database
- Blade views
- Local development via `php artisan serve`

## Getting started

### Requirements

- PHP `^8.3`, as required by `composer.json`. The project itself is
  developed and documented with PHP 8.5 (`php8.5`); replace `php` with
  `php8.5` in the commands below if that is how PHP is installed on your
  machine.
- Composer.
- SQLite (`DB_CONNECTION=sqlite` in `.env.example`).
- Node.js and npm, for the Vite and Tailwind CSS frontend build defined in
  `package.json`. No Node.js version is pinned in the repository.

### Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

`.env.example` sets `DB_CONNECTION=sqlite` without a `DB_DATABASE` path, so
the default SQLite file `database/database.sqlite` is used.

To build the frontend assets (this is also part of the `composer setup`
script in `composer.json`):

```bash
npm install
npm run build
```

### Loading the APR company catalog

The database is not included in the repository: `database/.gitignore`
excludes all `*.sqlite*` files. Downloaded APR snapshots are not included
either, because `storage/app` is git-ignored. After `php artisan migrate`,
the `companies` table is empty until you import the APR data yourself.

The data comes from the APR Open Data API. `config/apr.php` reads two
environment variables, neither of which is in `.env.example`:

- `APR_API_URL` — the API endpoint. Default:
  `https://openapi.apr.gov.rs/api/opendata/companies`.
- `APR_CA_BUNDLE` — the CA certificate bundle used to verify the APR TLS
  connection. Default: `storage/app/apr/ca/apr-ca.pem`. Because
  `storage/app` is git-ignored, this file is not in the repository and
  you have to provide it yourself.

`App\Services\AprApiService` sends a plain GET request to `APR_API_URL`
with no token or authentication header, so no API token is needed. If the
CA bundle file does not exist or is not readable, the service throws an
exception (`APR CA bundle not found: <path>`) before making any
request, and `apr:sync` fails.

Loading the catalog takes two steps.

**1. Download a snapshot: `apr:sync`**

```bash
php artisan apr:sync
```

This command takes no options. It downloads the current APR Open Data
snapshot, saves it locally, and prints the snapshot date, the number of
companies, and the path of the saved file. `APR_DATA_SYNC.md` gives the
location as `storage/app/apr/snapshots/{YYYYMMDD}/companies.json`.

Note: `APR_DATA_SYNC.md` shows a `--date` option for `apr:sync`, but the
command does not define one.

**2. Import the snapshot: `apr:import`**

```bash
php artisan apr:import \
  --file=storage/app/apr/snapshots/YYYYMMDD/companies.json \
  --limit=134374 \
  --batch=500 \
  --write
```

Options:

| Option | Default | Meaning |
|---|---|---|
| `--file` | `storage/app/apr-response.json` | Path to the APR JSON snapshot, absolute or relative to the project root. The default is not where `apr:sync` saves snapshots, so pass the path that `apr:sync` printed. |
| `--limit` | `5` | Maximum number of records to process. Must be between 1 and 134374. |
| `--batch` | `500` | Number of records per SQL upsert batch. Must be between 1 and 1000. |
| `--write` | off | Write to the database. Without it, the command only does a dry run and shows a preview of up to 5 records. |

With `--write`, the command asks for confirmation before it writes
anything. Records are upserted by registration number, so running the
import again updates existing companies instead of creating duplicates.
Run it without `--write` first to check the file.

### Running the app

```bash
php artisan serve
```

The app is then available at `http://localhost:8000` (`APP_URL` in
`.env.example`). `composer.json` also defines a `composer dev` script,
which runs `php artisan dev`.

### Running the tests

```bash
php artisan test
```

You can also run `composer test`, which clears the configuration cache
first and then runs `php artisan test`.

## Core architecture

### APR data

The `companies` table stores imported, source APR data:

- registration number;
- company name;
- municipality;
- status;
- legal form;
- business activity;
- date of incorporation;
- APR snapshot date.

APR data is the source catalog and must not be overwritten by CRM or audit
data.

### CRM data

The `company_leads` table stores manually entered CRM data:

- website URL;
- website status;
- phone;
- email;
- lead status;
- lead score;
- notes;
- website check date;
- first contact date.

The relationship between `Company` and `CompanyLead` is strictly 1:1 and is
enforced by a unique index on `company_id`.

### Website audit

The `company_website_audits` table stores the history of technical and
manual website checks.

A single CRM lead can have multiple website audit records. An audit can be:

- `pending`;
- `completed`;
- `failed`.

### Company enrichment

The `company_data_points` table stores candidate and verified company data:

- website;
- email;
- phone;
- address.

Enrichment data is kept separate from both APR data and CRM data. No
enrichment record automatically changes the APR catalog or a CRM lead.

## Currently completed

### APR catalog

- Local APR company catalog.
- Import of 125,054 companies.
- Search by company name and registration number.
- Filter by municipality.
- Filter by activity code.
- Filter by APR status.
- Pagination.
- Company detail page.

### CRM

- Manual creation and updating of CRM leads.
- Website URL, phone, email, and notes.
- Website status.
- Lead status.
- Lead score.
- Automatic first contact date.
- CRM list at `/leads`.
- Filtering and sorting of the CRM list.
- CRM status in the company catalog.
- Website URL validation and normalization.
- Automatic last website check date.

### Website audit

- `company_website_audits` table.
- `CompanyWebsiteAudit` model.
- `CompanyLead hasMany CompanyWebsiteAudit` relationship.
- `CompanyWebsiteAudit belongsTo CompanyLead` relationship.
- Support for multiple audits per lead.
- Cascade deletion of audits when a lead is deleted.
- Audit statuses: `pending`, `completed`, and `failed`.
- Audit history on the company detail page.
- Manual form for creating a website audit.
- Manual technical URL check.
- Checks for HTTP status, HTTPS, redirects, response time, page title, and
  meta description.
- Feature tests for the model, relationships, validation, UI, and technical
  check.

### Company enrichment

- `company_data_points` table.
- `CompanyDataPoint` model.
- `Company hasMany CompanyDataPoint` relationship.
- Cascade deletion of enrichment records when a company is deleted.
- Support for website, email, phone, and address.
- Constants for sources, confidence, license status, and record status.
- Validation rules for enrichment data.
- Manual addition of enrichment candidates through the UI.
- Manual verification, rejection, and flagging for review.
- Explicit application of a verified website URL to the CRM.
- Running a website audit from a verified website candidate.
- Feature tests for the model, relationships, validation, UI, and audit
  flow.

## Company enrichment principle

The enrichment layer is used to collect and manually verify additional
company data.

Each record in `company_data_points` has:

- data type;
- value;
- source;
- confidence;
- license status;
- record status;
- optional note;
- optional source reference.

The record statuses are:

- `candidate` — candidate for review;
- `verified` — manually verified;
- `rejected` — manually rejected;
- `needs_review` — requires further checking.

For now, enrichment data is added manually. Automated sources, such as
OpenStreetMap, Wikidata, or CompanyWall, will come only after the manual
review layer and clear verification rules are in place.

## Applying a verified website candidate

A verified website candidate from the enrichment layer can be applied to
the CRM only through an explicit user action.

When the user applies the candidate to the CRM:

- if no lead exists, a new lead is created;
- if a lead exists, only `website_url`, `website_status`,
  and `website_checked_at` are updated;
- `lead_status`, `lead_score`, phone, email, and notes are not changed;
- APR data is not changed;
- the enrichment record remains `verified`.

## Running an audit from an enrichment candidate

For a verified website candidate belonging to a company that already has a
CRM lead, a website audit can be run.

The audit is created in the existing `company_website_audits` table, and
`requested_url` is taken from the verified enrichment candidate.

An audit can be run only if:

- the data point is of type `website`;
- the data point has the status `verified`;
- the company has a CRM lead.

Running an audit does not change the enrichment record or the CRM data.

## Development principles

- APR data remains the source catalog.
- CRM data is the manual sales layer.
- Enrichment data is a separate layer of candidates and verifications.
- Website audit is a separate layer of technical and visual checks.
- No automated process changes APR data.
- The CRM is changed only through explicit user actions.
- AI analysis comes only after objective technical and visual checks.
- The final decision is always confirmed by the user.

## Documentation

- `APR_DATA_SYNC.md` — APR data import and synchronization rules.
- `crm.md` — CRM model, statuses, and rules.
- `website-audits.md` — website audit model and flow.
- `company-enrichment.md` — enrichment model, statuses, and rules.
- `PROJECT_STATUS.md` — current project status.
- `ROADMAP.md` — further development plan.
- `DECISIONS.md` — architectural and product decisions.
