<section class="card audits">
    <h2>Website kandidati</h2>

    <form
        method="POST"
        action="{{ route('companies.data-points.store', $company) }}"
        class="audit-form"
    >
        @csrf

        <h3>Dodaj website kandidat</h3>

        <div class="form-grid">
            <div class="field full">
                <label for="data_point_value">Website URL</label>
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

    @if ($company->companyDataPoints->isEmpty())
        <div class="lead-summary muted">
            Za ovu firmu još nema sačuvanih website kandidata.
        </div>
    @else
        @foreach ($company->companyDataPoints as $dataPoint)
            <article class="audit-item">
                <div class="audit-header">
                    <strong>{{ $dataPoint->value }}</strong>

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
                                action="{{ route('data-points.reject', $dataPoint) }}"
                            >
                                @csrf

                                <button type="submit" class="audit-run-button">
                                    Odbaci
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="audit-details">
                    <p><strong>Izvor:</strong> {{ ucfirst($dataPoint->source) }}</p>
                    <p><strong>Confidence:</strong> {{ ucfirst($dataPoint->confidence) }}</p>
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
