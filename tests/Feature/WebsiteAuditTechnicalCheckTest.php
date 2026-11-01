<?php

namespace Tests\Feature;

use App\Jobs\RunWebsiteAuditJob;
use App\Models\Company;
use App\Models\CompanyLead;
use App\Models\CompanyWebsiteAudit;
use App\Services\WebsiteAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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

    public function test_connection_failure_is_rethrown_and_leaves_the_audit_pending(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://example.com' => Http::failedConnection('Connection timed out'),
        ]);

        $audit = $this->createAudit();

        try {
            app(WebsiteAuditService::class)->check($audit);
            $this->fail('A connection failure should be rethrown so the queue can retry the check.');
        } catch (ConnectionException $exception) {
            $this->assertStringContainsString('Connection timed out', $exception->getMessage());
        }

        $audit->refresh();

        $this->assertSame(CompanyWebsiteAudit::STATUS_PENDING, $audit->status);
        $this->assertNull($audit->completed_at);
        $this->assertNull($audit->error_message);
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

    public function test_ui_queues_a_technical_check(): void
    {
        Queue::fake([RunWebsiteAuditJob::class]);

        $audit = $this->createAudit();

        $response = $this->post(route('website-audits.run', $audit));

        $response->assertRedirect(
            route('companies.show', $audit->companyLead->company)
        );
        $response->assertSessionHas(
            'success',
            'The technical website check has been queued. Refresh the page in a few seconds to see the result.'
        );

        $this->assertSame(CompanyWebsiteAudit::STATUS_PENDING, $audit->refresh()->status);

        Queue::assertPushed(
            RunWebsiteAuditJob::class,
            fn (RunWebsiteAuditJob $job): bool => $job->audit->is($audit)
        );
    }

    public function test_ui_queues_the_same_audit_only_once(): void
    {
        Queue::fake([RunWebsiteAuditJob::class]);

        $audit = $this->createAudit();

        $this->post(route('website-audits.run', $audit));
        $this->post(route('website-audits.run', $audit));

        Queue::assertPushedTimes(RunWebsiteAuditJob::class, 1);
    }

    public function test_it_truncates_a_page_title_longer_than_500_characters(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><title>'.str_repeat('Č', 700).'</title></head><body>OK</body></html>',
                200
            ),
        ]);

        $audit = $this->createAudit();

        $service = app(WebsiteAuditService::class);
        $audit = $service->check($audit);

        $this->assertSame(CompanyWebsiteAudit::STATUS_COMPLETED, $audit->status);
        $this->assertSame(str_repeat('Č', 500), $audit->page_title);
    }

    public function test_it_stores_a_final_url_longer_than_255_characters(): void
    {
        $longUrl = 'https://example.com/'.str_repeat('a', 400);

        Http::fake([
            'https://example.com/*' => Http::response('<html><body>OK</body></html>', 200),
        ]);

        $audit = $this->createAudit();
        $audit->update(['requested_url' => $longUrl]);

        $service = app(WebsiteAuditService::class);
        $audit = $service->check($audit);

        $this->assertSame(CompanyWebsiteAudit::STATUS_COMPLETED, $audit->status);
        $this->assertSame($longUrl, $audit->final_url);
    }
}
