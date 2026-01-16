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

// ##############################
// Facades
// ##############################

use Illuminate\Support\Facades\Log;

// #######################################
// DeleteEpisodeFromGithub Job
// #######################################

class DeleteEpisodeFromGithub implements ShouldQueue
{
    // ##############################
    // Traits
    // ##############################

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ##############################
    // Properties
    // ##############################

    public int $episodeId;

    // ##############################
    // Construction
    // ##############################

    public function __construct(int $episodeId)
    {
        $this->episodeId = $episodeId;
    }

    // ##############################
    // Job Execution
    // ##############################

    public function handle(): void
    {
        // ####################
        // Initialize GitHub Client
        // ####################

        $github = GithubClient::fromConfig();

        if (! $github) {
            Log::warning('DeleteEpisodeFromGithub skipped due to missing config');
            return;
        }

        // ####################
        // Delete Episode File
        // ####################

        $github->deleteEpisodeFile($this->episodeId);
    }
}
