<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyLead;
use App\Models\CompanyWebsiteAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyWebsiteAuditUiTest extends TestCase
{
    use RefreshDatabase;

    private function createCompanyWithLead(string $registrationNumber = '22000001'): Company
    {
        $company = Company::create([
            'registration_number' => $registrationNumber,
            'business_name' => 'Audit UI Test Firma DOO',
            'municipality_code' => '11000',
            'municipality_name' => 'Beograd',
            'status' => 'Активан',
            'founded_at' => '2020-01-01',
            'legal_form' => 'DOO',
            'activity_code' => '6201',
            'source_snapshot_date' => '2026-10-10',
            'imported_at' => '2026-10-11 08:00:00',
        ]);

        $company->lead()->create([
            'website_url' => 'https://example.com',
            'website_status' => 'active',
            'lead_status' => 'new',
            'lead_score' => 10,
        ]);

        return $company;
    }

    public function test_it_creates_a_website_audit_from_the_ui_form(): void
    {
        $company = $this->createCompanyWithLead();

        $response = $this->post(
            route('companies.website-audits.store', $company),
            [
                'requested_url' => 'https://example.com',
                'final_url' => 'https://www.example.com/',
                'status' => CompanyWebsiteAudit::STATUS_COMPLETED,
                'http_status' => 200,
                'is_reachable' => '1',
                'has_https' => '1',
                'redirect_count' => 1,
                'response_time_ms' => 500,
                'page_title' => 'Example',
                'meta_description' => 'Test meta description',
                'notes' => 'Ručna provera iz UI forme.',
                'error_message' => null,
                'started_at' => now()->format('Y-m-d H:i:s'),
                'completed_at' => now()->format('Y-m-d H:i:s'),
            ]
        );

        $response->assertRedirect(route('companies.show', $company));
        $response->assertSessionHas('success', 'The website audit was added successfully.');

        $this->assertDatabaseHas('company_website_audits', [
            'company_lead_id' => $company->lead->id,
            'requested_url' => 'https://example.com',
            'final_url' => 'https://www.example.com/',
            'status' => 'completed',
            'http_status' => 200,
            'is_reachable' => true,
            'has_https' => true,
            'redirect_count' => 1,
            'response_time_ms' => 500,
            'page_title' => 'Example',
            'meta_description' => 'Test meta description',
            'notes' => 'Ručna provera iz UI forme.',
        ]);
    }

    public function test_it_rejects_an_invalid_website_audit_url(): void
    {
        $company = $this->createCompanyWithLead('22000002');

        $response = $this->post(
            route('companies.website-audits.store', $company),
            [
                'requested_url' => 'primer',
                'status' => CompanyWebsiteAudit::STATUS_PENDING,
            ]
        );

        $response->assertSessionHasErrors('requested_url');
        $this->assertDatabaseCount('company_website_audits', 0);
    }

    public function test_it_rejects_an_invalid_audit_status(): void
    {
        $company = $this->createCompanyWithLead('22000003');

        $response = $this->post(
            route('companies.website-audits.store', $company),
            [
                'requested_url' => 'https://example.com',
                'status' => 'invalid_status',
            ]
        );

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseCount('company_website_audits', 0);
    }

    public function test_it_does_not_create_audit_without_lead(): void
    {
        $company = Company::create([
            'registration_number' => '22000004',
            'business_name' => 'Firma Bez Leada DOO',
            'municipality_code' => '11000',
            'municipality_name' => 'Beograd',
            'status' => 'Активан',
            'founded_at' => '2020-01-01',
            'legal_form' => 'DOO',
            'activity_code' => '6201',
            'source_snapshot_date' => '2026-10-10',
            'imported_at' => '2026-10-11 08:00:00',
        ]);

        $response = $this->post(
            route('companies.website-audits.store', $company),
            [
                'requested_url' => 'https://example.com',
                'status' => CompanyWebsiteAudit::STATUS_PENDING,
            ]
        );

        $response->assertRedirect(route('companies.show', $company));
        $this->assertDatabaseCount('company_website_audits', 0);
    }

    public function test_it_stores_long_urls_and_page_title_from_the_ui_form(): void
    {
        $company = $this->createCompanyWithLead('22000005');
        $requestedUrl = 'https://example.com/'.str_repeat('a', 400);
        $finalUrl = 'https://www.example.com/'.str_repeat('b', 400);
        $pageTitle = str_repeat('Ž', 400);

        $response = $this->post(
            route('companies.website-audits.store', $company),
            [
                'requested_url' => $requestedUrl,
                'final_url' => $finalUrl,
                'status' => CompanyWebsiteAudit::STATUS_COMPLETED,
                'page_title' => $pageTitle,
            ]
        );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('company_website_audits', [
            'company_lead_id' => $company->lead->id,
            'requested_url' => $requestedUrl,
            'final_url' => $finalUrl,
            'page_title' => $pageTitle,
        ]);
    }
}
