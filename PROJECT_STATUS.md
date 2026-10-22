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

### Company enrichment podaci

Tabela `company_data_points` čuva dodatne podatke o firmama koji dolaze
iz ručnog unosa ili budućih enrichment izvora.

Veza je:

```text
Company hasMany CompanyDataPoint
CompanyDataPoint belongsTo Company
```

`company_id` je nullable jer enrichment zapis može biti `unmatched`
kada se ne može pouzdano povezati sa APR firmom.

Brisanje APR firme briše i sve njene enrichment zapise kroz
`cascadeOnDelete`.

Podržani tipovi podataka su:

- `website`;
- `email`;
- `phone`;
- `address`.

Podržani izvori su:

- `manual`;
- `companywall`;
- `openstreetmap`;
- `wikidata`;
- `google_places`;
- `apr`.

Podržani statusi zapisa su:

- `candidate`;
- `verified`;
- `rejected`;
- `unmatched`;
- `needs_review`.

APR podaci se nikada ne menjaju enrichment podacima.

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
- Ako sajt nije dostupan ili provera uspe, audit dobija status `failed`
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

### Company enrichment

- Kreirana je tabela `company_data_points`.
- Kreiran je `CompanyDataPoint` model.
- Dodata je relacija `Company hasMany CompanyDataPoint`.
- `company_id` je nullable i koristi `cascadeOnDelete`.
- Podržani tipovi podataka su `website`, `email`, `phone` i `address`.
- Podržani izvori su `manual`, `companywall`, `openstreetmap`, `wikidata`,
  `google_places` i `apr`.
- Podržani nivoi poverenja su `high`, `medium` i `low`.
- Podržani licencni statusi su `allowed`, `restricted`, `forbidden`
  i `unknown`.
- Podržani statusi zapisa su `candidate`, `verified`, `rejected`,
  `unmatched` i `needs_review`.
- Na detalju firme postoji sekcija **Enrichment kandidati**.
- Korisnik može ručno da doda website, e-mail, telefon ili adresu
  kao kandidata.
- Website URL se normalizuje i validira pre čuvanja.
- E-mail adresa se validira pre čuvanja.
- Korisnik može ručno da potvrdi, odbije ili označi kandidata za pregled.
- Potvrđeni website kandidat može se eksplicitno primeniti u CRM.
- Primenom u CRM kreira se novi lead ako ne postoji ili se ažurira
  postojeći lead.
- Primenom u CRM menjaju se samo `website_url`, `website_status`
  i `website_checked_at`.
- `lead_status`, `lead_score`, telefon, e-mail i napomene se ne menjaju
  automatski.
- Za potvrđen website kandidat kod firme koja ima CRM lead može se
  pokrenuti website audit.
- Audit koristi postojeći `WebsiteAuditService` i čuva se u postojećoj
  `company_website_audits` tabeli.
- CompanyWall, OSM, Wikidata i Google Places još uvek nisu integrisani.
- Nema automatske verifikacije identiteta firme niti automatske potvrde
  URL-a.

## Trenutni testni zapisi

Lokalno testiranje koristi zapis firme iz APR registra. Identitet firme
u ovom dokumentu zamenjen je neutralnim primerom. Navedeni CRM status,
score i datum kontakta služe isključivo za testiranje aplikacije i ne
predstavljaju stvarno kontaktiranje niti poslovni odnos.

- Firma: Primer firme
- Matični broj: `[placeholder]`
- Website URL: `https://example.com`
- Website status: `active`
- Lead status: `qualified`
- Lead score: `10`
- Datum prvog kontakta: `[testni datum]`

Za ovaj lead postoje ručni testni website audit zapisi koji služe za
proveru prikaza istorije audita, UI forme i tehničke provere sajta.

Postoje i ručni enrichment testni zapisi:

- `https://example.com` kao `verified` website kandidat;
- `https://example.com` kao `candidate` website kandidat;
- `https://example.org` kao `rejected` website kandidat.

## Aktivni fokus

Company enrichment temelj i ručni enrichment tok su završeni.

Sledeći korak je ručna potvrda website audit nalaza.

## Sledeći zadatak

Dodati ručni review website audit nalaza:

- `review_status`;
- `reviewed_at`;
- `reviewed_by`;
- ručne akcije za odobravanje, odbijanje i označavanje za pregled.

Nakon toga sledi OSM enrichment za jednu firmu.

## Plan nakon enrichment review-a

1. Dodati OSM enrichment za jednu firmu.
2. Definisati pravila identiteta firme preko MB/PIB.
3. Definisati scoring identitet match-a.
4. Dodati Wikidata enrichment kao dopunski izvor.
5. Integrirati CompanyWall tek nakon pismene potvrde uslova.
6. Implementirati automatsku verifikaciju URL-a tek nakon pouzdanog
   identiteta.
7. Dodati Lighthouse rezultate i desktop/mobile screenshotove.
8. Dodati AI analizu zasnovanu na tehničkim dokazima i snimcima.
9. Dodati nacrt ponude uz obaveznu ručnu proveru i odobrenje.
10. Tek nakon potvrđenog kvaliteta dodati queue, rate limiting i batch
    obradu.

## Trenutna ograničenja

- URL, telefon i e-mail unose se ručno.
- Enrichment kandidati se trenutno unose samo ručno.
- Website audit se unosi ručno kroz formu na detalju firme.
- Tehnička provera URL-a se pokreće ručno za pojedinačan audit.
- `website_checked_at` se automatski postavlja pri kreiranju leada i
  promeni `website_status`.
- Nema automatskog ili zakazanog ponavljanja tehničke provere.
- Nema Lighthouse rezultata ni vizuelnog audita.
- Nema integracije sa OSM, Wikidata, CompanyWall ili Google Places.
- Nema automatske verifikacije identiteta firme.
- Nema automatske potvrde enrichment URL-a.
- Potvrđeni website se u CRM primenjuje samo eksplicitnom korisničkom
  akcijom.
- AI analiza, Lighthouse rezultati i screenshotovi nisu implementirani.
- Nema AI API integracije.
- Nema automatskog slanja poruka, e-mailova ili ponuda.
- Nema autentikacije i više korisnika.
