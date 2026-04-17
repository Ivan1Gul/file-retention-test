<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class StoredFile extends Model
{
    protected $fillable = [
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'extension',
        'size_bytes',
        'uploaded_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    public function humanSize(): string
    {
        $bytes = $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);
        $normalized = $bytes / (1024 ** $power);

        return number_format($normalized, $power === 0 ? 0 : 2).' '.$units[$power];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function expiresInHuman(): string
    {
        return $this->expires_at->diffForHumans();
    }

    public function uploadedAtFormatted(): string
    {
        /** @var Carbon $uploadedAt */
        $uploadedAt = $this->uploaded_at;

        return $uploadedAt->format('Y-m-d H:i:s');
    }
}
