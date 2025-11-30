<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\File;

class SyncEpisodesToRedis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $episodes;

    public function __construct(array $episodes = [])
    {
        $this->episodes = $episodes;
    }

    public function handle(): void
    {
        $episodes = $this->episodes;
        if (empty($episodes)) {
            $dir = public_path('episodes');
            if (File::isDirectory($dir)) {
                foreach (File::glob($dir.'/*.json') as $file) {
                    $json = File::get($file);
                    $ep = json_decode($json, true);
                    if (is_array($ep) && isset($ep['id'])) {
                        $episodes[] = $ep;
                    }
                }
            }
        }

        // clear existing episodes list key before repopulating
        Redis::del('episodes:all');
        if (! empty($episodes)) {
            usort($episodes, function ($a, $b) {
                return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
            });
            Redis::set('episodes:all', json_encode($episodes));
            foreach ($episodes as $ep) {
                Redis::set('episode:'.intval($ep['id']), json_encode($ep));
            }
        } else {
            // Ensure empty arrays are reflected rather than undefined
            Redis::set('episodes:all', json_encode([]));
        }
    }
}
