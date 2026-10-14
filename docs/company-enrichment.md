# Firma Finder — Company Enrichment

## Svrha

Company enrichment modul služi za pronalaženje, proveru i potvrdu dodatnih
podataka o firmama iz APR registra, pre svega website URL-a.

APR JSON izvor koji se trenutno koristi ne sadrži website URL. Provereno je
da svih 134.374 zapisa ima samo sledeća polja:

- `PoslovnoIme`;
- `SifraOpstine`;
- `NazivOpstine`;
- `NazivStatus`;
- `DatumOsnivanja`;
- `NazivPravneForme`;
- `SifraDelatnosti`.

Zato se website i drugi kontakt podaci prikupljaju kroz zaseban enrichment
sloj, a ne kroz izmenu APR podataka.

---

## Osnovna pravila

1. APR, CRM, enrichment i website audit podaci ostaju odvojeni.
2. APR podaci u `companies` se nikada ne menjaju enrichment podacima.
3. Enrichment podaci ne smeju automatski da prepisuju CRM odluke.
4. Website URL se u CRM vodi samo kao potvrđena vrednost.
5. Svaki enrichment podatak mora imati poznat izvor.
6. Svaki izvor mora imati proveren licencni/pravni status korišćenja.
7. Sistem ne sme da zaobiđe CAPTCHA, login, rate limit, robots zaštite ili
   druge mere kontrole pristupa.
8. Masovno prikupljanje podataka radi se samo kroz dozvoljene izvore,
   ugovorene API-je ili zvanične izvoze.
9. AI i automatika mogu da predlože podatak, ali konačnu odluku uvek
   potvrđuje korisnik kada postoji bilo kakva sumnja.

---

## Temeljni problem

APR registar daje pouzdan identitet firme, ali ne daje njen website.

CompanyWall i slični servisi mogu dati website, ali je potrebno potvrditi:

- da li njihov API ili izvoz sadrži website URL;
- kako se firma povezuje preko matičnog broja (MB) ili PIB-a;
- koje su cene, kvote i rate limiti;
- da li je dozvoljeno čuvanje podataka u internom CRM-u;
- da li je dozvoljen scraping, i pod kojim uslovima.

OpenStreetMap i Wikidata su besplatni dopunski izvori, ali ne znaju APR
matični broj i ne mogu sami da pouzdano potvrde identitet firme.

---

## Arhitektura

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
Enrichment izvori
   ├── Ručni unos
   ├── CompanyWall
   ├── OpenStreetMap
   ├── Wikidata
   ├── Google Places
   └── Budući regionalni izvori
   ↓
company_data_points
   ↓
Automatska provera i rangiranje kandidata
   ↓
Ručni pregled spornih slučajeva
   ↓
Potvrđen website URL
   ↓
company_leads.website_url
   ↓
Website audit / Lighthouse / screenshot
```

APR Open Data API je trenutno implementiran kao izvor identiteta firme.
On osvežava lokalni APR katalog, ali ne dodaje website ni kontakt podatke.

---

## Izvori podataka

| Izvor | Interna vrednost | Šta može da da | Status |
|---|---|---|---|
| Ručni unos | `manual` | Website, telefon, e-mail, napomene | Implementiran |
| CompanyWall | `companywall` | Website, kontakti, MB/PIB | Čeka se potvrda uslova |
| OpenStreetMap | `openstreetmap` | Website, telefon, e-mail, lokacija | Planiran |
| Wikidata | `wikidata` | Zvanični sajt za poznate subjekte | Planiran |
| Google Places | `google_places` | Website, telefon, adresa, kategorija | Opciono, plaćeno |
| APR Open Data | `apr` | MB, naziv, status, delatnost, datum preseka | Implementiran kao identitet izvor |

APR Open Data API je povezan kroz `AprApiService`, komandu `apr:sync` i
postojeći `apr:import`. Lokalni APR katalog se ažurira upsertom po
matičnom broju firme.

CompanyWall, ako potvrdi pristup i website podatak, treba da bude
prioritetni automatski izvor jer omogućava povezivanje preko MB/PIB.

OpenStreetMap je besplatan izvor za lokalne biznise, ali pokrivenost zavisi
od toga da li je biznis mapiran u OSM-u.

Wikidata koristi property `P856 — official website` i koristan je pre svega
za veće ili poznate kompanije.

---

## Identitet firme

Identitet firme se ne određuje samo po nazivu.

Prioritet uparivanja je:

1. MB iz enrichment izvora se poklapa sa APR `registration_number`;
2. PIB iz enrichment izvora se poklapa sa APR PIB-om;
3. MB i PIB se poklapaju;
4. naziv + opština/grad služe samo kao dodatni signal;
5. naziv nikada nije dovoljan sam po sebi za automatsku potvrdu.

Ako enrichment izvor vrati MB ili PIB koji ne postoji u APR bazi, zapis se
čuva kao `unmatched` i ne povezuje se sa CRM leadom.

APR sync trenutno koristi matični broj kao stabilan ključ za upsert
lokalnih APR zapisa.

---

## Model podataka

Tabela:

```text
company_data_points
```

Relacija:

```text
Company hasMany CompanyDataPoint
CompanyDataPoint belongsTo Company
```

Polja:

| Polje | Tip | Svrha |
|---|---|---|
| `id` | primarni ključ | Identifikator zapisa |
| `company_id` | foreign key, nullable | APR firma ako je match pronađen |
| `external_identifier` | string, nullable | MB, PIB, Place ID ili drugi spoljni ID |
| `identifier_type` | string, nullable | `registration_number`, `pib`, `place_id`, `name_address` |
| `field` | string | `website`, `email`, `phone`, `address` |
| `value` | text | Nađena vrednost |
| `source` | string | Izvor podatka |
| `source_url` | string, nullable | URL izvora ili profila firme |
| `confidence` | string | `high`, `medium`, `low` |
| `identity_match_score` | integer | 0–100 |
| `data_license_status` | string | Licencni status izvora |
| `status` | string | Status potvrde zapisa |
| `retrieved_at` | timestamp, nullable | Kada je podatak preuzet |
| `verified_at` | timestamp, nullable | Kada je podatak potvrđen |
| `verified_by` | string, nullable | `system` ili `user` |
| `notes` | text, nullable | Internе napomene |
| `created_at` | timestamp | Datum kreiranja |
| `updated_at` | timestamp | Datum poslednje izmene |

`company_id` koristi `cascadeOnDelete`. Brisanje APR firme briše i njene
enrichment zapise.

---

## Dozvoljene vrednosti

### Tip podatka

- `website`
- `email`
- `phone`
- `address`

### Izvor

- `manual`
- `companywall`
- `openstreetmap`
- `wikidata`
- `google_places`
- `apr`

### Confidence

- `high`
- `medium`
- `low`

### Licencni status

- `allowed`
- `restricted`
- `forbidden`
- `unknown`

### Status enrichment zapisa

- `candidate`
- `verified`
- `rejected`
- `unmatched`
- `needs_review`

### Tip identifikatora

- `registration_number`
- `pib`
- `place_id`
- `name_address`

---

## Statusi enrichment zapisa

| Interna vrednost | Prikaz | Značenje |
|---|---|---|
| `candidate` | Kandidat | Podatak je pronađen, ali nije potvrđen |
| `verified` | Potvrđeno | Podatak je proveren i može se koristiti |
| `rejected` | Odbijeno | Podatak je odbijen |
| `unmatched` | Nije upareno | Izvor nije povezan sa poznatom APR firmom |
| `needs_review` | Za pregled | Postoji sukob ili nedovoljno jak signal |

---

## Nivoi poverenja

| Nivo | Značenje | Primer |
|---|---|---|
| `high` | Jak identitet i pouzdan izvor | CompanyWall MB/PIB match |
| `medium` | Delimično poklapanje | OSM naziv + opština |
| `low` | Slab ili nesiguran signal | Samo sličan naziv |

`confidence` ne zamenjuje licencni status. Podatak može biti visokog
poverenja, ali nedozvoljen za korišćenje ako izvor to ne dozvoljava.

---

## Licencni status izvora

| Vrednost | Značenje |
|---|---|
| `allowed` | Podatak sme da se čuva i koristi u internom CRM-u |
| `restricted` | Podatak se koristi uz ograničenja, npr. atribuciju ili ugovor |
| `forbidden` | Podatak se ne sme koristiti |
| `unknown` | Uslovi još nisu potvrđeni |

Dok CompanyWall ne potvrdi uslove, njegov izvor ostaje `unknown` ili
`restricted`, zavisno od njihovog odgovora.

---

## Ručni enrichment tok

Trenutno je implementiran ručni unos i pregled enrichment kandidata.

```text
Korisnik bira tip podatka
→ unosi vrednost, confidence i licencni status
→ sistem validira i normalizuje vrednost
→ kreira se candidate
→ korisnik potvrđuje, odbija ili označava za pregled
```

Podržani tipovi podataka su:

- `website`;
- `email`;
- `phone`;
- `address`.

Website URL se normalizuje tako što mu se po potrebi dodaje `https://`.
URL bez validnog domena se odbacuje.

E-mail adresa se validira pre čuvanja.

---

## Primena potvrđenog website URL-a u CRM

Potvrđeni website kandidat može se primeniti u CRM samo eksplicitnom
korisničkom akcijom.

```text
Website data point
→ status verified
→ korisnik klikće „Primeni u CRM“
→ company_leads.website_url se ažurira
```

Ako firma nema CRM lead, kreira se novi lead sa:

- `website_url` = potvrđeni URL;
- `website_status = active`;
- `lead_status = new`;
- `lead_score = 0`;
- `website_checked_at = now()`.

Ako firma već ima CRM lead, menjaju se samo:

- `website_url`;
- `website_status`;
- `website_checked_at`.

Ne menjaju se:

- `lead_status`;
- `lead_score`;
- `phone`;
- `email`;
- `notes`.

APR podaci se ne menjaju.

---

## Website audit iz enrichment kandidata

Za potvrđeni website kandidat, kod firme koja već ima CRM lead, može se
pokrenuti website audit.

```text
Verified website data point
→ korisnik klikće „Pokreni website audit“
→ kreira se CompanyWebsiteAudit
→ WebsiteAuditService proverava URL
→ rezultat se čuva u company_website_audits
```

Audit se može pokrenuti samo ako:

- data point je tipa `website`;
- data point ima status `verified`;
- firma ima CRM lead.

Audit koristi postojeći `WebsiteAuditService` i čuva:

- URL provere;
- krajnji URL;
- HTTP status;
- dostupnost;
- HTTPS status;
- broj redirekcija;
- vreme odgovora;
- naslov stranice;
- meta description;
- datum početka i završetka;
- grešku, ako je bilo.

Enrichment zapis se ne menja pokretanjem audita.

---

## Automatska verifikacija

Website URL može automatski da postane `verified` samo ako su ispunjeni
svi uslovi:

1. izvor ima dozvoljen licencni status;
2. MB i/ili PIB se poklapa sa APR firmom;
3. URL je validan;
4. URL je dostupan i vraća očekivan HTTP odgovor;
5. URL ne vodi na parkirani domen ili očigledno nepovezan sajt;
6. naziv firme se pojavljuje u domenu, naslovu stranice ili sadržaju;
7. ne postoji sukob sa drugim jakim izvorom.

Ako bilo koji uslov nedostaje, zapis ostaje `candidate` ili prelazi u
`needs_review`.

Automatska verifikacija trenutno nije implementirana.

---

## Primer uspešnog toka

```text
APR firma:
- ELEKTROHEM DOO
- MB: 17204106

CompanyWall:
- MB: 17204106
- Website: [https://elektrohem.rs](https://elektrohem.rs)

Provera:
- MB match: da
- URL radi: da
- HTTPS: da
- Naziv firme u sadržaju: da
- Licenca: dozvoljena

Rezultat:
- status: verified
- verified_by: system
- CRM website_url: [https://elektrohem.rs](https://elektrohem.rs)
- CRM website_status: active
```

---

## Primer spornog slučaja

```text
CompanyWall:
- MB: 17204106
- Website: [https://elektrohem.rs](https://elektrohem.rs)

OpenStreetMap:
- Naziv: Elektrohem
- Website: [https://elektrohem-trade.rs](https://elektrohem-trade.rs)

Rezultat:
- status: needs_review
- razlog: dva izvora vraćaju različite website URL-ove
```

Korisnik tada ručno bira tačan URL ili odbacuje oba kandidata.

---

## CompanyWall — trenutni status

CompanyWall je kontaktiran radi mogućeg pristupa podacima.

Potrebno je dobiti pisanu potvrdu za:

1. da li njihov Web API ili izvoz sadrži website URL;
2. kako se firma povezuje preko MB/PIB;
3. primer JSON/CSV odgovora;
4. cene, pakete, kvote i rate limite;
5. uslove čuvanja podataka u internom CRM-u;
6. da li je dozvoljen scraping i pod kojim uslovima.

Dok ti uslovi nisu potvrđeni, CompanyWall se ne integriše i ne koristi se
za automatsko prikupljanje podataka.

---

## APR Open Data — trenutni status

APR Open Data API je implementiran kao izvor identiteta firme.

Implementirano je:

- `AprApiService` za preuzimanje APR snapshot-a;
- `apr:sync` za preuzimanje kompletnog snapshot-a;
- lokalno čuvanje snapshot-a u `storage/app/apr/snapshots/`;
- `apr:import` za upis ili ažuriranje firmi u `companies`;
- upsert po matičnom broju;
- čuvanje datuma APR preseka u `source_snapshot_date`;
- čuvanje datuma lokalnog uvoza u `imported_at`.

APR sync ne dodaje website URL, telefon, e-mail ili bilo koji enrichment
podatak. Njegova uloga je da lokalni APR identitet firme bude ažuran.

---

## Regionalni plan

Prva verzija enrichment sistema razvija se za Srbiju.

Kasnije se dodaju adapteri po zemljama:

| Zemlja | Izvor / pristup | Status |
|---|---|---|
| Srbija | APR Open Data API + enrichment izvori | APR identitet izvor implementiran |
| Slovenija | AJPES / PRS | Kasnije |
| Bugarska | CompanyBook.BG | Kasnije |
| Crna Gora | CompanyWall ili komercijalni izvor | Kasnije |
| Bosna i Hercegovina | BIFIDEX / lokalni registri | Kasnije |
| Albanija | BIFIDEX / lokalni registri | Kasnije |
| Severna Makedonija | BIFIDEX / lokalni registri | Kasnije |

Svaka zemlja treba da ima svoj adapter, ali svi adapteri vraćaju isti
normalizovani format podataka.

---

## Šta se ne radi

- Ne masovno se skrejpuje CompanyWall bez pisane saglasnosti.
- Ne pogađa se domen po nazivu firme, npr. `nazivfirme.rs`.
- Ne ubacuje se automatski URL u CRM bez dovoljno jakog poklapanja.
- Ne menjaju se APR podaci enrichment podacima.
- Ne koriste se izvori koji zabranjuju automatizovano prikupljanje.
- Ne vrši se masovno crawl-ovanje svih firmi u prvoj verziji.
- Ne šalju se poruke, e-mailovi ili ponude bez ručnog odobrenja.
- APR sync ne briše firme koje nestanu iz novijeg preseka.

---

## Plan implementacije

1. [x] Kreirati `company_data_points` tabelu i `CompanyDataPoint` model.
2. [x] Dodati relaciju `Company hasMany CompanyDataPoint`.
3. [x] Dodati konstante za izvore, statusе, confidence i licencni status.
4. [x] Omogućiti ručni unos i potvrdu website kandidata.
5. [ ] Dodati ručni review website audit nalaza.
6. [ ] Dodati OSM enrichment za jednu firmu.
7. [ ] Dodati Wikidata enrichment kao dopunski izvor.
8. [ ] Integrirati CompanyWall nakon potvrde uslova.
9. [ ] Dodati pravila auto-verifikacije.
10. [ ] Dodati regionalne adaptere.
11. [ ] Tek nakon pouzdanih potvrđenih URL-ova razvijati Lighthouse i
    screenshot modul.
