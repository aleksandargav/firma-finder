<?php

namespace App\Console\Commands;

use App\Services\AprApiService;
use Illuminate\Console\Command;
use RuntimeException;

class SyncAprCompanies extends Command
{
    protected $signature = 'apr:sync';



    protected $description = 'Download the APR Open Data snapshot and save it locally';

    public function handle(AprApiService $service): int
    {
        $this->info('Downloading the APR Open Data snapshot...');

        try {
            $snapshot = $service->fetchSnapshot();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('APR snapshot saved successfully.');
        $this->line("Snapshot date: {$snapshot['snapshot_date']}");
        $this->line("Number of companies: {$snapshot['company_count']}");
        $this->line("File: {$snapshot['path']}");

        return self::SUCCESS;
    }
}
