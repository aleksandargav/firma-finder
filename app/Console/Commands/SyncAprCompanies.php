<?php

namespace App\Console\Commands;

use App\Services\AprApiService;
use Illuminate\Console\Command;
use RuntimeException;

class SyncAprCompanies extends Command
{
    protected $signature = 'apr:sync';

    protected $description = 'Preuzmi APR Open Data snapshot i sačuvaj ga lokalno';

    public function handle(AprApiService $service): int
    {
        $this->info('Preuzimanje APR Open Data snapshot-a...');

        try {
            $snapshot = $service->fetchSnapshot();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('APR snapshot je uspešno sačuvan.');
        $this->line("Datum preseka: {$snapshot['snapshot_date']}");
        $this->line("Broj firmi: {$snapshot['company_count']}");
        $this->line("Fajl: {$snapshot['path']}");

        return self::SUCCESS;
    }
}
