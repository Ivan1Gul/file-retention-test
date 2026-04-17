<?php

namespace Tests\Feature;

use App\Contracts\PublishesDeletionNotifications;
use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoredFileManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $localDiskRoot;

    public function test_file_can_be_uploaded_and_saved_in_database(): void
    {
        $this->prepareLocalDisk();
        $this->bindPublisherSpy();

        $response = $this->post(route('files.store'), [
            'file' => $this->makeUploadedFile('contract.pdf', '%PDF-1.4 test payload', 'application/pdf'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'File uploaded successfully.');

        $this->assertDatabaseCount('stored_files', 1);

        /** @var StoredFile $storedFile */
        $storedFile = StoredFile::query()->firstOrFail();
        $this->assertFileExists($this->localDiskPath($storedFile->path));
    }

    public function test_file_can_be_deleted_manually_and_notification_is_published(): void
    {
        $this->prepareLocalDisk();
        $publisherSpy = $this->bindPublisherSpy();

        Storage::disk('local')->put('uploads/test.pdf', 'test-content');

        $storedFile = StoredFile::query()->create([
            'original_name' => 'test.pdf',
            'stored_name' => 'uuid-test.pdf',
            'disk' => 'local',
            'path' => 'uploads/test.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 12,
            'uploaded_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $this->delete(route('files.destroy', $storedFile))
            ->assertOk()
            ->assertJsonPath('message', 'File deleted and message published to RabbitMQ.');

        $this->assertDatabaseMissing('stored_files', ['id' => $storedFile->id]);
        $this->assertCount(1, $publisherSpy->payloads);
        $this->assertSame('manual', $publisherSpy->payloads[0]['reason']);
    }

    public function test_expired_files_are_deleted_by_scheduled_command(): void
    {
        $this->prepareLocalDisk();
        $publisherSpy = $this->bindPublisherSpy();

        Storage::disk('local')->put('uploads/expired.docx', 'expired-content');

        StoredFile::query()->create([
            'original_name' => 'expired.docx',
            'stored_name' => 'expired.docx',
            'disk' => 'local',
            'path' => 'uploads/expired.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'extension' => 'docx',
            'size_bytes' => 24,
            'uploaded_at' => now()->subDays(2),
            'expires_at' => now()->subMinute(),
        ]);

        Artisan::call('files:purge-expired');

        $this->assertDatabaseCount('stored_files', 0);
        $this->assertCount(1, $publisherSpy->payloads);
        $this->assertSame('expired', $publisherSpy->payloads[0]['reason']);
    }

    private function bindPublisherSpy(): object
    {
        $publisherSpy = new class implements PublishesDeletionNotifications
        {
            public array $payloads = [];

            public function publish(array $payload): bool
            {
                $this->payloads[] = $payload;

                return true;
            }
        };

        $this->app->instance(PublishesDeletionNotifications::class, $publisherSpy);

        return $publisherSpy;
    }

    private function makeUploadedFile(string $name, string $content, string $mimeType): UploadedFile
    {
        $directory = base_path('../tmp/testing-uploads');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = $directory.'/'.uniqid('upload-', true).'-'.$name;
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $mimeType, test: true);
    }

    private function prepareLocalDisk(): void
    {
        $root = base_path('../tmp/testing-storage-'.uniqid('', true));

        if (! is_dir($root)) {
            mkdir($root, 0777, true);
        }

        Storage::forgetDisk('local');
        Config::set('filesystems.disks.local.root', $root);
        Storage::forgetDisk('local');
        $this->localDiskRoot = $root;
    }

    private function localDiskPath(string $relativePath): string
    {
        return $this->localDiskRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }
}
