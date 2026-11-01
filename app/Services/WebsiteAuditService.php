<?php

namespace App\Services;

use App\Models\CompanyWebsiteAudit;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebsiteAuditService
{
    private const PAGE_TITLE_MAX_LENGTH = 500;

    /**
     * @throws ConnectionException When the site cannot be reached, so the queue can retry the check.
     */
    public function check(CompanyWebsiteAudit $audit): CompanyWebsiteAudit
    {
        $audit->update([
            'status' => CompanyWebsiteAudit::STATUS_PENDING,
            'started_at' => now(),
            'completed_at' => null,
            'error_message' => null,
        ]);

        $requestedUrl = $audit->requested_url;

        if (! $requestedUrl) {
            return $this->markAsFailed($audit, 'The URL to check is not defined.');
        }

        $startedAt = microtime(true);

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->withHeaders([
                    'User-Agent' => 'FirmaFinderAudit/1.0',
                ])
                ->get($requestedUrl);

            $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

            $effectiveUri = $response->effectiveUri()?->__toString();

            $audit->update([
                'status' => CompanyWebsiteAudit::STATUS_COMPLETED,
                'http_status' => $response->status(),
                'is_reachable' => $response->successful(),
                'has_https' => str_starts_with((string) $effectiveUri, 'https://'),
                'redirect_count' => $this->countRedirects($requestedUrl, $effectiveUri),
                'response_time_ms' => $responseTimeMs,
                'final_url' => $effectiveUri,
                'page_title' => $this->extractTitle($response->body()),
                'meta_description' => $this->extractMetaDescription($response->body()),
                'completed_at' => now(),
            ]);

            return $audit->refresh();
        } catch (ConnectionException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->markAsFailed(
                $audit,
                'Error during the technical check: ' . $exception->getMessage()
            );
        }
    }

    /**
     * Stores a failed result with the reason shown in the audit history.
     */
    public function markAsFailed(CompanyWebsiteAudit $audit, string $message): CompanyWebsiteAudit
    {
        $audit->update([
            'status' => CompanyWebsiteAudit::STATUS_FAILED,
            'is_reachable' => false,
            'completed_at' => now(),
            'error_message' => $message,
        ]);

        return $audit->refresh();
    }

    private function countRedirects(?string $requestedUrl, ?string $finalUrl): int
    {
        if (! $requestedUrl || ! $finalUrl) {
            return 0;
        }

        $requestedHost = parse_url($requestedUrl, PHP_URL_HOST);
        $finalHost = parse_url($finalUrl, PHP_URL_HOST);

        if ($requestedUrl === $finalUrl) {
            return 0;
        }

        return 1;
    }

    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return Str::substr(
                trim(html_entity_decode($matches[1])),
                0,
                self::PAGE_TITLE_MAX_LENGTH
            );
        }

        return null;
    }

    private function extractMetaDescription(string $html): ?string
    {
        if (preg_match(
            '/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/is',
            $html,
            $matches
        )) {
            return trim(html_entity_decode($matches[1]));
        }

        return null;
    }
}
