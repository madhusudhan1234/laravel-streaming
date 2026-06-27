<?php

namespace App\Services;

use App\Http\Requests\StoreEpisodeRequest;
use App\Http\Requests\UpdateEpisodeRequest;
use App\Jobs\AppendEpisode;
use App\Jobs\DeleteEpisodeFromGithub;
use App\Jobs\SyncEpisodesToRedis;
use App\Models\Episode;
use App\Repositories\EpisodeRepository;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class EpisodeService
{
    public function __construct(
        private AudioUploadService $uploadService,
        private GitHubEpisodesService $github,
        private EpisodeCacheService $cache,
    ) {}

    public function getAll(): array
    {
        if (app()->environment('testing')) {
            return Episode::orderBy('id')->get()->toArray();
        }

        $cached = $this->cache->getAll();
        if (! empty($cached)) {
            return $cached;
        }

        try {
            return Episode::orderBy('id')->get()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function find(int $id): array|Episode|null
    {
        if (! app()->environment('testing')) {
            $episode = EpisodeRepository::find($id);
            if ($episode) {
                return $episode;
            }
        }

        return Episode::find($id);
    }

    public function findByFilename(string $filename): array|Episode|null
    {
        $base = basename($filename);

        if (! app()->environment('testing')) {
            return EpisodeRepository::findByFilename($base);
        }

        return Episode::where('filename', $base)->first();
    }

    public function store(StoreEpisodeRequest $request): void
    {
        $audioFile = $request->file('audio_file');
        $filename = time().'_'.$audioFile->getClientOriginalName();

        $upload = $this->uploadService->upload($audioFile, $filename);

        $episodeData = [
            'title' => $request->title,
            'description' => $request->description,
            'filename' => $filename,
            'storage_disk' => $upload['disk'],
            'url' => $upload['url'],
            'file_size' => $this->formatFileSize($audioFile->getSize()),
            'format' => strtoupper($audioFile->getClientOriginalExtension()),
            'published_date' => $request->published_date,
            'duration' => $this->extractDuration($audioFile->getRealPath()),
        ];

        if (app()->environment('testing')) {
            Episode::create($episodeData);

            return;
        }

        $now = now()->format('Y-m-d H:i:s');
        $episodeData['created_at'] = $now;
        $episodeData['updated_at'] = $now;

        Bus::chain([
            new AppendEpisode($episodeData),
            new SyncEpisodesToRedis,
        ])->dispatch();
    }

    public function update(UpdateEpisodeRequest $request, int $id): bool
    {
        $updateData = [
            'title' => $request->title,
            'description' => $request->description,
            'published_date' => $request->published_date,
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ];

        if ($request->hasFile('audio_file')) {
            $current = app()->environment('testing')
                ? Episode::find($id)
                : EpisodeRepository::find($id);

            if ($current) {
                $currentUrl = is_array($current) ? ($current['url'] ?? '') : ($current->url ?? '');
                if ($currentUrl) {
                    $this->uploadService->delete($currentUrl);
                }
            }

            $audioFile = $request->file('audio_file');
            $filename = time().'_'.$audioFile->getClientOriginalName();
            $upload = $this->uploadService->upload($audioFile, $filename);

            $updateData['filename'] = $filename;
            $updateData['storage_disk'] = $upload['disk'];
            $updateData['url'] = $upload['url'];
            $updateData['file_size'] = $this->formatFileSize($audioFile->getSize());
            $updateData['format'] = strtoupper($audioFile->getClientOriginalExtension());
            $updateData['duration'] = $this->extractDuration($audioFile->getRealPath());
        }

        if (app()->environment('testing')) {
            $model = Episode::find($id);
            if (! $model) {
                return false;
            }
            $model->update($updateData);

            return true;
        }

        $updated = EpisodeRepository::update($id, $updateData);
        if (! $updated) {
            return false;
        }

        if ($this->github->isConfigured()) {
            $this->github->upsert($id, $updated);
            SyncEpisodesToRedis::dispatch();
        }

        return true;
    }

    public function delete(int $id): bool
    {
        $current = app()->environment('testing')
            ? Episode::find($id)
            : EpisodeRepository::find($id);

        if ($current) {
            $currentUrl = is_array($current) ? ($current['url'] ?? '') : ($current->url ?? '');
            if ($currentUrl) {
                $this->uploadService->delete($currentUrl);
            }
        }

        if (app()->environment('testing')) {
            $model = Episode::find($id);
            if (! $model) {
                return false;
            }
            $model->delete();

            return true;
        }

        // Optimistically remove from Redis so the public API reflects the deletion immediately.
        $episodes = array_values(array_filter(
            $this->cache->getAll(),
            fn ($ep) => intval($ep['id'] ?? 0) !== $id
        ));
        $this->cache->forget($id);
        $this->cache->setAll($episodes);

        if ($this->github->isConfigured()) {
            Bus::chain([
                new DeleteEpisodeFromGithub($id),
                new SyncEpisodesToRedis,
            ])->dispatch();
        }

        return true;
    }

    public function sync(): void
    {
        SyncEpisodesToRedis::dispatch();
    }

    private function extractDuration(string $filePath): ?float
    {
        try {
            $getID3 = new \getID3;
            $fileInfo = $getID3->analyze($filePath);
            if (isset($fileInfo['playtime_seconds'])) {
                return round($fileInfo['playtime_seconds'] / 60, 2);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract audio duration: '.$e->getMessage());
        }

        return null;
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $unitIndex = min((int) floor($base), count($units) - 1);

        return round(pow(1024, $base - $unitIndex), 2).' '.$units[$unitIndex];
    }
}
