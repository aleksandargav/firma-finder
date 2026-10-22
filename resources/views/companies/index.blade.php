<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Finder — firme</title>

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
            width: min(1500px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 56px;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .crm-link {
            display: inline-flex;
            align-items: center;
            min-height: 40px;
            padding: 0 14px;
            color: #08130c;
            background: #67e08a;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .crm-link:hover {
            background: #7deb98;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .subtitle {
            margin: 0 0 24px;
            color: #aab3c2;
        }

        .filters {
            display: grid;
            grid-template-columns: minmax(220px, 2fr) repeat(4, minmax(160px, 1fr)) auto;
            gap: 12px;
            align-items: end;
            margin-bottom: 24px;
            padding: 18px;
            background: #191c22;
            border: 1px solid #2a303b;
            border-radius: 12px;
        }

        label {
            display: grid;
            gap: 7px;
            color: #bfc8d8;
            font-size: 13px;
            font-weight: 700;
        }

        input,
        select,
        button,
        .reset-link {
            min-height: 40px;
            border-radius: 8px;
            font: inherit;
        }

        input,
        select {
            width: 100%;
            padding: 9px 11px;
            color: #e9edf3;
            background: #111318;
            border: 1px solid #3a4352;
        }

        button,
        .reset-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
        }

        button {
            color: #06120a;
            background: #67e08a;
        }

        .reset-link {
            color: #d7deea;
            background: #252a33;
        }

        .summary {
            margin: 0 0 14px;
            color: #aab3c2;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid #2a303b;
            border-radius: 12px;
            background: #191c22;
        }

        table {
            width: 100%;
            min-width: 1150px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px 14px;
            border-bottom: 1px solid #2a303b;
            text-align: left;
            vertical-align: top;
        }

        th {
            color: #9faabd;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        td {
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .name {
            min-width: 360px;
            font-weight: 700;
        }

        .company-link {
            color: #e9edf3;
            text-decoration: none;
        }

        .company-link:hover {
            color: #67e08a;
            text-decoration: underline;
        }

        .muted {
            color: #aab3c2;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            color: #b9f5c9;
            background: #143b22;
            font-size: 12px;
            font-weight: 700;
        }

        .crm-badge {
            display: inline-block;
            padding: 4px 8px;
            border: 1px solid #3a4352;
            border-radius: 999px;
            color: #aab3c2;
            background: #111318;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .empty {
            padding: 30px;
            text-align: center;
            color: #aab3c2;
        }

        nav[role="navigation"] {
            margin-top: 20px;
        }

        nav[role="navigation"] a,
        nav[role="navigation"] span {
            display: inline-flex;
            min-width: 36px;
            min-height: 36px;
            align-items: center;
            justify-content: center;
            margin-right: 4px;
            padding: 0 10px;
            border: 1px solid #3a4352;
            border-radius: 7px;
            color: #d7deea;
            text-decoration: none;
        }

        nav[role="navigation"] [aria-current="page"] span {
            color: #06120a;
            background: #67e08a;
            border-color: #67e08a;
        }

        nav[role="navigation"] svg {
            width: 16px;
            height: 16px;
        }

        @media (max-width: 1050px) {
            .filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 620px) {
            .container {
                width: min(100% - 24px, 1500px);
                padding-top: 22px;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <div class="page-header">
            <div>
                <h1>Firma Finder</h1>
                <p class="subtitle">
                    APR registar privrednih društava — datum preseka:
                    {{ optional($companies->first())->source_snapshot_date?->format('d.m.Y.') ?? '—' }}
                </p>
            </div>

            <a class="crm-link" href="{{ route('leads.index') }}">
                CRM leadovi →
            </a>
        </div>

        <form class="filters" method="GET" action="{{ route('companies.index') }}">
            <label>
                Naziv ili matični broj
                <input
                    type="search"
                    name="search"
                    value="{{ $filters['search'] }}"
                    placeholder="npr. Primer firme ili matični broj"
                >
            </label>

            <label>
                Opština
                <select name="municipality">
                    <option value="">Sve opštine</option>

                    @foreach ($municipalities as $item)
                        <option
                            value="{{ $item->municipality_code }}"
                            @selected($filters['municipality'] === $item->municipality_code)
                        >
                            {{ $item->municipality_name }} ({{ $item->municipality_code }})
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                Šifra delatnosti
                <input
                    type="search"
                    name="activity"
                    value="{{ $filters['activity'] }}"
                    inputmode="numeric"
                    placeholder="npr. 6201"
                >
            </label>

            <label>
                Status
                <select name="status">
                    <option value="all" @selected($filters['status'] === 'all')>
                        Svi statusi
                    </option>

                    @foreach ($statuses as $item)
                        <option
                            value="{{ $item }}"
                            @selected($filters['status'] === $item)
                        >
                            {{ $item }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                CRM stanje
                <select name="lead_filter">
                    <option value="all" @selected($filters['lead_filter'] === 'all')>
                        Sve firme
                    </option>

                    <option value="with_lead" @selected($filters['lead_filter'] === 'with_lead')>
                        Imaju lead
                    </option>

                    <option value="without_lead" @selected($filters['lead_filter'] === 'without_lead')>
                        Nemaju lead
                    </option>
                </select>
            </label>

            <button type="submit">Pretraži</button>

            <a class="reset-link" href="{{ route('companies.index') }}">
                Resetuj
            </a>
        </form>

        <p class="summary">
            Prikazano {{ $companies->firstItem() ?? 0 }}–{{ $companies->lastItem() ?? 0 }}
            od {{ number_format($companies->total(), 0, ',', '.') }} firmi.
        </p>

        <div class="table-wrap">
            @if ($companies->isEmpty())
                <p class="empty">Nema firmi za zadate filtere.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Matični broj</th>
                            <th>Naziv firme</th>
                            <th>Opština</th>
                            <th>Delatnost</th>
                            <th>Status</th>
                            <th>CRM</th>
                            <th>Osnovana</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>{{ $company->registration_number }}</td>
                                <td class="name">
                                    <a
                                        href="{{ route('companies.show', ['company' => $company->registration_number]) }}"
                                        class="company-link"
                                    >
                                        {{ $company->business_name }}
                                    </a>
                                </td>                                <td>
                                    {{ $company->municipality_name ?? '—' }}
                                    @if ($company->municipality_code)
                                        <span class="muted">({{ $company->municipality_code }})</span>
                                    @endif
                                </td>
                                <td>{{ $company->activity_code ?? '—' }}</td>
                                <td>
                                    <span class="badge">{{ $company->status ?? '—' }}</span>
                                </td>

                                <td>
                                    @if ($company->lead)
                                        <a
                                            class="crm-badge"
                                            href="{{ route('companies.show', $company) }}"
                                        >
                                            {{ App\Models\CompanyLead::LEAD_STATUSES[$company->lead->lead_status] ?? $company->lead->lead_status }}
                                        </a>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $company->founded_at?->format('d.m.Y.') ?? '—' }}
                                </td>
                                <td>
                                    {{ $company->founded_at?->format('d.m.Y.') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{ $companies->links() }}
    </main>
</body>
</html>
