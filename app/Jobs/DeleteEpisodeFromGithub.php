<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeleteEpisodeFromGithub implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $episodeId;

    public function __construct(int $episodeId)
    {
        $this->episodeId = $episodeId;
    }

    public function handle(): void
    {
        $token = config('episodes.token');
        $owner = config('episodes.owner');
        $repo = config('episodes.name');
        $branch = config('episodes.branch');
        $envFolder = config('episodes.env');

        if (! $token || ! $owner || ! $repo) {
            Log::warning('DeleteEpisodeFromGithub skipped due to missing config');

            return;
        }

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        $episodePath = $envFolder.'/episodes/'.intval($this->episodeId).'.json';
        $base = 'https://api.github.com/repos/'.rawurlencode($owner).'/'.rawurlencode($repo).'/contents/';

        // delete episode file
        $sha = $this->getSha($base.$episodePath, $headers, $branch);
        if ($sha) {
            $delBody = [
                'message' => 'Delete episode '.intval($this->episodeId),
                'sha' => $sha,
                'branch' => $branch,
            ];
            $delRes = Http::withHeaders($headers)->delete($base.$episodePath, $delBody);
            if (! $delRes->successful()) {
                Log::error('Failed to delete episode file on GitHub', ['status' => $delRes->status(), 'body' => $delRes->body()]);
            }
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
