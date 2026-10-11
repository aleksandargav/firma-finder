<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $company->business_name }} — Firma Finder</title>

    <style>
        :root {
            color-scheme: dark;
            font-family: Arial, Helvetica, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #101114;
            color: #e9edf3;
        }

        .container {
            width: min(1100px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 56px;
        }

        .back-link {
            display: inline-flex;
            margin-bottom: 20px;
            color: #67e08a;
            text-decoration: none;
            font-weight: 700;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .card {
            padding: 26px;
            background: #191c22;
            border: 1px solid #2a303b;
            border-radius: 14px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 28px;
            line-height: 1.25;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            color: #b9f5c9;
            background: #143b22;
            font-size: 13px;
            font-weight: 700;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 28px;
        }

        .item {
            padding: 16px;
            background: #111318;
            border: 1px solid #2a303b;
            border-radius: 10px;
        }

        .label {
            margin-bottom: 7px;
            color: #9faabd;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .value {
            font-size: 16px;
            overflow-wrap: anywhere;
        }

        .muted {
            color: #aab3c2;
        }

        .notice {
            margin-top: 20px;
            padding: 16px;
            color: #aab3c2;
            background: #111318;
            border: 1px dashed #3a4352;
            border-radius: 10px;
            line-height: 1.5;
        }

        @media (max-width: 720px) {
            .grid {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 23px;
            }
        }
    </style>
</head>
<body>
<main class="container">
    <a class="back-link" href="{{ url()->previous() }}">← Nazad na listu</a>

    <section class="card">
        <h1>{{ $company->business_name }}</h1>
        <span class="badge">{{ $company->status ?? '—' }}</span>

        <div class="grid">
            <div class="item">
                <div class="label">Matični broj</div>
                <div class="value">{{ $company->registration_number }}</div>
            </div>

            <div class="item">
                <div class="label">Pravna forma</div>
                <div class="value">{{ $company->legal_form ?? '—' }}</div>
            </div>

            <div class="item">
                <div class="label">Opština</div>
                <div class="value">
                    {{ $company->municipality_name ?? '—' }}
                    @if ($company->municipality_code)
                        <span class="muted">({{ $company->municipality_code }})</span>
                    @endif
                </div>
            </div>

            <div class="item">
                <div class="label">Šifra delatnosti</div>
                <div class="value">{{ $company->activity_code ?? '—' }}</div>
            </div>

            <div class="item">
                <div class="label">Datum osnivanja</div>
                <div class="value">
                    {{ $company->founded_at?->format('d.m.Y.') ?? '—' }}
                </div>
            </div>

            <div class="item">
                <div class="label">Datum preseka APR podataka</div>
                <div class="value">
                    {{ $company->source_snapshot_date?->format('d.m.Y.') ?? '—' }}
                </div>
            </div>

            <div class="item">
                <div class="label">Uvezeno u bazu</div>
                <div class="value">
                    {{ $company->imported_at?->format('d.m.Y. H:i') ?? '—' }}
                </div>
            </div>
        </div>

        <div class="notice">
            APR podaci u ovom skupu ne sadrže telefon, e-mail ni web-sajt firme.
            Ta polja dodajemo u sledećim koracima iz odvojenog izvora.
        </div>
    </section>
</main>
</body>
</html>
