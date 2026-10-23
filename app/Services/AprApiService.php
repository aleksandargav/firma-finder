<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AprApiService
{
    public function fetchSnapshot(): array
    {
        $caBundle = config('apr.ca_bundle');

        if (! is_file($caBundle) || ! is_readable($caBundle)) {
            throw new RuntimeException("APR CA bundle not found: {$caBundle}");
        }

        $response = Http::withOptions([
            'verify' => $caBundle,
            'timeout' => 120,
        ])->get(config('apr.api_url'));

        if ($response->failed()) {
            throw new RuntimeException(
                "APR API request failed: HTTP {$response->status()}"
            );
        }

        $data = $response->json();

        if (! isset($data['DatumPreseka'], $data['Podaci'])) {
            throw new RuntimeException(
                'The APR API response is missing the expected fields DatumPreseka and Podaci.'
            );
        }

        $snapshotDate = $data['DatumPreseka'];
        $path = $this->snapshotPath($snapshotDate);

        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true)) {
            throw new RuntimeException("Unable to create directory: {$directory}");
        }

        file_put_contents($path, $response->body());

        return [
            'snapshot_date' => $snapshotDate,
            'path' => $path,
            'company_count' => count($data['Podaci']),
        ];
    }

    public function snapshotPath(string $snapshotDate): string
    {
        $date = Str::of($snapshotDate)->replace('-', '');

        return config('apr.snapshot_directory')
            . "/{$date}/companies.json";
    }
}
