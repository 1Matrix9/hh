<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Utils;

class BunnyStreamService
{
    public function __construct(
        private ?string $libraryId = null,
        private ?string $apiKey = null
    ) {
        $this->libraryId = $this->libraryId ?? config('services.bunny.stream_library_id');
        $this->apiKey    = $this->apiKey ?? config('services.bunny.stream_api_key');
    }

    protected function client()
    {
        return Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/*+json',
            'AccessKey'    => $this->apiKey,
        ])->baseUrl("https://video.bunnycdn.com/library/{$this->libraryId}");
    }

    public function createVideo(string $title): array
    {
        $res = $this->client()->post('/videos', ['title' => $title])->throw();
        $data = $res->json();
        return is_array($data) ? $data : [];
    }

    public function getVideo(string $guid): array
    {
        $res = $this->client()->get("/videos/{$guid}")->throw();
        $data = $res->json();
        return is_array($data) ? $data : [];
    }

    // FIXED: streaming upload without getClient()
    public function uploadBinary(string $guid, string $pathToFile): void
    {
        $url = "https://video.bunnycdn.com/library/{$this->libraryId}/videos/{$guid}";

        $handle = fopen($pathToFile, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open file for reading');
        }

        try {
            // Create a PSR-7 stream to avoid reading the entire file into memory
            /** @var StreamInterface $stream */
            $stream = Utils::streamFor($handle);

            $response = Http::withHeaders([
                'AccessKey' => $this->apiKey,
                'Content-Type' => 'application/octet-stream',
            ])
                ->timeout(0)                 // allow large uploads
                ->withBody($stream, 'application/octet-stream')
                ->put($url);

            $response->throw();
        } finally {
            fclose($handle);
        }
    }

    // PHP
    public function updateVideo(string $guid, array $payload): array
    {
        $res = $this->client()
            ->put("/videos/{$guid}", $payload)
            ->throw();

        $data = $res->json();
        return is_array($data) ? $data : [];
    }



    public function deleteVideo(string $guid): void
    {
        $this->client()->delete("/videos/{$guid}")->throw();
    }
}
