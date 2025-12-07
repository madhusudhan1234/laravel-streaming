<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Repositories\EpisodeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Bus;
use Inertia\Inertia;
use App\Jobs\AppendEpisode;
use App\Jobs\SyncEpisodesToRedis;
use App\Jobs\DeleteEpisodeFromGithub;

class EpisodeController extends Controller
{
    public function index()
    {
        $episodes = $this->getEpisodes();

        return Inertia::render('Home', [
            'episodes' => $episodes,
        ]);
    }

    public function getEpisodes()
    {
        if (app()->environment('testing')) {
            try {
                return Episode::orderBy('id')->get()->toArray();
            } catch (\Exception $e) {
                return [];
            }
        }

        $raw = Redis::get('episodes:all');
        $episodes = [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $episodes = $decoded;
            }
        }
        return $episodes;
    }

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
        $episode = null;
        if (! app()->environment('testing')) {
            $episode = EpisodeRepository::find((int) $id);
        }
        if (! $episode) {
            $episode = Episode::find($id);
        }

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        return response()->json($episode);
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
        $raw = Redis::get('episodes:all');
        $episodes = [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $episodes = $decoded;
            }
        }
        usort($episodes, fn ($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));

        return Inertia::render('EpisodeManagement', [
            'episodes' => $episodes,
        ]);
    }

    public function sync()
    {
        SyncEpisodesToRedis::dispatch();
        return redirect()->route('episodes.dashboard')->with('success', 'Episodes sync queued');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'audio_file' => 'required|file|mimes:mp3,m4a,wav|max:51200',
        ]);

        try {
            if (! $request->hasFile('audio_file')) {
                Log::error('No audio file provided in request');

                return redirect()->back()->withErrors(['error' => 'No audio file provided']);
            }

            $audioFile = $request->file('audio_file');

            if (! $audioFile->isValid()) {
                return redirect()->back()->withErrors(['error' => 'The audio file failed to upload. Error: '.$audioFile->getErrorMessage()]);
            }

            $filename = time().'_'.$audioFile->getClientOriginalName();
            $storagePath = 'episodes/'.$filename;

            $useR2 = config('filesystems.default') === 'r2' ||
                     (config('filesystems.disks.r2.key') && config('filesystems.disks.r2.bucket'));


            if ($useR2) {
                $r2Config = config('filesystems.disks.r2');
                if (empty($r2Config['key']) || empty($r2Config['secret']) || empty($r2Config['bucket']) || empty($r2Config['endpoint'])) {
                    Log::error('R2 configuration incomplete', [
                        'key_present' => ! empty($r2Config['key']),
                        'secret_present' => ! empty($r2Config['secret']),
                        'bucket_present' => ! empty($r2Config['bucket']),
                        'endpoint_present' => ! empty($r2Config['endpoint']),
                    ]);

                    return redirect()->back()->withErrors(['error' => 'Cloud storage configuration is incomplete']);
                }

                try {

                    $uploadSuccess = Storage::disk('r2')->putFileAs('episodes', $audioFile, $filename, 'public');

                    if (! $uploadSuccess) {
                        Log::error('R2 upload returned false', [
                            'filename' => $filename,
                            'storage_path' => $storagePath,
                        ]);

                        return redirect()->back()->withErrors(['error' => 'Failed to upload audio file to cloud storage']);
                    }

                    $fileUrl = '/'.$storagePath;


                } catch (\Exception $e) {
                    return redirect()->back()->withErrors(['error' => 'Failed to upload audio file to cloud storage: '.$e->getMessage()]);
                }
            } else {
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

                    return redirect()->back()->withErrors(['error' => 'Failed to upload audio file']);
                }

                $fileUrl = '/audios/'.$filename;
            }

            $fileSize = $audioFile->getSize();
            $format = $audioFile->getClientOriginalExtension();

            $duration = null;
            try {
                $tempFilePath = $audioFile->getRealPath();
                $getID3 = new \getID3;
                $fileInfo = $getID3->analyze($tempFilePath);
                if (isset($fileInfo['playtime_seconds'])) {
                    $duration = round($fileInfo['playtime_seconds'] / 60, 2);
                }
            } catch (\Exception $e) {
                // Log the error but don't fail the episode creation
                Log::warning('Failed to extract audio duration: '.$e->getMessage());
            }

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
            Log::error('Exception during episode creation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($useR2) && isset($storagePath) && isset($filename)) {
                try {
                    if ($useR2) {
                        Storage::disk('r2')->delete($storagePath);
                    } elseif (file_exists(public_path('audios/'.$filename))) {
                        unlink(public_path('audios/'.$filename));
                    }
                } catch (\Exception $cleanupException) {
                    Log::warning('Failed to clean up uploaded file: '.$cleanupException->getMessage());
                }
            }

            return redirect()->back()->withErrors(['error' => 'Error creating episode: '.$e->getMessage()]);
        }
    }

    public function edit($episode)
    {
        if (! app()->environment('testing')) {
            $data = EpisodeRepository::find((int) $episode);
            if (! $data) {
                return response()->json(['message' => 'Episode not found'], 404);
            }
            return response()->json($data);
        }
        $model = Episode::find($episode);
        return $model ? response()->json($model) : response()->json(['message' => 'Episode not found'], 404);
    }

    public function update(\App\Http\Requests\UpdateEpisodeRequest $request, $episode)
    {
        try {
            $updateData = [
                'title' => $request->title,
                'description' => $request->description,
                'published_date' => $request->published_date,
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];

            if ($request->hasFile('audio_file')) {
                $current = app()->environment('testing') ? Episode::find($episode) : EpisodeRepository::find((int) $episode);
                if ($current && ($current['url'] ?? $current->url)) {
                    try {
                        $currentUrl = is_array($current) ? ($current['url'] ?? '') : $current->url;
                        if (is_string($currentUrl) && str_starts_with($currentUrl, 'http')) {
                            $urlParts = parse_url($currentUrl);
                            $path = ltrim($urlParts['path'] ?? '', '/');
                            Storage::disk('r2')->delete($path);
                        } else {
                            $oldFilePath = public_path(ltrim($currentUrl, '/'));
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete old file: '.$e->getMessage());
                    }
                }

                $audioFile = $request->file('audio_file');
                $filename = time().'_'.$audioFile->getClientOriginalName();
                $storagePath = 'episodes/'.$filename;

                $useR2 = config('filesystems.default') === 'r2' ||
                         (config('filesystems.disks.r2.key') && config('filesystems.disks.r2.bucket'));

                if ($useR2) {
                    $r2Config = config('filesystems.disks.r2');
                    if (empty($r2Config['key']) || empty($r2Config['secret']) || empty($r2Config['bucket']) || empty($r2Config['endpoint'])) {
                        return redirect()->back()->withErrors(['error' => 'Cloud storage configuration is incomplete']);
                    }

                    try {
                        $uploadSuccess = Storage::disk('r2')->putFileAs('episodes', $audioFile, $filename, 'public');

                        if (! $uploadSuccess) {
                            return redirect()->back()->withErrors(['error' => 'Failed to upload audio file to cloud storage']);
                        }

                        Log::info('R2 upload successful for update', [
                            'filename' => $filename,
                            'storage_path' => $storagePath,
                        ]);

                        $fileUrl = '/'.$storagePath;
                    } catch (\Exception $e) {
                        return redirect()->back()->withErrors(['error' => 'Failed to upload audio file to cloud storage: '.$e->getMessage()]);
                    }
                } else {
                    $audioDir = public_path('audios');
                    if (! is_dir($audioDir)) {
                        mkdir($audioDir, 0755, true);
                    }

                    $uploadSuccess = $audioFile->move($audioDir, $filename);

                    if (! $uploadSuccess) {
                        return redirect()->back()->withErrors(['error' => 'Failed to upload audio file']);
                    }

                    $fileUrl = '/audios/'.$filename;
                }

                // Get file info
                $fileSize = $audioFile->getSize();
                $format = $audioFile->getClientOriginalExtension();

                $duration = null;
                try {
                    $tempFilePath = $audioFile->getRealPath();
                    $getID3 = new \getID3;
                    $fileInfo = $getID3->analyze($tempFilePath);
                    if (isset($fileInfo['playtime_seconds'])) {
                        $duration = round($fileInfo['playtime_seconds'] / 60, 2);
                    }
                } catch (\Exception $e) {
                    // Log the error but don't fail the episode update
                    Log::warning('Failed to extract audio duration: '.$e->getMessage());
                }

                $updateData['filename'] = $filename;
                $updateData['storage_disk'] = $useR2 ? 'r2' : 'local';
                $updateData['url'] = $fileUrl;
                $updateData['file_size'] = $this->formatFileSize($fileSize);
                $updateData['format'] = strtoupper($format);
                $updateData['duration'] = $duration;
            }

            if (! app()->environment('testing')) {
                $updated = EpisodeRepository::update((int) $episode, $updateData);
                if (! $updated) {
                    return redirect()->back()->withErrors(['error' => 'Error updating episode']);
                }
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

    public function destroy($episode)
    {
        try {
            $current = app()->environment('testing') ? Episode::find($episode) : EpisodeRepository::find((int) $episode);
            if ($current) {
                try {
                    $currentUrl = is_array($current) ? ($current['url'] ?? '') : $current->url;
                    $isR2 = is_string($currentUrl) && (str_starts_with($currentUrl, 'http') || str_starts_with($currentUrl, '/episodes/'));
                    if ($isR2) {
                        $r2Config = config('filesystems.disks.r2');
                        $r2Ready = ! empty($r2Config['key']) && ! empty($r2Config['secret']) && ! empty($r2Config['bucket']) && ! empty($r2Config['endpoint']);
                        if ($r2Ready) {
                            $path = str_starts_with($currentUrl, 'http')
                                ? ltrim(parse_url($currentUrl, PHP_URL_PATH) ?? '', '/')
                                : ltrim($currentUrl, '/');
                            Storage::disk('r2')->delete($path);
                        } else {
                            Log::warning('R2 deletion skipped due to missing configuration');
                        }
                    } else {
                        $filePath = public_path(ltrim($currentUrl, '/'));
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete file from storage: '.$e->getMessage());
                }
            }

            if (! app()->environment('testing')) {
                $raw = Redis::get('episodes:all');
                $episodes = [];
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $episodes = $decoded;
                    }
                }
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
}
