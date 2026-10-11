# Firma Finder — Website Audits

## Svrha

Website audit modul služi za proveru i procenu javno dostupnog sajta firme.

Cilj nije automatsko proglašavanje sajta dobrim ili lošim. Cilj je da se
prikupe proverljivi tehnički i vizuelni dokazi koji pomažu da se utvrdi:

- da li sajt postoji i da li se otvara;
- da li radi preko HTTPS-a;
- da li postoji problem sa brzinom, pristupačnošću, SEO osnovama ili
  tehničkim praksama;
- kako izgleda na desktopu i mobilnom prikazu;
- da li postoje konkretne prilike za unapređenje;
- da li je prikladnija manja izmena, unapređenje, redizajn ili novi sajt.

Audit je pomoć za kvalifikaciju leada i pripremu ponude. Ne predstavlja
automatsku konačnu stručnu ili poslovnu ocenu.

---

## Osnovna pravila

1. APR, CRM i audit podaci ostaju odvojeni.
2. Jedan CRM lead može imati više audita kroz vreme.
3. Svaki audit čuva datum, URL i rezultate tog konkretnog trenutka.
4. Stari audit se ne prepisuje kada se pokrene nova provera.
5. Tehnički rezultat, AI procena i ljudska potvrda čuvaju se odvojeno.
6. AI ne sme automatski slati poruku, e-mail ili ponudu.
7. Svaka eksterno poslata poruka mora biti ručno pregledana i odobrena.
8. Sistem ne sme zaobilaziti CAPTCHA, login, rate limit, robots zaštite,
   pristupne zabrane ili druge mere kontrole pristupa.
9. Provere moraju biti ograničene po brzini i obimu.

---

## Planirani model podataka

Planirana tabela:

```text
company_website_audits
```

Planirana relacija:

```text
CompanyLead hasMany CompanyWebsiteAudit
CompanyWebsiteAudit belongsTo CompanyLead
```

Povezivanje preko `company_lead_id` je namerno: audit postoji samo za
firmu koja je postala CRM lead i ima poznat ili predložen URL.

Tačan naziv kolona i relacija potvrđuju se pre izrade migracije.

---

## Planirana polja

### Identitet i tok obrade

| Polje | Primer | Svrha |
|---|---|---|
| `id` | `1` | Primarni ključ |
| `company_lead_id` | `42` | Lead kojem audit pripada |
| `requested_url` | `https://primer.rs` | URL predat na proveru |
| `final_url` | `https://www.primer.rs/` | Krajnji URL nakon redirekcija |
| `status` | `completed` | Stanje audita |
| `audit_version` | `v1` | Verzija audit logike |
| `started_at` | datum i vreme | Početak obrade |
| `completed_at` | datum i vreme | Kraj obrade |
| `error_message` | tekst | Razlog neuspeha, kada postoji |

Predloženi statusi:

```text
queued
running
completed
failed
cancelled
```

### Tehnička dostupnost

| Polje | Primer | Svrha |
|---|---|---|
| `http_status` | `200` | HTTP odgovor krajnjeg URL-a |
| `is_reachable` | `true` | Da li je provera uspela |
| `response_time_ms` | `680` | Vreme odgovora |
| `has_https` | `true` | Da li krajnji URL koristi HTTPS |
| `redirect_count` | `1` | Broj redirekcija |
| `page_title` | `Naziv firme` | Javni naslov stranice |
| `meta_description` | tekst | SEO osnovni podatak, ako postoji |

### Lighthouse rezultati

| Polje | Opseg | Svrha |
|---|---:|---|
| `performance_score` | 0–100 | Laboratorijska ocena performansi |
| `accessibility_score` | 0–100 | Laboratorijska ocena pristupačnosti |
| `best_practices_score` | 0–100 | Laboratorijska ocena tehničkih praksi |
| `seo_score` | 0–100 | Laboratorijska SEO ocena |
| `lighthouse_report_path` | putanja | Sačuvan detaljan Lighthouse izveštaj |

Napomena: Lighthouse rezultat je laboratorijska procena iz jednog
testa, a ne potpuna slika stvarnog iskustva svih korisnika.

### Vizuelni artefakti

| Polje | Primer | Svrha |
|---|---|---|
| `desktop_screenshot_path` | putanja | Snimak desktop prikaza |
| `mobile_screenshot_path` | putanja | Snimak mobilnog prikaza |
| `desktop_viewport` | `1440x900` | Dimenzije desktop provere |
| `mobile_viewport` | `390x844` | Dimenzije mobilne provere |

Snimci su dokaz onoga što je javno bilo prikazano u trenutku provere.

### AI procena

| Polje | Primer | Svrha |
|---|---|---|
| `ai_status` | `completed` | Status AI obrade |
| `ai_model` | naziv modela | Model korišćen za analizu |
| `ai_prompt_version` | `website-audit-v1` | Verzija instrukcije |
| `ai_confidence` | `medium` | Pouzdanost procene |
| `mobile_status` | `poor` | Vizuelna procena mobilnog prikaza |
| `desktop_status` | `good` | Vizuelna procena desktop prikaza |
| `design_status` | `dated` | Vizuelni i profesionalni utisak |
| `conversion_status` | `weak` | Jasnoća CTA i toka do kontakta |
| `recommended_work` | `redesign` | Predloženi tip usluge |
| `priority_score` | `78` | Predloženi prioritet |
| `findings_json` | JSON | Strukturisani pojedinačni nalazi |
| `ai_summary` | tekst | Sažetak procene |
| `recommended_actions` | tekst | Predložene izmene |

Predložene vrednosti:

```text
mobile_status:
unknown
good
poor
broken

desktop_status:
unknown
good
poor
broken

design_status:
unknown
modern
dated
poor

conversion_status:
unknown
strong
adequate
weak

recommended_work:
unknown
none
minor_improvements
improvements
redesign
new_website

ai_confidence:
low
medium
high
```

AI procena mora biti zasnovana na tehničkim rezultatima i screenshotovima,
a ne na pretpostavkama o poslovanju firme.

### Ljudska provera

| Polje | Svrha |
|---|---|
| `reviewed_at` | Datum ručne provere |
| `review_notes` | Korekcije i dopune korisnika |
| `approved_at` | Datum odobrenja nalaza |
| `approval_notes` | Razlog odobrenja ili ograničenja korišćenja |

U početnoj lokalnoj verziji nema korisničkih naloga, pa nije potrebno
dodavati `reviewed_by` i `approved_by`. Ta polja dolaze tek sa
autentikacijom i više korisnika.

---

## Dokaz i tvrdnje

Svaki nalaz treba razlikovati po vrsti:

| Tip | Primer | Kako se koristi |
|---|---|---|
| Tehnička činjenica | `HTTP 404`, `HTTPS nije dostupan` | Može se navesti kao proverena činjenica |
| Automatizovana metrika | Lighthouse SEO `42` | Navodi se sa kontekstom da je laboratorijska procena |
| Vizuelno zapažanje | Tekst je sitan na mobilnom snimku | AI predlog, potvrđuje korisnik |
| Poslovna preporuka | Dodati jači CTA | Predlog, ne predstavlja objektivnu činjenicu |

U ponudi se koriste samo odobreni nalazi i korektne formulacije, na primer:

```text
Na javno dostupnoj početnoj strani primetili smo da je navigacija na
manjem ekranu teško čitljiva. Predlažemo prilagođavanje mobilnog menija
i jasniji put do kontakt forme.
```

Ne koristiti formulacije kao:

```text
Vaš sajt je loš.
Zbog sajta gubite klijente.
Vaš SEO ne radi.
```

osim ako postoji konkretan dokaz i korisnik ručno prilagodi poruku.

---

## Planirani tok rada

```text
1. Korisnik potvrdi ili unese URL u CRM formi.
2. Korisnik ručno pokrene audit za jedan lead.
3. Sistem kreira audit sa statusom queued.
4. Pozadinski posao proverava URL i čuva tehničke rezultate.
5. Sistem pravi desktop i mobilni screenshot.
6. Sistem pokreće Lighthouse i čuva rezultate.
7. AI analizira dokaze i vraća strukturisan rezultat.
8. Audit dobija status completed ili failed.
9. Korisnik pregleda screenshotove, rezultate i AI predloge.
10. Korisnik upisuje review notes i odobrava ili odbacuje nalaze.
11. Tek nakon odobrenja može se generisati nacrt ponude.
```

U prvoj verziji audit se pokreće za jednu firmu ručno. Batch obrada,
queue i zakazani ponovni audit dolaze tek kada pojedinačni audit bude
pouzdan.

---

## Tehnološki plan

Planirani slojevi:

| Sloj | Predlog | Uloga |
|---|---|---|
| Laravel | postojeća aplikacija | CRM, modeli, UI, audit istorija |
| Queue | Laravel job | Pozadinski tok audita |
| HTTP provera | PHP HTTP client ili poseban servis | Dostupnost, redirekcije i status |
| Browser automatizacija | Playwright | Renderovanje i screenshotovi |
| Tehnički audit | Lighthouse | Performance, accessibility, SEO i best practices |
| AI analiza | API izabran kasnije | Strukturisani nalazi iz dokaza |
| Skladištenje | Laravel storage | Screenshotovi i izveštaji |

Pre implementacije treba proveriti lokalne Node.js, Chrome/Chromium i
Lighthouse uslove, kao i način pokretanja Playwright procesa iz Laravel
queue posla.

---

## Van opsega prve verzije

- Masovno automatsko crawl-ovanje svih firmi.
- Zaobilaženje CAPTCHA, autentikacije ili drugih zaštita.
- Automatizovano slanje e-mailova i poruka.
- Automatsko tvrđenje poslovne štete bez provere.
- Potpuna zamena za stručni UX, SEO ili bezbednosni audit.
- Procena svih unutrašnjih stranica sajta u jednom prolazu.
