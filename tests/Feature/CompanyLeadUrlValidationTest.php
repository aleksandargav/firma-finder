<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyLeadUrlValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(string $registrationNumber): Company
    {
        return Company::create([
            'registration_number' => $registrationNumber,
            'business_name' => 'Test Firma DOO',
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

    public function test_it_normalizes_url_without_protocol_to_https(): void
    {
        $company = $this->createCompany('30000001');

        $response = $this->post(route('companies.leads.store', $company), [
            'website_url' => 'primer.rs',
            'website_status' => 'active',
            'lead_status' => 'new',
            'lead_score' => 10,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('company_leads', [
            'company_id' => $company->id,
            'website_url' => 'https://primer.rs',
        ]);
    }

    public function test_it_keeps_existing_http_protocol(): void
    {
        $company = $this->createCompany('30000002');

        $response = $this->post(route('companies.leads.store', $company), [
            'website_url' => 'http://primer.rs',
            'website_status' => 'active',
            'lead_status' => 'new',
            'lead_score' => 10,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('company_leads', [
            'company_id' => $company->id,
            'website_url' => 'http://primer.rs',
        ]);
    }

    public function test_it_rejects_an_invalid_url(): void
    {
        $company = $this->createCompany('30000003');

        $response = $this->post(route('companies.leads.store', $company), [
            'website_url' => 'primer',
            'website_status' => 'active',
            'lead_status' => 'new',
            'lead_score' => 10,
        ]);

        $response->assertSessionHasErrors('website_url');
        $this->assertDatabaseCount('company_leads', 0);
    }

    public function test_it_allows_an_empty_url(): void
    {
        $company = $this->createCompany('30000004');

        $response = $this->post(route('companies.leads.store', $company), [
            'website_url' => '',
            'website_status' => 'unknown',
            'lead_status' => 'new',
            'lead_score' => 0,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('company_leads', [
            'company_id' => $company->id,
            'website_url' => null,
        ]);
    }
}
