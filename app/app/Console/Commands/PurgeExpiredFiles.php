<?php

namespace App\Console\Commands;

use App\Models\StoredFile;
use App\Services\DeleteStoredFileAction;
use Illuminate\Console\Command;

class PurgeExpiredFiles extends Command
{
    protected $signature = 'files:purge-expired';

    protected $description = 'Delete files that exceeded the 24-hour retention period.';

    public function handle(DeleteStoredFileAction $deleteStoredFileAction): int
    {
        $deletedCount = 0;
        $failedCount = 0;

        StoredFile::query()
            ->expired()
            ->orderBy('id')
            ->chunkById(100, function ($files) use (&$deletedCount, &$failedCount, $deleteStoredFileAction): void {
                foreach ($files as $storedFile) {
                    $published = $deleteStoredFileAction->execute($storedFile, 'expired');

                    $deletedCount++;

                    if (! $published) {
                        $failedCount++;
                    }
                }
            });

        $this->info("Deleted {$deletedCount} expired file(s). RabbitMQ publish failures: {$failedCount}.");

        return self::SUCCESS;
    }
}
