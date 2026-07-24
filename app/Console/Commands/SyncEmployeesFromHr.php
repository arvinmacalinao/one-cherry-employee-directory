<?php

namespace App\Console\Commands;

use App\Enums\SyncType;
use App\Services\HrSync\HrSyncService;
use Illuminate\Console\Command;

class SyncEmployeesFromHr extends Command
{
    protected $signature = 'hr:sync {--manual : Mark this run as manually triggered rather than scheduled}';

    protected $description = 'Pull employees from the HR system and sync new hires, promotions, and resignations';

    public function handle(HrSyncService $syncService): int
    {
        $this->info('Starting HR sync...');

        $log = $syncService->sync(
            $this->option('manual') ? SyncType::Manual : SyncType::Scheduled,
        );

        $this->table(
            ['Status', 'New Hires', 'Promotions', 'Deactivated', 'Errors'],
            [[$log->status->value, $log->records_imported, $log->records_transferred, $log->records_deactivated, count($log->errors ?? [])]],
        );

        foreach ($log->errors ?? [] as $error) {
            $this->warn("  - {$error}");
        }

        return $log->status->value === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
