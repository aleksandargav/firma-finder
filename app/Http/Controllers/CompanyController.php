<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\CompanyLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $municipality = trim((string) $request->query('municipality', ''));
        $activity = trim((string) $request->query('activity', ''));
        $status = trim((string) $request->query('status', 'Активан'));

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
            ],
        ]);
    }
    public function show(Company $company): View
    {
        return view('companies.show', [
            'company' => $company,
        ]);
    }
    public function storeLead(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'website_url' => ['nullable', 'string', 'max:500'],
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
            ->with('success', 'Lead je uspešno dodat.');
    }

    public function updateLead(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'website_url' => ['nullable', 'string', 'max:500'],
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
            ->with('success', 'Lead je uspešno ažuriran.');
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
}
