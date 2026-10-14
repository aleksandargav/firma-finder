<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JsonException;

class ImportAprCompanies extends Command
{
    protected $signature = 'apr:import
        {--file=storage/app/apr-response.json : Putanja do APR JSON snapshot-a}
        {--limit=5 : Maksimalan broj zapisa za obradu}
        {--batch=500 : Broj zapisa po SQL upsert batch-u}
        {--write : Upisi podatke u bazu; bez ove opcije radi se samo dry-run}';

    protected $description = 'Ucitaj APR JSON i po potrebi uvezi firme u SQLite bazu u batch-evima';

    public function handle(): int
    {
        $file = $this->option('file');
        $path = str_starts_with($file, '/')
            ? $file
            : base_path($file);

        if (!is_file($path) || !is_readable($path)) {
            $this->error("APR JSON fajl nije pronađen ili nije čitljiv: {$path}");

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
                throw new \RuntimeException('Ne mogu da pročitam APR JSON fajl.');
            }

            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException|\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $snapshotDate = $data['DatumPreseka'] ?? null;
        $companies = $data['Podaci'] ?? null;

        if (!is_string($snapshotDate) || !is_array($companies)) {
            $this->error('APR JSON nema očekivana polja DatumPreseka i Podaci.');

            return self::FAILURE;
        }

        $write = (bool) $this->option('write');
        $rows = [];
        $previewRows = [];
        $processed = 0;
        $skipped = 0;
        $written = 0;
        $timestamp = now();

        $this->info($write ? 'WRITE MODE je zatražen.' : 'DRY-RUN: baza neće biti menjana.');
        $this->line("Datum preseka: {$snapshotDate}");
        $this->line("Limit: {$limit}");
        $this->line("Batch veličina: {$batchSize}");

        if ($write && !$this->confirm(
            "Upisati do {$limit} APR zapisa u SQLite bazu u batch-evima od {$batchSize}?",
            false
        )) {
            $this->warn('Upis je otkazan.');

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
        $this->line("Obrađeno: {$processed}");
        $this->line("Preskočeno: {$skipped}");

        if ($write) {
            $this->info("Uspešno upisano ili ažurirano: {$written} zapisa.");
        }

        $this->newLine();

        $this->table(
            ['MB', 'Naziv', 'Opština', 'Status', 'Delatnost', 'Osnovana'],
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
                'DRY-RUN završen. Za stvarni upis koristi: php8.5 artisan apr:import --limit=5 --write'
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
                "Opcija --{$name} mora biti ceo broj između {$minimum} i {$maximum}."
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
