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
}
