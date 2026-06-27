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

class AppendEpisode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly array $episode) {}

    public function handle(GitHubEpisodesService $github): void
    {
        $episode = $this->episode;
        $episode['id'] = $episode['id'] ?? $this->nextId();

        Log::info('Queue: episode appended', ['id' => $episode['id'], 'title' => $episode['title'] ?? null]);

        $github->upsert((int) $episode['id'], $episode);
    }

    private function nextId(): int
    {
        $raw = Redis::get('episodes:all');
        $existing = is_string($raw) ? (json_decode($raw, true) ?? []) : [];
        $max = 0;
        foreach ($existing as $e) {
            $max = max($max, (int) ($e['id'] ?? 0));
        }

        return $max + 1;
    }
}
