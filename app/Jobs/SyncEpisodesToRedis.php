<?php

namespace App\Jobs;

use App\Services\EpisodeCacheService;
use App\Services\GitHubEpisodesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncEpisodesToRedis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly array $episodes = []) {}

    public function handle(GitHubEpisodesService $github, EpisodeCacheService $cache): void
    {
        $episodes = $this->episodes ?: $github->fetchAll();

        if (empty($episodes)) {
            Log::warning('SyncEpisodesToRedis: no episodes fetched', [
                'owner' => config('episodes.owner'),
                'repo' => config('episodes.name'),
                'env' => config('episodes.env'),
                'branch' => config('episodes.branch'),
            ]);
        }

        $cache->writeAll($episodes);
    }
}
