<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStoredFileRequest;
use App\Models\StoredFile;
use App\Services\DeleteStoredFileAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoredFileController extends Controller
{
    public function index()
    {
        return view('files.index', [
            'files' => StoredFile::query()
                ->latest('uploaded_at')
                ->get(),
        ]);
    }

    public function store(StoreStoredFileRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $directory = 'uploads/'.now()->format('Y/m/d');
        $storedName = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs($directory, $storedName, 'local');

        $storedFile = StoredFile::query()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'extension' => strtolower($file->getClientOriginalExtension()),
            'size_bytes' => $file->getSize(),
            'uploaded_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        return response()->json([
            'message' => 'File uploaded successfully.',
            'file' => [
                'id' => $storedFile->id,
                'original_name' => $storedFile->original_name,
                'stored_name' => $storedFile->stored_name,
                'mime_type' => $storedFile->mime_type,
                'size' => $storedFile->humanSize(),
                'uploaded_at' => $storedFile->uploadedAtFormatted(),
                'expires_at' => $storedFile->expires_at->format('Y-m-d H:i:s'),
                'expires_in_human' => $storedFile->expiresInHuman(),
                'download_url' => route('files.download', $storedFile),
                'delete_url' => route('files.destroy', $storedFile),
            ],
        ], 201);
    }

    public function destroy(StoredFile $storedFile, DeleteStoredFileAction $deleteStoredFileAction): JsonResponse
    {
        $published = $deleteStoredFileAction->execute($storedFile, 'manual');

        return response()->json([
            'message' => $published
                ? 'File deleted and message published to RabbitMQ.'
                : 'File deleted, but RabbitMQ publication failed. Check logs.',
        ]);
    }

    public function download(StoredFile $storedFile): StreamedResponse
    {
        abort_unless(Storage::disk($storedFile->disk)->exists($storedFile->path), 404);

        return Storage::disk($storedFile->disk)->download(
            $storedFile->path,
            $storedFile->original_name,
        );
    }
}
