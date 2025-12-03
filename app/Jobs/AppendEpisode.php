<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;

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
        $existingRaw = Redis::get('episodes:all');
        $existing = [];
        if (is_string($existingRaw)) {
            $decoded = json_decode($existingRaw, true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }
        $maxId = 0;
        foreach ($existing as $e) {
            $maxId = max($maxId, (int) ($e['id'] ?? 0));
        }
        $episode = $this->episode;
        $episode['id'] = $episode['id'] ?? ($maxId + 1);

        

        Log::info('Queue: episode appended', ['episode' => $episode]);
        $token = env('GITHUB_TOKEN');
        $owner = env('EPISODES_REPO_OWNER');
        $repo = env('EPISODES_REPO_NAME');
        $branch = env('EPISODES_BRANCH', 'main');
        $envFolder = env('EPISODES_ENV', 'production');
        if ($token && $owner && $repo) {
            $headers = [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ];
            $base = 'https://api.github.com/repos/'.rawurlencode($owner).'/'.rawurlencode($repo).'/contents/';
            $path = $envFolder.'/episodes/'.intval($episode['id']).'.json';
            $sha = null;
            $get = Http::withHeaders($headers)->get($base.$path, ['ref' => $branch]);
            if ($get->ok()) {
                $payload = $get->json();
                $sha = $payload['sha'] ?? null;
            }
            $content = base64_encode(json_encode($episode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $body = [
                'message' => 'Upsert episode '.intval($episode['id']),
                'content' => $content,
                'branch' => $branch,
            ];
            if ($sha) {
                $body['sha'] = $sha;
            }
            Http::withHeaders($headers)->put($base.$path, $body);
        }
    }
}
