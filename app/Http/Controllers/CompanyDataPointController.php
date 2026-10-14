<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyDataPoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyDataPointController extends Controller
{
    public function store(Request $request, Company $company): RedirectResponse
    {
        $request->merge([
            'value' => $this->normalizeWebsiteUrl(
                $request->input('value')
            ),
        ]);

        $validated = $request->validate([
            'value' => [
                'required',
                'url',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $host = parse_url((string) $value, PHP_URL_HOST);

                    if ($host === false || $host === null || ! str_contains($host, '.')) {
                        $fail('Website URL mora sadržati validan domen, na primer primer.rs.');
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $company->companyDataPoints()->create([
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => $validated['value'],
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => CompanyDataPoint::CONFIDENCE_MEDIUM,
            'identity_match_score' => 0,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
            'retrieved_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'Website kandidat je uspešno dodat.');
    }

    public function verify(CompanyDataPoint $dataPoint): RedirectResponse
    {
        if ($dataPoint->field !== CompanyDataPoint::FIELD_WEBSITE) {
            return redirect()
                ->route('companies.show', $dataPoint->company)
                ->with('error', 'Može se potvrditi samo website kandidat.');
        }

        if ($dataPoint->source !== CompanyDataPoint::SOURCE_MANUAL) {
            return redirect()
                ->route('companies.show', $dataPoint->company)
                ->with('error', 'Za sada se ručno potvrđuju samo ručno uneti kandidati.');
        }

        $dataPoint->update([
            'status' => CompanyDataPoint::STATUS_VERIFIED,
            'verified_at' => now(),
            'verified_by' => 'user',
        ]);

        return redirect()
            ->route('companies.show', $dataPoint->company)
            ->with('success', 'Website kandidat je potvrđen.');
    }

    public function reject(CompanyDataPoint $dataPoint): RedirectResponse
    {
        if ($dataPoint->field !== CompanyDataPoint::FIELD_WEBSITE) {
            return redirect()
                ->route('companies.show', $dataPoint->company)
                ->with('error', 'Može se odbaciti samo website kandidat.');
        }

        $dataPoint->update([
            'status' => CompanyDataPoint::STATUS_REJECTED,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return redirect()
            ->route('companies.show', $dataPoint->company)
            ->with('success', 'Website kandidat je odbijen.');
    }

    private function normalizeWebsiteUrl(?string $websiteUrl): ?string
    {
        $websiteUrl = trim((string) $websiteUrl);

        if ($websiteUrl === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $websiteUrl)) {
            $websiteUrl = 'https://' . $websiteUrl;
        }

        return $websiteUrl;
    }
}
