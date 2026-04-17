<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class StoreStoredFileRequest extends FormRequest
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'docx'];

    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var UploadedFile|null $file */
            $file = $this->file('file');

            if (! $file instanceof UploadedFile) {
                return;
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = strtolower((string) $file->getMimeType());

            if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $validator->errors()->add('file', 'Only PDF and DOCX files are allowed.');
            }

            if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
                $validator->errors()->add('file', 'The uploaded file MIME type is not supported.');
            }
        });
    }
}
