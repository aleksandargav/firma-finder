<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM leadovi — Firma Finder</title>

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
            width: min(1400px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 56px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        h1 {
            margin: 0;
            font-size: 28px;
        }

        .back-link {
            color: #67e08a;
            text-decoration: none;
            font-weight: 700;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .card {
            padding: 22px;
            background: #191c22;
            border: 1px solid #2a303b;
            border-radius: 14px;
        }

        .filters {
            display: grid;
            grid-template-columns: minmax(240px, 2fr) repeat(3, minmax(150px, 1fr)) auto;
            gap: 12px;
            align-items: end;
            margin-bottom: 22px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        label {
            color: #9faabd;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        input,
        select {
            width: 100%;
            padding: 10px 11px;
            color: #e9edf3;
            background: #111318;
            border: 1px solid #2a303b;
            border-radius: 8px;
            font-size: 14px;
        }

        button {
            padding: 10px 16px;
            color: #08130c;
            background: #67e08a;
            border: 0;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #7deb98;
        }

        .result-count {
            margin: 0 0 16px;
            color: #aab3c2;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid #2a303b;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        th,
        td {
            padding: 13px 14px;
            text-align: left;
            border-bottom: 1px solid #2a303b;
            vertical-align: top;
        }

        th {
            color: #9faabd;
            background: #111318;
            font-size: 12px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .company-link {
            color: #e9edf3;
            text-decoration: none;
            font-weight: 700;
            line-height: 1.4;
        }

        .company-link:hover {
            color: #67e08a;
        }

        .muted {
            color: #aab3c2;
            font-size: 13px;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            color: #b9f5c9;
            background: #143b22;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .empty {
            padding: 28px;
            color: #aab3c2;
            text-align: center;
        }

        .pagination {
            margin-top: 20px;
        }

        @media (max-width: 900px) {
            .filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .container {
                width: min(100% - 24px, 1400px);
                padding-top: 20px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .filters {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <div class="topbar">
            <h1>CRM leadovi</h1>
            <a class="back-link" href="{{ route('companies.index') }}">← Sve firme</a>
        </div>

        <section class="card">
            <form method="GET" action="{{ route('leads.index') }}" class="filters">
                <div class="field">
                    <label for="search">Pretraga firme</label>
                    <input
                        id="search"
                        name="search"
                        type="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Naziv firme ili matični broj"
                    >
                </div>

                <div class="field">
                    <label for="lead_status">Status leada</label>
                    <select id="lead_status" name="lead_status">
                        <option value="">Svi statusi</option>
                        @foreach (App\Models\CompanyLead::LEAD_STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected($filters['lead_status'] === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="website_status">Status sajta</label>
                    <select id="website_status" name="website_status">
                        <option value="">Svi statusi</option>
                        @foreach (App\Models\CompanyLead::WEBSITE_STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected($filters['website_status'] === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="sort">Sortiranje</label>
                    <select id="sort" name="sort">
                        <option value="score_desc" @selected($filters['sort'] === 'score_desc')>
                            Score: najveći prvo
                        </option>
                        <option value="score_asc" @selected($filters['sort'] === 'score_asc')>
                            Score: najmanji prvo
                        </option>
                        <option value="updated_desc" @selected($filters['sort'] === 'updated_desc')>
                            Poslednje izmenjeno
                        </option>
                    </select>
                </div>

                <button type="submit">Primeni</button>
            </form>

            <p class="result-count">
                Ukupno leadova: {{ $leads->total() }}
            </p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Firma</th>
                            <th>Lead status</th>
                            <th>Score</th>
                            <th>Sajt</th>
                            <th>Kontakt</th>
                            <th>Kontaktirano</th>
                            <th>Izmenjeno</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr>
                                <td>
                                    <a
                                        class="company-link"
                                        href="{{ route('companies.show', $lead->company) }}"
                                    >
                                        {{ $lead->company->business_name }}
                                    </a>
                                    <div class="muted">
                                        MB: {{ $lead->company->registration_number }}
                                    </div>
                                </td>

                                <td>
                                    <span class="badge">
                                        {{ App\Models\CompanyLead::LEAD_STATUSES[$lead->lead_status] ?? $lead->lead_status }}
                                    </span>
                                </td>

                                <td>{{ $lead->lead_score }}</td>

                                <td>
                                    <div>
                                        {{ App\Models\CompanyLead::WEBSITE_STATUSES[$lead->website_status] ?? $lead->website_status }}
                                    </div>

                                    @if ($lead->website_url)
                                        <a
                                            class="muted"
                                            href="{{ $lead->website_url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {{ $lead->website_url }}
                                        </a>
                                    @endif
                                </td>

                                <td>
                                    @if ($lead->phone)
                                        <div>{{ $lead->phone }}</div>
                                    @endif

                                    @if ($lead->email)
                                        <div class="muted">{{ $lead->email }}</div>
                                    @endif

                                    @if (! $lead->phone && ! $lead->email)
                                        <span class="muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $lead->contacted_at?->format('d.m.Y. H:i') ?? '—' }}
                                </td>

                                <td>
                                    {{ $lead->updated_at?->format('d.m.Y. H:i') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty">
                                    Nema leadova za izabrane filtere.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($leads->hasPages())
                <div class="pagination">
                    {{ $leads->links() }}
                </div>
            @endif
        </section>
    </main>
</body>
</html>
