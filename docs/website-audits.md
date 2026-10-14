# Website audit

Website audit sloj čuva tehničke i ručne provere sajta firme. Odvojen je
od APR podataka, CRM podataka i enrichment podataka.

## Model

Audit podaci se čuvaju u tabeli `company_website_audits`.

Svaki audit pripada tačno jednom CRM leadu:

```txt
CompanyLead 1 — N CompanyWebsiteAudit
CompanyWebsiteAudit N — 1 CompanyLead
```

Jedan CRM lead može imati više audit zapisa. To omogućava istoriju
provera tokom vremena.

Brisanje CRM leada briše i sve povezane audit zapise.

## Statusi audita

| Status | Značenje |
|---|---|
| `pending` | Audit je kreiran, ali još nije završen |
| `completed` | Audit je uspešno završen |
| `failed` | Audit nije uspeo |

## Polja

| Polje | Namena |
|---|---|
| `company_lead_id` | CRM lead čiji se sajt proverava |
| `status` | Status audita |
| `requested_url` | URL koji je korisnik ili sistem zatražio |
| `final_url` | Krajnji URL nakon redirekcija |
| `http_status` | HTTP status kod |
| `is_reachable` | Da li je sajt dostupan |
| `uses_https` | Da li krajnji URL koristi HTTPS |
| `redirect_count` | Broj redirekcija |
| `response_time_ms` | Vreme odgovora u milisekundama |
| `page_title` | Naslov stranice |
| `meta_description` | Meta description stranice |
| `notes` | Ručne napomene |
| `error_message` | Razlog neuspeha |
| `checked_at` | Datum i vreme provere |
| `created_at` | Datum kreiranja audita |
| `updated_at` | Datum poslednje izmene |

## Ručno kreiranje audita

Na detalju firme, u okviru CRM lead sekcije, korisnik može ručno kreirati
novi website audit.

Korisnik unosi:

- URL sajta;
- opcionu napomenu.

Novi audit se kreira sa statusom `pending`.

## Ručna tehnička provera

Za audit u statusu `pending` korisnik može pokrenuti tehničku proveru
dugmetom **Pokreni tehničku proveru**.

Provera se izvršava kroz `WebsiteAuditService`.

Tokom provere aplikacija čuva:

- da li je URL dostupan;
- HTTP status kod;
- krajnji URL nakon redirekcija;
- da li krajnji URL koristi HTTPS;
- broj redirekcija;
- vreme odgovora;
- naslov stranice;
- meta description;
- razlog greške, ako provera ne uspe.

Nakon uspešne provere, audit dobija status `completed`.

Ako provera ne uspe, audit dobija status `failed`, a razlog se čuva u
`error_message`.

## Prikaz istorije

Na detalju firme prikazuje se lista prethodnih website audit zapisa.

Za svaki audit prikazuju se:

- datum provere;
- status;
- zatraženi URL;
- krajnji URL;
- HTTP status;
- HTTPS status;
- broj redirekcija;
- vreme odgovora;
- naslov stranice;
- meta description;
- napomene;
- razlog greške, ako postoji.

## Audit iz enrichment kandidata

Za potvrđeni website kandidat, kod firme koja već ima CRM lead, može se
pokrenuti website audit.

Audit se kreira u postojećoj tabeli `company_website_audits`, a
`requested_url` se uzima iz potvrđenog enrichment kandidata.

Audit se može pokrenuti samo ako:

- data point je tipa `website`;
- data point ima status `verified`;
- firma ima CRM lead.

Pokretanje audita ne menja enrichment zapis niti CRM podatke.

## Sledeći koraci

Sledeća faza je ručni review audit rezultata.

Planirana su sledeća stanja:

| Status | Značenje |
|---|---|
| `unreviewed` | Audit još nije pregledan |
| `approved` | Korisnik je potvrdio nalaz |
| `rejected` | Korisnik je odbacio nalaz |
| `needs_review` | Nalaz zahteva dodatnu proveru |

Nakon toga slede:

- Lighthouse metrike;
- desktop i mobilni screenshotovi;
- ručna potvrda nalaza;
- AI analiza zasnovana na potvrđenim rezultatima.

## Testovi

Pokriveni su sledeći slučajevi:

- kreiranje `CompanyWebsiteAudit` zapisa;
- relacija prema `CompanyLead`;
- cascade brisanje audita pri brisanju leada;
- validacija statusa audita;
- ručno kreiranje audita kroz UI;
- prikaz istorije audita;
- ručna tehnička provera URL-a;
- čuvanje HTTP statusa, HTTPS statusa, redirekcija, vremena odgovora,
  naslova stranice i meta description-a;
- čuvanje greške kada provera ne uspe.
