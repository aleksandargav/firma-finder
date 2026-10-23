# Firma Finder — Architecture Decisions

This document records important architectural decisions and the reasons
behind them.

---

## 2026-10-11 — APR and CRM data are strictly separated

### Decision

Source APR data is stored in the `companies` table.

Manual CRM data is stored in the `company_leads` table.

### Reason

APR data is an imported snapshot of the official register and must not be
mixed with manual notes, contacts, URLs, and sales statuses.

CRM data changes often, while the APR import may be repeated with a newer
data snapshot.

### Consequences

- The APR import must not overwrite CRM data.
- CRM forms and future audit data do not change the `companies` table.
- Future website checks are stored in a separate table, not in `companies`.

---

## 2026-10-11 — Company and CompanyLead have a 1:1 relationship

### Decision

A company can have at most one CRM lead.

The relationship is defined as:

```text
Company hasOne CompanyLead
CompanyLead belongsTo Company
```

The database enforces the rule with a unique index:

```text
UNIQUE(company_leads.company_id)
```

### Reason

The application currently keeps one CRM record per company.

The website check history, and later the communication history, are not the
same thing as the current CRM record and should not be added as additional
lead records.

### Consequences

- An existing lead is updated rather than a second row being created.
- Future website audits get a separate table with a 1:N relationship to the
  company or the lead.
- Future communication can also get its own history table.

---

## 2026-10-11 — The first contact date is historical data

### Decision

The `contacted_at` field is filled in automatically when a lead first has
the status `contacted`.

When the status later changes to `qualified`, `rejected`, or `converted`,
the first contact date is neither cleared nor changed.

### Reason

The first contact date is a fact from the history of the relationship with
the company, not the current status of the lead.

### Consequences

`CompanyLeadObserver` sets the date only when:

```text
lead_status = contacted
and
contacted_at is empty
```

---

## 2026-10-11 — Statuses are stored as stable internal keys

### Decision

The database stores stable English values such as:

```text
unknown
active
inactive
new
in_progress
contacted
qualified
rejected
converted
```

The interface shows display labels defined in model constants.

### Reason

The internal values stay stable if the interface language or the wording
of a label changes later.

### Consequences

- Validation uses `Rule::in(array_keys(...))`.
- Blade views use the constants for the user-facing status label.
- Filters and a future API do not depend on the displayed text.

---

## 2026-10-11 — Website audit is a separate historical entity

### Decision

The future website audit will not be just a few new fields on
`company_leads`.

It will use a separate table, with the planned name
`company_website_audits`.

### Reason

A website can change its URL, technical state, or design. The history of
checks, technical results, screenshots, AI findings, and manual reviews
needs to be kept without overwriting older findings.

### Consequences

The planned relationship will be roughly:

```text
CompanyLead hasMany CompanyWebsiteAudit
CompanyWebsiteAudit belongsTo CompanyLead
```

The exact model is confirmed before implementation.

---

## 2026-10-11 — AI is an assistant, not an automated sales agent

### Decision

AI may analyze technical findings and screenshots, suggest a type of
service, and generate a draft message or offer.

AI will not send emails, messages, or offers automatically.

### Reason

AI assessments can be incomplete or wrong. Every message sent externally
must be based on real evidence and manually approved.

### Consequences

The future workflow will be:

```text
Technical audit + screenshots
→ Structured AI assessment
→ Manual review of findings
→ Draft offer
→ Manual approval
→ Sending
```

---

## 2026-10-14 — Website URLs are validated and normalized

### Decision

A website URL entered by the user is validated before it is saved.

If the URL has no protocol, `https://` is added automatically.

If the URL already starts with `http://` or `https://`, the protocol is
left unchanged.

A URL without a valid domain is not saved.

### Reason

Normalization means that a URL entered without a protocol, such as
`example.com`, is stored as `https://example.com` instead of in a
different form.

Keeping the existing protocol matters because some websites may still not
be available over HTTPS.

### Consequences

- The URL is stored in normalized form.
- Validation checks that the value is a valid URL.
- An invalid URL is rejected before a lead is created or updated.

Known limitation: normalization only adds `https://` when the protocol is
missing and does not remove `www.`, so `https://www.example.com` and
`https://example.com` are stored as different values.

---

## 2026-10-14 — The website check date is updated automatically

### Decision

The `website_checked_at` field is set automatically when a lead is created
or when `website_status` changes.

### Reason

The check date is the moment when the user confirmed or changed the
current state of the website, not the moment the CRM record was created.

### Consequences

- `website_checked_at` is not entered manually by the user.
- `CompanyLeadObserver` updates the date only when `website_status`
  changes.
- `applyWebsiteDataPointToLead` always sets the date to `now()` when a
  verified website candidate is applied to the CRM.
- Showing the date of the last check gives context for the manual website
  assessment.

Known inconsistency: applying a verified website candidate updates
`website_checked_at` even when `website_status` does not change.

---

## 2026-10-14 — Website enrichment is separate from APR and CRM data

### Decision

The APR JSON source does not contain a website URL. Websites and other
contact details are collected through a separate enrichment layer.

Enrichment data is stored in the `company_data_points` table.

Each record has:

- data source;
- identity match;
- license status;
- confidence;
- verification status.

### Reason

APR provides a reliable company identity, but not a website. CompanyWall,
OSM, Wikidata, and future sources may return different candidates, so it
is necessary to know where a value comes from, whether its use is
permitted, and whether it has been verified.

### Consequences

- APR data is not changed by enrichment data.
- CompanyWall, OSM, Wikidata, and future sources use the same model.
- Planned: when automatic verification is built, it will be allowed only
  for strong matches and permitted sources. Verification is currently
  manual only, through `verify()`.
- Disputed values remain candidates for manual review.
- Planned: Lighthouse and screenshots will run only against a verified
  website URL. Neither is implemented yet, and the manual audit form
  currently accepts any URL.

---

## 2026-10-14 — CompanyWall is not integrated without written confirmation of terms

### Decision

CompanyWall is not used for automated data collection until the following
are confirmed in writing:

- whether their Web API or export includes the website URL;
- how a company is matched by registration number (MB) or tax ID (PIB);
- the JSON/CSV response format;
- prices, plans, quotas, and rate limits;
- the terms for storing the data in an internal CRM;
- the terms of, and permission for, scraping, if it is used.

### Reason

CompanyWall is a commercial credit-rating platform. Its terms of use must
be reviewed, and automated data collection requires their written
permission. No integration is built until that is confirmed.

### Consequences

- No in-house scraper is built without written permission.
- CompanyWall data, once available, enters as the `companywall` source.
- A website URL is first stored as a candidate.
- Planned: when automatic verification is built, it will be allowed only
  if the license status is permitted and the MB/PIB reliably matches an
  APR company. Verification is currently manual only, through `verify()`.

---

## 2026-10-14 — APR sync uses a snapshot and upsert

### Decision

APR data is downloaded through the APR Open Data API into a local JSON
snapshot and then imported into the `companies` table with `apr:import`.

The import upserts by company registration number.

### Reason

Storing the snapshot separately makes the import repeatable and makes
debugging and testing possible without calling the API again.

Upserting by registration number prevents duplicate companies when the
same snapshot is imported again.

### Consequences

- `apr:sync` does not write directly to the database.
- `apr:import` is the only step that changes the `companies` table.
- The APR import updates only source APR data.
- CRM, enrichment, and audit data remain untouched.
- `source_snapshot_date` clearly marks the APR snapshot of each record.
- `imported_at` records the time of the last local import.

---

## 2026-10-14 — APR sync does not delete companies automatically

### Decision

APR sync does not delete companies that are missing from the new API
snapshot.

### Reason

A company disappearing from one snapshot does not automatically mean that
the local CRM record, work history, or audit data are no longer relevant.

### Consequences

- The local number of companies may be higher than the number of records
  in the new snapshot.
- Any marking of inactive or removed companies will be a separate decision
  and a separate migration.
- CRM data is not deleted as a result of APR sync.

---

## 2026-10-14 — A verified enrichment URL is applied to the CRM manually

### Decision

A verified website candidate from `company_data_points` can be applied to
the CRM only through an explicit user action.

### Reason

An enrichment source can offer a URL, but it cannot decide on its own
whether that URL should replace existing CRM data.

### Consequences

- Applying it updates only `website_url`, `website_status`,
  and `website_checked_at`.
- `lead_status`, `lead_score`, phone, email, and notes are not changed.
- APR data is not changed.
- The enrichment record remains `verified`.

---

## 2026-10-14 — A website audit from an enrichment candidate requires a CRM lead

### Decision

A website audit from a verified enrichment candidate can be run only if
the company already has a CRM lead.

### Reason

The audit belongs to the CRM lead and the existing website check flow.

### Consequences

- The audit is created in the existing `company_website_audits` table.
- `requested_url` is taken from the verified enrichment candidate.
- Running the audit does not change the enrichment record.
- Automatic verification of company identity is not implemented yet.

---

## Rule for future schema changes

Once a Laravel migration has been run, the existing migration file is not
changed.

Every change to an existing table is made with a new migration.

Reason: local and future databases must be able to go through the entire
schema history consistently.