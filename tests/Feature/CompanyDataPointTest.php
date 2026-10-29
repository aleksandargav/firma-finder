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

    private function validAttributes(array $overrides = []): array
    {
        return array_merge([
            'company_id' => null,
            'external_identifier' => '99999999',
            'identifier_type' => CompanyDataPoint::IDENTIFIER_REGISTRATION_NUMBER,
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://example.com',
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'source_url' => 'https://example.com/source',
            'confidence' => CompanyDataPoint::CONFIDENCE_HIGH,
            'identity_match_score' => 100,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
            'retrieved_at' => now(),
            'verified_at' => null,
            'verified_by' => null,
            'notes' => 'Test data point',
        ], $overrides);
    }

    private function createCompany(string $registrationNumber): Company
    {
        return Company::create([
            'registration_number' => $registrationNumber,
            'business_name' => 'TEST DOO',
            'source_snapshot_date' => '2026-10-14',
        ]);
    }

    public function test_creates_data_point_for_company(): void
    {
        $company = $this->createCompany('12345678');

        $dataPoint = $company->dataPoints()->create(
            $this->validAttributes(['company_id' => $company->id])
        );

        $this->assertDatabaseHas('company_data_points', [
            'id' => $dataPoint->id,
            'company_id' => $company->id,
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://example.com',
            'source' => CompanyDataPoint::SOURCE_MANUAL,
            'confidence' => CompanyDataPoint::CONFIDENCE_HIGH,
            'data_license_status' => CompanyDataPoint::LICENSE_ALLOWED,
            'status' => CompanyDataPoint::STATUS_CANDIDATE,
        ]);

        $this->assertSame($company->id, $dataPoint->company->id);
    }

    public function test_company_can_have_multiple_data_points(): void
    {
        $company = $this->createCompany('12345679');

        $company->dataPoints()->create($this->validAttributes([
            'company_id' => $company->id,
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://example.com',
        ]));

        $company->dataPoints()->create($this->validAttributes([
            'company_id' => $company->id,
            'field' => CompanyDataPoint::FIELD_PHONE,
            'value' => '+381600000000',
        ]));

        $this->assertCount(2, $company->dataPoints);
        $this->assertSame(
            [
                CompanyDataPoint::FIELD_PHONE,
                CompanyDataPoint::FIELD_WEBSITE,
            ],
            $company->dataPoints
                ->pluck('field')
                ->sort()
                ->values()
                ->all()
        );
    }

    public function test_company_has_many_data_points_relation(): void
    {
        $company = $this->createCompany('12345680');

        $dataPoint = $company->dataPoints()->create(
            $this->validAttributes(['company_id' => $company->id])
        );

        $this->assertTrue($company->dataPoints->contains($dataPoint));
        $this->assertSame($company->id, $dataPoint->company_id);
    }

    public function test_data_point_belongs_to_company(): void
    {
        $company = $this->createCompany('12345681');

        $dataPoint = $company->dataPoints()->create(
            $this->validAttributes(['company_id' => $company->id])
        );

        $this->assertInstanceOf(Company::class, $dataPoint->company);
        $this->assertSame($company->id, $dataPoint->company->id);
    }

    public function test_validation_rejects_invalid_field(): void
    {
        $attributes = $this->validAttributes([
            'company_id' => $this->createCompany('12345682')->id,
            'field' => 'invalid-field',
        ]);

        $validator = Validator::make($attributes, CompanyDataPoint::rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('field', $validator->errors()->toArray());
        $this->assertDatabaseCount('company_data_points', 0);
    }

    public function test_validation_rejects_invalid_source(): void
    {
        $attributes = $this->validAttributes([
            'company_id' => $this->createCompany('12345683')->id,
            'source' => 'invalid-source',
        ]);

        $validator = Validator::make($attributes, CompanyDataPoint::rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('source', $validator->errors()->toArray());
        $this->assertDatabaseCount('company_data_points', 0);
    }

    public function test_validation_rejects_invalid_confidence(): void
    {
        $attributes = $this->validAttributes([
            'company_id' => $this->createCompany('12345684')->id,
            'confidence' => 'invalid-confidence',
        ]);

        $validator = Validator::make($attributes, CompanyDataPoint::rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('confidence', $validator->errors()->toArray());
        $this->assertDatabaseCount('company_data_points', 0);
    }

    public function test_validation_rejects_invalid_data_license_status(): void
    {
        $attributes = $this->validAttributes([
            'company_id' => $this->createCompany('12345685')->id,
            'data_license_status' => 'invalid-license',
        ]);

        $validator = Validator::make($attributes, CompanyDataPoint::rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('data_license_status', $validator->errors()->toArray());
        $this->assertDatabaseCount('company_data_points', 0);
    }

    public function test_validation_rejects_invalid_status(): void
    {
        $attributes = $this->validAttributes([
            'company_id' => $this->createCompany('12345686')->id,
            'status' => 'invalid-status',
        ]);

        $validator = Validator::make($attributes, CompanyDataPoint::rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
        $this->assertDatabaseCount('company_data_points', 0);
    }

    public function test_validation_accepts_all_supported_fields(): void
    {
        foreach (CompanyDataPoint::fields() as $index => $field) {
            $company = $this->createCompany('1234569' . $index);

            $attributes = $this->validAttributes([
                'company_id' => $company->id,
                'field' => $field,
                'value' => 'Test value for ' . $field,
            ]);

            $validator = Validator::make($attributes, CompanyDataPoint::rules());

            $this->assertTrue(
                $validator->passes(),
                "Field [{$field}] should be valid."
            );
        }
    }

    public function test_deleting_company_cascades_data_points(): void
    {
        $company = $this->createCompany('12345688');

        $company->dataPoints()->create($this->validAttributes([
            'company_id' => $company->id,
            'field' => CompanyDataPoint::FIELD_WEBSITE,
            'value' => 'https://example.com',
        ]));

        $company->dataPoints()->create($this->validAttributes([
            'company_id' => $company->id,
            'field' => CompanyDataPoint::FIELD_PHONE,
            'value' => '+381600000000',
        ]));

        $this->assertDatabaseCount('company_data_points', 2);

        $company->delete();

        $this->assertDatabaseCount('company_data_points', 0);
    }

    public function test_validation_accepts_valid_manual_website_candidate(): void
    {
        $attributes = $this->validAttributes([
            'company_id' => $this->createCompany('12345689')->id,
        ]);

        $validator = Validator::make($attributes, CompanyDataPoint::rules());

        $this->assertTrue($validator->passes());
    }
}
