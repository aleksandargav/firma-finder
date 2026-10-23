# Firma Finder — Company Enrichment

## Purpose

The company enrichment module is used to find, check, and verify
additional data about companies from the APR register, primarily the
website URL.

The APR JSON source currently in use does not contain a website URL. It
has been confirmed that all 134,374 records contain only the following
fields:

- `PoslovnoIme`;
- `SifraOpstine`;
- `NazivOpstine`;
- `NazivStatus`;
- `DatumOsnivanja`;
- `NazivPravneForme`;
- `SifraDelatnosti`.

For this reason, websites and other contact details are collected through
a separate enrichment layer rather than by changing APR data.

---

## Basic rules

1. APR, CRM, enrichment, and website audit data remain separate.
2. APR data in `companies` is never changed by enrichment data.
3. Enrichment data must not automatically overwrite CRM decisions.
4. A website URL from the enrichment layer reaches the CRM only as a
   verified value. The CRM form itself accepts any valid URL entered by
   the user.
5. Every enrichment value must have a known source.
6. Every source must have a checked license/legal status of use.
7. The system must not bypass CAPTCHAs, logins, rate limits, robots
   protections, or other access control measures.
8. Bulk data collection is done only through permitted sources, contracted
   APIs, or official exports.
9. AI and automation may suggest a value, but the user always confirms
   the final decision whenever there is any doubt.

---

## The core problem

The APR register provides a reliable company identity, but not the
company's website.

CompanyWall and similar services may provide a website, but the following
needs to be confirmed:

- whether their API or export includes the website URL;
- how a company is matched by registration number (MB) or tax ID (PIB);
- what the prices, quotas, and rate limits are;
- whether storing the data in an internal CRM is permitted;
- whether scraping is permitted, and under what conditions.

OpenStreetMap and Wikidata are free supplementary sources, but they do not
know the APR registration number and cannot reliably confirm a company's
identity on their own.

---

## Architecture

Target architecture:

```text
APR Open Data API
   ↓
apr:sync
   ↓
Local APR snapshot
   ↓
apr:import
   ↓
companies
   ↓
Enrichment sources
   ├── Manual entry
   ├── CompanyWall
   ├── OpenStreetMap
   ├── Wikidata
   ├── Google Places
   └── Future regional sources
   ↓
company_data_points
   ↓
Automatic checking and ranking of candidates
   ↓
Manual review of disputed cases
   ↓
Verified website URL
   ↓
company_leads.website_url
   ↓
Website audit / Lighthouse / screenshot
```

Not implemented yet: the CompanyWall, OpenStreetMap, Wikidata, Google
Places, and regional sources; automatic checking and ranking of
candidates; Lighthouse; and screenshots. Today, candidates come only from
manual entry and are reviewed manually.

The APR Open Data API is currently implemented as the company identity
source. It refreshes the local APR catalog but does not add a website or
contact details.

---

## Data sources

| Source | Internal value | What it can provide | Status |
|---|---|---|---|
| Manual entry | `manual` | Website, phone, email, address, notes | Implemented |
| CompanyWall | `companywall` | Website, contacts, MB/PIB | Awaiting confirmation of terms |
| OpenStreetMap | `openstreetmap` | Website, phone, email, location | Planned |
| Wikidata | `wikidata` | Official website for well-known entities | Planned |
| Google Places | `google_places` | Website, phone, address, category | Optional, paid |
| APR Open Data | `apr` | MB, name, status, activity, snapshot date | Implemented as the identity source |

The APR Open Data API is connected through `AprApiService`, the `apr:sync`
command, and the existing `apr:import`. The local APR catalog is updated
by upserting on the company registration number.

If CompanyWall confirms access and the website data, it should be the
primary automated source, because it allows matching by MB/PIB.

OpenStreetMap is a free source for local businesses, but coverage depends
on whether the business is mapped in OSM.

Wikidata uses property `P856 — official website` and is useful mainly for
larger or well-known companies.

---

## Company identity

A company's identity is not determined by its name alone.

Planned: when matching against external sources is built, the matching
priority will be:

1. the MB from the enrichment source matches the APR
   `registration_number`;
2. the PIB from the enrichment source matches the APR PIB;
3. both MB and PIB match;
4. name + municipality/city serve only as an additional signal;
5. the name alone is never enough for automatic verification.

Note: the `companies` table does not currently store a PIB, so PIB
matching also requires adding the PIB to the APR catalog.

Planned: if an enrichment source returns an MB or PIB that does not exist
in the APR database, the record will be stored as `unmatched` and not
linked to a CRM lead. The `unmatched` status exists, but nothing sets it
yet.

APR sync currently uses the registration number as the stable key for
upserting local APR records.

---

## Data model

Table:

```text
company_data_points
```

Relationship:

```text
Company hasMany CompanyDataPoint
CompanyDataPoint belongsTo Company
```

Fields:

| Field | Type | Purpose |
|---|---|---|
| `id` | primary key | Record identifier |
| `company_id` | foreign key, nullable | The APR company, if a match was found |
| `external_identifier` | string, nullable | MB, PIB, Place ID, or another external ID |
| `identifier_type` | string, nullable | `registration_number`, `pib`, `place_id`, `name_address` |
| `field` | string | `website`, `email`, `phone`, `address` |
| `value` | text | The value found |
| `source` | string | Data source |
| `source_url` | string, nullable | URL of the source or the company profile |
| `confidence` | string | `high`, `medium`, `low` |
| `identity_match_score` | integer | 0–100 |
| `data_license_status` | string | License status of the source |
| `status` | string | Verification status of the record |
| `retrieved_at` | timestamp, nullable | When the value was retrieved |
| `verified_at` | timestamp, nullable | When the value was verified |
| `verified_by` | string, nullable | `user` (manual verification); `system` is planned for automatic verification |
| `notes` | text, nullable | Internal notes |
| `created_at` | timestamp | Creation date |
| `updated_at` | timestamp | Date of the last change |

`company_id` uses `cascadeOnDelete`. Deleting an APR company also deletes
its enrichment records.

---

## Allowed values

### Data type

- `website`
- `email`
- `phone`
- `address`

### Source

- `manual`
- `companywall`
- `openstreetmap`
- `wikidata`
- `google_places`
- `apr`

### Confidence

- `high`
- `medium`
- `low`

### License status

- `allowed`
- `restricted`
- `forbidden`
- `unknown`

### Enrichment record status

- `candidate`
- `verified`
- `rejected`
- `unmatched`
- `needs_review`

### Identifier type

- `registration_number`
- `pib`
- `place_id`
- `name_address`

---

## Enrichment record statuses

| Internal value | Display | Meaning |
|---|---|---|
| `candidate` | Candidate | The value was found but has not been verified |
| `verified` | Verified | The value has been checked and can be used |
| `rejected` | Rejected | The value has been rejected |
| `unmatched` | Unmatched | The source is not linked to a known APR company |
| `needs_review` | Needs_review | There is a conflict or the signal is not strong enough |

The status badge in the UI shows the internal value with its first letter
capitalized; there are no separate display labels.

---

## Confidence levels

| Level | Meaning | Example |
|---|---|---|
| `high` | Strong identity and a reliable source | CompanyWall MB/PIB match |
| `medium` | Partial match | OSM name + municipality |
| `low` | Weak or uncertain signal | Only a similar name |

`confidence` does not replace the license status. A value can have high
confidence but still not be permitted for use if the source does not
allow it.

---

## Source license status

| Value | Meaning |
|---|---|
| `allowed` | The value may be stored and used in the internal CRM |
| `restricted` | The value is used with restrictions, e.g. attribution or a contract |
| `forbidden` | The value must not be used |
| `unknown` | The terms have not been confirmed yet |

Until CompanyWall confirms the terms, its source remains `unknown` or
`restricted`, depending on their answer.

---

## Manual enrichment flow

Manual entry and review of enrichment candidates are currently
implemented.

```text
The user chooses a data type
→ enters the value, confidence, and license status
→ the system validates and normalizes the value
→ a candidate is created
→ the user verifies it, rejects it, or flags it for review
```

The supported data types are:

- `website`;
- `email`;
- `phone`;
- `address`.

A website URL is normalized by adding `https://` when needed. A URL
without a valid domain is rejected.

An email address is validated before it is saved.

---

## Applying a verified website URL to the CRM

A verified website candidate can be applied to the CRM only through an
explicit user action.

```text
Website data point
→ status verified
→ the user clicks "Apply to CRM"
→ company_leads.website_url is updated
```

If the company has no CRM lead, a new lead is created with:

- `website_url` = the verified URL;
- `website_status = active`;
- `lead_status = new`;
- `lead_score = 0`;
- `website_checked_at = now()`.

If the company already has a CRM lead, only the following are changed:

- `website_url`;
- `website_status`;
- `website_checked_at`.

The following are not changed:

- `lead_status`;
- `lead_score`;
- `phone`;
- `email`;
- `notes`.

APR data is not changed.

---

## Website audit from an enrichment candidate

For a verified website candidate of a company that already has a CRM
lead, a website audit can be run.

```text
Verified website data point
→ the user clicks "Run website audit"
→ a CompanyWebsiteAudit is created
→ WebsiteAuditService checks the URL
→ the result is stored in company_website_audits
```

An audit can be run only if:

- the data point is of type `website`;
- the data point has the status `verified`;
- the company has a CRM lead.

The audit uses the existing `WebsiteAuditService` and stores:

- the URL to check;
- the final URL;
- the HTTP status;
- reachability;
- HTTPS status;
- a redirect flag (`redirect_count`, 0 or 1);
- the response time;
- the page title;
- the meta description;
- the start and end date;
- the error, if there was one.

Note: `redirect_count` is 1 whenever the final URL differs from the
requested URL, so a difference such as a trailing slash also gives 1.

Running the audit does not change the enrichment record.

---

## Automatic verification

Planned: when automatic verification is built, a website URL will be able
to become `verified` automatically only if all of the following
conditions are met:

1. the source has a permitted license status;
2. the MB and/or PIB matches an APR company;
3. the URL is valid;
4. the URL is reachable and returns the expected HTTP response;
5. the URL does not lead to a parked domain or an obviously unrelated
   website;
6. the company name appears in the domain, page title, or content;
7. there is no conflict with another strong source.

If any condition is missing, the record remains `candidate` or moves to
`needs_review`.

Automatic verification is not implemented yet. Verification is currently
manual only, through `verify()`.

---

## Example of a successful flow

The following examples are hypothetical and serve only to explain possible
flows. They do not represent actual results from CompanyWall, OSM, or
other integrations. Automatic verification is not implemented yet.

```text
APR company:
- Test Company DOO (fictional name)
- MB: [placeholder]

Hypothetical source:
- MB: [placeholder]
- Website: https://example.com

Check:
- MB match: yes
- URL works: yes
- HTTPS: yes
- Company name in content: yes
- The source's terms of use must be checked before integrating and using
  the data.

Hypothetical result:
- status: verified
- CRM website_url: https://example.com
- CRM website_status: active
```

---

## Example of a disputed case

```text
Hypothetical source A:
- MB: [placeholder]
- Website: https://example.com

Hypothetical source B:
- Name: Test Company
- Website: https://example.org

Hypothetical result:
- status: needs_review
- reason: the two sources return different website URLs
```

The user then manually chooses the correct URL or rejects both
candidates.

---

## CompanyWall — current status

CompanyWall has been contacted about possible access to their data.

Written confirmation is needed for:

1. whether their Web API or export includes the website URL;
2. how a company is matched by MB/PIB;
3. an example JSON/CSV response;
4. prices, plans, quotas, and rate limits;
5. the terms for storing the data in an internal CRM;
6. whether scraping is permitted, and under what conditions.

Until these terms are confirmed, CompanyWall is not integrated and is not
used for automated data collection.

---

## APR Open Data — current status

The APR Open Data API is implemented as the company identity source.

Implemented:

- `AprApiService` for downloading the APR snapshot;
- `apr:sync` for downloading the complete snapshot;
- local storage of the snapshot in `storage/app/apr/snapshots/`;
- `apr:import` for writing or updating companies in `companies`;
- upsert by registration number;
- storage of the APR snapshot date in `source_snapshot_date`;
- storage of the local import date in `imported_at`.

APR sync does not add a website URL, phone, email, or any enrichment data.
Its role is to keep the local APR company identity up to date.

---

## Regional plan

The first version of the enrichment system is being developed for Serbia.

Country-specific adapters will be added later:

| Country | Source / access | Status |
|---|---|---|
| Serbia | APR Open Data API + enrichment sources | APR identity source implemented |
| Slovenia | AJPES / PRS | Later |
| Bulgaria | CompanyBook.BG | Later |
| Montenegro | CompanyWall or a commercial source | Later |
| Bosnia and Herzegovina | BIFIDEX / local registers | Later |
| Albania | BIFIDEX / local registers | Later |
| North Macedonia | BIFIDEX / local registers | Later |

Each country should have its own adapter, but all adapters return the same
normalized data format.

---

## What is not done

- CompanyWall is not scraped in bulk without written permission.
- Domains are not guessed from the company name, e.g. `companyname.rs`.
- A URL is not inserted into the CRM automatically without a sufficiently
  strong match.
- APR data is not changed by enrichment data.
- Sources that forbid automated collection are not used.
- There is no bulk crawling of all companies in the first version.
- No messages, emails, or offers are sent without manual approval.
- APR sync does not delete companies that disappear from a newer snapshot.

---

## Implementation plan

1. [x] Create the `company_data_points` table and the `CompanyDataPoint`
   model.
2. [x] Add the `Company hasMany CompanyDataPoint` relationship.
3. [x] Add constants for sources, statuses, confidence, and license
   status.
4. [x] Allow manual entry and verification of website candidates.
5. [ ] Add a manual review of website audit findings.
6. [ ] Add OSM enrichment for a single company.
7. [ ] Add Wikidata enrichment as a supplementary source.
8. [ ] Integrate CompanyWall after the terms are confirmed.
9. [ ] Add auto-verification rules.
10. [ ] Add regional adapters.
11. [ ] Develop the Lighthouse and screenshot module only once there are
    reliably verified URLs.