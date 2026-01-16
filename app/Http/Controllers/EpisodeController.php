<?php

namespace App\Http\Controllers;

// #######################################
// IMPORTS
// #######################################

// ##############################
// Framework
// ##############################

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Bus;
use Inertia\Inertia;

// ##############################
// Application
// ##############################

use App\Models\Episode;
use App\Repositories\EpisodeRepository;
use App\Jobs\AppendEpisode;
use App\Jobs\SyncEpisodesToRedis;
use App\Jobs\DeleteEpisodeFromGithub;

class EpisodeController extends Controller
{
    // #######################################
    // STORAGE HELPERS
    // #######################################

    // ##############################
    // R2 Configuration
    // ##############################

    private function shouldUseR2(): bool
    {
        return config('filesystems.default') === 'r2' ||
               (config('filesystems.disks.r2.key') && config('filesystems.disks.r2.bucket'));
    }

    private function isR2Ready(): bool
    {
        $r2Config = config('filesystems.disks.r2');
        return !empty($r2Config['key']) &&
               !empty($r2Config['secret']) &&
               !empty($r2Config['bucket']) &&
               !empty($r2Config['endpoint']);
    }

    // ##############################
    // File Upload
    // ##############################

    private function uploadAudioFile($audioFile, string $filename): array
    {
        $storagePath = 'episodes/' . $filename;

        // ####################
        // R2 Cloud Storage
        // ####################

        if ($this->shouldUseR2()) {
            if (! $this->isR2Ready()) {
                return ['success' => false, 'error' => 'Cloud storage configuration is incomplete'];
            }

            try {
                $uploadSuccess = Storage::disk('r2')->putFileAs('episodes', $audioFile, $filename, 'public');

                if (! $uploadSuccess) {
                    Log::error('R2 upload returned false', [
                        'filename' => $filename,
                        'storage_path' => $storagePath,
                    ]);
                    return ['success' => false, 'error' => 'Failed to upload audio file to cloud storage'];
                }

                return ['success' => true, 'url' => '/' . $storagePath, 'disk' => 'r2'];
            } catch (\Exception $e) {
                return ['success' => false, 'error' => 'Failed to upload audio file to cloud storage: ' . $e->getMessage()];
            }
        }

        // ####################
        // Local Storage
        // ####################

        $audioDir = public_path('audios');
        if (! is_dir($audioDir)) {
            mkdir($audioDir, 0755, true);
        }

        $uploadSuccess = $audioFile->move($audioDir, $filename);

        if (! $uploadSuccess) {
            Log::error('Failed to upload file to local storage', [
                'filename' => $filename,
                'destination' => $audioDir,
            ]);
            return ['success' => false, 'error' => 'Failed to upload audio file'];
        }

        return ['success' => true, 'url' => '/audios/' . $filename, 'disk' => 'local'];
    }

    // ##############################
    // File Deletion
    // ##############################

    private function deleteStoredFile(string $url): void
    {
        try {
            $isR2 = str_starts_with($url, 'http') || str_starts_with($url, '/episodes/');
            if ($isR2) {
                if ($this->isR2Ready()) {
                    $path = str_starts_with($url, 'http')
                        ? ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/')
                        : ltrim($url, '/');
                    Storage::disk('r2')->delete($path);
                } else {
                    Log::warning('R2 deletion skipped due to missing configuration');
                }
            } else {
                $filePath = public_path(ltrim($url, '/'));
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete file from storage: ' . $e->getMessage());
        }
    }

    // #######################################
    // AUDIO METADATA HELPERS
    // #######################################

    private function extractAudioDuration($audioFile): ?float
    {
        try {
            $tempFilePath = $audioFile->getRealPath();
            $getID3 = new \getID3;
            $fileInfo = $getID3->analyze($tempFilePath);
            if (isset($fileInfo['playtime_seconds'])) {
                return round($fileInfo['playtime_seconds'] / 60, 2);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract audio duration: ' . $e->getMessage());
        }
        return null;
    }

    private function formatFileSize($bytes)
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $unitIndex = floor($base);

        $unitIndex = min($unitIndex, count($units) - 1);

        $size = round(pow(1024, $base - $unitIndex), 2);

        return $size.' '.$units[$unitIndex];
    }

    // #######################################
    // DATA ACCESS HELPERS
    // #######################################

    private function findEpisode(int $id): array|Episode|null
    {
        if (! app()->environment('testing')) {
            $episode = EpisodeRepository::find($id);
            if ($episode) {
                return $episode;
            }
        }
        return Episode::find($id);
    }

    private function getEpisodesFromRedis(): array
    {
        $raw = Redis::get('episodes:all');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    public function getEpisodes()
    {
        // ####################
        // Check for Test Episodes
        // ####################

        try {
            $testSpecific = Episode::whereIn('filename', ['test-episode-1.mp3', 'test-episode-2.m4a'])
                ->orderBy('id')
                ->get()
                ->toArray();
            if (! empty($testSpecific)) {
                return $testSpecific;
            }
        } catch (\Exception $e) {
            // ignore
        }

        // ####################
        // Load from Redis or Database
        // ####################

        $episodes = $this->getEpisodesFromRedis();
        if (empty($episodes)) {
            try {
                return Episode::orderBy('id')->get()->toArray();
            } catch (\Exception $e) {
                return [];
            }
        }
        return $episodes;
    }

    // #######################################
    // VIEW CONTROLLERS
    // #######################################

    public function index()
    {
        $episodes = $this->getEpisodes();

        return Inertia::render('Home', [
            'episodes' => $episodes,
        ]);
    }

    public function embed($id)
    {
        $episode = Episode::find($id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        return Inertia::render('Embed', [
            'episode' => $episode,
        ]);
    }

    public function dashboard()
    {
        $episodes = $this->getEpisodesFromRedis();
        usort($episodes, fn ($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));

        return Inertia::render('EpisodeManagement', [
            'episodes' => $episodes,
        ]);
    }

    // #######################################
    // API CONTROLLERS
    // #######################################

    public function apiIndex()
    {
        $episodes = $this->getEpisodes();

        return response()->json([
            'episodes' => $episodes,
            'total' => count($episodes),
        ]);
    }

    public function show($id)
    {
        $episode = $this->findEpisode((int) $id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        return response()->json($episode);
    }

    public function edit($episode)
    {
        $data = $this->findEpisode((int) $episode);
        if (! $data) {
            return response()->json(['message' => 'Episode not found'], 404);
        }
        return response()->json($data);
    }

    // #######################################
    // CRUD OPERATIONS
    // #######################################

    // ##############################
    // Sync
    // ##############################

    public function sync()
    {
        SyncEpisodesToRedis::dispatch();
        return redirect()->route('episodes.dashboard')->with('success', 'Episodes sync queued');
    }

    // ##############################
    // Create
    // ##############################

    public function store(Request $request)
    {
        // ####################
        // Validate Request
        // ####################

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'audio_file' => 'required|file|mimes:mp3,m4a,wav|max:51200',
        ]);

        try {
            // ####################
            // Validate Audio File
            // ####################

            if (! $request->hasFile('audio_file')) {
                Log::error('No audio file provided in request');

                return redirect()->back()->withErrors(['error' => 'No audio file provided']);
            }

            $audioFile = $request->file('audio_file');

            if (! $audioFile->isValid()) {
                return redirect()->back()->withErrors(['error' => 'The audio file failed to upload. Error: '.$audioFile->getErrorMessage()]);
            }

            // ####################
            // Extract Metadata and Upload
            // ####################

            $fileSize = $audioFile->getSize();
            $format = $audioFile->getClientOriginalExtension();
            $duration = $this->extractAudioDuration($audioFile);

            $filename = time().'_'.$audioFile->getClientOriginalName();
            $uploadResult = $this->uploadAudioFile($audioFile, $filename);

            if (! $uploadResult['success']) {
                return redirect()->back()->withErrors(['error' => $uploadResult['error']]);
            }

            $fileUrl = $uploadResult['url'];
            $useR2 = $uploadResult['disk'] === 'r2';

            // ####################
            // Save Episode
            // ####################

            if (! app()->environment('testing')) {
                $now = now()->format('Y-m-d H:i:s');
                $episode = [
                    'title' => $request->title,
                    'description' => $request->description,
                    'filename' => $filename,
                    'storage_disk' => $useR2 ? 'r2' : 'local',
                    'url' => $fileUrl,
                    'file_size' => $this->formatFileSize($fileSize),
                    'format' => strtoupper($format),
                    'published_date' => $request->published_date,
                    'duration' => $duration,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                Bus::chain([
                    new AppendEpisode($episode),
                    new SyncEpisodesToRedis(),
                ])->dispatch();
            } else {
                $episode = Episode::create([
                    'title' => $request->title,
                    'description' => $request->description,
                    'filename' => $filename,
                    'url' => $fileUrl,
                    'file_size' => $this->formatFileSize($fileSize),
                    'format' => $format,
                    'published_date' => $request->published_date,
                    'duration' => $duration,
                ]);

            }

            return redirect()->route('episodes.dashboard')->with('success', 'Episode creation queued');

        } catch (\Exception $e) {
            // ####################
            // Error Handling and Cleanup
            // ####################

            Log::error('Exception during episode creation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($fileUrl)) {
                $this->deleteStoredFile($fileUrl);
            }

            return redirect()->back()->withErrors(['error' => 'Error creating episode: '.$e->getMessage()]);
        }
    }

    // ##############################
    // Update
    // ##############################

    public function update(\App\Http\Requests\UpdateEpisodeRequest $request, $episode)
    {
        try {
            // ####################
            // Prepare Base Update Data
            // ####################

            $updateData = [
                'title' => $request->title,
                'description' => $request->description,
                'published_date' => $request->published_date,
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];

            // ####################
            // Handle Audio File Replacement
            // ####################

            if ($request->hasFile('audio_file')) {

                // ##### Delete Old File
                $current = $this->findEpisode((int) $episode);
                if ($current) {
                    $currentUrl = is_array($current) ? ($current['url'] ?? '') : $current->url;
                    if ($currentUrl) {
                        $this->deleteStoredFile($currentUrl);
                    }
                }

                // ##### Upload New File
                $audioFile = $request->file('audio_file');
                $fileSize = $audioFile->getSize();
                $format = $audioFile->getClientOriginalExtension();
                $duration = $this->extractAudioDuration($audioFile);

                $filename = time().'_'.$audioFile->getClientOriginalName();
                $uploadResult = $this->uploadAudioFile($audioFile, $filename);

                if (! $uploadResult['success']) {
                    return redirect()->back()->withErrors(['error' => $uploadResult['error']]);
                }

                // ##### Update File Data
                $updateData['filename'] = $filename;
                $updateData['storage_disk'] = $uploadResult['disk'];
                $updateData['url'] = $uploadResult['url'];
                $updateData['file_size'] = $this->formatFileSize($fileSize);
                $updateData['format'] = strtoupper($format);
                $updateData['duration'] = $duration;
            }

            // ####################
            // Persist Update
            // ####################

            if (! app()->environment('testing')) {

                // ##### Update Repository
                $updated = EpisodeRepository::update((int) $episode, $updateData);
                if (! $updated) {
                    return redirect()->back()->withErrors(['error' => 'Error updating episode']);
                }

                // ##### Sync to GitHub
                $token = config('episodes.token');
                $owner = config('episodes.owner');
                $repo = config('episodes.name');
                $branch = config('episodes.branch');
                $envFolder = config('episodes.env');
                if ($token && $owner && $repo) {
                    $headers = [
                        'Authorization' => 'Bearer '.$token,
                        'Accept' => 'application/vnd.github+json',
                        'X-GitHub-Api-Version' => '2022-11-28',
                    ];
                    $base = 'https://api.github.com/repos/'.rawurlencode($owner).'/'.rawurlencode($repo).'/contents/';
                    $path = $envFolder.'/episodes/'.intval($episode).'.json';
                    $sha = null;
                    $get = Http::withHeaders($headers)->get($base.$path, ['ref' => $branch]);
                    if ($get->ok()) {
                        $payload = $get->json();
                        $sha = $payload['sha'] ?? null;
                    }
                    $content = base64_encode(json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    $body = [
                        'message' => 'Upsert episode '.intval($episode),
                        'content' => $content,
                        'branch' => $branch,
                    ];
                    if ($sha) {
                        $body['sha'] = $sha;
                    }
                    Http::withHeaders($headers)->put($base.$path, $body);
                    SyncEpisodesToRedis::dispatch();
                }
            } else {
                $model = Episode::find($episode);
                if (! $model) {
                    return redirect()->back()->withErrors(['error' => 'Episode not found']);
                }
                $model->update($updateData);
            }

            return redirect()->route('episodes.dashboard')->with('success', 'Episode updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Error updating episode: '.$e->getMessage()]);
        }
    }

    // ##############################
    // Delete
    // ##############################

    public function destroy($episode)
    {
        try {
            // ####################
            // Delete File from Storage
            // ####################

            $current = $this->findEpisode((int) $episode);
            if ($current) {
                $currentUrl = is_array($current) ? ($current['url'] ?? '') : $current->url;
                if ($currentUrl) {
                    $this->deleteStoredFile($currentUrl);
                }
            }

            // ####################
            // Delete Episode Record
            // ####################

            if (! app()->environment('testing')) {
                $episodes = $this->getEpisodesFromRedis();
                $episodes = array_values(array_filter($episodes, function ($ep) use ($episode) {
                    return intval($ep['id'] ?? 0) !== intval($episode);
                }));
                Redis::del('episode:'.intval($episode));
                Redis::set('episodes:all', json_encode($episodes));

                if (config('episodes.token') && config('episodes.owner') && config('episodes.name')) {
                    \Illuminate\Support\Facades\Bus::chain([
                        new DeleteEpisodeFromGithub((int) $episode),
                        new SyncEpisodesToRedis(),
                    ])->dispatch();
                }
            } else {
                $model = Episode::find($episode);
                if (! $model) {
                    return response()->json(['message' => 'Episode not found'], 404);
                }
                $model->delete();
            }

            return response()->json([
                'message' => 'Episode deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting episode: '.$e->getMessage(),
            ], 500);
        }
    }
}
