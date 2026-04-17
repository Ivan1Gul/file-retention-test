<?php

namespace App\Services;

use App\Contracts\PublishesDeletionNotifications;
use App\Models\StoredFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteStoredFileAction
{
    public function __construct(
        private readonly PublishesDeletionNotifications $publisher,
    ) {
    }

    public function execute(StoredFile $storedFile, string $reason): bool
    {
        $payload = [
            'event' => 'file.deleted',
            'reason' => $reason,
            'recipient_email' => config('rabbitmq.notification_email'),
            'deleted_at' => now()->toIso8601String(),
            'file' => [
                'id' => $storedFile->id,
                'original_name' => $storedFile->original_name,
                'stored_name' => $storedFile->stored_name,
                'path' => $storedFile->path,
                'mime_type' => $storedFile->mime_type,
                'size_bytes' => $storedFile->size_bytes,
                'uploaded_at' => optional($storedFile->uploaded_at)->toIso8601String(),
                'expires_at' => optional($storedFile->expires_at)->toIso8601String(),
            ],
        ];

        Log::info(sprintf(
            'Preparing RabbitMQ notification for "%s" to %s.',
            $storedFile->original_name,
            $payload['recipient_email'],
        ), $payload);

        $disk = Storage::disk($storedFile->disk);
        $deletedFromStorage = false;

        if ($storedFile->disk === 'local') {
            $absolutePath = rtrim((string) config('filesystems.disks.local.root'), DIRECTORY_SEPARATOR)
                .DIRECTORY_SEPARATOR
                .ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedFile->path), DIRECTORY_SEPARATOR);

            if (is_file($absolutePath)) {
                $deletedFromStorage = @unlink($absolutePath) || ! file_exists($absolutePath);
            }
        } elseif (method_exists($disk, 'path')) {
            $absolutePath = $disk->path($storedFile->path);

            if (is_file($absolutePath)) {
                $deletedFromStorage = @unlink($absolutePath) || ! file_exists($absolutePath);
            }
        }

        if (! $deletedFromStorage && $disk->exists($storedFile->path)) {
            $disk->delete($storedFile->path);
        }

        $storedFile->delete();

        $published = $this->publisher->publish($payload);

        Log::info(sprintf(
            'Publishing deletion message for "%s" to "%s" finished with status: %s.',
            $storedFile->original_name,
            $payload['recipient_email'],
            $published ? 'published' : 'failed',
        ), $payload);

        return $published;
    }
}
