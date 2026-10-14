<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyLeadFilterTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(string $registrationNumber, string $businessName): Company
    {
        return Company::create([
            'registration_number' => $registrationNumber,
            'business_name' => $businessName,
            'municipality_code' => '11000',
            'municipality_name' => 'Beograd',
            'status' => 'Активан',
            'founded_at' => '2020-01-01',
            'legal_form' => 'DOO',
            'activity_code' => '6201',
            'source_snapshot_date' => '2026-10-10',
            'imported_at' => '2026-10-11 08:00:00',
        ]);
    }

    private function createLead(Company $company): CompanyLead
    {
        return $company->lead()->create([
            'website_url' => 'https://example.com',
            'website_status' => 'active',
            'lead_status' => 'qualified',
            'lead_score' => 10,
        ]);
    }

    public function test_all_filter_returns_companies_with_and_without_leads(): void
    {
        $companyWithLead = $this->createCompany('10000001', 'Firma Sa Leadom');
        $companyWithoutLead = $this->createCompany('10000002', 'Firma Bez Leada');

        $this->createLead($companyWithLead);

        $response = $this->get(route('companies.index', ['lead_filter' => 'all']));

        $response->assertOk();
        $response->assertSee('Firma Sa Leadom');
        $response->assertSee('Firma Bez Leada');
    }

    public function test_with_lead_filter_returns_only_companies_with_leads(): void
    {
        $companyWithLead = $this->createCompany('10000003', 'Firma Sa Leadom');
        $companyWithoutLead = $this->createCompany('10000004', 'Firma Bez Leada');

        $this->createLead($companyWithLead);

        $response = $this->get(route('companies.index', ['lead_filter' => 'with_lead']));

        $response->assertOk();
        $response->assertSee('Firma Sa Leadom');
        $response->assertDontSee('Firma Bez Leada');
    }

    public function test_without_lead_filter_returns_only_companies_without_leads(): void
    {
        $companyWithLead = $this->createCompany('10000005', 'Firma Sa Leadom');
        $companyWithoutLead = $this->createCompany('10000006', 'Firma Bez Leada');

        $this->createLead($companyWithLead);

        $response = $this->get(route('companies.index', ['lead_filter' => 'without_lead']));

        $response->assertOk();
        $response->assertSee('Firma Bez Leada');
        $response->assertDontSee('Firma Sa Leadom');
    }

    public function test_invalid_lead_filter_falls_back_to_all(): void
    {
        $companyWithLead = $this->createCompany('10000007', 'Firma Sa Leadom');
        $companyWithoutLead = $this->createCompany('10000008', 'Firma Bez Leada');

        $this->createLead($companyWithLead);

        $response = $this->get(route('companies.index', ['lead_filter' => 'neispravno']));

        $response->assertOk();
        $response->assertSee('Firma Sa Leadom');
        $response->assertSee('Firma Bez Leada');
    }
}
