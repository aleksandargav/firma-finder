# Firma Finder — Architecture Decisions

Ovaj dokument beleži važne arhitektonske odluke i razloge iza njih.

---

## 2026-10-11 — APR i CRM podaci su strogo odvojeni

### Odluka

Izvorni APR podaci čuvaju se u tabeli `companies`.

Ručni CRM podaci čuvaju se u tabeli `company_leads`.

### Razlog

APR podaci predstavljaju uvezeni snimak zvaničnog registra i ne smeju se
mešati sa ručnim beleškama, kontaktima, URL-ovima i prodajnim statusima.

CRM podaci se često menjaju, dok APR uvoz može biti ponovljen sa novijim
presekom podataka.

### Posledica

- APR import ne sme prepisivati CRM podatke.
- CRM forme i budući audit podaci ne menjaju tabelu `companies`.
- Buduće website provere čuvaju se u posebnoj tabeli, a ne u `companies`.

---

## 2026-10-11 — Company i CompanyLead imaju 1:1 odnos

### Odluka

Jedna firma može imati najviše jedan aktivni CRM lead zapis.

Relacija je definisana kao:

```text
Company hasOne CompanyLead
CompanyLead belongsTo Company
```

Baza garantuje pravilo kroz jedinstveni indeks:

```text
UNIQUE(company_leads.company_id)
```

### Razlog

Aplikacija trenutno vodi jedan CRM karton po firmi.

Istorija provera sajta i kasnije istorija komunikacije nisu isto što i
trenutni CRM karton i ne treba ih dodavati kao dodatne lead zapise.

### Posledica

- Za postojeći lead koristi se ažuriranje, a ne kreiranje drugog reda.
- Budući auditi sajta dobijaju posebnu tabelu sa relacijom 1:N prema firmi
  ili leadu.
- Buduća komunikacija takođe može dobiti zasebnu istorijsku tabelu.

---

## 2026-10-11 — Datum prvog kontakta je istorijski podatak

### Odluka

Polje `contacted_at` automatski se popunjava kada lead prvi put ima
status `contacted`.

Kada se status kasnije promeni u `qualified`, `rejected` ili `converted`,
datum prvog kontakta se ne briše i ne menja.

### Razlog

Datum prvog kontakta predstavlja činjenicu iz istorije odnosa sa firmom,
a ne trenutni status leada.

### Posledica

`CompanyLeadObserver` postavlja datum samo kada važi:

```text
lead_status = contacted
i
contacted_at je prazan
```

---

## 2026-10-11 — Statusi se čuvaju kao stabilni interni ključevi

### Odluka

U bazi se čuvaju stabilne engleske vrednosti kao što su:

```text
unknown
active
inactive
new
in_progress
contacted
qualified
rejected
converted
```

U interfejsu se prikazuju srpski nazivi kroz konstante modela.

### Razlog

Interne vrednosti ostaju stabilne ako se kasnije promeni jezik interfejsa
ili formulacija oznake.

### Posledica

- Validacija koristi `Rule::in(array_keys(...))`.
- Blade prikazi koriste konstante za korisnički naziv statusa.
- Filteri i budući API ne zavise od prikazanog teksta.

---

## 2026-10-11 — Website audit je zasebna istorijska celina

### Odluka

Budući website audit neće biti samo nekoliko novih polja na `company_leads`.

Koristiće se zasebna tabela, planiranog naziva `company_website_audits`.

### Razlog

Sajt može promeniti URL, tehničko stanje ili dizajn. Potrebno je sačuvati
istoriju provera, tehničke rezultate, screenshotove, AI nalaze i ručne
recenzije, bez prepisivanja starijih nalaza.

### Posledica

Planirana relacija će biti približno:

```text
CompanyLead hasMany CompanyWebsiteAudit
CompanyWebsiteAudit belongsTo CompanyLead
```

Tačan model se potvrđuje pre implementacije.

---

## 2026-10-11 — AI je pomoćnik, ne automatski prodajni agent

### Odluka

AI može analizirati tehničke nalaze i screenshotove, predložiti tip usluge
i generisati nacrt poruke ili ponude.

AI neće automatski slati e-mailove, poruke ili ponude.

### Razlog

AI procene mogu biti nepotpune ili netačne. Svaka eksterno poslata poruka
mora biti zasnovana na stvarnim dokazima i ručno odobrena.

### Posledica

Budući tok rada će biti:

```text
Tehnički audit + screenshotovi
→ AI strukturisana procena
→ Ručna provera nalaza
→ Nacrt ponude
→ Ručno odobrenje
→ Slanje
```

---

## 2026-10-14 — Website URL se validira i normalizuje

### Odluka

Korisnički unet website URL se validira pre čuvanja.

Ako URL nema protokol, automatski se dodaje `https://`.

Ako URL već ima `http://` ili `https://`, protokol ostaje nepromenjen.

URL bez validnog domena se ne čuva.

### Razlog

Normalizacija sprečava nekonzistentne zapise poput `example.com`,
`www.example.com` i `https://example.com` za isti sajt.

Zadržavanje postojećeg protokola je važno jer neki sajtovi možda još uvek
nisu dostupni preko HTTPS-a.

### Posledica

- URL se čuva u normalizovanom obliku.
- Validacija proverava da li vrednost predstavlja validan URL.
- Neispravan URL se odbacuje pre kreiranja ili ažuriranja leada.

---

## 2026-10-14 — Datum provere sajta se ažurira automatski

### Odluka

Polje `website_checked_at` automatski se postavlja kada se kreira lead
ili kada se promeni `website_status`.

### Razlog

Datum provere predstavlja trenutak kada je korisnik potvrdio ili promenio
trenutno stanje sajta, a ne trenutak kreiranja CRM zapisa.

### Posledica

- `website_checked_at` ne unosi korisnik ručno.
- Datum se ažurira samo pri promeni statusa sajta.
- Prikaz datuma poslednje provere daje kontekst za ručnu procenu sajta.

---

## 2026-10-14 — Website enrichment je odvojen od APR i CRM podataka

### Odluka

APR JSON izvor ne sadrži website URL. Website i drugi kontakt podaci
prikupljaju se kroz zaseban enrichment sloj.

Enrichment podaci čuvaju se u tabeli `company_data_points`.

Svaki zapis ima:

- izvor podataka;
- identitet match;
- licencni status;
- confidence;
- status potvrde.

### Razlog

APR daje pouzdan identitet firme, ali ne website. CompanyWall, OSM,
Wikidata i budući izvori mogu dati različite kandidate, pa je potrebno
znati odakle podatak dolazi, da li je dozvoljen i da li je potvrđen.

### Posledica

- APR podaci se ne menjaju enrichment podacima.
- CompanyWall, OSM, Wikidata i budući izvori koriste isti model.
- Automatska potvrda je dozvoljena samo za jaka poklapanja i dozvoljene izvore.
- Sporne vrednosti ostaju kandidati za ručni pregled.
- Lighthouse i screenshotovi rade samo nad potvrđenim website URL-om.

---

## 2026-10-14 — CompanyWall se ne integriše bez pisane potvrde uslova

### Odluka

CompanyWall se ne koristi za automatsko prikupljanje podataka dok ne budu
pisano potvrđeni:

- da li njihov Web API ili izvoz sadrži website URL;
- kako se firma povezuje preko MB/PIB;
- format JSON/CSV odgovora;
- cene, paketi, kvote i rate limiti;
- uslovi čuvanja podataka u internom CRM-u;
- uslovi i dozvola za scraping, ako se on koristi.

### Razlog

CompanyWall je komercijalna bonitetna platforma. Njihovi uslovi korišćenja
zabranjuju automatizovano prikupljanje podataka bez saglasnosti.

### Posledica

- Ne pravi se sopstveni scraper bez pisane saglasnosti.
- CompanyWall podaci, kada budu dostupni, ulaze kao `companywall` izvor.
- Website URL se prvo čuva kao kandidat.
- Automatska potvrda je moguća samo ako je licencni status dozvoljen i
  MB/PIB se pouzdano poklapa sa APR firmom.

---

## 2026-10-14 — APR sync koristi snapshot i upsert

### Odluka

APR podaci se preuzimaju preko APR Open Data API-ja u lokalni JSON
snapshot, a zatim se uvoze u tabelu `companies` kroz `apr:import`.

Import koristi upsert po matičnom broju firme.

### Razlog

Odvojeno čuvanje snapshot-a omogućava ponovljiv uvoz, lakše otklanjanje
grešaka i testiranje bez ponovnog pozivanja API-ja.

Upsert po matičnom broju sprečava dupliranje firmi pri ponovljenom
uvozu istog preseka.

### Posledica

- `apr:sync` ne upisuje direktno u bazu.
- `apr:import` je jedina faza koja menja tabelu `companies`.
- APR import ažurira samo izvorne APR podatke.
- CRM, enrichment i audit podaci ostaju netaknuti.
- `source_snapshot_date` jasno označava APR presek svakog zapisa.
- `imported_at` beleži vreme poslednjeg lokalnog uvoza.

---

## 2026-10-14 — APR sync ne briše firme automatski

### Odluka

APR sync ne briše firme koje nisu prisutne u novom API snapshot-u.

### Razlog

Nestanak firme iz jednog preseka ne mora automatski značiti da lokalni
CRM karton, istorija rada ili audit podaci više nisu relevantni.

### Posledica

- Lokalni broj firmi može biti veći od broja zapisa u novom snapshot-u.
- Eventualno označavanje neaktivnih ili uklonjenih firmi biće zasebna
  odluka i zasebna migracija.
- CRM podaci se ne brišu kao posledica APR sync-a.

---

## 2026-10-14 — Potvrđeni enrichment URL se u CRM primenjuje ručno

### Odluka

Potvrđeni website kandidat iz `company_data_points` može se primeniti u CRM
samo eksplicitnom korisničkom akcijom.

### Razlog

Enrichment izvor može da ponudi URL, ali ne može sam da odluči da li taj URL
treba da zameni postojeći CRM podatak.

### Posledica

- Primenom se ažuriraju samo `website_url`, `website_status`
  i `website_checked_at`.
- `lead_status`, `lead_score`, telefon, e-mail i napomene se ne menjaju.
- APR podaci se ne menjaju.
- Enrichment zapis ostaje `verified`.

---

## 2026-10-14 — Website audit iz enrichment kandidata zahteva CRM lead

### Odluka

Website audit iz potvrđenog enrichment kandidata može se pokrenuti samo ako
firma već ima CRM lead.

### Razlog

Audit pripada CRM leadu i postojećem toku provere sajta.

### Posledica

- Audit se kreira u postojećoj tabeli `company_website_audits`.
- `requested_url` se uzima iz potvrđenog enrichment kandidata.
- Enrichment zapis se ne menja pokretanjem audita.
- Automatska verifikacija identiteta firme još uvek nije implementirana.

---

## Pravilo za buduće izmene šeme

Kada je Laravel migracija već izvršena, ne menja se postojeći migration
fajl.

Svaka promena postojeće tabele radi se novom migracijom.

Razlog: lokalne i buduće baze moraju moći konzistentno da pređu kroz celu
istoriju šeme.
