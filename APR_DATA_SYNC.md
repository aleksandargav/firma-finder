# Firma Finder — APR Data Sync

## Purpose

The APR Data Sync module downloads the current snapshot of companies from
the APR Open Data API and synchronizes it locally with the `companies`
table.

The goal is to keep the local APR catalog up to date without duplicating
records and without affecting CRM, enrichment, or website audit data.

---

## Current state

The local MySQL database contains 134,374 companies from the APR snapshot
`2026-09-30`.

A full sync has been completed successfully:

- processed: 134,374 records;
- skipped: 0;
- successfully written or updated: 134,374 records;
- total number of companies after the sync: 134,374;
- number of records with the snapshot date `2026-09-30`: 134,374.

After the move from SQLite to MySQL (2026-10-29), the same snapshot was
imported again into the empty MySQL database: 134,374 processed,
0 skipped, 134,374 written.

The test suite passes on MySQL: 72 tests and 186 assertions.

---

## Architecture

```text
APR Open Data API
   ↓
AprApiService
   ↓
storage/app/apr/snapshots/{YYYYMMDD}/companies.json
   ↓
apr:import
   ↓
companies table
```

The sync runs in two phases:

1. `apr:sync` downloads the complete JSON snapshot through the API.
2. `apr:import` reads the snapshot and writes or updates companies in the
   local database.

This flow allows the API download and the database write to be tested and
repeated separately.

---

## Implemented components

| Component | Path | Role |
|---|---|---|
| `AprApiService` | `app/Services/AprApiService.php` | Communication with the APR Open Data API |
| `SyncAprCompanies` | `app/Console/Commands/SyncAprCompanies.php` | Artisan command `apr:sync` |
| `ImportAprCompanies` | `app/Console/Commands/ImportAprCompanies.php` | Artisan command `apr:import` |
| APR configuration | `config/apr.php` | API URL (`APR_API_URL`), CA bundle (`APR_CA_BUNDLE`), and snapshot directory |

`config/apr.php` holds no token, and `AprApiService` sends no token or
authentication header. It reads:

- `APR_API_URL`, with the default public URL
  `https://openapi.apr.gov.rs/api/opendata/companies`;
- `APR_CA_BUNDLE`, with the default `storage/app/apr/ca/apr-ca.pem`;
- the snapshot directory, `storage/app/apr/snapshots`.

---

## The `apr:sync` command

All commands run inside the `app` container, because the database host
`mysql` is only reachable there.

The command downloads the current complete APR snapshot. It takes no
options.

```bash
docker compose exec app php artisan apr:sync
```

The snapshot date is taken from the `DatumPreseka` field of the API
response. For the snapshot `2026-09-30`, the result is saved to:

```text
storage/app/apr/snapshots/20260930/companies.json
```

The snapshot is saved locally before anything is written to the database.

---

## The `apr:import` command

The command imports a local JSON snapshot into the `companies` table.

Example for a small test:

```bash
docker compose exec app php -d memory_limit=512M artisan apr:import \
  --file=storage/app/apr/snapshots/20260930/companies.json \
  --limit=5 \
  --batch=500 \
  --write
```

Example for a full sync:

```bash
docker compose exec app php -d memory_limit=512M artisan apr:import \
  --file=storage/app/apr/snapshots/20260930/companies.json \
  --limit=134374 \
  --batch=500 \
  --write
```

Without `--write`, the command runs in dry-run mode and does not write any
data to the database.

`apr:import` decodes the whole JSON snapshot at once, which peaks at
about 200 MB, even with a small `--limit`. Running it with
`-d memory_limit=512M` is recommended. Streaming the JSON is planned
(see ROADMAP.md, portfolio plan step 3).

With `--write`, the command asks for confirmation before writing. With
`--no-interaction`, the answer defaults to "no" and nothing is written.

---

## Writing data

The import uses upsert logic based on the company registration number.

Rules:

1. If no company with the given registration number exists, a new record
   is created.
2. If the company exists, its APR fields are updated with the new values.
3. CRM, enrichment, and audit data are never changed by the APR import.
4. `source_snapshot_date` stores the APR snapshot date.
5. `imported_at` records the time of the last local import.
6. The batch size is configurable through `--batch`.

---

## Verification after the sync

After a full import, check:

```bash
docker compose exec app php artisan tinker --execute="dump(App\Models\Company::count()); dump(App\Models\Company::where('source_snapshot_date', '2026-09-30')->count());"
```

Expected result for the snapshot `2026-09-30`:

```text
134374
134374
```

Example check for a single company:

Before running it, replace `ENTER_REGISTRATION_NUMBER` with a valid
registration number of the company you want to check.

```bash
docker compose exec app php artisan tinker --execute="dump(App\Models\Company::where('registration_number', 'ENTER_REGISTRATION_NUMBER')->first(['registration_number','business_name','source_snapshot_date','imported_at','updated_at'])?->toArray());"
```

---

## Tested flow

1. A small test import of 5 records was run with `--write`.
2. It was confirmed that the total number of companies stayed the same.
3. It was confirmed that an existing company gets a new
   `source_snapshot_date`, `imported_at`, and `updated_at`.
4. A full sync of 134,374 records was run.
5. It was confirmed that there are no duplicates and that all records
   belong to the same APR snapshot.
6. The complete test suite was run; all tests pass.

---

## Limitations

- The importer loads the whole JSON snapshot into memory.
- `apr:sync` has not yet been run inside the Docker container.
- The sync currently runs as a manual Artisan command.
- There is no scheduled automatic refresh.
- There is no incremental sync based on changes; the complete snapshot is
  used.
- There are no notifications when a sync fails.
- Companies that no longer exist in a new APR snapshot are not deleted
  automatically.
- There is no production deployment; the application is currently local.