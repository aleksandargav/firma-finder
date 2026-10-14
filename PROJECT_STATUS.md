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

APR podaci se nikada ne menjaju CRM ili audit podacima.

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
Sledeći korak je proširenje audit modula Lighthouse rezultatima i
desktop/mobile screenshotovima.

## Sledeći zadatak

Dodati Lighthouse rezultate i desktop/mobile screenshotove u website
audit modul, uz zasebno skladištenje artefakata.

Pre implementacije proveriti lokalne uslove:

- Node.js verziju;
- Chrome ili Chromium instalaciju;
- Lighthouse instalaciju;
- način pokretanja Lighthouse-a iz Laravel aplikacije.

## Plan nakon website audit osnove

1. Dodati Lighthouse rezultate za performance, accessibility,
   best practices i SEO.
2. Dodati desktop i mobilne screenshotove.
3. Dodati lokalno skladištenje audit artefakata.
4. Dodati AI analizu zasnovanu na tehničkim dokazima i snimcima.
5. Dodati nacrt ponude uz obaveznu ručnu proveru i odobrenje.
6. Tek nakon potvrđenog kvaliteta dodati queue, rate limiting i batch
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
