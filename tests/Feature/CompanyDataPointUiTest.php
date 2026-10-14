<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyDataPoint;
use App\Models\CompanyLead;
use App\Models\CompanyWebsiteAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompanyDataPointUiTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(string $registrationNumber): Company
    {
        return Company::create([
            'registration_number' => $registrationNumber,
            'business_name' => 'TEST DOO',
            'source_snapshot_date' => '2026-10-14',
        ]);
    }

    private function createDataPoint(
        Company $company,
        string $field,
        string $value,
        string $status = CompanyDataPoint::STATUS_CANDIDATE,
    ): CompanyDataPoint {
        return $company->dataPoints()->create([
            'field' => $field,
            'value' => $value,
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => CompanyDataPoint::CONFIDENCE_MEDIUM,
            'identity_match_score' => 100,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => $status,
        ]);
    }

    public function test_user_can_create_website_candidate(): void
    {
        $company = $this->createCompany('12345678');

        $response = $this->post(
            "/companies/{$company->registration_number}/data-points",
            [
                'field' => CompanyDataPoint::FIELD_WEBSITE,
                'value' => 'example.com',
                'confidence' => CompanyDataPoint::CONFIDENCE_MEDIUM,
                'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
                'notes' => 'Pronađeno ručno.',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('company_data_points', [
            'company_id' => $company->id,
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://example.com',
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => CompanyDataPoint::CONFIDENCE_MEDIUM,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
        ]);
    }

    public function test_user_can_create_email_candidate(): void
    {
        $company = $this->createCompany('12345679');

        $response = $this->post(
            "/companies/{$company->registration_number}/data-points",
            [
                'field' => CompanyDataPoint::FIELD_EMAIL,
                'value' => 'info@example.com',
                'confidence' => CompanyDataPoint::CONFIDENCE_HIGH,
                'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('company_data_points', [
            'company_id' => $company->id,
            'field' => CompanyDataPoint::FIELD_EMAIL,
            'value' => 'info@example.com',
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
        ]);
    }

    public function test_user_can_create_phone_candidate(): void
    {
        $company = $this->createCompany('12345680');

        $response = $this->post(
            "/companies/{$company->registration_number}/data-points",
            [
                'field' => CompanyDataPoint::FIELD_PHONE,
                'value' => '+381600000000',
                'confidence' => CompanyDataPoint::CONFIDENCE_MEDIUM,
                'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('company_data_points', [
            'company_id' => $company->id,
            'field' => CompanyDataPoint::FIELD_PHONE,
            'value' => '+381600000000',
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
        ]);
    }

    public function test_user_can_create_address_candidate(): void
    {
        $company = $this->createCompany('12345681');

        $response = $this->post(
            "/companies/{$company->registration_number}/data-points",
            [
                'field' => CompanyDataPoint::FIELD_ADDRESS,
                'value' => 'Kneza Miloša 10, Beograd',
                'confidence' => CompanyDataPoint::CONFIDENCE_LOW,
                'data_license_status' => CompanyDataPoint::LICENSE_UNKNOWN,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('company_data_points', [
            'company_id' => $company->id,
            'field' => CompanyDataPoint::FIELD_ADDRESS,
            'value' => 'Kneza Miloša 10, Beograd',
            'confidence' => CompanyDataPoint::CONFIDENCE_LOW,
            'data_license_status' => CompanyDataPoint::LICENSE_UNKNOWN,
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
        ]);
    }

    public function test_user_cannot_create_invalid_website_candidate(): void
    {
        $company = $this->createCompany('12345682');

        $response = $this->post(
            "/companies/{$company->registration_number}/data-points",
            [
                'field' => CompanyDataPoint::FIELD_WEBSITE,
                'value' => 'not a website',
                'confidence' => CompanyDataPoint::CONFIDENCE_MEDIUM,
                'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('value');

        $this->assertDatabaseCount('company_data_points', 0);
    }

    public function test_user_cannot_create_invalid_email_candidate(): void
    {
        $company = $this->createCompany('12345683');

        $response = $this->post(
            "/companies/{$company->registration_number}/data-points",
            [
                'field' => CompanyDataPoint::FIELD_EMAIL,
                'value' => 'invalid-email',
                'confidence' => CompanyDataPoint::CONFIDENCE_MEDIUM,
                'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('value');

        $this->assertDatabaseCount('company_data_points', 0);
    }

    public function test_user_can_verify_a_pending_data_point(): void
    {
        $company = $this->createCompany('12345684');
        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_WEBSITE,
            'https://example.com'
        );

        $response = $this->post("/data-points/{$dataPoint->id}/verify");

        $response->assertRedirect();

        $this->assertDatabaseHas('company_data_points', [
            'id' => $dataPoint->id,
            'status' => CompanyDataPoint::STATUS_VERIFIED,
        ]);

        $this->assertNotNull($dataPoint->fresh()->verified_at);
    }

    public function test_user_can_reject_a_pending_data_point(): void
    {
        $company = $this->createCompany('12345685');
        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_EMAIL,
            'info@example.com'
        );

        $response = $this->post("/data-points/{$dataPoint->id}/reject");

        $response->assertRedirect();

        $this->assertDatabaseHas('company_data_points', [
            'id' => $dataPoint->id,
            'status' => CompanyDataPoint::STATUS_REJECTED,
        ]);

        $this->assertNull($dataPoint->fresh()->verified_at);
    }

    public function test_user_can_mark_data_point_for_review(): void
    {
        $company = $this->createCompany('12345686');
        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_PHONE,
            '+381600000000'
        );

        $response = $this->post("/data-points/{$dataPoint->id}/needs-review");

        $response->assertRedirect();

        $this->assertDatabaseHas('company_data_points', [
            'id' => $dataPoint->id,
            'status' => CompanyDataPoint::STATUS_NEEDS_REVIEW,
        ]);

        $this->assertNull($dataPoint->fresh()->verified_at);
    }

    public function test_verifying_one_data_point_does_not_change_another(): void
    {
        $company = $this->createCompany('12345687');

        $verified = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_WEBSITE,
            'https://example.com'
        );

        $rejected = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_WEBSITE,
            'https://example.org'
        );

        $this->post("/data-points/{$verified->id}/verify");
        $this->post("/data-points/{$rejected->id}/reject");

        $this->assertSame(CompanyDataPoint::STATUS_VERIFIED, $verified->fresh()->status);
        $this->assertSame(CompanyDataPoint::STATUS_REJECTED, $rejected->fresh()->status);
    }

    public function test_user_can_apply_verified_website_to_lead_when_lead_does_not_exist(): void
    {
        $company = $this->createCompany('12345688');

        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_WEBSITE,
            'https://example.com',
            CompanyDataPoint::STATUS_VERIFIED
        );

        $dataPoint->update([
            'verified_at' => now(),
            'verified_by' => 'user',
        ]);

        $response = $this->post("/data-points/{$dataPoint->id}/apply-to-lead");

        $response->assertRedirect();

        $this->assertDatabaseHas('company_leads', [
            'company_id' => $company->id,
            'website_url' => 'https://example.com',
            'website_status' => CompanyLead::WEBSITE_STATUS_ACTIVE,
            'lead_status' => CompanyLead::LEAD_STATUS_NEW,
            'lead_score' => 0,
        ]);

        $this->assertNotNull($company->lead->website_checked_at);
    }

    public function test_user_can_apply_verified_website_to_existing_lead(): void
    {
        $company = $this->createCompany('12345689');

        $company->lead()->create([
            'website_url' => 'https://old-example.com',
            'website_status' => CompanyLead::WEBSITE_STATUS_UNKNOWN,
            'lead_status' => CompanyLead::LEAD_STATUS_CONTACTED,
            'lead_score' => 45,
            'phone' => '+381600000000',
            'email' => 'info@example.com',
            'notes' => 'Postojeći CRM podaci.',
            'website_checked_at' => now()->subDay(),
        ]);

        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_WEBSITE,
            'https://example.com',
            CompanyDataPoint::STATUS_VERIFIED
        );

        $dataPoint->update([
            'verified_at' => now(),
            'verified_by' => 'user',
        ]);

        $response = $this->post("/data-points/{$dataPoint->id}/apply-to-lead");

        $response->assertRedirect();

        $this->assertDatabaseHas('company_leads', [
            'company_id' => $company->id,
            'website_url' => 'https://example.com',
            'website_status' => CompanyLead::WEBSITE_STATUS_ACTIVE,
            'lead_status' => CompanyLead::LEAD_STATUS_CONTACTED,
            'lead_score' => 45,
            'phone' => '+381600000000',
            'email' => 'info@example.com',
            'notes' => 'Postojeći CRM podaci.',
        ]);

        $this->assertNotNull($company->lead->fresh()->website_checked_at);
    }

    public function test_user_cannot_apply_unverified_website_to_lead(): void
    {
        $company = $this->createCompany('12345690');

        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_WEBSITE,
            'https://example.com',
            CompanyDataPoint::STATUS_CANDIDATE
        );

        $response = $this->post("/data-points/{$dataPoint->id}/apply-to-lead");

        $response->assertRedirect();

        $this->assertDatabaseMissing('company_leads', [
            'company_id' => $company->id,
            'website_url' => 'https://example.com',
        ]);
    }

    public function test_user_cannot_apply_non_website_data_point_to_lead(): void
    {
        $company = $this->createCompany('12345691');

        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_EMAIL,
            'info@example.com',
            CompanyDataPoint::STATUS_VERIFIED
        );

        $response = $this->post("/data-points/{$dataPoint->id}/apply-to-lead");

        $response->assertRedirect();

        $this->assertDatabaseMissing('company_leads', [
            'company_id' => $company->id,
        ]);
    }

    public function test_user_can_run_website_audit_for_verified_website_data_point(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response(
                '<html><head><title>Example</title></head><body>Example</body></html>',
                200
            ),
        ]);

        $company = $this->createCompany('12345692');

        $company->lead()->create([
            'website_url' => 'https://example.com',
            'website_status' => CompanyLead::WEBSITE_STATUS_ACTIVE,
            'lead_status' => CompanyLead::LEAD_STATUS_NEW,
            'lead_score' => 0,
        ]);

        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_WEBSITE,
            'https://example.com',
            CompanyDataPoint::STATUS_VERIFIED
        );

        $response = $this->post("/data-points/{$dataPoint->id}/run-audit");

        $response->assertRedirect();

        $this->assertDatabaseHas('company_website_audits', [
            'company_lead_id' => $company->lead->id,
            'requested_url' => 'https://example.com',
            'status' => CompanyWebsiteAudit::STATUS_COMPLETED,
            'http_status' => 200,
            'is_reachable' => true,
            'has_https' => true,
        ]);
    }

    public function test_user_cannot_run_audit_for_unverified_website_data_point(): void
    {
        Http::fake();

        $company = $this->createCompany('12345693');

        $company->lead()->create([
            'website_url' => 'https://example.com',
            'website_status' => CompanyLead::WEBSITE_STATUS_ACTIVE,
            'lead_status' => CompanyLead::LEAD_STATUS_NEW,
            'lead_score' => 0,
        ]);

        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_WEBSITE,
            'https://example.com',
            CompanyDataPoint::STATUS_CANDIDATE
        );

        $response = $this->post("/data-points/{$dataPoint->id}/run-audit");

        $response->assertRedirect();

        $this->assertDatabaseCount('company_website_audits', 0);
    }

    public function test_user_cannot_run_audit_for_non_website_data_point(): void
    {
        Http::fake();

        $company = $this->createCompany('12345694');

        $company->lead()->create([
            'website_url' => 'https://example.com',
            'website_status' => CompanyLead::WEBSITE_STATUS_ACTIVE,
            'lead_status' => CompanyLead::LEAD_STATUS_NEW,
            'lead_score' => 0,
        ]);

        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_EMAIL,
            'info@example.com',
            CompanyDataPoint::STATUS_VERIFIED
        );

        $response = $this->post("/data-points/{$dataPoint->id}/run-audit");

        $response->assertRedirect();

        $this->assertDatabaseCount('company_website_audits', 0);
    }

    public function test_user_cannot_run_audit_without_company_lead(): void
    {
        Http::fake();

        $company = $this->createCompany('12345695');

        $dataPoint = $this->createDataPoint(
            $company,
            CompanyDataPoint::FIELD_WEBSITE,
            'https://example.com',
            CompanyDataPoint::STATUS_VERIFIED
        );

        $response = $this->post("/data-points/{$dataPoint->id}/run-audit");

        $response->assertRedirect();

        $this->assertDatabaseCount('company_website_audits', 0);
    }
}
