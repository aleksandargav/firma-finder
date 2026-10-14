<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyDataPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CompanyDataPointTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_have_company_data_points(): void
    {
        $company = Company::create([
            'registration_number' => '12345678',
            'business_name' => 'TEST DOO',
            'source_snapshot_date' => '2026-10-14',
        ]);

        $dataPoint = $company->companyDataPoints()->create([
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://example.com',
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => CompanyDataPoint::CONFIDENCE_HIGH,
            'identity_match_score' => 100,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => CompanyDataPoint::STATUS_VERIFIED,
            'verified_by' => 'user',
        ]);

        $this->assertTrue($company->companyDataPoints->contains($dataPoint));
        $this->assertSame($company->id, $dataPoint->company->id);
    }

    public function test_company_can_have_multiple_data_points(): void
    {
        $company = Company::create([
            'registration_number' => '12345679',
            'business_name' => 'TEST DOO 2',
            'source_snapshot_date' => '2026-10-14',
        ]);

        $company->companyDataPoints()->create([
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://example.com',
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => CompanyDataPoint::CONFIDENCE_HIGH,
            'identity_match_score' => 100,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => CompanyDataPoint::STATUS_VERIFIED,
            'verified_by' => 'user',
        ]);

        $company->companyDataPoints()->create([
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://another-example.com',
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => CompanyDataPoint::CONFIDENCE_LOW,
            'identity_match_score' => 40,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
        ]);

        $this->assertCount(2, $company->companyDataPoints);
    }

    public function test_validation_rejects_invalid_status(): void
    {
        $validator = Validator::make([
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://example.com',
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => CompanyDataPoint::CONFIDENCE_HIGH,
            'identity_match_score' => 100,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => 'invalid-status',
        ], CompanyDataPoint::validationRules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_validation_rejects_invalid_source(): void
    {
        $validator = Validator::make([
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://example.com',
            'source' => 'invalid-source',
            'confidence' => CompanyDataPoint::CONFIDENCE_HIGH,
            'identity_match_score' => 100,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
        ], CompanyDataPoint::validationRules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('source', $validator->errors()->toArray());
    }

    public function test_validation_accepts_valid_manual_website_candidate(): void
    {
        $validator = Validator::make([
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://example.com',
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => CompanyDataPoint::CONFIDENCE_HIGH,
            'identity_match_score' => 100,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
        ], CompanyDataPoint::validationRules());

        $this->assertTrue($validator->passes());
    }
}
