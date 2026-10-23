# Firma Finder — Project Status

## Last updated

2026-10-14

## Project purpose

Firma Finder is a local Laravel application for finding, reviewing, and
qualifying companies from the Serbian Business Registers Agency (APR)
register.

The primary goal is to organize the sales process for business website
development, redesign, and improvement services.

## Technology

- Laravel
- PHP 8.3 or newer (developed on 8.5)
- SQLite
- Blade
- Local development: `php artisan serve`

## Data and architecture

### APR data

The `companies` table stores imported source APR data:

- registration number;
- company name;
- municipality;
- status;
- legal form;
- activity code;
- date of incorporation;
- APR snapshot date;
- local import date.

APR data is never changed by CRM, enrichment, or audit data.

### CRM data

The `company_leads` table stores manual CRM data:

- `website_url`;
- `website_status`;
- `phone`;
- `email`;
- `lead_status`;
- `lead_score`;
- `notes`;
- `website_checked_at`;
- `contacted_at`.

The `Company → CompanyLead` relationship is strictly 1:1 and is protected
by a unique index on `company_leads.company_id`.

### Website audit data

The `company_website_audits` table stores the history of manual technical
website checks for CRM leads.

The relationship is:

```text
CompanyLead hasMany CompanyWebsiteAudit
CompanyWebsiteAudit belongsTo CompanyLead
```

A single CRM lead can have multiple website audits. Deleting a lead also
deletes all related audits through `cascadeOnDelete`.

APR data in `companies` is never changed by audit data.

### Company enrichment data

The `company_data_points` table stores additional company data that comes
from manual entry or future enrichment sources.

The relationship is:

```text
Company hasMany CompanyDataPoint
CompanyDataPoint belongsTo Company
```

`company_id` is nullable because an enrichment record can be `unmatched`
when it cannot be reliably linked to an APR company.

Deleting an APR company also deletes all of its enrichment records through
`cascadeOnDelete`.

The supported data types are:

- `website`;
- `email`;
- `phone`;
- `address`.

The supported sources are:

- `manual`;
- `companywall`;
- `openstreetmap`;
- `wikidata`;
- `google_places`;
- `apr`.

The supported record statuses are:

- `candidate`;
- `verified`;
- `rejected`;
- `unmatched`;
- `needs_review`.

APR data is never changed by enrichment data.

## Currently completed

### APR catalog

- An APR catalog of 134,374 companies (snapshot `2026-09-30`) has been
  imported locally.
- The company list is available at `/companies`.
- Search and filters are available by:
    - company name;
    - registration number;
    - municipality;
    - activity code;
    - APR status.
- The list is paginated at 50 companies per page.
- The company detail page is available at:
  `/companies/{registration_number}`.

### CRM

- The `company_leads` table has been created.
- The `CompanyLead` model has been created.
- Eloquent relationships have been added:
    - `Company::lead()` (`hasOne`);
    - `CompanyLead::company()` (`belongsTo`).
- Constants have been added for:
    - website status;
    - lead status.
- The company detail page has a CRM form for:
    - website URL;
    - website status;
    - phone;
    - email;
    - lead status;
    - lead score from 0 to 100;
    - notes.
- There are routes for creating and updating a CRM record:
    - `POST /companies/{registration_number}/lead`;
    - `PUT /companies/{registration_number}/lead`.
- The CRM list is available at `/leads`.
- The CRM list supports:
    - search by name and registration number;
    - filtering by lead status;
    - filtering by website status;
    - sorting by score or by last update.
- The main catalog at `/companies` shows a CRM column.
- The CRM status links to the company detail page for editing.
- The CRM filter in the company catalog supports:
    - all companies;
    - only companies with a lead;
    - only companies without a lead.
- An invalid `lead_filter` value is treated as `all`.
- The website URL is validated and normalized:
    - a URL without a protocol gets `https://`;
    - an existing `http://` or `https://` protocol is left unchanged;
    - a URL without a valid domain is rejected.
- `CompanyLeadObserver` sets `website_checked_at` automatically when a
  lead is created and when `website_status` changes.
  `applyWebsiteDataPointToLead` always sets it to `now()`.
- The company's CRM record shows the date of the last website check.
- There are feature tests for the CRM filter, CRUD, the observer, URL
  validation, and `website_checked_at`.

### First contact date

`CompanyLeadObserver` automatically sets `contacted_at` when a lead first
gets the status `contacted`.

The first contact date is kept when the status later changes, for example
from `contacted` to `qualified`.

### Website audit foundation

- The `company_website_audits` table has been created.
- The `CompanyWebsiteAudit` model has been created.
- Eloquent relationships have been added:
    - `CompanyLead::websiteAudits()` (`hasMany`);
    - `CompanyWebsiteAudit::companyLead()` (`belongsTo`).
- A single CRM lead can have multiple website audits.
- Audit records are deleted together with the lead through
  `cascadeOnDelete`.
- The supported audit statuses are:
    - `pending`;
    - `completed`;
    - `failed`.
- The company detail page has a **Website audit history** section.
- The user can manually add a website audit through a form on the company
  detail page.
- An audit stores:
    - the URL to check;
    - the final URL;
    - the HTTP status;
    - website reachability;
    - HTTPS status;
    - a redirect flag (`redirect_count`, 0 or 1);
    - the response time;
    - the page title;
    - the meta description;
    - notes;
    - the error, if there was one;
    - the start and end date of the check.
- Website audits are created manually through the form on the company
  detail page, or from a verified enrichment candidate through the
  `data-points.run-audit` route.
- `WebsiteAuditService` has been added for running a technical website
  check manually.
- The technical check stores the HTTP status, final URL, HTTPS status,
  redirect flag, response time, page title, and meta description.
- Note: `redirect_count` is 1 whenever the final URL differs from the
  requested URL, so a difference such as a trailing slash also gives 1.
- An audit gets the status `failed` only when the connection fails or an
  exception is thrown; the reason for the error is stored.
- A website that answers with an HTTP error gets the status `completed`
  with `is_reachable = false`.
- The company detail page has a **Run technical check** button for each
  existing audit.
- There are feature tests for:
    - creating an audit for a lead;
    - the lead → audits relationship;
    - multiple audits for the same lead;
    - cascade deletion of audits;
    - audit status validation;
    - manual creation of an audit through the UI;
    - rejection of an invalid URL and status;
    - preventing audit creation without a CRM lead;
    - a successful technical URL check;
    - a failed technical check when the website is unreachable;
    - a technical check when no URL is defined;
    - running the technical check through the UI.

### Company enrichment

- The `company_data_points` table has been created.
- The `CompanyDataPoint` model has been created.
- The `Company hasMany CompanyDataPoint` relationship has been added.
- `company_id` is nullable and uses `cascadeOnDelete`.
- The supported data types are `website`, `email`, `phone`, and `address`.
- The supported sources are `manual`, `companywall`, `openstreetmap`,
  `wikidata`, `google_places`, and `apr`.
- The supported confidence levels are `high`, `medium`, and `low`.
- The supported license statuses are `allowed`, `restricted`, `forbidden`,
  and `unknown`.
- The supported record statuses are `candidate`, `verified`, `rejected`,
  `unmatched`, and `needs_review`.
- The company detail page has an **Enrichment candidates** section.
- The user can manually add a website, email, phone number, or address as
  a candidate.
- A website URL is normalized and validated before it is saved.
- An email address is validated before it is saved.
- The user can manually verify or reject a candidate, or flag it for
  review.
- A verified website candidate can be explicitly applied to the CRM.
- Applying it to the CRM creates a new lead if none exists, or updates the
  existing lead.
- Applying it to the CRM changes only `website_url`, `website_status`,
  and `website_checked_at`.
- `lead_status`, `lead_score`, phone, email, and notes are not changed
  automatically.
- For a verified website candidate of a company that has a CRM lead, a
  website audit can be run.
- The audit uses the existing `WebsiteAuditService` and is stored in the
  existing `company_website_audits` table.
- CompanyWall, OSM, Wikidata, and Google Places are not integrated yet.
- There is no automatic verification of company identity and no automatic
  URL verification.

## Current test records

Local testing uses a company record from the APR register. The company's
identity has been replaced with a neutral example in this document. The
CRM status, score, and contact date listed here are used only for testing
the application and do not represent actual contact or a business
relationship.

- Company: Example company
- Registration number: `[placeholder]`
- Website URL: `https://example.com`
- Website status: `active`
- Lead status: `qualified`
- Lead score: `10`
- First contact date: `[test date]`

This lead has manual test website audit records used to check the audit
history display, the UI form, and the technical website check.

There are also manual enrichment test records:

- `https://example.com` as a `verified` website candidate;
- `https://example.com` as a `candidate` website candidate;
- `https://example.org` as a `rejected` website candidate.

## Current focus

The company enrichment foundation and the manual enrichment flow are
complete.

The next step is manual confirmation of website audit findings.

## Next task

Add a manual review of website audit findings. `company_website_audits`
already has a `reviewed_at` column; the task is to add:

- `review_status`;
- `reviewed_by`;
- manual actions for approving, rejecting, and flagging for review, which
  also set the existing `reviewed_at`.

After that comes OSM enrichment for a single company.

## Plan after the enrichment review

1. Add OSM enrichment for a single company.
2. Define company identity rules based on MB/PIB.
3. Define identity match scoring.
4. Add Wikidata enrichment as a supplementary source.
5. Integrate CompanyWall only after written confirmation of the terms.
6. Implement automatic URL verification only after identity is reliable.
7. Add Lighthouse results and desktop/mobile screenshots.
8. Add AI analysis based on technical evidence and screenshots.
9. Add a draft offer with mandatory manual review and approval.
10. Only after quality is confirmed, add queues, rate limiting, and batch
    processing.

## Current limitations

- URL, phone, and email are entered manually.
- Enrichment candidates are currently entered only manually.
- Website audits are created manually through the form on the company
  detail page, or from a verified enrichment candidate through the
  `data-points.run-audit` route.
- The technical URL check is run manually for an individual audit.
- `CompanyLeadObserver` sets `website_checked_at` automatically when a
  lead is created and when `website_status` changes.
- Known inconsistency: `applyWebsiteDataPointToLead` always sets
  `website_checked_at` to `now()`, even when `website_status` does not
  change.
- There is no automatic or scheduled repetition of the technical check.
- There are no Lighthouse results and no visual audit.
- There is no integration with OSM, Wikidata, CompanyWall, or Google
  Places.
- There is no automatic verification of company identity.
- There is no automatic verification of enrichment URLs.
- A verified website is applied to the CRM only through an explicit user
  action.
- AI analysis, Lighthouse results, and screenshots are not implemented.
- There is no AI API integration.
- There is no automatic sending of messages, emails, or offers.
- There is no authentication and no multi-user support.