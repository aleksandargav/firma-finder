# Firma Finder — APR Data Sync

## Svrha

APR Data Sync modul služi za preuzimanje aktuelnog snimka privrednih
društava iz APR Open Data API-ja i njegovu lokalnu sinhronizaciju sa
tabelom `companies`.

Cilj je da lokalni APR katalog ostane ažuran, bez dupliranja zapisa i bez
uticaja na CRM, enrichment ili website audit podatke.

---

## Trenutno stanje

Lokalna baza sadrži 134.374 firmi iz APR preseka `2026-09-30`.

Pun sync je uspešno izvršen:

- obrađeno: 134.374 zapisa;
- preskočeno: 0;
- uspešno upisano ili ažurirano: 134.374 zapisa;
- ukupan broj firmi nakon sync-a: 134.374;
- broj zapisa sa datumom preseka `2026-09-30`: 134.374.

Test suite prolazi: 42 testa i 112 asercija.

---

## Arhitektura

```text
APR Open Data API
   ↓
AprApiService
   ↓
storage/app/apr/snapshots/{YYYYMMDD}/companies.json
   ↓
apr:import
   ↓
companies tabela
```

Sync se odvija u dve faze:

1. `apr:sync` preuzima kompletan JSON snapshot preko API-ja.
2. `apr:import` čita snapshot i upisuje ili ažurira firme u lokalnoj bazi.

Ovakav tok omogućava da se API preuzimanje i upis u bazu testiraju i
ponavljaju odvojeno.

---

## Implementirane komponente

| Komponenta | Putanja | Uloga |
|---|---|---|
| `AprApiService` | `app/Services/AprApiService.php` | Komunikacija sa APR Open Data API-jem |
| `SyncAprCompanies` | `app/Console/Commands/SyncAprCompanies.php` | Artisan komanda `apr:sync` |
| `ImportAprCompanies` | `app/Console/Commands/ImportAprCompanies.php` | Artisan komanda `apr:import` |
| APR konfiguracija | `config/apr.php` | API URL, token i povezana podešavanja |

---

## Komanda `apr:sync`

Komanda preuzima kompletan APR snapshot za zadati datum preseka.

```bash
php8.5 artisan apr:sync \
  --date=2026-09-30
```

Rezultat se čuva u:

```text
storage/app/apr/snapshots/20260930/companies.json
```

Snapshot se čuva lokalno pre nego što se bilo šta upiše u bazu.

---

## Komanda `apr:import`

Komanda uvozi lokalni JSON snapshot u tabelu `companies`.

Primer za mali test:

```bash
php8.5 artisan apr:import \
  --file=storage/app/apr/snapshots/20260930/companies.json \
  --limit=5 \
  --batch=500 \
  --write
```

Primer za pun sync:

```bash
php8.5 artisan apr:import \
  --file=storage/app/apr/snapshots/20260930/companies.json \
  --limit=134374 \
  --batch=500 \
  --write
```

Bez `--write`, komanda radi u dry-run režimu i ne upisuje podatke u bazu.

---

## Upis podataka

Import koristi upsert logiku po matičnom broju firme.

Pravila:

1. Ako firma sa datim matičnim brojem ne postoji, kreira se novi zapis.
2. Ako firma postoji, APR polja se ažuriraju novim vrednostima.
3. CRM, enrichment i audit podaci se nikada ne menjaju APR importom.
4. `source_snapshot_date` čuva datum APR preseka.
5. `imported_at` beleži vreme poslednjeg lokalnog uvoza.
6. Batch veličina je konfigurabilna kroz `--batch`.

---

## Verifikacija nakon sync-a

Nakon punog uvoza proveriti:

```bash
php8.5 artisan tinker --execute="dump(App\Models\Company::count()); dump(App\Models\Company::where('source_snapshot_date', '2026-09-30')->count());"
```

Očekivani rezultat za presek `2026-09-30`:

```text
134374
134374
```

Primer provere pojedinačne firme:

```bash
php8.5 artisan tinker --execute="dump(App\Models\Company::where('registration_number', '17204106')->first(['registration_number','business_name','source_snapshot_date','imported_at','updated_at'])->toArray());"
```

---

## Testirani tok

1. Pokrenut je mali test uvoz od 5 zapisa sa `--write`.
2. Provereno je da ukupan broj firmi ostaje nepromenjen.
3. Provereno je da postojeća firma dobija novi `source_snapshot_date`, `imported_at` i `updated_at`.
4. Pokrenut je pun sync za 134.374 zapisa.
5. Provereno je da nema duplikata i da su svi zapisi vezani za isti APR presek.
6. Pokrenut je kompletan test suite; svi testovi prolaze.

---

## Ograničenja

- Sync trenutno radi kao ručna Artisan komanda.
- Nema zakazanog automatskog osvežavanja.
- Nema inkrementalnog sync-a po promenama; koristi se kompletan snapshot.
- Nema notifikacija o neuspehu sync-a.
- Nema automatskog brisanja firmi koje više ne postoje u novom APR preseku.
- Nema produkcijskog deployment-a; aplikacija je trenutno lokalna.
