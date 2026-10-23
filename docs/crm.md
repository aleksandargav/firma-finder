# CRM

The CRM layer stores manual sales data about companies. It is separate
from the source APR data and from the enrichment data.

## Model

CRM data is stored in the `company_leads` table.

Each record belongs to exactly one company from the `companies` table.

The relationship is strictly 1:1:

```txt
Company 1 — 1 CompanyLead
```

The constraint is enforced by a unique index on the `company_id` column.

## Fields

| Field | Purpose |
|---|---|
| `company_id` | Company from the APR catalog |
| `website_url` | Website URL, entered manually or applied from a verified candidate |
| `website_status` | Website status |
| `website_checked_at` | Date of the last website check |
| `phone` | Company phone number |
| `email` | Company email address |
| `lead_status` | Sales lead status |
| `lead_score` | Manually entered lead priority, from 0 to 100 |
| `notes` | Free-form notes |
| `contacted_at` | Date of first contact |
| `created_at` | Lead creation date |
| `updated_at` | Date of the last change |

## Website statuses

| Status | Meaning |
|---|---|
| `unknown` | Unknown; the default for a new lead |
| `no_website` | The company has no website |
| `active` | The website is active |
| `inactive` | The website is inactive |
| `error` | An error occurred while checking the website |

## Lead statuses

| Status | Meaning |
|---|---|
| `new` | New lead; the default |
| `in_progress` | The lead is being worked on |
| `contacted` | First contact has been made |
| `qualified` | The lead is qualified |
| `rejected` | The lead has been rejected |
| `converted` | The lead has been converted |

## Rules

- APR data is never overwritten by CRM data.
- A CRM lead is created and updated manually.
- A company can have at most one CRM lead.
- `contacted_at` is set automatically when `lead_status` is `contacted`
  and the value has not been set yet.
- `CompanyLeadObserver` sets `website_checked_at` automatically when
  `website_status` changes.
- Known inconsistency: applying a verified website candidate always sets
  `website_checked_at` to `now()`, even when `website_status` does not
  change.
- The website URL is validated and normalized before it is saved.
- Deleting a company also deletes the related CRM lead.

## Website URL validation

The website URL must be a valid URL.

If the user enters an address without a protocol, the application
normalizes it by adding `https://`.

Examples:

| Input | Stored value |
|---|---|
| `example.com` | `https://example.com` |
| `www.example.com` | `https://www.example.com` |
| `http://example.com` | `http://example.com` |
| `https://example.com` | `https://example.com` |

An invalid URL is rejected by validation.

Known limitation: normalization only adds `https://` when the protocol is
missing and does not remove `www.`, so `https://www.example.com` and
`https://example.com` are stored as different values.

## CRM list

The CRM list is available at `/leads`.

It supports:

- search by company name or registration number;
- filtering by website status;
- filtering by lead status;
- sorting by lead score (highest or lowest first) or by date of the last
  change.

## CRM status in the catalog

The company catalog has a CRM state filter:

- all companies;
- only companies with a lead;
- only companies without a lead.

This makes it easier to find companies that have not yet entered the CRM
process.

## Applying a verified website candidate

A verified website candidate from the enrichment layer can be applied to
the CRM only through an explicit user action.

When the user clicks **Apply to CRM**:

- if no lead exists, a new lead is created;
- if a lead exists, only `website_url`, `website_status`,
  and `website_checked_at` are updated;
- `lead_status`, `lead_score`, phone, email, and notes are not changed;
- APR data is not changed;
- the enrichment record remains `verified`.

## Running an audit from an enrichment candidate

For a verified website candidate of a company that already has a CRM lead,
a website audit can be run.

The audit is created in the `company_website_audits` table, and
`requested_url` is taken from the verified enrichment candidate.

An audit can be run only if:

- the data point is of type `website`;
- the data point has the status `verified`;
- the company has a CRM lead.

Running the audit does not change the enrichment record or the CRM data.

## Tests

The following cases are covered:

- creating a CRM lead;
- updating a CRM lead;
- status and lead score validation;
- updating a lead keeps a single `company_leads` row per `company_id`
  (the unique index itself is not tested directly);
- automatic setting of `contacted_at`;
- CRM list filters;
- the CRM filter in the company catalog;
- URL validation and normalization;
- automatic setting of `website_checked_at`;
- creating and updating website audits;
- the technical URL check.