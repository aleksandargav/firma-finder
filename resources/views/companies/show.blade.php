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

        .audits {
            margin-top: 28px;
        }

        .audit-item {
            margin-bottom: 18px;
            padding: 18px;
            background: #111318;
            border: 1px solid #2a303b;
            border-radius: 10px;
        }

        .audit-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .audit-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .audit-run-button {
            padding: 8px 14px;
            color: #e9edf3;
            background: #2a303b;
            border: 1px solid #3a4352;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .audit-run-button:hover {
            background: #343c49;
        }

        .audit-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .audit-details {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #2a303b;
        }

        .audit-details p {
            margin: 0 0 8px;
            line-height: 1.5;
        }

        .audit-status-pending {
            background: #3a3113;
            color: #ffe08a;
        }

        .audit-status-completed {
            background: #143b22;
            color: #b9f5c9;
        }

        .audit-status-failed {
            background: #41181c;
            color: #ffd3d3;
        }

        @media (max-width: 720px) {
            .audit-grid {
                grid-template-columns: 1fr;
            }
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

                <br>

                <strong>Poslednja provera sajta:</strong>
                {{ $company->lead->website_checked_at?->format('d.m.Y. H:i') ?? 'Još nije provereno' }}

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

    @include('companies.partials.company-data-points')

    @if ($company->lead)
        <section class="card audits">
            <h2>Website audit istorija</h2>

            <form
                method="POST"
                action="{{ route('companies.website-audits.store', $company) }}"
                class="audit-form"
            >
                @csrf

                <h3>Dodaj website audit</h3>

                <div class="form-grid">
                    <div class="field full">
                        <label for="requested_url">URL provere</label>
                        <input
                            id="requested_url"
                            name="requested_url"
                            type="url"
                            value="{{ old('requested_url', $company->lead->website_url) }}"
                            placeholder="https://primer.rs"
                        >
                        @error('requested_url')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field full">
                        <label for="final_url">Krajnji URL</label>
                        <input
                            id="final_url"
                            name="final_url"
                            type="url"
                            value="{{ old('final_url') }}"
                            placeholder="https://www.primer.rs/"
                        >
                        @error('final_url')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="audit_status">Status audita</label>
                        <select id="audit_status" name="status">
                            @foreach (App\Models\CompanyWebsiteAudit::STATUSES as $status)
                                <option
                                    value="{{ $status }}"
                                    @selected(old('status', App\Models\CompanyWebsiteAudit::STATUS_PENDING) === $status)
                                >
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="http_status">HTTP status</label>
                        <input
                            id="http_status"
                            name="http_status"
                            type="number"
                            min="100"
                            max="599"
                            value="{{ old('http_status') }}"
                            placeholder="200"
                        >
                        @error('http_status')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="is_reachable">Da li sajt radi?</label>
                        <select id="is_reachable" name="is_reachable">
                            <option value="">Nepoznato</option>
                            <option value="1" @selected(old('is_reachable') === '1')>Da</option>
                            <option value="0" @selected(old('is_reachable') === '0')>Ne</option>
                        </select>
                        @error('is_reachable')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="has_https">HTTPS</label>
                        <select id="has_https" name="has_https">
                            <option value="">Nepoznato</option>
                            <option value="1" @selected(old('has_https') === '1')>Da</option>
                            <option value="0" @selected(old('has_https') === '0')>Ne</option>
                        </select>
                        @error('has_https')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="redirect_count">Broj redirekcija</label>
                        <input
                            id="redirect_count"
                            name="redirect_count"
                            type="number"
                            min="0"
                            max="100"
                            value="{{ old('redirect_count') }}"
                        >
                        @error('redirect_count')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="response_time_ms">Vreme odgovora (ms)</label>
                        <input
                            id="response_time_ms"
                            name="response_time_ms"
                            type="number"
                            min="0"
                            max="600000"
                            value="{{ old('response_time_ms') }}"
                        >
                        @error('response_time_ms')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field full">
                        <label for="audit_page_title">Naslov stranice</label>
                        <input
                            id="audit_page_title"
                            name="page_title"
                            type="text"
                            value="{{ old('page_title') }}"
                        >
                        @error('page_title')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field full">
                        <label for="audit_meta_description">Meta description</label>
                        <textarea
                            id="audit_meta_description"
                            name="meta_description"
                        >{{ old('meta_description') }}</textarea>
                        @error('meta_description')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field full">
                        <label for="audit_notes">Napomene</label>
                        <textarea
                            id="audit_notes"
                            name="notes"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field full">
                        <label for="audit_error_message">Greška, ako je bilo</label>
                        <textarea
                            id="audit_error_message"
                            name="error_message"
                        >{{ old('error_message') }}</textarea>
                        @error('error_message')
                        <span class="error-text">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="actions">
                    <button type="submit">Dodaj audit</button>
                </div>
            </form>

            @if ($company->lead->websiteAudits->isEmpty())
                <div class="lead-summary muted">
                    Za ovaj lead još nema sačuvanih website audita.
                </div>
            @else
                @foreach ($company->lead->websiteAudits as $audit)
                    <article class="audit-item">
                        <div class="audit-header">
                            <strong>Audit #{{ $audit->id }}</strong>

                            <div class="audit-header-actions">
                                <span class="badge audit-status-{{ $audit->status }}">
                                    {{ ucfirst($audit->status) }}
                                </span>

                                <form
                                    method="POST"
                                    action="{{ route('website-audits.run', $audit) }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="audit-run-button"
                                    >
                                        Pokreni tehničku proveru
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="audit-grid">
                            <div class="item">
                                <div class="label">URL provere</div>
                                <div class="value">{{ $audit->requested_url ?? '—' }}</div>
                            </div>

                            <div class="item">
                                <div class="label">Krajnji URL</div>
                                <div class="value">{{ $audit->final_url ?? '—' }}</div>
                            </div>

                            <div class="item">
                                <div class="label">HTTP status</div>
                                <div class="value">{{ $audit->http_status ?? '—' }}</div>
                            </div>

                            <div class="item">
                                <div class="label">Dostupnost</div>
                                <div class="value">
                                    {{ $audit->is_reachable === null ? '—' : ($audit->is_reachable ? 'Da' : 'Ne') }}
                                </div>
                            </div>

                            <div class="item">
                                <div class="label">HTTPS</div>
                                <div class="value">
                                    {{ $audit->has_https === null ? '—' : ($audit->has_https ? 'Da' : 'Ne') }}
                                </div>
                            </div>

                            <div class="item">
                                <div class="label">Vreme odgovora</div>
                                <div class="value">
                                    {{ $audit->response_time_ms !== null ? $audit->response_time_ms . ' ms' : '—' }}
                                </div>
                            </div>

                            <div class="item">
                                <div class="label">Datum audita</div>
                                <div class="value">{{ $audit->created_at?->format('d.m.Y. H:i') ?? '—' }}</div>
                            </div>

                            <div class="item">
                                <div class="label">Audit verzija</div>
                                <div class="value">{{ $audit->audit_version }}</div>
                            </div>
                        </div>

                        @if ($audit->page_title || $audit->meta_description || $audit->notes || $audit->error_message)
                            <div class="audit-details">
                                @if ($audit->page_title)
                                    <p><strong>Naslov stranice:</strong> {{ $audit->page_title }}</p>
                                @endif

                                @if ($audit->meta_description)
                                    <p><strong>Meta description:</strong> {{ $audit->meta_description }}</p>
                                @endif

                                @if ($audit->notes)
                                    <p><strong>Napomene:</strong> {{ $audit->notes }}</p>
                                @endif

                                @if ($audit->error_message)
                                    <p><strong>Greška:</strong> {{ $audit->error_message }}</p>
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach
            @endif
        </section>
    @endif
</main>
</body>
</html>
