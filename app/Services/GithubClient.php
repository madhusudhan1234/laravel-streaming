<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GithubClient
{
    // #######################################
    // Configuration
    // #######################################

    private string $token;
    private string $owner;
    private string $repo;
    private string $branch;
    private string $envFolder;

    private function __construct(
        string $token,
        string $owner,
        string $repo,
        string $branch,
        string $envFolder
    ) {
        $this->token = $token;
        $this->owner = $owner;
        $this->repo = $repo;
        $this->branch = $branch;
        $this->envFolder = $envFolder;
    }

    public static function fromConfig(): ?self
    {
        $token = config('episodes.token');
        $owner = config('episodes.owner');
        $repo = config('episodes.name');
        $branch = config('episodes.branch');
        $envFolder = config('episodes.env');

        if (! $token || ! $owner || ! $repo) {
            return null;
        }

        return new self($token, $owner, $repo, $branch ?? 'main', $envFolder ?? '');
    }

    // #######################################
    // Episode File Operations
    // #######################################

    public function deleteEpisodeFile(int $episodeId): bool
    {
        // ##############################
        // Check if file exists
        // ##############################

        $path = $this->episodePath($episodeId);
        $sha = $this->getFileSha($path);

        if (! $sha) {
            return true; // File doesn't exist, nothing to delete
        }

        // ##############################
        // Perform deletion
        // ##############################

        $response = Http::withHeaders($this->headers())->delete(
            $this->contentsUrl($path),
            [
                'message' => 'Delete episode '.$episodeId,
                'sha' => $sha,
                'branch' => $this->branch,
            ]
        );

        if (! $response->successful()) {
            Log::error('Failed to delete episode file on GitHub', [
                'episodeId' => $episodeId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    public function upsertEpisodeFile(int $episodeId, array $content): bool
    {
        // ##############################
        // Build request body
        // ##############################

        $path = $this->episodePath($episodeId);
        $sha = $this->getFileSha($path);

        $body = [
            'message' => 'Upsert episode '.$episodeId,
            'content' => base64_encode(json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
            'branch' => $this->branch,
        ];

        if ($sha) {
            $body['sha'] = $sha;
        }

        // ##############################
        // Perform upsert
        // ##############################

        $response = Http::withHeaders($this->headers())->put(
            $this->contentsUrl($path),
            $body
        );

        if (! $response->successful()) {
            Log::error('Failed to upsert episode file on GitHub', [
                'episodeId' => $episodeId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    // #######################################
    // HTTP Helpers
    // #######################################

    private function getFileSha(string $path): ?string
    {
        $response = Http::withHeaders($this->headers())->get(
            $this->contentsUrl($path),
            ['ref' => $this->branch]
        );

        if ($response->ok()) {
            return $response->json()['sha'] ?? null;
        }

        return null;
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }

    // #######################################
    // URL and Path Builders
    // #######################################

    private function contentsUrl(string $path): string
    {
        return 'https://api.github.com/repos/'
            .rawurlencode($this->owner).'/'
            .rawurlencode($this->repo)
            .'/contents/'.$path;
    }

    private function episodePath(int $episodeId): string
    {
        return $this->envFolder.'/episodes/'.$episodeId.'.json';
    }
}
