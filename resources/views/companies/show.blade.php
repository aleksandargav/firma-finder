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

        h2 {
            margin: 0 0 18px;
            font-size: 21px;
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

        .crm {
            margin-top: 28px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            color: #9faabd;
            font-size: 13px;
            font-weight: 700;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px 12px;
            color: #e9edf3;
            background: #111318;
            border: 1px solid #2a303b;
            border-radius: 8px;
            font-size: 15px;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .actions {
            margin-top: 18px;
            display: flex;
            justify-content: flex-end;
        }

        button {
            padding: 11px 20px;
            color: #08130c;
            background: #67e08a;
            border: 0;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #7deb98;
        }

        .alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 10px;
            font-weight: 700;
        }

        .alert-success {
            color: #b9f5c9;
            background: #143b22;
            border: 1px solid #1f6b39;
        }

        .alert-error {
            color: #ffd3d3;
            background: #41181c;
            border: 1px solid #7d2f36;
        }

        .error-text {
            color: #ff9d9d;
            font-size: 13px;
        }

        .lead-summary {
            margin-bottom: 22px;
            padding: 16px;
            background: #111318;
            border: 1px solid #2a303b;
            border-radius: 10px;
        }

        @media (max-width: 720px) {
            .grid,
            .form-grid {
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
    <a class="back-link" href="{{ route('companies.index') }}">← Nazad na listu</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            Popuni obavezna polja i proveri unete podatke.
        </div>
    @endif

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
            CRM podaci se unose ručno u odvojenom delu aplikacije.
        </div>
    </section>

    <section class="card crm">
        <h2>CRM podaci</h2>

        @if ($company->lead)
            <div class="lead-summary">
                <strong>Status:</strong>
                {{ App\Models\CompanyLead::LEAD_STATUSES[$company->lead->lead_status] ?? $company->lead->lead_status }}

                <br>

                <strong>Website:</strong>
                {{ App\Models\CompanyLead::WEBSITE_STATUSES[$company->lead->website_status] ?? $company->lead->website_status }}

                @if ($company->lead->contacted_at)
                    <br>
                    <strong>Kontaktirano:</strong>
                    {{ $company->lead->contacted_at->format('d.m.Y. H:i') }}
                @endif
            </div>
        @else
            <div class="lead-summary muted">
                Za ovu firmu još nema CRM zapisa.
            </div>
        @endif

        <form
            method="POST"
            action="{{ $company->lead ? route('companies.leads.update', $company) : route('companies.leads.store', $company) }}"
        >
            @csrf

            @if ($company->lead)
                @method('PUT')
            @endif

            <div class="form-grid">
                <div class="field full">
                    <label for="website_url">Website URL</label>
                    <input
                        id="website_url"
                        name="website_url"
                        type="url"
                        value="{{ old('website_url', $company->lead?->website_url) }}"
                        placeholder="https://primer.rs"
                    >
                    @error('website_url')
                    <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="website_status">Status sajta</label>
                    <select id="website_status" name="website_status">
                        @foreach (App\Models\CompanyLead::WEBSITE_STATUSES as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('website_status', $company->lead?->website_status ?? 'unknown') === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('website_status')
                    <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="lead_status">Status leada</label>
                    <select id="lead_status" name="lead_status">
                        @foreach (App\Models\CompanyLead::LEAD_STATUSES as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('lead_status', $company->lead?->lead_status ?? 'new') === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('lead_status')
                    <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="phone">Telefon</label>
                    <input
                        id="phone"
                        name="phone"
                        type="text"
                        value="{{ old('phone', $company->lead?->phone) }}"
                    >
                    @error('phone')
                    <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="email">E-mail</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $company->lead?->email) }}"
                    >
                    @error('email')
                    <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="lead_score">Lead score (0–100)</label>
                    <input
                        id="lead_score"
                        name="lead_score"
                        type="number"
                        min="0"
                        max="100"
                        value="{{ old('lead_score', $company->lead?->lead_score ?? 0) }}"
                    >
                    @error('lead_score')
                    <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field full">
                    <label for="notes">Napomene</label>
                    <textarea
                        id="notes"
                        name="notes"
                    >{{ old('notes', $company->lead?->notes) }}</textarea>
                    @error('notes')
                    <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="actions">
                <button type="submit">
                    {{ $company->lead ? 'Sačuvaj izmene' : 'Dodaj lead' }}
                </button>
            </div>
        </form>
    </section>
</main>
</body>
</html>
