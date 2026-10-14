# CRM

CRM sloj čuva ručne prodajne podatke o firmama. Odvojen je od izvornih
APR podataka i od enrichment podataka.

## Model

CRM podaci se čuvaju u tabeli `company_leads`.

Svaki zapis pripada tačno jednoj firmi iz `companies` tabele.

Veza je striktno 1:1:

```txt
Company 1 — 1 CompanyLead
```

Ograničenje je zaštićeno unique indeksom nad kolonom `company_id`.

## Polja

| Polje | Namena |
|---|---|
| `company_id` | Firma iz APR kataloga |
| `website_url` | Ručno unet ili potvrđen URL sajta |
| `website_status` | Status sajta |
| `website_checked_at` | Datum poslednje provere sajta |
| `phone` | Telefon firme |
| `email` | E-mail adresa firme |
| `lead_status` | Status prodajnog leada |
| `lead_score` | Ručni ili izračunati prioritet leada |
| `notes` | Slobodne napomene |
| `contacted_at` | Datum prvog kontakta |
| `created_at` | Datum kreiranja leada |
| `updated_at` | Datum poslednje izmene |

## Statusi sajta

| Status | Značenje |
|---|---|
| `none` | Nema sajta ili status još nije proveren |
| `working` | Sajt radi |
| `outdated` | Sajt radi, ali je vizuelno ili sadržajno zastareo |
| `broken` | Sajt ne radi ili ima ozbiljne tehničke probleme |
| `no_website` | Firma nema sajt |

## Statusi leada

| Status | Značenje |
|---|---|
| `new` | Nov lead |
| `contacted` | Uspostavljen prvi kontakt |
| `qualified` | Lead je kvalifikovan |
| `proposal_sent` | Poslata ponuda |
| `won` | Uspesan kraj |
| `lost` | Izgubljen lead |
| `on_hold` | Privremeno pauziran |

## Pravila

- APR podaci se nikada ne prepisuju CRM podacima.
- CRM lead se kreira i ažurira ručno.
- Jedna firma može imati najviše jedan CRM lead.
- `contacted_at` se automatski postavlja kada se `lead_status` promeni
  u `contacted`, a vrednost još nije postavljena.
- `website_checked_at` se automatski postavlja kada se ručno menja
  `website_status`.
- Website URL se validira i normalizuje pre čuvanja.
- Brisanje firme briše i povezani CRM lead.

## Website URL validacija

Website URL mora biti validan URL.

Ako korisnik unese adresu bez protokola, aplikacija je normalizuje
dodavanjem `https://`.

Primeri:

| Unos | Sačuvana vrednost |
|---|---|
| `firma.rs` | `https://firma.rs` |
| `www.firma.rs` | `https://www.firma.rs` |
| `http://firma.rs` | `http://firma.rs` |
| `https://firma.rs` | `https://firma.rs` |

Nevalidan URL se odbija validacijom.

## CRM lista

CRM lista je dostupna na `/leads`.

Podržava:

- pretragu po nazivu firme;
- filter po opštini;
- filter po statusu sajta;
- filter po statusu leada;
- sortiranje po nazivu firme, opštini, lead score-u i datumu izmene.

## CRM status u katalogu

Na katalogu firmi postoji filter CRM stanja:

- sve firme;
- samo firme sa leadom;
- samo firme bez leada.

Time se omogućava lakše pronalaženje firmi koje još nisu ušle u CRM
proces.

## Primena potvrđenog website kandidata

Potvrđeni website kandidat iz enrichment sloja može se primeniti u CRM
samo eksplicitnom korisničkom akcijom.

Kada korisnik klikne **Primeni u CRM**:

- ako lead ne postoji, kreira se novi lead;
- ako lead postoji, ažuriraju se samo `website_url`, `website_status`
  i `website_checked_at`;
- `lead_status`, `lead_score`, telefon, e-mail i napomene se ne menjaju;
- APR podaci se ne menjaju;
- enrichment zapis ostaje `verified`.

## Pokretanje audita iz enrichment kandidata

Za potvrđeni website kandidat, kod firme koja već ima CRM lead, može se
pokrenuti website audit.

Audit se kreira u tabeli `company_website_audits`, a `requested_url` se
uzima iz potvrđenog enrichment kandidata.

Audit se može pokrenuti samo ako:

- data point je tipa `website`;
- data point ima status `verified`;
- firma ima CRM lead.

Pokretanje audita ne menja enrichment zapis niti CRM podatke.

## Testovi

Pokriveni su sledeći slučajevi:

- kreiranje CRM leada;
- ažuriranje CRM leada;
- validacija statusa i lead score-a;
- 1:1 ograničenje nad `company_id`;
- automatsko postavljanje `contacted_at`;
- filteri CRM liste;
- CRM filter na katalogu firmi;
- URL validacija i normalizacija;
- automatsko postavljanje `website_checked_at`;
- kreiranje i ažuriranje website audita;
- tehnička provera URL-a.
