<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            $owner = config('episodes.owner');
            $name = config('episodes.name');
            $branch = config('episodes.branch');
            $envFolder = config('episodes.env');
            $token = config('episodes.token');
            if ($token && $owner && $name) {
                $headers = [
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ];
                $listUrl = 'https://api.github.com/repos/'.trim($owner).'/'.trim($name).'/contents/'.trim($envFolder).'/episodes';
                $listRes = Http::withHeaders($headers)->get($listUrl, ['ref' => $branch]);
                if ($listRes->ok()) {
                    $items = $listRes->json();
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $type = $item['type'] ?? null;
                            $nameOnly = $item['name'] ?? null;
                            $path = $item['path'] ?? null;
                            if ($type === 'file' && is_string($nameOnly) && str_ends_with($nameOnly, '.json') && is_string($path)) {
                                $fileRes = Http::withHeaders($headers)->get('https://api.github.com/repos/'.trim($owner).'/'.trim($name).'/contents/'.trim($path), ['ref' => $branch]);
                                if ($fileRes->ok()) {
                                    $payload = $fileRes->json();
                                    $contentRaw = $payload['content'] ?? null;
                                    if (is_string($contentRaw)) {
                                        $decoded = base64_decode($contentRaw);
                                        $ep = json_decode($decoded, true);
                                        if (is_array($ep) && isset($ep['id'])) {
                                            $episodes[] = $ep;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (empty($episodes)) {
            Log::warning('SyncEpisodesToRedis episodes not fetched and not available', [
                'owner' => config('episodes.owner'),
                'repo' => config('episodes.name'),
                'env' => config('episodes.env'),
                'branch' => config('episodes.branch'),
            ]);
        }

        foreach (Redis::keys('episode:*') as $k) {
            Redis::del($k);
        }
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
            Redis::set('episodes:all', json_encode([]));
        }
    }
}
