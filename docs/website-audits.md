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

## Implementirani model podataka

Tabela:

```text
company_website_audits
```

Relacija:

```text
CompanyLead hasMany CompanyWebsiteAudit
CompanyWebsiteAudit belongsTo CompanyLead
```

Povezivanje preko `company_lead_id` je namerno: audit postoji samo za
firmu koja je postala CRM lead i ima poznat ili predložen URL.

Brisanje CRM leada briše i sve njegove website audite kroz
`cascadeOnDelete`.

Prva implementirana verzija koristi užu osnovnu šemu. Sadrži tehničke i
ručne podatke o proveri sajta, bez AI, Lighthouse i screenshot polja.

### Implementirana polja

| Polje | Tip | Svrha |
|---|---|---|
| `id` | primarni ključ | Identifikator audita |
| `company_lead_id` | foreign key | CRM lead kojem audit pripada |
| `requested_url` | string, nullable | URL predat na proveru |
| `final_url` | string, nullable | Krajnji URL nakon redirekcija |
| `status` | string | Stanje audita |
| `audit_version` | string | Verzija audit logike |
| `http_status` | integer, nullable | HTTP status krajnjeg odgovora |
| `is_reachable` | boolean, nullable | Da li je sajt dostupan |
| `has_https` | boolean, nullable | Da li krajnji URL koristi HTTPS |
| `redirect_count` | integer, nullable | Broj redirekcija |
| `response_time_ms` | integer, nullable | Vreme odgovora |
| `page_title` | string, nullable | Naslov stranice |
| `meta_description` | text, nullable | Meta description stranice |
| `notes` | text, nullable | Ručne napomene korisnika |
| `reviewed_at` | timestamp, nullable | Datum ručne potvrde |
| `started_at` | timestamp, nullable | Početak obrade |
| `completed_at` | timestamp, nullable | Kraj obrade |
| `error_message` | text, nullable | Razlog neuspeha |
| `created_at` | timestamp | Datum kreiranja audita |
| `updated_at` | timestamp | Datum poslednje izmene |

Tabela ima indeks nad `company_lead_id` i `created_at`, što podržava
prikaz istorije audita po leadu, od najnovijeg ka najstarijem.

AI, Lighthouse, screenshot, konverzioni i odobrenja podaci biće dodati
kasnije kroz zasebne migracije, kada bude definisana njihova stvarna
struktura i izvor podataka.

---

## Statusi audita

| Interna vrednost | Prikaz | Značenje |
|---|---|---|
| `pending` | Pending | Audit je kreiran, ali još nije završen |
| `completed` | Completed | Audit je završen |
| `failed` | Failed | Audit nije uspeo |

Status se validira u `CompanyWebsiteAudit` modelu. Nepoznata vrednost
se ne može sačuvati.

---

## Ručni UI tok

Trenutna verzija koristi ručni unos audita na detalju firme.

```text
1. Korisnik otvori detalj firme sa postojećim CRM leadom.
2. Korisnik popuni formu „Dodaj website audit“.
3. Sistem validira URL, status i tehničke podatke.
4. Sistem kreira novi audit zapis.
5. Novi audit se prikazuje na vrhu sekcije „Website audit istorija“.
```

Ruta za kreiranje audita:

```text
POST /companies/{registration_number}/website-audits
```

Audit se može dodati samo firmi koja već ima CRM lead.

---

## Tehnička provera URL-a

Tehnička provera se pokreće ručno klikom na **Pokreni tehničku proveru**
pored postojećeg audita.

Ruta:

```text
POST /website-audits/{audit}/run
```

Servis `App\Services\WebsiteAuditService`:

1. postavlja audit u status `pending`;
2. čuva `started_at`;
3. šalje GET zahtev ka `requested_url`;
4. čuva HTTP status i krajnji URL;
5. proverava da li krajnji URL koristi HTTPS;
6. čuva vreme odgovora u milisekundama;
7. pokušava da izvuče naslov stranice i meta description;
8. postavlja status `completed` kada je odgovor primljen;
9. postavlja status `failed` i `error_message` kada sajt nije dostupan
   ili dođe do greške.

Trenutna provera ne koristi queue, batch obradu, Lighthouse, screenshotove
ni AI analizu.

---

## Prikaz istorije

Na detalju firme, u sekciji **Website audit istorija**, prikazuju se:

- redni broj audita;
- status;
- URL provere;
- krajnji URL;
- HTTP status;
- dostupnost;
- HTTPS status;
- vreme odgovora;
- datum audita;
- audit verzija;
- naslov stranice;
- meta description;
- ručne napomene;
- greška, ako postoji.

Auditi se prikazuju od najnovijeg ka najstarijem.

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
1. Korisnik otvori detalj firme sa postojećim CRM leadom.
2. Korisnik ručno popuni formu za website audit.
3. Sistem kreira audit sa izabranim statusom, trenutno najčešće
   `pending`, `completed` ili `failed`.
4. Korisnik može da pokrene tehničku proveru za postojeći audit.
5. Sistem ažurira audit sa tehničkim rezultatima.
6. Kasnije: sistem pravi desktop i mobilni screenshot.
7. Kasnije: sistem pokreće Lighthouse i čuva rezultate.
8. Kasnije: AI analizira dokaze i vraća strukturisan rezultat.
9. Kasnije: korisnik pregleda, potvrđuje ili odbacuje nalaze.
10. Kasnije: tek nakon odobrenja može se generisati nacrt ponude.
```

U prvoj verziji audit se unosi ručno za jednu firmu. Batch obrada,
queue i zakazani ponovni audit dolaze tek kada pojedinačni audit bude
pouzdan.

---

## Tehnološki plan

| Sloj | Predlog | Uloga |
|---|---|---|
| Laravel | postojeća aplikacija | CRM, modeli, UI, audit istorija |
| HTTP provera | Laravel HTTP client | Dostupnost, redirekcije i status |
| Queue | Laravel job, kasnije | Pozadinski tok audita |
| Browser automatizacija | Playwright, kasnije | Renderovanje i screenshotovi |
| Tehnički audit | Lighthouse, kasnije | Performance, accessibility, SEO i best practices |
| AI analiza | API izabran kasnije | Strukturisani nalazi iz dokaza |
| Skladištenje | Laravel storage, kasnije | Screenshotovi i izveštaji |

Pre implementacije Lighthouse-a i screenshotova treba proveriti lokalne
Node.js, Chrome/Chromium i Lighthouse uslove, kao i način pokretanja
Playwright procesa iz Laravel queue posla.

---

## Van opsega trenutne verzije

- AI analiza, Lighthouse rezultati i screenshotovi.
- Automatsko ili zakazano pokretanje tehničke provere URL-a.
- Masovno kreiranje audita za više firmi.
- Masovno automatsko crawl-ovanje svih firmi.
- Zaobilaženje CAPTCHA, autentikacije ili drugih zaštita.
- Automatizovano slanje e-mailova i poruka.
- Automatsko tvrđenje poslovne štete bez provere.
- Potpuna zamena za stručni UX, SEO ili bezbednosni audit.
- Procena svih unutrašnjih stranica sajta u jednom prolazu.
