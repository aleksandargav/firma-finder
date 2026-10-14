# Firma Finder

Firma Finder je lokalna Laravel aplikacija za pronalaženje, pregled i
kvalifikaciju privrednih društava iz APR registra Srbije.

Primarni cilj je organizovanje prodajnog procesa za usluge izrade,
redizajna i unapređenja poslovnih sajtova.

---

## Tehnologija

- Laravel
- PHP 8.5
- SQLite
- Blade
- Lokalni razvoj: `php8.5 artisan serve`

---

## Osnovne funkcionalnosti

### APR katalog

- Pregled APR firmi na `/companies`.
- Pretraga po nazivu firme i matičnom broju.
- Filteri po opštini, šifri delatnosti i APR statusu.
- Paginacija i detalj firme na `/companies/{registration_number}`.

### APR API sync

- Preuzimanje APR snapshot-a preko APR Open Data API-ja.
- Lokalno čuvanje snapshot-a pre upisa u bazu.
- Upsert firmi po matičnom broju.
- Čuvanje datuma APR preseka i datuma lokalnog uvoza.
- APR sync ne menja CRM, enrichment ili audit podatke.
- APR sync ne briše firme automatski.

### CRM

- Ručno kreiranje i ažuriranje CRM leada za firmu.
- Website URL, status sajta, telefon, e-mail, lead status, lead score
  i napomene.
- 1:1 veza između firme i CRM leada.
- CRM lista na `/leads` sa pretragom, filterima i sortiranjem.
- Filter kataloga: sve firme, firme sa leadom, firme bez leada.
- Validacija i normalizacija website URL-a.
- Automatsko praćenje datuma poslednje provere sajta i datuma prvog
  kontakta.

### Website audit

- Ručno kreiranje website audita za CRM lead.
- Čuvanje istorije audita po leadu.
- Ručna tehnička provera URL-a.
- Čuvanje HTTP statusa, krajnjeg URL-a, HTTPS statusa, broja redirekcija,
  vremena odgovora, naslova stranice i meta description-a.
- Prikaz istorije audita na detalju firme.

---

## APR komande

### Preuzimanje APR snapshot-a

```bash
php8.5 artisan apr:sync \
  --date=2026-09-30
```

Snapshot se čuva u:

```text
storage/app/apr/snapshots/{YYYYMMDD}/companies.json
```

### Test uvoz

```bash
php8.5 artisan apr:import \
  --file=storage/app/apr/snapshots/20260930/companies.json \
  --limit=5 \
  --batch=500 \
  --write
```

### Pun uvoz

```bash
php8.5 artisan apr:import \
  --file=storage/app/apr/snapshots/20260930/companies.json \
  --limit=134374 \
  --batch=500 \
  --write
```

Bez `--write`, `apr:import` radi u dry-run režimu.

---

## Arhitektura podataka

```text
APR Open Data API
   ↓
apr:sync
   ↓
Lokalni APR snapshot
   ↓
apr:import
   ↓
companies
   ↓
CRM / enrichment / website audit
```

### Tabele

| Tabela | Svrha |
|---|---|
| `companies` | Izvorni APR podaci o firmama |
| `company_leads` | Ručni CRM podaci, 1:1 sa firmom |
| `company_data_points` | Planirani enrichment podaci i website kandidati |
| `company_website_audits` | Istorija website audita, 1:N sa CRM leadom |

### Osnovna pravila

- APR podaci su izvorni registarski podaci.
- CRM podaci se nikada ne prepisuju APR importom.
- Enrichment podaci se čuvaju odvojeno od APR i CRM podataka.
- Website URL ulazi u CRM samo kao potvrđena vrednost.
- Website audit čuva istoriju provera, a ne prepisuje stare zapise.
- AI ne šalje poruke, e-mailove ili ponude bez ručnog odobrenja.

---

## Dokumentacija

- [`PROJECT_STATUS.md`](PROJECT_STATUS.md) — trenutno stanje projekta.
- [`ROADMAP.md`](ROADMAP.md) — plan razvoja i status zadataka.
- [`DECISIONS.md`](DECISIONS.md) — arhitektonske odluke.
- [`APR_DATA_SYNC.md`](APR_DATA_SYNC.md) — APR API sync i uvoz.
- [`docs/company-enrichment.md`](docs/company-enrichment.md) — enrichment
  arhitektura i plan izvora podataka.
- [`docs/crm.md`](docs/crm.md) — CRM modul.
- [`docs/website-audits.md`](docs/website-audits.md) — website audit modul.

---

## Testiranje

Pokreni kompletan test suite:

```bash
php8.5 artisan test
```

Trenutni status:

- 42 testa;
- 112 asercija;
- svi testovi prolaze.

---

## Razvoj

Instalacija zavisnosti:

```bash
composer install
```

Pokretanje lokalnog razvojnog servera:

```bash
php8.5 artisan serve
```

Projekat je verzionisan kroz Git i sinhronizovan sa privatnim GitHub
repozitorijumom.
