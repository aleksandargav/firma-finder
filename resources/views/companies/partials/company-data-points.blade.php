<section class="card audits">
    <h2>Enrichment kandidati</h2>

    <form
        method="POST"
        action="{{ route('companies.data-points.store', $company) }}"
        class="audit-form"
    >
        @csrf

        <h3>Dodaj enrichment kandidata</h3>

        <div class="form-grid">
            <div class="field">
                <label for="data_point_field">Tip podatka</label>
                <select id="data_point_field" name="field">
                    @foreach (App\Models\CompanyDataPoint::fields() as $field)
                        <option
                            value="{{ $field }}"
                            @selected(old('field') === $field)
                        >
                            {{ ucfirst($field) }}
                        </option>
                    @endforeach
                </select>

                @error('field')
                <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="data_point_value">Vrednost</label>
                <input
                    id="data_point_value"
                    name="value"
                    type="text"
                    value="{{ old('value') }}"
                    placeholder="https://primer.rs"
                >

                @error('value')
                <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="data_point_confidence">Confidence</label>
                <select id="data_point_confidence" name="confidence">
                    @foreach (App\Models\CompanyDataPoint::confidences() as $confidence)
                        <option
                            value="{{ $confidence }}"
                            @selected(old('confidence', App\Models\CompanyDataPoint::CONFIDENCE_MEDIUM) === $confidence)
                        >
                            {{ ucfirst($confidence) }}
                        </option>
                    @endforeach
                </select>

                @error('confidence')
                <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="data_point_license">Licencni status</label>
                <select id="data_point_license" name="data_license_status">
                    @foreach (App\Models\CompanyDataPoint::licenseStatuses() as $licenseStatus)
                        <option
                            value="{{ $licenseStatus }}"
                            @selected(old('data_license_status', App\Models\CompanyDataPoint::LICENSE_ALLOWED) === $licenseStatus)
                        >
                            {{ ucfirst($licenseStatus) }}
                        </option>
                    @endforeach
                </select>

                @error('data_license_status')
                <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="field full">
                <label for="data_point_notes">Napomene</label>
                <textarea
                    id="data_point_notes"
                    name="notes"
                >{{ old('notes') }}</textarea>

                @error('notes')
                <span class="error-text">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="actions">
            <button type="submit">Dodaj kandidata</button>
        </div>
    </form>

    @if ($company->dataPoints->isEmpty())
        <div class="lead-summary muted">
            Za ovu firmu još nema sačuvanih enrichment kandidata.
        </div>
    @else
        @foreach ($company->dataPoints as $dataPoint)
            <article class="audit-item">
                <div class="audit-header">
                    <strong>
                        {{ ucfirst($dataPoint->field) }}:
                        {{ $dataPoint->value }}
                    </strong>

                    <div class="audit-header-actions">
                        <span class="badge">
                            {{ ucfirst($dataPoint->status) }}
                        </span>

                        @if ($dataPoint->status === App\Models\CompanyDataPoint::STATUS_CANDIDATE)
                            <form
                                method="POST"
                                action="{{ route('data-points.verify', $dataPoint) }}"
                            >
                                @csrf

                                <button type="submit" class="audit-run-button">
                                    Potvrdi
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('data-points.needs-review', $dataPoint) }}"
                            >
                                @csrf

                                <button type="submit" class="audit-run-button">
                                    Za pregled
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('data-points.reject', $dataPoint) }}"
                            >
                                @csrf

                                <button type="submit" class="audit-run-button">
                                    Odbaci
                                </button>
                            </form>
                        @endif

                        @if (
                            $dataPoint->field === App\Models\CompanyDataPoint::FIELD_WEBSITE
                            && $dataPoint->status === App\Models\CompanyDataPoint::STATUS_VERIFIED
                        )
                            <form
                                method="POST"
                                action="{{ route('data-points.apply-to-lead', $dataPoint) }}"
                            >
                                @csrf

                                <button type="submit" class="audit-run-button">
                                    Primeni u CRM
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="audit-details">
                    <p><strong>Izvor:</strong> {{ ucfirst($dataPoint->source) }}</p>

                    @if (
                        $dataPoint->field === App\Models\CompanyDataPoint::FIELD_WEBSITE
                        && $dataPoint->status === App\Models\CompanyDataPoint::STATUS_VERIFIED
                    )
                        <p>
                            <strong>Trenutni CRM website:</strong>
                            {{ $company->lead?->website_url ?? 'nije definisan' }}
                        </p>
                    @endif

                    <p><strong>Confidence:</strong> {{ ucfirst($dataPoint->confidence) }}</p>
                    <p><strong>Licenca:</strong> {{ ucfirst($dataPoint->data_license_status) }}</p>
                    <p><strong>Dodat:</strong> {{ $dataPoint->created_at?->format('d.m.Y. H:i') }}</p>

                    @if ($dataPoint->verified_at)
                        <p><strong>Potvrđeno:</strong> {{ $dataPoint->verified_at->format('d.m.Y. H:i') }}</p>
                    @endif

                    @if ($dataPoint->notes)
                        <p><strong>Napomene:</strong> {{ $dataPoint->notes }}</p>
                    @endif
                </div>
            </article>
        @endforeach
    @endif
</section>
