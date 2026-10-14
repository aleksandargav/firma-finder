# Firma Finder

Firma Finder je lokalna Laravel aplikacija za pronalaženje, pregled i
kvalifikaciju privrednih društava iz APR registra Srbije.

Aplikacija koristi lokalni APR katalog i služi za organizovanje prodajnog
procesa za usluge izrade, redizajna i unapređenja poslovnih sajtova.

## Tehnologija

- Laravel
- PHP 8.5
- SQLite lokalna baza
- Blade prikazi
- Lokalni razvoj kroz `php8.5 artisan serve`

## Osnovna arhitektura

### APR podaci

Tabela `companies` čuva uvezene, izvorne APR podatke:

- matični broj;
- naziv firme;
- opštinu;
- status;
- pravnu formu;
- delatnost;
- datum osnivanja;
- datum APR preseka.

APR podaci su izvorni katalog i ne smeju biti prepisivani CRM ili audit
podacima.

### CRM podaci

Tabela `company_leads` čuva ručne CRM podatke:

- URL sajta;
- status sajta;
- telefon;
- e-mail;
- lead status;
- lead score;
- napomene;
- datum provere sajta;
- datum prvog kontakta.

Veza između `Company` i `CompanyLead` je striktno 1:1 i zaštićena je
unique indeksom nad `company_id`.

### Website audit

Tabela `company_website_audits` čuva istoriju tehničkih i ručnih provera
sajta.

Jedan CRM lead može imati više website audit zapisa. Audit može biti:

- `pending`;
- `completed`;
- `failed`.

### Company enrichment

Tabela `company_data_points` čuva kandidate i potvrđene podatke o firmi:

- website;
- e-mail;
- telefon;
- adresa.

Enrichment podaci su odvojeni od APR podataka i od CRM podataka. Nijedan
enrichment zapis ne menja automatski APR katalog niti CRM lead.

## Trenutno završeno

### APR katalog

- Lokalni APR katalog firmi.
- Uvoz 125.054 firmi.
- Pretraga po nazivu firme i matičnom broju.
- Filter po opštini.
- Filter po šifri delatnosti.
- Filter po APR statusu.
- Paginacija.
- Detalj firme.

### CRM

- Ručno kreiranje i ažuriranje CRM leada.
- URL sajta, telefon, e-mail i napomene.
- Status sajta.
- Status leada.
- Lead score.
- Automatski datum prvog kontakta.
- CRM lista na `/leads`.
- Filteri i sortiranje CRM liste.
- CRM status u katalogu firmi.
- Validacija i normalizacija website URL-a.
- Automatski datum poslednje provere sajta.

### Website audit

- Tabela `company_website_audits`.
- Model `CompanyWebsiteAudit`.
- Relacija `CompanyLead hasMany CompanyWebsiteAudit`.
- Relacija `CompanyWebsiteAudit belongsTo CompanyLead`.
- Mogućnost više audita za isti lead.
- Cascade brisanje audita pri brisanju leada.
- Statusi audita: `pending`, `completed` i `failed`.
- Prikaz istorije audita na detalju firme.
- Ručna forma za kreiranje website audita.
- Ručna tehnička provera URL-a.
- Provera HTTP statusa, HTTPS-a, redirekcija, vremena odgovora,
  naslova stranice i meta description-a.
- Feature testovi za model, relacije, validaciju, UI i tehničku proveru.

### Company enrichment

- Tabela `company_data_points`.
- Model `CompanyDataPoint`.
- Relacija `Company hasMany CompanyDataPoint`.
- Cascade brisanje enrichment zapisa pri brisanju firme.
- Podrška za website, e-mail, telefon i adresu.
- Konstante za izvore, confidence, licencni status i status zapisa.
- Validaciona pravila za enrichment podatke.
- Ručno dodavanje enrichment kandidata kroz UI.
- Ručna potvrda, odbijanje i označavanje za pregled.
- Eksplicitna primena potvrđenog website URL-a u CRM.
- Pokretanje website audita iz potvrđenog website kandidata.
- Feature testovi za model, relacije, validaciju, UI i audit tok.

## Company enrichment princip

Enrichment sloj služi za prikupljanje i ručnu verifikaciju dodatnih
podataka o firmi.

Svaki zapis u `company_data_points` ima:

- tip podatka;
- vrednost;
- izvor;
- confidence;
- licencni status;
- status zapisa;
- opcionu napomenu;
- opcionu izvornu referencu.

Statusi zapisa su:

- `candidate` — kandidat za pregled;
- `verified` — ručno potvrđen;
- `rejected` — ručno odbijen;
- `needs_review` — zahteva dodatnu proveru.

Za sada se enrichment podaci dodaju ručno. Automatski izvori, poput
OpenStreetMap, Wikidata ili CompanyWall, dolaze tek nakon ručnog review
sloja i jasnih pravila verifikacije.

## Primena potvrđenog website kandidata

Potvrđeni website kandidat iz enrichment sloja može se primeniti u CRM
samo eksplicitnom korisničkom akcijom.

Kada korisnik klikne **Primeni u CRM**:

- ako lead ne postoji, kreira se novi lead;
- ako lead postoji, ažuriraju se samo `website_url`, `website_status`
  i `website_checked_at`;
- `lead_status`, `lead_score`, telefon, e-mail i napomene se ne menjaju;
- APR podaci se ne menjaju;
- enrichment zapis ostaje `verified`.

## Pokretanje audita iz enrichment kandidata

Za potvrđeni website kandidat, kod firme koja već ima CRM lead, može se
pokrenuti website audit.

Audit se kreira u postojećoj tabeli `company_website_audits`, a
`requested_url` se uzima iz potvrđenog enrichment kandidata.

Audit se može pokrenuti samo ako:

- data point je tipa `website`;
- data point ima status `verified`;
- firma ima CRM lead.

Pokretanje audita ne menja enrichment zapis niti CRM podatke.

## Razvojni principi

- APR podaci ostaju izvorni katalog.
- CRM podaci su ručni prodajni sloj.
- Enrichment podaci su odvojeni sloj kandidata i potvrda.
- Website audit je odvojen sloj tehničkih i vizuelnih provera.
- Nijedan automatski proces ne menja APR podatke.
- CRM se menja samo eksplicitnim korisničkim akcijama.
- AI analiza dolazi tek nakon objektivnih tehničkih i vizuelnih provera.
- Konačnu odluku uvek potvrđuje korisnik.

## Dokumentacija

- `APR_DATA_SYNC.md` — pravila uvoza i sinhronizacije APR podataka.
- `crm.md` — CRM model, statusi i pravila.
- `website-audits.md` — website audit model i tok.
- `company-enrichment.md` — enrichment model, statusi i pravila.
- `PROJECT_STATUS.md` — trenutno stanje projekta.
- `ROADMAP.md` — plan daljeg razvoja.
- `DECISIONS.md` — arhitekturne i produktne odluke.
