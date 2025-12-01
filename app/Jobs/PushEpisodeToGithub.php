<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushEpisodeToGithub implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $episode;

    public function __construct(array $episode)
    {
        $this->episode = $episode;
    }

    public function handle(): void
    {
        $token = env('GITHUB_TOKEN');
        $owner = env('EPISODES_REPO_OWNER');
        $repo = env('EPISODES_REPO_NAME');
        $branch = env('EPISODES_BRANCH', 'main');
        $envFolder = env('EPISODES_ENV', 'production');

        if (! $token || ! $owner || ! $repo) {
            Log::warning('PushEpisodeToGithub skipped due to missing env');
            return;
        }

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        $id = intval($this->episode['id'] ?? 0);
        if ($id <= 0) {
            Log::warning('PushEpisodeToGithub missing valid id');
            return;
        }

        $episodePath = $envFolder.'/episodes/'.$id.'.json';
        $base = 'https://api.github.com/repos/'.rawurlencode($owner).'/'.rawurlencode($repo).'/contents/';

        // upsert episode file
        $existingSha = $this->getSha($base.$episodePath, $headers, $branch);
        $content = base64_encode(json_encode($this->episode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $putBody = [
            'message' => 'Upsert episode '.$id,
            'content' => $content,
            'branch' => $branch,
        ];
        if ($existingSha) {
            $putBody['sha'] = $existingSha;
        }
        $putRes = Http::withHeaders($headers)->put($base.$episodePath, $putBody);
        if (! $putRes->successful()) {
            Log::error('Failed to upsert episode file on GitHub', ['status' => $putRes->status(), 'body' => $putRes->body()]);
        }

        // no index maintenance
    }

    private function getSha(string $url, array $headers, string $branch): ?string
    {
        $res = Http::withHeaders($headers)->get($url, ['ref' => $branch]);
        if ($res->ok()) {
            $data = $res->json();
            return $data['sha'] ?? null;
        }
        return null;
    }
}
