<?php

namespace App\Jobs;

use App\Repositories\EpisodeRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AppendEpisode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $episode;

    public function __construct(array $episode)
    {
        $this->episode = $episode;
    }

    public function handle(): void
    {
        $added = EpisodeRepository::add($this->episode);
        
        if (! $added) {
            Log::error('Queue: failed to append episode', ['episode' => $this->episode]);
        } else {
            Log::info('Queue: episode appended', ['episode' => $added]);
        }
    }
}

