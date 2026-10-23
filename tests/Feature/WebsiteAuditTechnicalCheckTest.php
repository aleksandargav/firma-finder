<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyLead;
use App\Models\CompanyWebsiteAudit;
use App\Services\WebsiteAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebsiteAuditTechnicalCheckTest extends TestCase
{
    use RefreshDatabase;

    private function createAudit(): CompanyWebsiteAudit
    {
        $company = Company::create([
            'registration_number' => '23000001',
            'business_name' => 'Tehnička Provera Test DOO',
            'municipality_code' => '11000',
            'municipality_name' => 'Beograd',
            'status' => 'Активан',
            'founded_at' => '2020-01-01',
            'legal_form' => 'DOO',
            'activity_code' => '6201',
            'source_snapshot_date' => '2026-10-10',
            'imported_at' => '2026-10-11 08:00:00',
        ]);

        $lead = $company->lead()->create([
            'website_url' => 'https://example.com',
            'website_status' => 'active',
            'lead_status' => 'new',
            'lead_score' => 10,
        ]);

        return $lead->websiteAudits()->create([
            'requested_url' => 'https://example.com',
            'status' => CompanyWebsiteAudit::STATUS_PENDING,
        ]);
    }

    public function test_it_completes_a_successful_technical_check(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><title>Example</title><meta name="description" content="Test description"></head><body>OK</body></html>',
                200
            ),
        ]);

        $audit = $this->createAudit();

        $service = app(WebsiteAuditService::class);
        $audit = $service->check($audit);

        $this->assertSame(CompanyWebsiteAudit::STATUS_COMPLETED, $audit->status);
        $this->assertSame(200, $audit->http_status);
        $this->assertTrue($audit->is_reachable);
        $this->assertTrue($audit->has_https);
        $this->assertSame('Example', $audit->page_title);
        $this->assertSame('Test description', $audit->meta_description);
        $this->assertNotNull($audit->started_at);
        $this->assertNotNull($audit->completed_at);
        $this->assertNull($audit->error_message);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://example.com'
                && $request->method() === 'GET';
        });
    }

    public function test_it_fails_when_site_is_unreachable(): void
    {
        Http::fake([
            'https://example.com' => function () {
                throw new \Illuminate\Http\Client\ConnectionException(
                    'Connection timed out'
                );
            },
        ]);

        $audit = $this->createAudit();

        $service = app(WebsiteAuditService::class);
        $audit = $service->check($audit);

        $this->assertSame(CompanyWebsiteAudit::STATUS_FAILED, $audit->status);
        $this->assertFalse($audit->is_reachable);
        $this->assertNotNull($audit->error_message);
        $this->assertStringContainsString(
            'Connection timed out',
            $audit->error_message
        );
        $this->assertNotNull($audit->completed_at);
    }

    public function test_it_fails_when_requested_url_is_missing(): void
    {
        $audit = $this->createAudit();

        $audit->update([
            'requested_url' => null,
        ]);

        $service = app(WebsiteAuditService::class);
        $audit = $service->check($audit);

        $this->assertSame(CompanyWebsiteAudit::STATUS_FAILED, $audit->status);
        $this->assertFalse($audit->is_reachable);
        $this->assertSame(
            'The URL to check is not defined.',
            $audit->error_message
        );
    }

    public function test_ui_can_run_a_technical_check(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><title>Example</title></head><body>OK</body></html>',
                200
            ),
        ]);

        $audit = $this->createAudit();

        $response = $this->post(route('website-audits.run', $audit));

        $response->assertRedirect(
            route('companies.show', $audit->companyLead->company)
        );

        $response->assertSessionHas(
            'success',
            'The technical website check is complete.'
        );

        $audit->refresh();

        $this->assertSame(CompanyWebsiteAudit::STATUS_COMPLETED, $audit->status);
        $this->assertSame(200, $audit->http_status);
        $this->assertTrue($audit->is_reachable);
    }
}
