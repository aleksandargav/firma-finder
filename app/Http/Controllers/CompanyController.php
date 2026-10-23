<?php

namespace App\Http\Controllers;

use App\Models\CompanyDataPoint;
use App\Services\WebsiteAuditService;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\CompanyLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use App\Models\CompanyWebsiteAudit;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $municipality = trim((string) $request->query('municipality', ''));
        $activity = trim((string) $request->query('activity', ''));
        $status = trim((string) $request->query('status', 'Активан'));

        $leadFilter = trim((string) $request->query('lead_filter', 'all'));

        if (! in_array($leadFilter, ['all', 'with_lead', 'without_lead'], true)) {
            $leadFilter = 'all';
        }

        $companies = Company::query()
            ->with('lead')
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('business_name', 'like', "%{$search}%")
                            ->orWhere('registration_number', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                $municipality !== '',
                fn (Builder $query): Builder => $query->where(
                    'municipality_code',
                    $municipality
                )
            )
            ->when(
                $activity !== '',
                fn (Builder $query): Builder => $query->where(
                    'activity_code',
                    $activity
                )
            )
            ->when(
                $status !== 'all',
                fn (Builder $query): Builder => $query->where('status', $status)
            )

            ->when(
                $leadFilter === 'with_lead',
                fn (Builder $query): Builder => $query->has('lead')
            )
            ->when(
                $leadFilter === 'without_lead',
                fn (Builder $query): Builder => $query->doesntHave('lead')
            )

            ->orderBy('business_name')
            ->paginate(50)
            ->withQueryString();

        $municipalities = Company::query()
            ->select('municipality_code', 'municipality_name')
            ->whereNotNull('municipality_code')
            ->whereNotNull('municipality_name')
            ->distinct()
            ->orderBy('municipality_name')
            ->get();

        $statuses = Company::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('companies.index', [
            'companies' => $companies,
            'municipalities' => $municipalities,
            'statuses' => $statuses,
            'filters' => [
                'search' => $search,
                'municipality' => $municipality,
                'activity' => $activity,
                'status' => $status,
                'lead_filter' => $leadFilter,
            ],
        ]);
    }
    public function show(Company $company): View
    {
        $company->load([
            'lead.websiteAudits' => fn ($query) => $query->latestFirst(),
        ]);

        return view('companies.show', [
            'company' => $company,
        ]);
    }
    public function storeLead(Request $request, Company $company): RedirectResponse
    {
        $request->merge([
            'website_url' => $this->normalizeWebsiteUrl(
                $request->input('website_url')
            ),
        ]);
        $validated = $request->validate([
            'website_url' => [
                'nullable',
                'url',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $host = parse_url((string) $value, PHP_URL_HOST);

                    if ($host === false || $host === null || ! str_contains($host, '.')) {
                        $fail('The website URL must contain a valid domain, for example example.com.');
                    }
                },
            ],
            'website_status' => ['required', Rule::in(array_keys(CompanyLead::WEBSITE_STATUSES))],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'lead_status' => ['required', Rule::in(array_keys(CompanyLead::LEAD_STATUSES))],
            'lead_score' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $company->lead()->create($validated);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'The lead was added successfully.');
    }

    public function updateLead(Request $request, Company $company): RedirectResponse
    {
        $request->merge([
            'website_url' => $this->normalizeWebsiteUrl(
                $request->input('website_url')
            ),
        ]);

        $validated = $request->validate([
            'website_url' => [
                'nullable',
                'url',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $host = parse_url((string) $value, PHP_URL_HOST);

                    if ($host === false || $host === null || ! str_contains($host, '.')) {
                        $fail('The website URL must contain a valid domain, for example example.com.');
                    }
                },
            ],
            'website_status' => ['required', Rule::in(array_keys(CompanyLead::WEBSITE_STATUSES))],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'lead_status' => ['required', Rule::in(array_keys(CompanyLead::LEAD_STATUSES))],
            'lead_score' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $company->lead()->updateOrCreate(
            ['company_id' => $company->id],
            $validated
        );

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'The lead was updated successfully.');
    }

    public function storeWebsiteAudit(Request $request, Company $company): RedirectResponse
    {
        $lead = $company->lead;

        if (! $lead) {
            return redirect()
                ->route('companies.show', $company)
                ->with('error', 'A website audit can only be added for a company that has a CRM lead.');
        }

        $request->merge([
            'requested_url' => $this->normalizeWebsiteUrl(
                $request->input('requested_url')
            ),
        ]);

        $validated = $request->validate([
            'requested_url' => [
                'nullable',
                'url',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $host = parse_url((string) $value, PHP_URL_HOST);

                    if ($host === false || $host === null || ! str_contains($host, '.')) {
                        $fail('The URL to check must contain a valid domain, for example example.com.');
                    }
                },
            ],
            'final_url' => [
                'nullable',
                'url',
                'max:500',
            ],
            'status' => [
                'required',
                Rule::in(CompanyWebsiteAudit::STATUSES),
            ],
            'http_status' => ['nullable', 'integer', 'min:100', 'max:599'],
            'is_reachable' => ['nullable', 'boolean'],
            'has_https' => ['nullable', 'boolean'],
            'redirect_count' => ['nullable', 'integer', 'min:0', 'max:100'],
            'response_time_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
            'page_title' => ['nullable', 'string', 'max:500'],
            'meta_description' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'error_message' => ['nullable', 'string', 'max:5000'],
            'started_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
        ]);

        $lead->websiteAudits()->create($validated);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'The website audit was added successfully.');
    }

    public function runWebsiteAudit(
        CompanyWebsiteAudit $audit,
        WebsiteAuditService $auditService
    ): RedirectResponse {
        $auditService->check($audit);

        return redirect()
            ->route('companies.show', $audit->companyLead->company)
            ->with('success', 'The technical website check is complete.');
    }

    public function applyWebsiteDataPointToLead(
        CompanyDataPoint $dataPoint
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
                ->with('error', 'Only a verified website candidate can be applied to the CRM.');
        }

        if ($dataPoint->status !== CompanyDataPoint::STATUS_VERIFIED) {
            return redirect()
                ->route('companies.show', $company)
                ->with('error', 'Only a verified website candidate can be applied to the CRM.');
        }

        $company->lead()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'website_url' => $dataPoint->value,
                'website_status' => CompanyLead::WEBSITE_STATUS_ACTIVE,
                'website_checked_at' => now(),
            ]
        );

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'The verified website was applied to the CRM.');
    }

    public function leadsIndex(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $leadStatus = trim((string) $request->query('lead_status', ''));
        $websiteStatus = trim((string) $request->query('website_status', ''));
        $sort = trim((string) $request->query('sort', 'score_desc'));

        $leads = CompanyLead::query()
            ->with('company')
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->whereHas(
                        'company',
                        function (Builder $companyQuery) use ($search): void {
                            $companyQuery->where('business_name', 'like', "%{$search}%")
                                ->orWhere('registration_number', 'like', "%{$search}%");
                        }
                    );
                }
            )
            ->when(
                $leadStatus !== '',
                fn (Builder $query): Builder => $query->where('lead_status', $leadStatus)
            )
            ->when(
                $websiteStatus !== '',
                fn (Builder $query): Builder => $query->where('website_status', $websiteStatus)
            )
            ->when(
                $sort === 'score_asc',
                fn (Builder $query): Builder => $query
                    ->orderBy('lead_score')
                    ->orderByDesc('updated_at')
            )
            ->when(
                $sort === 'updated_desc',
                fn (Builder $query): Builder => $query
                    ->orderByDesc('updated_at')
                    ->orderByDesc('lead_score')
            )
            ->when(
                ! in_array($sort, ['score_asc', 'updated_desc'], true),
                fn (Builder $query): Builder => $query
                    ->orderByDesc('lead_score')
                    ->orderByDesc('updated_at')
            )
            ->paginate(25)
            ->withQueryString();

        return view('leads.index', [
            'leads' => $leads,
            'filters' => [
                'search' => $search,
                'lead_status' => $leadStatus,
                'website_status' => $websiteStatus,
                'sort' => $sort,
            ],
        ]);
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
