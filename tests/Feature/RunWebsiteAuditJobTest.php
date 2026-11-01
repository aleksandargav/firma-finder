<?php

namespace Tests\Feature;

use App\Jobs\RunWebsiteAuditJob;
use App\Models\Company;
use App\Models\CompanyWebsiteAudit;
use App\Services\WebsiteAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class RunWebsiteAuditJobTest extends TestCase
{
    use RefreshDatabase;

    private function createAudit(): CompanyWebsiteAudit
    {
        $company = Company::create([
            'registration_number' => '23000002',
            'business_name' => 'Queue Provera Test DOO',
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

    public function test_server_error_response_completes_the_audit_without_retrying(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://example.com' => Http::response('<html><body>Error</body></html>', 500),
        ]);

        $audit = $this->createAudit();

        (new RunWebsiteAuditJob($audit))->handle(app(WebsiteAuditService::class));

        $audit->refresh();

        $this->assertSame(CompanyWebsiteAudit::STATUS_COMPLETED, $audit->status);
        $this->assertSame(500, $audit->http_status);
        $this->assertFalse($audit->is_reachable);
        $this->assertNull($audit->error_message);
    }

    public function test_last_connection_failure_marks_the_audit_as_failed(): void
    {
        $audit = $this->createAudit();

        (new RunWebsiteAuditJob($audit))->failed(new ConnectionException('Connection timed out'));

        $audit->refresh();

        $this->assertSame(CompanyWebsiteAudit::STATUS_FAILED, $audit->status);
        $this->assertFalse($audit->is_reachable);
        $this->assertSame('The website could not be reached: Connection timed out', $audit->error_message);
        $this->assertNotNull($audit->completed_at);
    }

    public function test_other_job_failure_marks_the_audit_as_failed(): void
    {
        $audit = $this->createAudit();

        (new RunWebsiteAuditJob($audit))->failed(new RuntimeException('Job timed out'));

        $audit->refresh();

        $this->assertSame(CompanyWebsiteAudit::STATUS_FAILED, $audit->status);
        $this->assertSame('The technical check job failed: Job timed out', $audit->error_message);
    }
}
