<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyLead;
use App\Models\CompanyWebsiteAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyWebsiteAuditTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(string $registrationNumber = '21000001'): Company
    {
        return Company::create([
            'registration_number' => $registrationNumber,
            'business_name' => 'Audit Test Firma DOO',
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
            'lead_status' => 'new',
            'lead_score' => 10,
        ]);
    }

    public function test_it_creates_a_website_audit_for_a_lead(): void
    {
        $company = $this->createCompany();
        $lead = $this->createLead($company);

        $audit = $lead->websiteAudits()->create([
            'requested_url' => 'https://example.com',
            'final_url' => 'https://www.example.com/',
            'status' => CompanyWebsiteAudit::STATUS_COMPLETED,
            'http_status' => 200,
            'is_reachable' => true,
            'has_https' => true,
            'redirect_count' => 1,
            'response_time_ms' => 640,
            'page_title' => 'Example',
            'meta_description' => 'Example meta description',
            'notes' => 'Ručna tehnička provera sajta.',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->assertDatabaseHas('company_website_audits', [
            'id' => $audit->id,
            'company_lead_id' => $lead->id,
            'requested_url' => 'https://example.com',
            'status' => 'completed',
            'http_status' => 200,
            'is_reachable' => true,
            'has_https' => true,
        ]);

        $this->assertSame('v1', $audit->audit_version);
    }

    public function test_lead_has_many_website_audits(): void
    {
        $company = $this->createCompany('21000002');
        $lead = $this->createLead($company);

        $lead->websiteAudits()->create([
            'requested_url' => 'https://example.com',
            'status' => CompanyWebsiteAudit::STATUS_COMPLETED,
            'http_status' => 200,
            'is_reachable' => true,
        ]);

        $lead->websiteAudits()->create([
            'requested_url' => 'https://example.com',
            'status' => CompanyWebsiteAudit::STATUS_FAILED,
            'error_message' => 'Connection timeout',
        ]);

        $this->assertCount(2, $lead->websiteAudits()->get());
        $this->assertSame(2, $lead->websiteAudits()->count());
    }

    public function test_same_lead_can_have_multiple_audits(): void
    {
        $company = $this->createCompany('21000003');
        $lead = $this->createLead($company);

        for ($i = 0; $i < 3; $i++) {
            $lead->websiteAudits()->create([
                'requested_url' => 'https://example.com',
                'status' => CompanyWebsiteAudit::STATUS_COMPLETED,
            ]);
        }

        $this->assertSame(3, $lead->websiteAudits()->count());
    }

    public function test_audits_are_deleted_when_lead_is_deleted(): void
    {
        $company = $this->createCompany('21000004');
        $lead = $this->createLead($company);

        $audit = $lead->websiteAudits()->create([
            'requested_url' => 'https://example.com',
            'status' => CompanyWebsiteAudit::STATUS_COMPLETED,
        ]);

        $lead->delete();

        $this->assertDatabaseMissing('company_website_audits', [
            'id' => $audit->id,
        ]);

        $this->assertDatabaseCount('company_website_audits', 0);
    }

    public function test_audit_belongs_to_lead(): void
    {
        $company = $this->createCompany('21000005');
        $lead = $this->createLead($company);

        $audit = $lead->websiteAudits()->create([
            'requested_url' => 'https://example.com',
            'status' => CompanyWebsiteAudit::STATUS_PENDING,
        ]);

        $this->assertTrue($audit->companyLead->is($lead));
        $this->assertTrue($lead->websiteAudits->contains($audit));
    }

    public function test_it_rejects_an_invalid_audit_status(): void
    {
        $company = $this->createCompany('21000006');
        $lead = $this->createLead($company);

        $this->expectException(\InvalidArgumentException::class);

        $lead->websiteAudits()->create([
            'requested_url' => 'https://example.com',
            'status' => 'invalid_status',
        ]);
    }

    public function test_it_updates_audit_status(): void
    {
        $company = $this->createCompany('21000007');
        $lead = $this->createLead($company);

        $audit = $lead->websiteAudits()->create([
            'requested_url' => 'https://example.com',
            'status' => CompanyWebsiteAudit::STATUS_PENDING,
        ]);

        $audit->update([
            'status' => CompanyWebsiteAudit::STATUS_COMPLETED,
            'http_status' => 200,
            'is_reachable' => true,
            'completed_at' => now(),
        ]);

        $this->assertDatabaseHas('company_website_audits', [
            'id' => $audit->id,
            'status' => 'completed',
            'http_status' => 200,
            'is_reachable' => true,
        ]);
    }
}
