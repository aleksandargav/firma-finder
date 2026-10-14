<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyLeadCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(string $registrationNumber = '20000001'): Company
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

    public function test_it_creates_a_company_lead(): void
    {
        $company = $this->createCompany();

        $response = $this->post(route('companies.leads.store', $company), [
            'website_url' => 'https://example.com',
            'website_status' => 'active',
            'phone' => '+381601234567',
            'email' => 'info@example.com',
            'lead_status' => 'new',
            'lead_score' => 25,
            'notes' => 'Prvi kontakt podaci.',
        ]);

        $response->assertRedirect(route('companies.show', $company));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('company_leads', [
            'company_id' => $company->id,
            'website_url' => 'https://example.com',
            'website_status' => 'active',
            'lead_status' => 'new',
            'lead_score' => 25,
        ]);
    }

    public function test_it_updates_the_existing_company_lead(): void
    {
        $company = $this->createCompany('20000002');

        $company->lead()->create([
            'website_url' => 'https://old-example.com',
            'website_status' => 'unknown',
            'lead_status' => 'new',
            'lead_score' => 0,
        ]);

        $response = $this->put(route('companies.leads.update', $company), [
            'website_url' => 'https://new-example.com',
            'website_status' => 'active',
            'phone' => '+381601234567',
            'email' => 'info@example.com',
            'lead_status' => 'qualified',
            'lead_score' => 80,
            'notes' => 'Ažurirani podaci.',
        ]);

        $response->assertRedirect(route('companies.show', $company));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('company_leads', [
            'company_id' => $company->id,
            'website_url' => 'https://new-example.com',
            'website_status' => 'active',
            'lead_status' => 'qualified',
            'lead_score' => 80,
        ]);

        $this->assertDatabaseCount('company_leads', 1);
    }

    public function test_it_rejects_an_invalid_lead_score(): void
    {
        $company = $this->createCompany('20000003');

        $response = $this->post(route('companies.leads.store', $company), [
            'website_url' => 'https://example.com',
            'website_status' => 'active',
            'lead_status' => 'new',
            'lead_score' => 101,
        ]);

        $response->assertSessionHasErrors('lead_score');
        $this->assertDatabaseCount('company_leads', 0);
    }

    public function test_it_rejects_an_invalid_lead_status(): void
    {
        $company = $this->createCompany('20000004');

        $response = $this->post(route('companies.leads.store', $company), [
            'website_url' => 'https://example.com',
            'website_status' => 'active',
            'lead_status' => 'invalid_status',
            'lead_score' => 10,
        ]);

        $response->assertSessionHasErrors('lead_status');
        $this->assertDatabaseCount('company_leads', 0);
    }

    public function test_it_sets_contacted_at_only_the_first_time(): void
    {
        $company = $this->createCompany('20000005');

        $lead = $company->lead()->create([
            'website_url' => 'https://example.com',
            'website_status' => 'unknown',
            'lead_status' => 'contacted',
            'lead_score' => 10,
        ]);

        $this->assertNotNull($lead->refresh()->contacted_at);

        $originalContactedAt = $lead->contacted_at;

        $lead->update([
            'lead_status' => 'qualified',
            'lead_score' => 70,
        ]);

        $this->assertSame(
            $originalContactedAt->format('Y-m-d H:i:s'),
            $lead->refresh()->contacted_at->format('Y-m-d H:i:s')
        );
    }

    public function test_it_does_not_change_contacted_at_when_status_was_not_contacted(): void
    {
        $company = $this->createCompany('20000006');

        $lead = $company->lead()->create([
            'website_url' => 'https://example.com',
            'website_status' => 'unknown',
            'lead_status' => 'new',
            'lead_score' => 10,
        ]);

        $this->assertNull($lead->refresh()->contacted_at);
    }
}
