<?php

namespace App\Jobs;

// #######################################
// Imports
// #######################################

// ##############################
// Laravel Queue Infrastructure
// ##############################

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// ##############################
// Laravel Facades
// ##############################

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// #######################################
// Job Class
// #######################################

class SyncEpisodesToRedis implements ShouldQueue
{
    // ##############################
    // Traits
    // ##############################

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ##############################
    // Properties
    // ##############################

    public array $episodes;

    // ##############################
    // Constructor
    // ##############################

    public function __construct(array $episodes = [])
    {
        $this->episodes = $episodes;
    }

    // ##############################
    // Main Job Handler
    // ##############################

    public function handle(): void
    {
        // ####################
        // Fetch Episodes
        // ####################

        $episodes = $this->episodes;
        if (empty($episodes)) {
            $episodes = $this->fetchEpisodesFromGitHub();
        }

        if (empty($episodes)) {
            Log::warning('SyncEpisodesToRedis episodes not fetched and not available', [
                'owner' => config('episodes.owner'),
                'repo' => config('episodes.name'),
                'env' => config('episodes.env'),
                'branch' => config('episodes.branch'),
            ]);
        }

        // ####################
        // Clear Existing Redis Data
        // ####################

        foreach (Redis::keys('episode:*') as $k) {
            Redis::del($k);
        }
        Redis::del('episodes:all');

        // ####################
        // Store Episodes in Redis
        // ####################

        if (! empty($episodes)) {
            usort($episodes, function ($a, $b) {
                return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
            });
            Redis::set('episodes:all', json_encode($episodes));
            foreach ($episodes as $ep) {
                Redis::set('episode:'.intval($ep['id']), json_encode($ep));
            }
        } else {
            Redis::set('episodes:all', json_encode([]));
        }
    }

    // ##############################
    // GitHub API
    // ##############################

    // ####################
    // Fetcher Factory
    // ####################

    /**
     * Create a closure that fetches content from a GitHub repo.
     * This captures the "GitHub content fetcher" concept as a first-class value.
     */
    private function makeGitHubFetcher(string $token, string $owner, string $name, string $branch): \Closure
    {
        $owner = trim($owner);
        $name = trim($name);

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        return function (string $path) use ($headers, $owner, $name, $branch): ?array {
            $url = "https://api.github.com/repos/{$owner}/{$name}/contents/" . trim($path);
            $response = Http::withHeaders($headers)->get($url, ['ref' => $branch]);
            return $response->ok() ? $response->json() : null;
        };
    }

    // ####################
    // Episode Fetching
    // ####################

    /**
     * Fetch episodes from GitHub repository.
     */
    private function fetchEpisodesFromGitHub(): array
    {
        // ########## Config
        $owner = config('episodes.owner');
        $name = config('episodes.name');
        $branch = config('episodes.branch');
        $envFolder = config('episodes.env');
        $token = config('episodes.token');

        if (! $token || ! $owner || ! $name) {
            return [];
        }

        // ########## Fetch and Process
        $fetchContent = $this->makeGitHubFetcher($token, $owner, $name, $branch);

        $items = $fetchContent(trim($envFolder) . "/episodes");
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn($item) => $this->isJsonFile($item))
            ->map(fn($item) => $this->fetchAndDecodeEpisode($item['path'], $fetchContent))
            ->filter(fn($ep) => is_array($ep) && isset($ep['id']))
            ->values()
            ->all();
    }

    /**
     * Fetch a file from GitHub and decode it as an episode.
     */
    private function fetchAndDecodeEpisode(string $path, \Closure $fetchContent): ?array
    {
        $payload = $fetchContent($path);
        $contentRaw = $payload['content'] ?? null;

        if (! is_string($contentRaw)) {
            return null;
        }

        $decoded = base64_decode($contentRaw);
        return json_decode($decoded, true);
    }

    // ##############################
    // File Utilities
    // ##############################

    /**
     * Check if a directory item is a JSON file.
     */
    private function isJsonFile(array $item): bool
    {
        $type = $item['type'] ?? null;
        $name = $item['name'] ?? null;
        $path = $item['path'] ?? null;

        return $type === 'file'
            && is_string($name)
            && str_ends_with($name, '.json')
            && is_string($path);
    }
}
