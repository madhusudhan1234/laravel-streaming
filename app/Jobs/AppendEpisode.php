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
// Application Services
// ##############################

use App\Services\GithubClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

// #######################################
// AppendEpisode Job
// #######################################

class AppendEpisode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ##############################
    // Properties
    // ##############################

    public array $episode;

    // ##############################
    // Construction
    // ##############################

    public function __construct(array $episode)
    {
        $this->episode = $episode;
    }

    // ##############################
    // Job Execution
    // ##############################

    public function handle(): void
    {
        // ####################
        // Fetch Existing Episodes from Redis
        // ####################

        $existingRaw = Redis::get('episodes:all');
        $existing = [];
        if (is_string($existingRaw)) {
            $decoded = json_decode($existingRaw, true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        // ####################
        // Compute Next Episode ID
        // ####################

        $maxId = 0;
        foreach ($existing as $e) {
            $maxId = max($maxId, (int) ($e['id'] ?? 0));
        }
        $episode = $this->episode;
        $episode['id'] = $episode['id'] ?? ($maxId + 1);

        Log::info('Queue: episode appended', ['episode' => $episode]);

        // ####################
        // Persist to GitHub
        // ####################

        $github = GithubClient::fromConfig();
        if ($github) {
            $github->upsertEpisodeFile((int) $episode['id'], $episode);
        }
    }
}
