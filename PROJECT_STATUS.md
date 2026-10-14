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

## Trenutni testni zapis

Postoji jedan ručni testni lead:

- Firma: ELEKTROHEM DOO, Beograd (Novi Beograd)
- Matični broj: `17204106`
- Website URL: `https://example.com`
- Website status: `active`
- Lead status: `qualified`
- Lead score: `10`
- Datum prvog kontakta: `2026-10-11 08:18:28 UTC`

Ovaj zapis služi za razvoj i proveru CRM ekrana.

## Aktivni fokus

Završavanje CRM dokumentacije i prelazak na razvoj website-audit modula.

## Sledeći zadatak

Dizajnirati i implementirati zasebnu tabelu `company_website_audits`
za istoriju website provera.

## Plan nakon CRM osnove

1. Dizajnirati zasebnu tabelu `company_website_audits`.
2. Dodati ručni pregled i istoriju website audita po firmi.
3. Dodati tehničku proveru URL-a, HTTP statusa, redirekcija i HTTPS-a.
4. Dodati Lighthouse rezultat i desktop/mobile screenshotove.
5. Dodati AI analizu zasnovanu na tehničkim dokazima i snimcima.
6. Dodati nacrt ponude uz obaveznu ručnu proveru i odobrenje.
7. Tek nakon potvrđenog kvaliteta dodati queue, rate limiting i batch obradu.

## Trenutna ograničenja

- URL, telefon i e-mail unose se ručno.
- Tehnička provera da li sajt radi još uvek se vrši ručno.
- `website_checked_at` se automatski postavlja pri kreiranju leada i promeni statusa sajta.
- Nema automatske tehničke provere HTTP statusa, redirekcija, HTTPS-a, Lighthouse rezultata ili vizuelnog audita.
- Nema istorije website audita; planirana je zasebna tabela `company_website_audits`.
- Nema AI API integracije.
- Nema automatskog slanja poruka, e-mailova ili ponuda.
- Nema autentikacije i više korisnika.
