# Firma Finder — Roadmap

## Princip rada

Razvoj ide postepeno:

1. stabilan model podataka;
2. ručni CRM tok;
3. testovi i zaštite;
4. objektivni tehnički audit;
5. vizuelni audit;
6. AI analiza zasnovana na dokazima;
7. nacrt ponude uz ručno odobrenje;
8. tek zatim automatizacija većeg obima.

AI nije zamena za proveru. Služi za ubrzanje procene, prioritizaciju i
pripremu nacrta, dok konačnu odluku i slanje uvek potvrđuje korisnik.

---

## Završeno

### APR katalog

- [x] Lokalni APR katalog firmi
- [x] Uvoz 125.054 firmi
- [x] Pretraga po nazivu firme i matičnom broju
- [x] Filter po opštini
- [x] Filter po šifri delatnosti
- [x] Filter po APR statusu
- [x] Paginacija
- [x] Detalj firme

### CRM osnova

- [x] Tabela `company_leads`
- [x] 1:1 veza `Company → CompanyLead`
- [x] Ručno kreiranje CRM leada
- [x] Ručna izmena CRM leada
- [x] URL sajta, telefon, e-mail i napomene
- [x] Status sajta
- [x] Status leada
- [x] Lead score
- [x] Automatski datum prvog kontakta
- [x] CRM lista na `/leads`
- [x] Filteri CRM liste
- [x] Sortiranje CRM liste
- [x] CRM status u katalogu firmi
- [x] Dokumentacija početnog stanja
- [x] CRM filter na katalogu firmi
- [x] Validacija i normalizacija website URL-a
- [x] Automatski datum poslednje provere sajta

### Website audit osnova

- [x] Tabela `company_website_audits`
- [x] `CompanyWebsiteAudit` model
- [x] Relacija `CompanyLead hasMany CompanyWebsiteAudit`
- [x] Relacija `CompanyWebsiteAudit belongsTo CompanyLead`
- [x] Mogućnost više audita za isti lead
- [x] Cascade brisanje audita pri brisanju leada
- [x] Statusi audita: `pending`, `completed`, `failed`
- [x] Prikaz istorije audita na detalju firme
- [x] Ručna forma za kreiranje website audita
- [x] Čuvanje URL provere, krajnjeg URL-a, HTTP statusa, dostupnosti,
  HTTPS statusa, broja redirekcija, vremena odgovora, naslova stranice,
  meta description-a, napomena i greške
- [x] Feature testovi za model, relacije, cascade brisanje, validaciju
  i UI tok

---

## Sledeće: CRM završetak

### CRM katalog

- [x] Filter: sve firme / samo firme sa leadom / samo firme bez leada
- [ ] Opcioni filter prema konkretnom CRM statusu direktno na katalogu
- [ ] Jasniji vizuelni statusi za različite faze leada
- [ ] Prikaz ili filter po lead score-u

### Validacija i UX

- [x] Validirati URL sajta kao stvarni URL, uz normalizaciju protokola
- [x] Automatski postaviti `website_checked_at` pri ručnoj promeni statusa sajta
- [x] Uvesti jasniju oznaku da li je URL proveravan i kada
- [ ] Omogućiti bezbedno brisanje CRM leada uz potvrdu, ako se pokaže potrebno

### Testovi

- [x] Feature test za kreiranje leada
- [x] Feature test za ažuriranje leada
- [x] Test validacije statusa i lead score-a
- [x] Test 1:1 ograničenja nad `company_id`
- [x] Test `CompanyLeadObserver` ponašanja za `contacted_at`
- [x] Test filtera CRM liste
- [x] Test filtera CRM stanja na katalogu
- [x] Testovi za URL validaciju i normalizaciju
- [x] Testovi za automatsko `website_checked_at`
- [x] Testovi za `CompanyWebsiteAudit` model, relacije i cascade brisanje
- [x] Testovi za ručno kreiranje website audita kroz UI

---

## Website audit bez AI-ja

### Model i istorija

- [x] Kreirati tabelu `company_website_audits`
- [x] Dodati relacije između leada i više audita
- [x] Prikaz liste prethodnih audita na detalju firme
- [x] Ručni unos audita i nalaza
- [x] Čuvanje datuma provere, URL-a i konačnog URL-a
- [x] Čuvanje statusa uspeha/neuspeha audita
- [ ] Ručna potvrda nalaza

### Tehnička provera

- [ ] Provera dostupnosti URL-a
- [ ] HTTP status kod
- [ ] Krajnji URL posle redirekcija
- [ ] Provera HTTPS-a
- [ ] Vreme odgovora
- [ ] Razlog greške, kada provera ne uspe
- [ ] Rate limiting i ograničenje paralelnih provera
- [ ] Audit queue/job infrastruktura

### Lighthouse i screenshotovi

- [ ] Lighthouse rezultat za performance
- [ ] Lighthouse rezultat za accessibility
- [ ] Lighthouse rezultat za best practices
- [ ] Lighthouse rezultat za SEO
- [ ] Desktop screenshot
- [ ] Mobile screenshot
- [ ] Lokalno skladištenje audit artefakata
- [ ] Pregled artefakata na stranici firme

---

## AI audit

- [ ] Izabrati AI provajdera i model
- [ ] Definisati format strukturisanog AI odgovora
- [ ] AI analiza tehničkih rezultata i screenshotova
- [ ] Ocena desktop prikaza
- [ ] Ocena mobilnog prikaza
- [ ] Ocena dizajna i jasnoće poruke
- [ ] Ocena CTA elemenata i konverzionih prilika
- [ ] Preporučeni tip rada:
    - manje izmene;
    - unapređenje postojećeg sajta;
    - redizajn;
    - novi sajt.
- [ ] AI confidence oznaka
- [ ] Ručna potvrda ili odbacivanje AI nalaza
- [ ] Čuvanje verzije prompta i modela korišćenog za audit

---

## Ponude i kontakt

- [ ] Nacrt personalizovane ponude zasnovan na odobrenim nalazima
- [ ] Izbor tona poruke
- [ ] Ručna izmena nacrta
- [ ] Ručno odobrenje pre bilo kakvog slanja
- [ ] Istorija nacrta i odobrenja
- [ ] Evidencija poslatog kontakta
- [ ] Naknadna faza i integracija e-mail slanja, samo uz eksplicitnu potvrdu

---

## Kasnije

- [ ] Autentikacija i više korisnika
- [ ] Uloge i dozvole
- [ ] Izvoz leadova i audita
- [ ] Dashboard sa CRM metrikama
- [ ] Dnevni/periodični ponovni audit
- [ ] Batch obrada odabranih firmi
- [ ] Konfigurabilni scoring model
- [ ] Deployment iz lokalnog okruženja
