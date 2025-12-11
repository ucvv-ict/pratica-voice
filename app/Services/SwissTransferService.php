<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SwissTransferService
{
    private string $baseUrl;
    private string $from;
    private int $validity;
    private ?int $maxViews;
    private ?string $password;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.swisstransfer.base_url', 'https://www.swisstransfer.com/api'), '/');
        $this->from     = config('services.swisstransfer.from', 'pratica-voice');
        $this->validity = (int) config('services.swisstransfer.validity', 720);
        $this->maxViews = config('services.swisstransfer.max_views');
        $this->password = config('services.swisstransfer.password');
        $this->timeout  = (int) config('services.swisstransfer.timeout', 120);
    }

    public function upload(string $filePath, string $fileName = null): string
    {
        $fileName = $fileName ?? basename($filePath);

        if (!config('services.swisstransfer.enabled')) {
            throw new \RuntimeException('SwissTransfer non è abilitato (SWISS_TRANSFER_ENABLED=false).');
        }

        // 1) Inizializza la richiesta
        $init = Http::timeout($this->timeout)
            ->retry(2, 500)
            ->post($this->baseUrl . '/transfer/init', [
            "files" => [
                [
                    "name" => $fileName,
                    "size" => filesize($filePath),
                    "type" => "application/zip",
                ]
            ],
            "from" => $this->from,
            "validity" => $this->validity, // ore
            "maxViews" => $this->maxViews,
            "password" => $this->password,
        ]);

        if (!$init->ok()) {
            throw new \RuntimeException("Errore durante init upload SwissTransfer: " . $init->body());
        }

        $response = $init->json();
        $transferId = $response["id"];
        $fileId     = $response["files"][0]["id"];
        $uploadUrl  = $response["files"][0]["uploadUrl"];

        // 2) Upload del file ZIP (PUT binario)
        $upload = Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/zip',
            ])
            ->send('PUT', $uploadUrl . '/' . $fileId, [
                'body' => fopen($filePath, 'r'),
            ]);

        if (!$upload->successful()) {
            throw new \RuntimeException("Errore durante l'upload del file su SwissTransfer: " . $upload->body());
        }

        // 3) Finalizza
        $finalize = Http::timeout($this->timeout)
            ->retry(2, 500)
            ->post($this->baseUrl . "/transfer/{$transferId}/finalize");

        if (!$finalize->successful()) {
            throw new \RuntimeException("Errore finalizzazione SwissTransfer: " . $finalize->body());
        }

        return $finalize->json()["downloadUrl"];
    }
}
