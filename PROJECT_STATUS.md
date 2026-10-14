# Firma Finder — Project Status

## Poslednje ažuriranje

2026-10-14

## Svrha projekta

Firma Finder je lokalna Laravel aplikacija za pronalaženje, pregled i
kvalifikaciju privrednih društava iz APR registra Srbije.

Primarni cilj je organizovanje prodajnog procesa za usluge izrade,
redizajna i unapređenja poslovnih sajtova.

## Tehnologija

- Laravel
- PHP 8.5
- SQLite
- Blade
- Lokalni razvoj: `php8.5 artisan serve`

## Podaci i arhitektura

### APR podaci

Tabela `companies` čuva uvezene izvorne APR podatke:

- matični broj;
- naziv firme;
- opštinu;
- status;
- pravnu formu;
- šifru delatnosti;
- datum osnivanja;
- datum APR preseka;
- datum lokalnog uvoza.

APR podaci se nikada ne menjaju CRM, enrichment ili audit podacima.

APR JSON izvor koji se trenutno koristi ne sadrži website URL. Provereno je
da svih 134.374 zapisa ima samo osnovna registarska polja.

### CRM podaci

Tabela `company_leads` čuva ručne CRM podatke:

- `website_url`;
- `website_status`;
- `phone`;
- `email`;
- `lead_status`;
- `lead_score`;
- `notes`;
- `website_checked_at`;
- `contacted_at`.

Veza `Company → CompanyLead` je striktno 1:1 i zaštićena je
jedinstvenim indeksom nad `company_leads.company_id`.

### Company enrichment podaci

Website i drugi kontakt podaci prikupljaju se kroz zaseban enrichment sloj.

Planirana tabela je `company_data_points`.

Svaki enrichment zapis ima:

- izvor podataka;
- identitet match;
- licencni status;
- confidence;
- status potvrde.

Planirani izvori su:

- `manual`;
- `companywall`;
- `openstreetmap`;
- `wikidata`;
- `google_places`;
- `apr` kao identitet izvor.

CompanyWall se ne integriše dok ne budu pisano potvrđeni uslovi korišćenja,
website podatak, MB/PIB uparivanje, kvote i licenca.

### Website audit podaci

Tabela `company_website_audits` čuva istoriju ručnih tehničkih provera
sajta za CRM leadove.

Veza je:

```text
CompanyLead hasMany CompanyWebsiteAudit
CompanyWebsiteAudit belongsTo CompanyLead
```

Jedan CRM lead može imati više website audita. Brisanje leada briše i
sve povezane audite kroz `cascadeOnDelete`.

APR podaci u `companies` se nikada ne menjaju audit podacima.

## Trenutno završeno

### APR katalog

- Lokalno je uvezen APR katalog sa 125.054 firmi.
- Lista firmi je dostupna na `/companies`.
- Postoje pretraga i filteri po:
    - nazivu firme;
    - matičnom broju;
    - opštini;
    - šifri delatnosti;
    - APR statusu.
- Lista koristi paginaciju od 50 firmi po stranici.
- Detalj firme je dostupan na:
  `/companies/{registration_number}`.

### CRM

- Kreirana je tabela `company_leads`.
- Kreiran je `CompanyLead` model.
- Dodate su Eloquent relacije:
    - `Company::lead()` (`hasOne`);
    - `CompanyLead::company()` (`belongsTo`).
- Dodate su konstante za:
    - status sajta;
    - status leada.
- Na detalju firme postoji CRM forma za:
    - URL sajta;
    - status sajta;
    - telefon;
    - e-mail;
    - status leada;
    - lead score od 0 do 100;
    - napomene.
- Postoje rute za kreiranje i izmenu CRM zapisa:
    - `POST /companies/{registration_number}/lead`;
    - `PUT /companies/{registration_number}/lead`.
- CRM lista je dostupna na `/leads`.
- CRM lista podržava:
    - pretragu po nazivu i matičnom broju;
    - filtriranje po statusu leada;
    - filtriranje po statusu sajta;
    - sortiranje po score-u ili poslednjoj izmeni.
- Glavni katalog na `/companies` prikazuje CRM kolonu.
- CRM status vodi na detalj firme radi izmene.
- CRM filter na katalogu firmi podržava:
    - sve firme;
    - samo firme sa leadom;
    - samo firme bez leada.
- Neispravna vrednost `lead_filter` se tretira kao `all`.
- Website URL se validira i normalizuje:
    - URL bez protokola dobija `https://`;
    - postojeći `http://` ili `https://` protokol ostaje nepromenjen;
    - URL bez validnog domena se odbacuje.
- `website_checked_at` se automatski postavlja pri kreiranju leada i
  promeni `website_status`.
- Na CRM kartonu firme prikazuje se datum poslednje provere sajta.
- Postoje feature testovi za CRM filter, CRUD, observer, URL validaciju
  i `website_checked_at`.

### Datum prvog kontakta

`CompanyLeadObserver` automatski upisuje `contacted_at` kada lead
prvi put dobije status `contacted`.

Datum prvog kontakta ostaje sačuvan kada se status kasnije promeni,
na primer iz `contacted` u `qualified`.

### Website audit osnova

- Kreirana je tabela `company_website_audits`.
- Kreiran je `CompanyWebsiteAudit` model.
- Dodate su Eloquent relacije:
    - `CompanyLead::websiteAudits()` (`hasMany`);
    - `CompanyWebsiteAudit::companyLead()` (`belongsTo`).
- Jedan CRM lead može imati više website audita.
- Audit zapisi se brišu zajedno sa leadom kroz `cascadeOnDelete`.
- Podržani statusi audita su:
    - `pending`;
    - `completed`;
    - `failed`.
- Na detalju firme postoji sekcija **Website audit istorija**.
- Korisnik može ručno da doda website audit kroz formu na detalju firme.
- Audit čuva:
    - URL provere;
    - krajnji URL;
    - HTTP status;
    - dostupnost sajta;
    - HTTPS status;
    - broj redirekcija;
    - vreme odgovora;
    - naslov stranice;
    - meta description;
    - napomene;
    - grešku, ako je bilo;
    - datum početka i završetka provere.
- Website audit se trenutno unosi ručno.
- Dodat je `WebsiteAuditService` za ručno pokretanje tehničke provere sajta.
- Tehnička provera čuva HTTP status, krajnji URL, HTTPS status, broj
  redirekcija, vreme odgovora, naslov stranice i meta description.
- Ako sajt nije dostupan ili provera ne uspe, audit dobija status `failed`
  i čuva se razlog greške.
- Na detalju firme postoji dugme **Pokreni tehničku proveru** za svaki
  postojeći audit.
- Postoje feature testovi za:
    - kreiranje audita za lead;
    - relaciju lead → audits;
    - više audita za isti lead;
    - cascade brisanje audita;
    - validaciju statusa audita;
    - ručno kreiranje audita kroz UI;
    - odbijanje nevalidnog URL-a i statusa;
    - sprečavanje kreiranja audita bez CRM leada;
    - uspešnu tehničku proveru URL-a;
    - neuspešnu tehničku proveru kada sajt nije dostupan;
    - tehničku proveru kada URL nije definisan;
    - pokretanje tehničke provere kroz UI.

### Company enrichment dokumentacija

- Proveren je APR JSON izvor `storage/app/apr-response.json`.
- Potvrđeno je da svih 134.374 APR zapisa ima samo sedam registarskih polja.
- Potvrđeno je da APR JSON ne sadrži website URL.
- Kreiran je dokument `docs/company-enrichment.md`.
- Definisana je arhitektura za više enrichment izvora.
- Definisani su planirani izvori: `manual`, `companywall`, `openstreetmap`,
  `wikidata`, `google_places` i `apr`.
- Definisani su planirani statusi: `candidate`, `verified`, `rejected`,
  `unmatched` i `needs_review`.
- Definisana je politika da CompanyWall ostaje neaktivan dok ne budu pisano
  potvrđeni uslovi korišćenja.

## Trenutni testni zapisi

Postoji jedan ručni testni lead:

- Firma: ELEKTROHEM DOO, Beograd (Novi Beograd)
- Matični broj: `17204106`
- Website URL: `https://example.com`
- Website status: `active`
- Lead status: `qualified`
- Lead score: `10`
- Datum prvog kontakta: `2026-10-11 08:18:28 UTC`

Za ovaj lead postoje ručni testni website audit zapisi koji služe za
proveru prikaza istorije audita, UI forme i tehničke provere sajta.

## Aktivni fokus

Website audit osnova i ručna tehnička provera URL-a su završeni.

Sledeći aktivni fokus je Company enrichment temelj: `company_data_points`,
ručni unos i potvrda website kandidata, a zatim OSM, Wikidata i CompanyWall
integracija nakon potvrde uslova.

## Sledeći zadatak

Kreirati `company_data_points` tabelu i `CompanyDataPoint` model.

Prva verzija treba da podrži:

- `Company hasMany CompanyDataPoint`;
- `CompanyDataPoint belongsTo Company`;
- `field = website`;
- `source = manual`;
- `status = candidate | verified | rejected | unmatched | needs_review`;
- `confidence = high | medium | low`;
- `data_license_status = allowed | restricted | forbidden | unknown`;
- ručni unos i potvrdu website kandidata.

Tek nakon toga dodaju se OSM, Wikidata i CompanyWall izvori.

## Plan nakon enrichment temelja

1. Dodati ručni unos i potvrdu website kandidata.
2. Dodati OSM enrichment za jednu firmu.
3. Dodati Wikidata enrichment kao dopunski izvor.
4. Integrirati CompanyWall nakon potvrde uslova.
5. Dodati pravila auto-verifikacije.
6. Dodati regionalne adaptere po zemljama.
7. Dodati Lighthouse rezultate za performance, accessibility,
   best practices i SEO.
8. Dodati desktop i mobilne screenshotove.
9. Dodati lokalno skladištenje audit artefakata.
10. Dodati AI analizu zasnovanu na tehničkim dokazima i snimcima.
11. Dodati nacrt ponude uz obaveznu ručnu proveru i odobrenje.
12. Tek nakon potvrđenog kvaliteta dodati queue, rate limiting i batch
    obradu.

## Trenutna ograničenja

- URL, telefon i e-mail unose se ručno.
- Website audit se unosi ručno kroz formu na detalju firme.
- Tehnička provera URL-a se pokreće ručno za pojedinačan audit.
- `website_checked_at` se automatski postavlja pri kreiranju leada i
  promeni statusa sajta.
- Nema automatskog ili zakazanog ponavljanja tehničke provere.
- Nema Lighthouse rezultata ni vizuelnog audita.
- AI analiza, Lighthouse rezultati i screenshotovi nisu implementirani.
- Nema AI API integracije.
- Nema automatskog slanja poruka, e-mailova ili ponuda.
- Nema autentikacije i više korisnika.
- Nema `company_data_points` tabele ni enrichment servisa.
- CompanyWall, OpenStreetMap i Wikidata enrichment nisu implementirani.
- CompanyWall se ne koristi dok ne budu pisano potvrđeni uslovi korišćenja.
