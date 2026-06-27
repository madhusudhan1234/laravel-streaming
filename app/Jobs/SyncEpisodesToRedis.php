<?php

namespace App\Jobs;

use App\Services\GitHubEpisodesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SyncEpisodesToRedis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly array $episodes = []) {}

    public function handle(GitHubEpisodesService $github): void
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

        $this->writeToRedis($episodes);
    }

    private function writeToRedis(array $episodes): void
    {
        foreach (Redis::keys('episode:*') as $key) {
            Redis::del($key);
        }
        Redis::del('episodes:all');

        if (empty($episodes)) {
            Redis::set('episodes:all', json_encode([]));

            return;
        }

        usort($episodes, fn ($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));
        Redis::set('episodes:all', json_encode($episodes));

        foreach ($episodes as $ep) {
            Redis::set('episode:'.intval($ep['id']), json_encode($ep));
        }
    }
}
