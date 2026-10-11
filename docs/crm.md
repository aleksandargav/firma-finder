# Firma Finder — CRM

## Svrha

CRM modul služi za ručno vođenje prodajnog procesa za firme iz APR kataloga.

APR tabela `companies` sadrži izvorne podatke iz registra.
CRM tabela `company_leads` sadrži podatke koji nastaju tokom internog rada:

- URL sajta;
- status sajta;
- telefon;
- e-mail;
- status prodajnog leada;
- lead score;
- napomene;
- datum provere sajta;
- datum prvog kontakta.

APR podaci se nikada ne menjaju kroz CRM formu.

---

## Relacija firme i leada

Jedna firma može imati najviše jedan CRM lead.

```text
Company hasOne CompanyLead
CompanyLead belongsTo Company
```

Ograničenje je obezbeđeno u bazi:

```text
UNIQUE(company_leads.company_id)
```

Lead se može kreirati na stranici detalja firme. Kada lead postoji,
ista forma služi za ažuriranje tog zapisa.

---

## CRM rute

| Ruta | Namena |
|---|---|
| `GET /companies` | APR katalog firmi, sa CRM statusom po firmi |
| `GET /companies/{registration_number}` | Detalj firme i CRM forma |
| `POST /companies/{registration_number}/lead` | Kreiranje CRM leada |
| `PUT /companies/{registration_number}/lead` | Ažuriranje CRM leada |
| `GET /leads` | Lista svih postojećih CRM leadova |

---

## Polja CRM leada

| Polje | Opis | Pravilo |
|---|---|---|
| `company_id` | Veza sa firmom | Obavezno, jedinstveno |
| `website_url` | Javna adresa sajta firme | Opciono, ručni unos |
| `website_status` | Trenutno poznato stanje sajta | Obavezno |
| `phone` | Telefon firme ili kontakt osobe | Opciono |
| `email` | E-mail firme ili kontakt osobe | Opciono, validan e-mail format |
| `lead_status` | Faza prodajnog procesa | Obavezno |
| `lead_score` | Interni prioritet leada | Obavezno, ceo broj od 0 do 100 |
| `notes` | Slobodne interne beleške | Opciono |
| `website_checked_at` | Datum poslednje provere sajta | Postoji, još se ne postavlja kroz UI |
| `contacted_at` | Datum prvog kontakta | Postavlja observer |
| `created_at` | Datum kreiranja CRM zapisa | Laravel automatski |
| `updated_at` | Datum poslednje izmene CRM zapisa | Laravel automatski |

---

## Status sajta

`website_status` opisuje da li je sajt poznat i da li se otvara.
Ne predstavlja ocenu kvaliteta sajta.

| Interna vrednost | Prikaz u aplikaciji | Značenje |
|---|---|---|
| `unknown` | Nepoznato | URL ili stanje sajta još nije provereno |
| `no_website` | Nema sajta | Firma nema poznat javni sajt |
| `active` | Sajt aktivan | Sajt se ručno otvorio i osnovno radi |
| `inactive` | Sajt neaktivan | Poznat URL trenutno ne radi ili je nedostupan |
| `error` | Greška pri proveri | Provera nije mogla pouzdano da se završi |

Napomena: trenutno se status postavlja ručno. Buduća tehnička provera
može predložiti ili automatski popuniti rezultat, ali ljudska provera
ostaje moguća.

---

## Status leada

`lead_status` opisuje prodajnu fazu odnosa sa firmom.

| Interna vrednost | Prikaz u aplikaciji | Kada se koristi |
|---|---|---|
| `new` | Nov | Lead je otvoren, ali još nije obrađen |
| `in_progress` | U obradi | Prikupljaju se informacije ili radi pregled sajta |
| `contacted` | Kontaktiran | Prvi kontakt prema firmi je ostvaren |
| `qualified` | Kvalifikovan | Lead je procenjen kao realna prodajna prilika |
| `rejected` | Odbijen | Nije odgovarajući lead ili nema interesovanja |
| `converted` | Konvertovan | Lead je postao klijent ili dogovorena saradnja |

Preporučeni tok:

```text
new
→ in_progress
→ contacted
→ qualified
→ converted
```

Lead može biti označen kao `rejected` iz bilo koje faze kada se utvrdi
da ne predstavlja odgovarajuću priliku.

---

## Lead score

`lead_score` je ceo broj od 0 do 100 i služi za internu prioritizaciju.

Predložena orijentacija:

| Opseg | Prioritet | Tumačenje |
|---:|---|---|
| 0–24 | Nizak | Nema dovoljno podataka ili je prilika slaba |
| 25–49 | Srednji | Ima potencijala, ali je potreban dodatni pregled |
| 50–74 | Visok | Dobra prilika za aktivan rad i kontakt |
| 75–100 | Veoma visok | Prioritetna prilika za detaljan audit i ponudu |

Score je trenutno ručni unos. Kasnije može biti delimično predložen na
osnovu audit rezultata, ali korisnik zadržava konačnu kontrolu.

---

## Datum prvog kontakta

`contacted_at` predstavlja datum prvog kontakta sa firmom.

Pravila:

1. Kada se lead prvi put sačuva sa statusom `contacted`, observer
   automatski upisuje trenutno vreme ako je `contacted_at` prazan.
2. Ako se status kasnije promeni u `qualified`, `rejected` ili
   `converted`, postojeći datum se ne briše.
3. Ako je datum već popunjen, ponovno čuvanje ne prepisuje ga.

Ovaj datum nije isto što i poslednja komunikacija. Istorija svake
buduće poruke ili poziva treba da bude zasebna funkcionalnost.

---

## Trenutni radni tok

1. Pronaći firmu na `/companies`.
2. Otvoriti detalj firme.
3. Dodati CRM lead ako ne postoji.
4. Ručno uneti dostupne kontakt podatke i URL sajta.
5. Postaviti status sajta i početni lead score.
6. Kada počne pregled ili prikupljanje podataka, promeniti status u
   `in_progress`.
7. Kada se ostvari prvi kontakt, promeniti status u `contacted`.
8. Nakon procene prilike, postaviti `qualified`, `rejected` ili
   `converted`.
9. Koristiti `/leads` za pregled, filtere i prioritizaciju postojećih
   leadova.

---

## Ograničenja trenutne verzije

- Nema automatske provere URL-a, HTTP statusa ili HTTPS-a.
- Nema strukturisane procene mobilnog i desktop prikaza.
- Nema Lighthouse rezultata.
- Nema screenshotova.
- Nema AI analize.
- Nema istorije pojedinačnih komunikacija.
- Nema automatskog slanja poruka.
- Nema brisanja CRM leada iz interfejsa.
