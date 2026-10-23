<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyDataPoint;
use App\Models\CompanyWebsiteAudit;
use App\Services\WebsiteAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyDataPointController extends Controller
{
    public function store(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'field' => [
                'required',
                'string',
                Rule::in(CompanyDataPoint::fields()),
            ],
            'value' => ['required', 'string', 'max:500'],
            'confidence' => [
                'required',
                'string',
                Rule::in(CompanyDataPoint::confidences()),
            ],
            'data_license_status' => [
                'required',
                'string',
                Rule::in(CompanyDataPoint::licenseStatuses()),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $value = $this->normalizeValue($validated['field'], $validated['value']);

        if ($value === null) {
            return back()
                ->withInput()
                ->withErrors([
                    'value' => 'The entered value is not valid for the selected data type.',
                ]);
        }

        $company->dataPoints()->create([
            'field' => $validated['field'],
            'value' => $value,
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => $validated['confidence'],
            'identity_match_score' => 100,
            'data_license_status' => $validated['data_license_status'],
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
            'retrieved_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'The enrichment candidate was added successfully.');
    }

    public function verify(CompanyDataPoint $dataPoint): RedirectResponse
    {
        if ($dataPoint->source !== CompanyDataPoint::SOURCE_MANUAL) {
            return redirect()
                ->route('companies.show', $dataPoint->company)
                ->with('error', 'For now, only manually entered candidates can be verified manually.');
        }

        $dataPoint->update([
            'status' => CompanyDataPoint::STATUS_VERIFIED,
            'verified_at' => now(),
            'verified_by' => 'user',
        ]);

        return redirect()
            ->route('companies.show', $dataPoint->company)


            ->with('success', 'The enrichment candidate was verified.');
    }

    public function reject(CompanyDataPoint $dataPoint): RedirectResponse
    {
        $dataPoint->update([
            'status' => CompanyDataPoint::STATUS_REJECTED,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return redirect()
            ->route('companies.show', $dataPoint->company)
            ->with('success', 'The enrichment candidate was rejected.');
    }

    public function needsReview(CompanyDataPoint $dataPoint): RedirectResponse
    {
        $dataPoint->update([
            'status' => CompanyDataPoint::STATUS_NEEDS_REVIEW,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return redirect()
            ->route('companies.show', $dataPoint->company)
            ->with('success', 'The enrichment candidate was flagged for review.');
    }

    public function runAudit(
        CompanyDataPoint $dataPoint,
        WebsiteAuditService $auditService
    ): RedirectResponse {
        $company = $dataPoint->company;

        if (! $company) {
            return redirect()
                ->route('companies.index')
                ->with('error', 'The enrichment record is not linked to an APR company.');
        }

        if ($dataPoint->field !== CompanyDataPoint::FIELD_WEBSITE) {
            return redirect()
                ->route('companies.show', $company)
                ->with('error', 'A website audit can only be run for a website candidate.');
        }

        if ($dataPoint->status !== CompanyDataPoint::STATUS_VERIFIED) {
            return redirect()
                ->route('companies.show', $company)
                ->with('error', 'A website audit can only be run for a verified candidate.');
        }

        $lead = $company->lead;

        if (! $lead) {
            return redirect()
                ->route('companies.show', $company)
                ->with('error', 'A website audit can only be run for a company that has a CRM lead.');
        }

        $audit = $lead->websiteAudits()->create([
            'requested_url' => $dataPoint->value,
            'status' => CompanyWebsiteAudit::STATUS_PENDING,
        ]);

        $auditService->check($audit);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'The website audit was run and completed.');
    }

    private function normalizeValue(string $field, string $value): ?string
    {
        $value = trim($value);

        return match ($field) {
            CompanyDataPoint::FIELD_WEBSITE => $this->normalizeWebsiteUrl($value),
            CompanyDataPoint::FIELD_EMAIL => $this->normalizeEmail($value),
            CompanyDataPoint::FIELD_PHONE,
            CompanyDataPoint::FIELD_ADDRESS => $value !== '' ? $value : null,
            default => null,
        };
    }

    private function normalizeWebsiteUrl(string $websiteUrl): ?string
    {
        if (! preg_match('#^https?://#i', $websiteUrl)) {
            $websiteUrl = 'https://' . $websiteUrl;
        }

        $host = parse_url($websiteUrl, PHP_URL_HOST);

        if ($host === false || $host === null || ! str_contains($host, '.')) {
            return null;
        }

        return $websiteUrl;
    }

    private function normalizeEmail(string $email): ?string
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ?: null;
    }
}
