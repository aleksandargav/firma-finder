<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyDataPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function createWebsiteCandidate(Company $company, string $url): CompanyDataPoint
    {
        return $company->dataPoints()->create([
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => $url,
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => CompanyDataPoint::CONFIDENCE_MEDIUM,
            'identity_match_score' => 100,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
        ]);
    }

    public function test_user_can_verify_a_pending_data_point(): void
    {
        $company = $this->createCompany('12345678');
        $dataPoint = $this->createWebsiteCandidate($company, 'https://example.com');

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
        $company = $this->createCompany('12345679');
        $dataPoint = $this->createWebsiteCandidate($company, 'https://example.org');

        $response = $this->post("/data-points/{$dataPoint->id}/reject");

        $response->assertRedirect();

        $this->assertDatabaseHas('company_data_points', [
            'id' => $dataPoint->id,
            'status' => CompanyDataPoint::STATUS_REJECTED,
        ]);

        $this->assertNull($dataPoint->fresh()->verified_at);
    }

    public function test_verifying_one_data_point_does_not_change_another(): void
    {
        $company = $this->createCompany('12345680');

        $verified = $this->createWebsiteCandidate($company, 'https://example.com');
        $rejected = $this->createWebsiteCandidate($company, 'https://example.org');

        $this->post("/data-points/{$verified->id}/verify");
        $this->post("/data-points/{$rejected->id}/reject");

        $this->assertSame(CompanyDataPoint::STATUS_VERIFIED, $verified->fresh()->status);
        $this->assertSame(CompanyDataPoint::STATUS_REJECTED, $rejected->fresh()->status);
    }
}
