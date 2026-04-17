<tr data-file-id="{{ $file->id }}">
    <td>
        <div class="fw-semibold">{{ $file->original_name }}</div>
        <div class="small text-secondary">{{ $file->stored_name }}</div>
    </td>
    <td>{{ $file->mime_type }}</td>
    <td>{{ $file->humanSize() }}</td>
    <td>{{ $file->uploadedAtFormatted() }}</td>
    <td>
        <div>{{ $file->expires_at->format('Y-m-d H:i:s') }}</div>
        <div class="small text-secondary">{{ $file->expiresInHuman() }}</div>
    </td>
    <td class="text-end">
        <div class="d-inline-flex gap-2">
            <a href="{{ route('files.download', $file) }}" class="btn btn-outline-secondary btn-sm">Download</a>
            <button
                type="button"
                class="btn btn-outline-danger btn-sm delete-file-button"
                data-delete-url="{{ route('files.destroy', $file) }}"
            >
                Delete
            </button>
        </div>
    </td>
</tr>
