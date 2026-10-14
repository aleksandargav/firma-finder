<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CompanyLeadWebsiteCheckedAtTest extends TestCase
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

    public function test_it_sets_website_checked_at_when_creating_a_lead(): void
    {
        Carbon::setTestNow('2026-10-14 12:00:00');

        $company = $this->createCompany('40000001');

        $response = $this->post(route('companies.leads.store', $company), [
            'website_url' => 'https://primer.rs',
            'website_status' => 'active',
            'lead_status' => 'new',
            'lead_score' => 10,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('company_leads', [
            'company_id' => $company->id,
            'website_status' => 'active',
            'website_checked_at' => '2026-10-14 12:00:00',
        ]);
    }

    public function test_it_updates_website_checked_at_when_website_status_changes(): void
    {
        Carbon::setTestNow('2026-10-14 12:00:00');

        $company = $this->createCompany('40000002');

        $company->lead()->create([
            'website_url' => 'https://primer.rs',
            'website_status' => 'unknown',
            'lead_status' => 'new',
            'lead_score' => 10,
            'website_checked_at' => '2026-10-01 09:00:00',
        ]);

        $response = $this->put(route('companies.leads.update', $company), [
            'website_url' => 'https://primer.rs',
            'website_status' => 'inactive',
            'lead_status' => 'new',
            'lead_score' => 10,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('company_leads', [
            'company_id' => $company->id,
            'website_status' => 'inactive',
            'website_checked_at' => '2026-10-14 12:00:00',
        ]);
    }

    public function test_it_does_not_change_website_checked_at_when_status_is_same(): void
    {
        Carbon::setTestNow('2026-10-14 12:00:00');

        $company = $this->createCompany('40000003');

        $lead = $company->lead()->create([
            'website_url' => 'https://primer.rs',
            'website_status' => 'active',
            'lead_status' => 'new',
            'lead_score' => 10,
        ]);

        $lead->update([
            'website_checked_at' => '2026-10-01 09:00:00',
        ]);

        $response = $this->put(route('companies.leads.update', $company), [
            'website_url' => 'https://primer.rs',
            'website_status' => 'active',
            'lead_status' => 'qualified',
            'lead_score' => 70,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('company_leads', [
            'company_id' => $company->id,
            'website_status' => 'active',
            'website_checked_at' => '2026-10-01 09:00:00',
        ]);
    }
}
