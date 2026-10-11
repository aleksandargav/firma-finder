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

## Pravilo za buduće izmene šeme

Kada je Laravel migracija već izvršena, ne menja se postojeći migration
fajl.

Svaka promena postojeće tabele radi se novom migracijom.

Razlog: lokalne i buduće baze moraju moći konzistentno da pređu kroz celu
istoriju šeme.
