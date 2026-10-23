<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JsonException;

class ImportAprCompanies extends Command
{
    protected $signature = 'apr:import

        {--file=storage/app/apr-response.json : Path to the APR JSON snapshot}
        {--limit=5 : Maximum number of records to process}
        {--batch=500 : Number of records per SQL upsert batch}
        {--write : Write the data to the database; without this option only a dry run is performed}';

    protected $description = 'Read the APR JSON and optionally import the companies into the SQLite database in batches';

    public function handle(): int
    {
        $file = $this->option('file');
        $path = str_starts_with($file, '/')
            ? $file
            : base_path($file);

        if (!is_file($path) || !is_readable($path)) {
            $this->error("APR JSON file not found or not readable: {$path}");

            return self::FAILURE;
        }

        $limit = $this->positiveIntegerOption('limit', 1, 134374);
        $batchSize = $this->positiveIntegerOption('batch', 1, 1000);

        if ($limit === null || $batchSize === null) {
            return self::FAILURE;
        }

        try {
            $json = file_get_contents($path);

            if ($json === false) {
                throw new \RuntimeException('Unable to read the APR JSON file.');
            }

            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException|\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $snapshotDate = $data['DatumPreseka'] ?? null;
        $companies = $data['Podaci'] ?? null;

        if (!is_string($snapshotDate) || !is_array($companies)) {
            $this->error('The APR JSON is missing the expected fields DatumPreseka and Podaci.');

            return self::FAILURE;
        }

        $write = (bool) $this->option('write');
        $rows = [];
        $previewRows = [];
        $processed = 0;
        $skipped = 0;
        $written = 0;
        $timestamp = now();

        $this->info($write ? 'WRITE MODE requested.' : 'DRY-RUN: the database will not be changed.');
        $this->line("Snapshot date: {$snapshotDate}");
        $this->line("Limit: {$limit}");
        $this->line("Batch size: {$batchSize}");

        if ($write && !$this->confirm(
            "Write up to {$limit} APR records to the SQLite database in batches of {$batchSize}?",
            false
        )) {
            $this->warn('Write cancelled.');

            return self::SUCCESS;
        }

        foreach ($companies as $registrationNumber => $company) {
            if ($processed >= $limit) {
                break;
            }

            if (!is_array($company) || trim((string) ($company['PoslovnoIme'] ?? '')) === '') {
                $skipped++;

                continue;
            }

            $row = [
                'registration_number' => (string) $registrationNumber,
                'business_name' => trim((string) $company['PoslovnoIme']),
                'municipality_code' => $this->nullableString($company['SifraOpstine'] ?? null),
                'municipality_name' => $this->nullableString($company['NazivOpstine'] ?? null),
                'status' => $this->nullableString($company['NazivStatus'] ?? null),
                'founded_at' => $this->nullableDate($company['DatumOsnivanja'] ?? null),
                'legal_form' => $this->nullableString($company['NazivPravneForme'] ?? null),
                'activity_code' => $this->nullableString($company['SifraDelatnosti'] ?? null),
                'source_snapshot_date' => $snapshotDate,
                'imported_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (count($previewRows) < 5) {
                $previewRows[] = $row;
            }

            if ($write) {
                $rows[] = $row;

                if (count($rows) >= $batchSize) {
                    $written += $this->upsertRows($rows);
                    $rows = [];
                }
            }

            $processed++;
        }

        if ($write && $rows !== []) {
            $written += $this->upsertRows($rows);
        }

        $this->newLine();
        $this->line("Processed: {$processed}");
        $this->line("Skipped: {$skipped}");

        if ($write) {
            $this->info("Successfully written or updated: {$written} records.");
        }

        $this->newLine();

        $this->table(
            ['MB', 'Name', 'Municipality', 'Status', 'Activity', 'Founded'],
            array_map(
                fn (array $row): array => [
                    $row['registration_number'],
                    $row['business_name'],
                    $row['municipality_name'] ?? '-',
                    $row['status'] ?? '-',
                    $row['activity_code'] ?? '-',
                    $row['founded_at'] ?? '-',
                ],
                $previewRows
            )
        );

        if (!$write) {
            $this->newLine();
            $this->comment(
                'DRY-RUN complete. To write the data, use: php artisan apr:import --limit=5 --write'
            );
        }

        return self::SUCCESS;
    }

    private function upsertRows(array $rows): int
    {
        DB::table('companies')->upsert(
            $rows,
            ['registration_number'],
            [
                'business_name',
                'municipality_code',
                'municipality_name',
                'status',
                'founded_at',
                'legal_form',
                'activity_code',
                'source_snapshot_date',
                'imported_at',
                'updated_at',
            ]
        );

        return count($rows);
    }

    private function positiveIntegerOption(
        string $name,
        int $minimum,
        int $maximum
    ): ?int {
        $value = filter_var(
            $this->option($name),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => $minimum, 'max_range' => $maximum]]
        );

        if ($value === false) {
            $this->error(
                "The --{$name} option must be an integer between {$minimum} and {$maximum}."
            );

            return null;
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || (
                $errors !== false
                && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)
            )
        ) {
            return null;
        }

        return $date->format('Y-m-d');
    }
}
