<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubEpisodesService
{
    private string $token;

    private string $owner;

    private string $repo;

    private string $branch;

    private string $envFolder;

    public function __construct()
    {
        $this->token = config('episodes.token', '');
        $this->owner = config('episodes.owner', '');
        $this->repo = config('episodes.name', '');
        $this->branch = config('episodes.branch', 'main');
        $this->envFolder = config('episodes.env', 'production');
    }

    public function isConfigured(): bool
    {
        return (bool) ($this->token && $this->owner && $this->repo);
    }

    public function fetchAll(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $listUrl = $this->baseUrl().$this->envFolder.'/episodes';
        $listRes = Http::withHeaders($this->headers())->get($listUrl, ['ref' => $this->branch]);

        if (! $listRes->ok()) {
            return [];
        }

        $episodes = [];
        foreach ($listRes->json() as $item) {
            if (($item['type'] ?? null) !== 'file' || ! str_ends_with($item['name'] ?? '', '.json')) {
                continue;
            }

            $fileRes = Http::withHeaders($this->headers())->get(
                $this->baseUrl().$item['path'],
                ['ref' => $this->branch]
            );

            if (! $fileRes->ok()) {
                continue;
            }

            $contentRaw = $fileRes->json()['content'] ?? null;
            if (! is_string($contentRaw)) {
                continue;
            }

            $ep = json_decode(base64_decode($contentRaw), true);
            if (is_array($ep) && isset($ep['id'])) {
                $episodes[] = $ep;
            }
        }

        return $episodes;
    }

    public function upsert(int $id, array $episode): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $path = $this->episodePath($id);
        $sha = $this->getSha($path);

        $body = [
            'message' => 'Upsert episode '.$id,
            'content' => base64_encode(json_encode($episode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
            'branch' => $this->branch,
        ];

        if ($sha) {
            $body['sha'] = $sha;
        }

        Http::withHeaders($this->headers())->put($this->baseUrl().$path, $body);
    }

    public function delete(int $id): void
    {
        if (! $this->isConfigured()) {
            Log::warning('GitHubEpisodesService::delete skipped — missing config');

            return;
        }

        $path = $this->episodePath($id);
        $sha = $this->getSha($path);

        if (! $sha) {
            return;
        }

        $res = Http::withHeaders($this->headers())->delete($this->baseUrl().$path, [
            'message' => 'Delete episode '.$id,
            'sha' => $sha,
            'branch' => $this->branch,
        ]);

        if (! $res->successful()) {
            Log::error('Failed to delete episode file on GitHub', [
                'status' => $res->status(),
                'body' => $res->body(),
            ]);
        }
    }

    private function getSha(string $path): ?string
    {
        $res = Http::withHeaders($this->headers())->get($this->baseUrl().$path, ['ref' => $this->branch]);

        return $res->ok() ? ($res->json()['sha'] ?? null) : null;
    }

    private function episodePath(int $id): string
    {
        return $this->envFolder.'/episodes/'.$id.'.json';
    }

    private function baseUrl(): string
    {
        return 'https://api.github.com/repos/'.rawurlencode($this->owner).'/'.rawurlencode($this->repo).'/contents/';
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }
}
