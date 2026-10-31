# Firma Finder — Roadmap

## Working principle

Development proceeds step by step:

1. a stable data model;
2. a manual CRM flow;
3. tests and safeguards;
4. an objective technical audit;
5. a visual audit;
6. evidence-based AI analysis;
7. a draft offer with manual approval;
8. only then, larger-scale automation.

AI is not a replacement for checking. It is used to speed up assessment,
prioritization, and draft preparation, while the user always confirms the
final decision and any sending.

The portfolio plan below takes priority over the order of the other
sections (see DECISIONS.md, 2026-10-29). Queues for website checks come
earlier than point 8 above; batch processing still waits.

---

## Portfolio plan

### 1. Docker Compose environment and MySQL

- [x] Docker Compose: PHP 8.5-FPM, Nginx, MySQL 8.4, Redis
- [x] Ports bound to `127.0.0.1` only
- [x] Move from SQLite to MySQL
- [x] Collation `utf8mb4_0900_ai_ci`, chosen after measuring on real data
- [x] Fix lowercase Cyrillic search (`београд` found nothing under SQLite)
- [x] Re-import the APR catalog into MySQL (134,374 companies)
- [x] Run the test suite on a separate MySQL test database
- [x] `TestDatabaseGuard` against running tests on another database
- [x] Widen URL and page title columns to match validation limits
- [x] Update the documentation
- [x] English README

### 2. Website checks as queued jobs

- [ ] Redis queue connection
- [ ] Laravel Horizon
- [ ] Website check job with retries, timeout, and logging

### 3. Tests

- [ ] Tests for `apr:import` as it is now
- [ ] Stream the APR JSON instead of loading it into memory
- [ ] Run `apr:sync` inside the Docker container (it has not been run
  there yet)
- [ ] More tests where coverage is missing; Pest where it fits

### 4. Continuous integration

- [ ] GitHub Actions: tests on MySQL, Pint, Larastan on every push
- [ ] Run Pint over the whole project in a separate commit

### 5. Vue 3 + Inertia frontend

Each screen is designed first and approved, then implemented screen by
screen.

- [ ] Fix the duplicated "Osnovana" column on `/companies`
- [ ] Fix the duplicated pagination on `/companies`
- [ ] Redesign the application in Vue 3 + Inertia + Tailwind, as a modern
  CRM interface
- [ ] Table with a sticky header
- [ ] Filters as chips
- [ ] Side panel with company details
- [ ] Ctrl+K command search and keyboard shortcuts
- [ ] Quick actions from the list
- [ ] Loading, empty, error, and saved states
- [ ] Responsive layout
- [ ] Accessibility: keyboard navigation and contrast
- [ ] Keep the dark theme
- [ ] Evaluate shadcn-vue for components

### 6. Deployment

- [ ] Small server (Hetzner or AWS EC2) with HTTPS
- [ ] Production Docker image
- [ ] Pin the PHP base image to an exact version, so builds are
  reproducible

### 7. Final README

- [ ] Architecture diagram
- [ ] Screenshots

---

## Completed

### APR catalog

- [x] Local APR company catalog
- [x] Import of 134,374 companies (snapshot `2026-09-30`)
- [x] Search by company name and registration number
- [x] Filter by municipality
- [x] Filter by activity code
- [x] Filter by APR status
- [x] Pagination
- [x] Company detail page

### CRM foundation

- [x] `company_leads` table
- [x] 1:1 `Company → CompanyLead` relationship
- [x] Manual creation of a CRM lead
- [x] Manual update of a CRM lead
- [x] Website URL, phone, email, and notes
- [x] Website status
- [x] Lead status
- [x] Lead score
- [x] Automatic first contact date
- [x] CRM list at `/leads`
- [x] CRM list filters
- [x] CRM list sorting
- [x] CRM status in the company catalog
- [x] Documentation of the initial state
- [x] CRM filter in the company catalog
- [x] Website URL validation and normalization
- [x] Automatic date of the last website check

### Website audit foundation

- [x] `company_website_audits` table
- [x] `CompanyWebsiteAudit` model
- [x] `CompanyLead hasMany CompanyWebsiteAudit` relationship
- [x] `CompanyWebsiteAudit belongsTo CompanyLead` relationship
- [x] Support for multiple audits per lead
- [x] Cascade deletion of audits when a lead is deleted
- [x] Audit statuses: `pending`, `completed`, `failed`
- [x] Audit history on the company detail page
- [x] Manual form for creating a website audit
- [x] Storage of the URL to check, final URL, HTTP status, reachability,
  HTTPS status, redirect flag (`redirect_count`, 0 or 1), response time,
  page title, meta description, notes, and error
- [x] Feature tests for the model, relationships, cascade deletion,
  validation, and UI flow

### Technical URL check

- [x] `WebsiteAuditService` for running the check manually
- [x] Route `POST /website-audits/{audit}/run`
- [x] **Run technical check** button on the company detail page
- [x] URL reachability check
- [x] HTTP status code
- [x] Final URL after redirects
- [x] HTTPS check
- [x] Response time
- [x] Page title extraction
- [x] Meta description extraction
- [x] Storage of the error reason when the check fails
- [x] Feature tests with fake HTTP responses

### Company enrichment foundation

- [x] `company_data_points` table
- [x] `CompanyDataPoint` model
- [x] `Company hasMany CompanyDataPoint` relationship
- [x] Cascade deletion of enrichment records when a company is deleted
- [x] Support for website, email, phone, and address
- [x] Constants for sources, confidence, license status, and record status
- [x] Validation rules for enrichment data
- [x] Manual addition of enrichment candidates through the UI
- [x] Manual verification, rejection, and flagging for review
- [x] Explicit application of a verified website URL to the CRM
- [x] Running a website audit from a verified website candidate
- [x] Feature tests for the model, relationships, validation, UI, and
  audit flow

---

## After the portfolio plan: Enrichment review

### Manual audit review

- [ ] Add `review_status` to the website audit
- [ ] Add `reviewed_by` to the website audit (`reviewed_at` already exists
  on `company_website_audits`)
- [ ] Allow manual approval of an audit
- [ ] Allow manual rejection of an audit
- [ ] Allow flagging an audit for review
- [ ] Show the review status in the website audit history
- [ ] Add tests for manual audit review

---

## Later: Automated enrichment

### Company identity

- [ ] Define company identity rules based on MB/PIB
- [ ] Define identity match scoring
- [ ] Support `unmatched` records when the MB/PIB does not exist in the
  APR database
- [ ] Prevent automatic verification based on the company name alone

### OpenStreetMap

- [ ] Integrate OSM enrichment for a single company
- [ ] Search for the company by name and municipality
- [ ] Store OSM candidates in `company_data_points`
- [ ] Mark OSM records as `candidate` or `needs_review`
- [ ] Store the source URL and license status
- [ ] Add tests with fake OSM responses

### Wikidata

- [ ] Integrate Wikidata as a supplementary source
- [ ] Use property `P856 — official website`
- [ ] Store Wikidata candidates in `company_data_points`
- [ ] Mark Wikidata records as `candidate` or `needs_review`
- [ ] Add tests with fake Wikidata responses

### CompanyWall

- [ ] Obtain written confirmation of the terms of use
- [ ] Confirm that the API or export includes the website URL
- [ ] Confirm how companies are matched by MB/PIB
- [ ] Confirm the JSON/CSV response format
- [ ] Confirm prices, quotas, and rate limits
- [ ] Confirm the terms for storing the data in the CRM
- [ ] Integrate CompanyWall only after the terms are confirmed

### Automatic verification

- [ ] Check whether the verified URL is reachable
- [ ] Check HTTPS and the final URL
- [ ] Check for the company name in the domain, title, or content
- [ ] Detect conflicts between multiple sources
- [ ] Automatically verify only strong matches and permitted sources
- [ ] Send weak or disputed values to `needs_review`

---

## CRM completion

### CRM catalog

- [x] Filter: all companies / only companies with a lead / only companies
  without a lead
- [ ] Optional filter by a specific CRM status directly in the catalog
- [ ] Clearer visual statuses for different lead stages
- [ ] Display of, or filter by, lead score

### Validation and UX

- [x] Validate the website URL as a real URL, with protocol normalization
- [x] Automatically set `website_checked_at` when the website status is
  changed manually
- [x] Add a clearer indication of whether and when the URL was checked
- [ ] Allow safe deletion of a CRM lead with confirmation, if it turns out
  to be needed

### Tests

- [x] Feature test for creating a lead
- [x] Feature test for updating a lead
- [x] Test for status and lead score validation
- [x] Test that updating a lead keeps a single `company_leads` row per
  `company_id` (the unique index itself is not tested directly)
- [x] Test of `CompanyLeadObserver` behavior for `contacted_at`
- [x] Test of the CRM list filters
- [x] Test of the CRM state filter in the catalog
- [x] Tests for URL validation and normalization
- [x] Tests for automatic `website_checked_at`
- [x] Tests for the `CompanyWebsiteAudit` model, relationships, and
  cascade deletion
- [x] Tests for manual creation of a website audit through the UI
- [x] Tests for the manual technical URL check
- [x] Tests run on MySQL instead of in-memory SQLite
- [x] Unit tests for `TestDatabaseGuard`
- [x] Tests for values longer than 255 characters in URL and page title
  columns

---

## Website audit without AI

### Model and history

- [x] Create the `company_website_audits` table
- [x] Add relationships between a lead and multiple audits
- [x] List previous audits on the company detail page
- [x] Manual entry of audits and findings
- [x] Storage of the check date, URL, and final URL
- [x] Storage of the audit success/failure status
- [ ] Manual confirmation of findings

### Technical check

- [x] URL reachability check
- [x] HTTP status code
- [x] Final URL after redirects
- [x] HTTPS check
- [x] Response time
- [x] Error reason when the check fails
- [ ] Rate limiting and a limit on parallel checks
- [ ] Audit queue/job infrastructure (portfolio plan, step 2)

### Lighthouse and screenshots

- [ ] Lighthouse performance score
- [ ] Lighthouse accessibility score
- [ ] Lighthouse best practices score
- [ ] Lighthouse SEO score
- [ ] Desktop screenshot
- [ ] Mobile screenshot
- [ ] Local storage of audit artifacts
- [ ] Artifact preview on the company page

---

## AI audit

- [ ] Choose an AI provider and model
- [ ] Define the format of the structured AI response
- [ ] AI analysis of technical results and screenshots
- [ ] Assessment of the desktop layout
- [ ] Assessment of the mobile layout
- [ ] Assessment of the design and message clarity
- [ ] Assessment of CTA elements and conversion opportunities
- [ ] Recommended type of work:
    - minor changes;
    - improvement of the existing website;
    - redesign;
    - new website.
- [ ] AI confidence label
- [ ] Manual confirmation or rejection of AI findings
- [ ] Storage of the prompt and model version used for the audit

---

## Offers and contact

- [ ] Draft of a personalized offer based on approved findings
- [ ] Choice of message tone
- [ ] Manual editing of the draft
- [ ] Manual approval before anything is sent
- [ ] History of drafts and approvals
- [ ] Record of contact made
- [ ] Later phase: email sending integration, only with explicit
  confirmation

---

## Later

- [ ] Authentication and multiple users
- [ ] Roles and permissions
- [ ] Export of leads and audits
- [ ] Dashboard with CRM metrics
- [ ] Daily/periodic re-audit
- [ ] Batch processing of selected companies
- [ ] Configurable scoring model
- [ ] Deployment (portfolio plan, step 6)
- [ ] Search across scripts: `beograd` should find `БЕОГРАД`
  (`business_name` stays unchanged; a search-only helper column needs a
  separate decision)
- [ ] True Serbian Latin sorting with
  `COLLATE utf8mb4_sr_latn_0900_ai_ci` in `ORDER BY` only
- [ ] 18 company names begin with punctuation and always sort first;
  solve in queries, `business_name` stays unchanged
- [ ] Decide whether `company_website_audits.requested_url` should be
  `NOT NULL`
