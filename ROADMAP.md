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

### Tehnička provera URL-a

- [x] `WebsiteAuditService` za ručno pokretanje provere
- [x] Ruta `POST /website-audits/{audit}/run`
- [x] Dugme **Pokreni tehničku proveru** na detalju firme
- [x] Provera dostupnosti URL-a
- [x] HTTP status kod
- [x] Krajnji URL posle redirekcija
- [x] Provera HTTPS-a
- [x] Vreme odgovora
- [x] Izdvajanje naslova stranice
- [x] Izdvajanje meta description-a
- [x] Čuvanje razloga greške kada provera ne uspe
- [x] Feature testovi sa fake HTTP odgovorima

### Company enrichment temelj

- [x] Tabela `company_data_points`
- [x] `CompanyDataPoint` model
- [x] Relacija `Company hasMany CompanyDataPoint`
- [x] Cascade brisanje enrichment zapisa pri brisanju firme
- [x] Podrška za website, e-mail, telefon i adresu
- [x] Konstante za izvore, confidence, licencni status i status zapisa
- [x] Validaciona pravila za enrichment podatke
- [x] Ručno dodavanje enrichment kandidata kroz UI
- [x] Ručna potvrda, odbijanje i označavanje za pregled
- [x] Eksplicitna primena potvrđenog website URL-a u CRM
- [x] Pokretanje website audita iz potvrđenog website kandidata
- [x] Feature testovi za model, relacije, validaciju, UI i audit tok

---

## Sledeće: Enrichment review

### Ručni review audita

- [ ] Dodati `review_status` na website audit
- [ ] Dodati `reviewed_at` i `reviewed_by` na website audit
- [ ] Omogućiti ručno odobravanje audita
- [ ] Omogućiti ručno odbijanje audita
- [ ] Omogućiti označavanje audita za pregled
- [ ] Prikazati review status u website audit istoriji
- [ ] Dodati testove za ručni review audita

---

## Sledeće: Automatizovani enrichment

### Identitet firme

- [ ] Definisati pravila identiteta firme preko MB/PIB
- [ ] Definisati scoring identitet match-a
- [ ] Podržati `unmatched` zapise kada MB/PIB ne postoje u APR bazi
- [ ] Sprečiti automatsku potvrdu samo na osnovu naziva firme

### OpenStreetMap

- [ ] Integrirati OSM enrichment za jednu firmu
- [ ] Pretraživati firmu po nazivu i opštini
- [ ] Čuvati OSM kandidate u `company_data_points`
- [] Označavati OSM zapise kao `candidate` ili `needs_review`
- [ ] Čuvati izvorni URL i licencni status
- [ ] Dodati testove sa fake OSM odgovorima

### Wikidata

- [ ] Integrirati Wikidata kao dopunski izvor
- [ ] Koristiti property `P856 — official website`
- [ ] Čuvati Wikidata kandidate u `company_data_points`
- [ ] Označavati Wikidata zapise kao `candidate` ili `needs_review`
- [ ] Dodati testove sa fake Wikidata odgovorima

### CompanyWall

- [ ] Dobiti pisanu potvrdu uslova korišćenja
- [ ] Potvrditi da API ili izvoz sadrži website URL
- [ ] Potvrditi način povezivanja preko MB/PIB
- [ ] Potvrditi format JSON/CSV odgovora
- [ ] Potvrditi cene, kvote i rate limite
- [ ] Potvrditi uslove čuvanja podataka u CRM-u
- [ ] Integrirati CompanyWall tek nakon potvrde uslova

### Automatska verifikacija

- [ ] Proveravati da li je potvrđeni URL dostupan
- [ ] Proveravati HTTPS i konačni URL
- [ ] Proveravati naziv firme u domenu, naslovu ili sadržaju
- [ ] Detektovati sukobe između više izvora
- [ ] Automatski verifikovati samo jaka poklapanja i dozvoljene izvore
- [ ] Slabe ili sporne vrednosti slati u `needs_review`

---

## CRM završetak

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
- [x] Testovi za ručnu tehničku proveru URL-a

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

- [x] Provera dostupnosti URL-a
- [x] HTTP status kod
- [x] Krajnji URL posle redirekcija
- [x] Provera HTTPS-a
- [x] Vreme odgovora
- [x] Razlog greške, kada provera ne uspe
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

- [ ] Nacrt personalizovane ponude zasnovane na odobrenim nalazima
- [ ] Izbor tona poruke
- [ ] Ručna izmena nacrta
- [ ] Ručno odobrenje pre bilo kakvog slanja
- [ ] Istorija nacrta i odobrenja
- [ ] Evidencija poslatog kontakta
- [ ] Kasnija faza i integracija e-mail slanja, samo uz eksplicitnu potvrdu

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
