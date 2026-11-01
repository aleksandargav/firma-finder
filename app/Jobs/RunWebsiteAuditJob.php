<?php

namespace App\Jobs;

use App\Models\CompanyWebsiteAudit;
use App\Services\WebsiteAuditService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs the technical check of one website audit on the queue.
 *
 * Only connection failures (timeouts, DNS errors, refused connections) are
 * retried. An HTTP error response such as 404 or 500 is a valid result and
 * completes the audit.
 */
#[Tries(3)]
#[Backoff([10, 60])]
#[Timeout(30)]
#[UniqueFor(300)]
#[DeleteWhenMissingModels]
class RunWebsiteAuditJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public CompanyWebsiteAudit $audit) {}

    public function uniqueId(): string
    {
        return (string) $this->audit->id;
    }

    public function handle(WebsiteAuditService $auditService): void
    {
        Log::channel('website-audits')->info('Website check started.', $this->logContext());

        try {
            $audit = $auditService->check($this->audit);
        } catch (ConnectionException $exception) {
            Log::channel('website-audits')->warning('Website check could not connect.', [
                ...$this->logContext(),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        Log::channel('website-audits')->info('Website check finished.', [
            ...$this->logContext(),
            'status' => $audit->status,
            'http_status' => $audit->http_status,
            'response_time_ms' => $audit->response_time_ms,
        ]);
    }

    /**
     * Marks the audit as failed once every attempt is used up or the job times out.
     */
    public function failed(?Throwable $exception): void
    {
        $message = $exception instanceof ConnectionException
            ? 'The website could not be reached: '.$exception->getMessage()
            : 'The technical check job failed: '.($exception?->getMessage() ?? 'unknown error');

        app(WebsiteAuditService::class)->markAsFailed($this->audit, $message);

        Log::channel('website-audits')->error('Website check failed.', [
            ...$this->logContext(),
            'error' => $message,
        ]);
    }

    /**
     * @return array{audit_id: int, requested_url: ?string, attempt: int}
     */
    private function logContext(): array
    {
        return [
            'audit_id' => $this->audit->id,
            'requested_url' => $this->audit->requested_url,
            'attempt' => $this->attempts(),
        ];
    }
}
