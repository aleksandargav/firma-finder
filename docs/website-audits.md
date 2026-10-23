# Website audit

The website audit layer stores technical and manual checks of a company's
website. It is separate from APR data, CRM data, and enrichment data.

## Model

Audit data is stored in the `company_website_audits` table.

Each audit belongs to exactly one CRM lead:

```txt
CompanyLead 1 — N CompanyWebsiteAudit
CompanyWebsiteAudit N — 1 CompanyLead
```

A single CRM lead can have multiple audit records. This makes it possible
to keep a history of checks over time.

Deleting a CRM lead also deletes all related audit records.

## Audit statuses

| Status | Meaning |
|---|---|
| `pending` | The audit has been created but not completed yet |
| `completed` | The check ran and the website returned an HTTP response, including an HTTP error |
| `failed` | The connection failed or an exception was thrown |

## Fields

| Field | Purpose |
|---|---|
| `company_lead_id` | The CRM lead whose website is checked |
| `status` | Audit status |
| `audit_version` | Audit version, `v1` by default |
| `requested_url` | The URL requested by the user or the system |
| `final_url` | The final URL after redirects |
| `http_status` | HTTP status code |
| `is_reachable` | Whether the website returned a successful (2xx) response |
| `has_https` | Whether the final URL uses HTTPS |
| `redirect_count` | Redirect flag: 0 or 1 |
| `response_time_ms` | Response time in milliseconds |
| `page_title` | Page title |
| `meta_description` | Page meta description |
| `notes` | Manual notes |
| `error_message` | Reason for failure |
| `started_at` | Date and time the check started |
| `completed_at` | Date and time the check ended |
| `reviewed_at` | Review date; not set by the application yet |
| `created_at` | Audit creation date |
| `updated_at` | Date of the last change |

Note: `redirect_count` is 1 whenever the final URL differs from the
requested URL, so a difference such as a trailing slash also gives 1.

## Manual audit creation

On the company detail page, in the **Website audit history** section,
which is shown only when the company has a CRM lead, the user can
manually create a new website audit.

The user enters:

- the URL to check;
- the audit status (`pending` by default);
- optionally, the final URL, HTTP status, reachability, HTTPS, redirect
  flag, response time, page title, meta description, notes, and error.

## Manual technical check

For every existing audit, the user can run a technical check with the
**Run technical check** button.

The check is performed by `WebsiteAuditService`.

During the check, the application stores:

- whether the URL is reachable;
- the HTTP status code;
- the final URL after redirects;
- whether the final URL uses HTTPS;
- the redirect flag;
- the response time;
- the page title;
- the meta description;
- the reason for the error, if the check fails.

When the website returns an HTTP response, the audit gets the status
`completed`. A website that answers with an HTTP error is also
`completed`, with `is_reachable = false`.

The audit gets the status `failed` only when the connection fails or an
exception is thrown, and the reason is stored in `error_message`.

## History display

The company detail page shows a list of previous website audit records.

For each audit, the following are shown:

- the audit date (`created_at`);
- the status;
- the requested URL;
- the final URL;
- the HTTP status;
- reachability;
- HTTPS status;
- the response time;
- the audit version;
- the page title;
- the meta description;
- notes;
- the reason for the error, if there is one.

The redirect flag is stored but not shown in the history.

## Audit from an enrichment candidate

For a verified website candidate of a company that already has a CRM lead,
a website audit can be run.

The audit is created in the existing `company_website_audits` table, and
`requested_url` is taken from the verified enrichment candidate.

An audit can be run only if:

- the data point is of type `website`;
- the data point has the status `verified`;
- the company has a CRM lead.

Running the audit does not change the enrichment record or the CRM data.

## Next steps

The next phase is a manual review of audit results.

The following states are planned:

| Status | Meaning |
|---|---|
| `unreviewed` | The audit has not been reviewed yet |
| `approved` | The user confirmed the finding |
| `rejected` | The user rejected the finding |
| `needs_review` | The finding needs further checking |

After that come:

- Lighthouse metrics;
- desktop and mobile screenshots;
- manual confirmation of findings;
- AI analysis based on confirmed results.

## Tests

The following cases are covered:

- creating a `CompanyWebsiteAudit` record;
- the relationship to `CompanyLead`;
- cascade deletion of audits when a lead is deleted;
- audit status validation;
- manual creation of an audit through the UI;
- the manual technical URL check;
- storing the HTTP status, HTTPS status, redirect flag, response time,
  page title, and meta description;
- storing the error when the check fails.

The audit history display is not covered by a test.