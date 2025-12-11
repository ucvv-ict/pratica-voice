<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class CloudflareR2Service
{
    private string $disk;
    private int $expiryMinutes;

    public function __construct()
    {
        $this->disk = 'r2';
        $this->expiryMinutes = (int) env('R2_URL_EXPIRY_MINUTES', 60 * 24 * 7); // default 7 giorni
    }

    public function uploadAndLink(string $localPath, ?string $key = null): array
    {
        $disk = Storage::disk($this->disk);

        $key = $key ?: 'fascicoli/' . basename($localPath);

        $stream = fopen($localPath, 'r');
        $disk->put($key, $stream, ['visibility' => 'private']);
        fclose($stream);

        $expiresAt = now()->addMinutes($this->expiryMinutes);

        return [
            'url' => $disk->temporaryUrl($key, $expiresAt),
            'expires_at' => $expiresAt,
        ];
    }
}
