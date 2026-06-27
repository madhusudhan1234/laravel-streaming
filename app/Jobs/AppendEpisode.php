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

class AppendEpisode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly array $episode) {}

    public function handle(GitHubEpisodesService $github, EpisodeCacheService $cache): void
    {
        $episode = $this->episode;
        $episode['id'] = $episode['id'] ?? ($cache->maxId() + 1);

        Log::info('Queue: episode appended', ['id' => $episode['id'], 'title' => $episode['title'] ?? null]);

        $github->upsert((int) $episode['id'], $episode);
    }
}
