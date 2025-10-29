<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EpisodeController extends Controller
{
    /**
     * Convert bytes to human readable format
     */
    private function formatFileSize($bytes)
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $unitIndex = floor($base);
        
        // Ensure we don't exceed array bounds
        $unitIndex = min($unitIndex, count($units) - 1);
        
        $size = round(pow(1024, $base - $unitIndex), 2);
        
        return $size . ' ' . $units[$unitIndex];
    }

    /**
     * Display the home page with all episodes
     */
    public function index()
    {
        $episodes = $this->getEpisodes();

        return Inertia::render('Home', [
            'episodes' => $episodes,
        ]);
    }

    /**
     * Get episodes data from database
     */
    public function getEpisodes()
    {
        try {
            return Episode::orderBy('id')->get()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * API endpoint to get episodes as JSON
     */
    public function apiIndex()
    {
        $episodes = $this->getEpisodes();

        return response()->json([
            'episodes' => $episodes,
            'total' => count($episodes),
        ]);
    }

    /**
     * Get a specific episode by ID
     */
    public function show($id)
    {
        $episode = Episode::find($id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        return response()->json($episode);
    }

    /**
     * Get episode for embed player
     */
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

    /**
     * Display episodes for dashboard management
     */
    public function dashboard()
    {
        $episodes = Episode::orderBy('id', 'desc')->get();

        return Inertia::render('EpisodeManagement', [
            'episodes' => $episodes,
        ]);
    }

    /**
     * Store a new episode
     */
    public function store(Request $request)
    {
        // Immediate debug output to verify method execution
        Log::info('=== EPISODE STORE METHOD CALLED ===', [
            'timestamp' => now(),
            'request_data' => $request->all(),
            'has_audio_file' => $request->hasFile('audio_file'),
            'user_id' => $request->user()?->id,
        ]);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'audio_file' => 'required|file|mimes:mp3,m4a,wav|max:51200', // 50MB max
        ]);

        try {
            // Validate audio file exists
            if (! $request->hasFile('audio_file')) {
                Log::error('No audio file provided in request');

                return redirect()->back()->withErrors(['error' => 'No audio file provided']);
            }

            $audioFile = $request->file('audio_file');

            // Check if file is valid
            if (! $audioFile->isValid()) {
                Log::error('Invalid audio file uploaded', [
                    'error' => $audioFile->getError(),
                    'error_message' => $audioFile->getErrorMessage(),
                ]);

                return redirect()->back()->withErrors(['error' => 'The audio file failed to upload. Error: '.$audioFile->getErrorMessage()]);
            }

            // Generate filename for storage
            $filename = time().'_'.$audioFile->getClientOriginalName();
            $storagePath = 'episodes/'.$filename;

            Log::info('Attempting to upload file', [
                'original_name' => $audioFile->getClientOriginalName(),
                'filename' => $filename,
                'storage_path' => $storagePath,
                'file_size' => $audioFile->getSize(),
                'mime_type' => $audioFile->getMimeType(),
            ]);

            // Determine if R2 is configured (prefer R2 if available, fallback to local)
            $useR2 = config('filesystems.default') === 'r2' || 
                     (config('filesystems.disks.r2.key') && config('filesystems.disks.r2.bucket'));

            Log::info('Storage method determined', [
                'use_r2' => $useR2,
                'filesystem_default' => config('filesystems.default'),
                'r2_key_configured' => !empty(config('filesystems.disks.r2.key')),
                'r2_bucket_configured' => !empty(config('filesystems.disks.r2.bucket')),
            ]);

            // Upload file and get URL
            if ($useR2) {
                // Validate R2 configuration before upload
                $r2Config = config('filesystems.disks.r2');
                if (empty($r2Config['key']) || empty($r2Config['secret']) || empty($r2Config['bucket']) || empty($r2Config['endpoint'])) {
                    Log::error('R2 configuration incomplete', [
                        'key_present' => !empty($r2Config['key']),
                        'secret_present' => !empty($r2Config['secret']),
                        'bucket_present' => !empty($r2Config['bucket']),
                        'endpoint_present' => !empty($r2Config['endpoint']),
                    ]);
                    
                    return redirect()->back()->withErrors(['error' => 'Cloud storage configuration is incomplete']);
                }

                try {
                    Log::info('Attempting R2 upload', [
                        'filename' => $filename,
                        'storage_path' => $storagePath,
                        'file_size' => $audioFile->getSize(),
                        'bucket' => $r2Config['bucket'],
                        'endpoint' => $r2Config['endpoint'],
                    ]);

                    // Upload to R2
                    $uploadSuccess = Storage::disk('r2')->putFileAs('episodes', $audioFile, $filename, 'public');
                    
                    if (!$uploadSuccess) {
                        Log::error('R2 upload returned false', [
                            'filename' => $filename,
                            'storage_path' => $storagePath,
                        ]);

                        return redirect()->back()->withErrors(['error' => 'Failed to upload audio file to cloud storage']);
                    }

                    // Generate full R2 URL
                    $fileUrl = Storage::disk('r2')->url($storagePath);
                    
                    Log::info('R2 upload successful', [
                        'filename' => $filename,
                        'file_url' => $fileUrl,
                    ]);

                } catch (\Exception $e) {
                    Log::error('Exception during R2 upload', [
                        'filename' => $filename,
                        'storage_path' => $storagePath,
                        'exception_message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    return redirect()->back()->withErrors(['error' => 'Failed to upload audio file to cloud storage: ' . $e->getMessage()]);
                }
            } else {
                // Fallback to local storage
                $audioDir = public_path('audios');
                if (! is_dir($audioDir)) {
                    mkdir($audioDir, 0755, true);
                }

                $uploadSuccess = $audioFile->move($audioDir, $filename);
                
                if (!$uploadSuccess) {
                    Log::error('Failed to upload file to local storage', [
                        'filename' => $filename,
                        'destination' => $audioDir,
                    ]);

                    return redirect()->back()->withErrors(['error' => 'Failed to upload audio file']);
                }

                // Store relative path for local files
                $fileUrl = '/audios/'.$filename;
                
                Log::info('Local upload successful', [
                    'filename' => $filename,
                    'file_url' => $fileUrl,
                ]);
            }

            // Get file info
            $fileSize = $audioFile->getSize();
            $format = $audioFile->getClientOriginalExtension();

            Log::info('File uploaded successfully', [
                'filename' => $filename,
                'use_r2' => $useR2,
                'size' => $fileSize,
                'format' => $format,
                'url' => $fileUrl,
            ]);

            // Extract duration using getID3 from the temporary uploaded file
            $duration = null;
            try {
                // Use the temporary file path to extract duration before it's moved/uploaded
                $tempFilePath = $audioFile->getRealPath();
                $getID3 = new \getID3;
                $fileInfo = $getID3->analyze($tempFilePath);
                if (isset($fileInfo['playtime_seconds'])) {
                    // Convert seconds to minutes with 2 decimal places
                    $duration = round($fileInfo['playtime_seconds'] / 60, 2);
                }
            } catch (\Exception $e) {
                // Log the error but don't fail the episode creation
                Log::warning('Failed to extract audio duration: '.$e->getMessage());
            }

            // Create episode
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

            Log::info('Episode created successfully', ['episode_id' => $episode->id]);

            // Return redirect back to episodes dashboard with success message
            return redirect()->route('episodes.dashboard')->with('success', 'Episode created successfully');

        } catch (\Exception $e) {
            Log::error('Exception during episode creation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up uploaded file if episode creation fails
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

            // Return redirect back with error message
            return redirect()->back()->withErrors(['error' => 'Error creating episode: '.$e->getMessage()]);
        }
    }

    /**
     * Show episode for editing
     */
    public function edit(Episode $episode)
    {
        return response()->json($episode);
    }

    /**
     * Update an existing episode
     */
    public function update(\App\Http\Requests\UpdateEpisodeRequest $request, Episode $episode)
    {
        try {
            $updateData = [
                'title' => $request->title,
                'description' => $request->description,
                'published_date' => $request->published_date,
            ];

            // Handle file upload if new file is provided
            if ($request->hasFile('audio_file')) {
                // Delete old file from storage
                if ($episode->url) {
                    try {
                        if ($episode->isStoredOnR2()) {
                            // Extract path from R2 URL for deletion
                            $urlParts = parse_url($episode->url);
                            $path = ltrim($urlParts['path'] ?? '', '/');
                            Storage::disk('r2')->delete($path);
                        } else {
                            // Delete local file
                            $oldFilePath = public_path(ltrim($episode->url, '/'));
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete old file: '.$e->getMessage());
                    }
                }

                // Upload new file
                $audioFile = $request->file('audio_file');
                $filename = time().'_'.$audioFile->getClientOriginalName();
                $storagePath = 'episodes/'.$filename;

                // Determine if R2 is configured (prefer R2 if available, fallback to local)
                $useR2 = config('filesystems.default') === 'r2' || 
                         (config('filesystems.disks.r2.key') && config('filesystems.disks.r2.bucket'));

                Log::info('Storage method determined for update', [
                    'use_r2' => $useR2,
                    'filesystem_default' => config('filesystems.default'),
                    'r2_key_configured' => !empty(config('filesystems.disks.r2.key')),
                    'r2_bucket_configured' => !empty(config('filesystems.disks.r2.bucket')),
                ]);

                // Upload file and get URL
                if ($useR2) {
                    // Validate R2 configuration before upload
                    $r2Config = config('filesystems.disks.r2');
                    if (empty($r2Config['key']) || empty($r2Config['secret']) || empty($r2Config['bucket']) || empty($r2Config['endpoint'])) {
                        Log::error('R2 configuration incomplete for update', [
                            'key_present' => !empty($r2Config['key']),
                            'secret_present' => !empty($r2Config['secret']),
                            'bucket_present' => !empty($r2Config['bucket']),
                            'endpoint_present' => !empty($r2Config['endpoint']),
                            'config' => array_map(function($value) {
                                return is_string($value) && strlen($value) > 10 ? substr($value, 0, 10) . '...' : $value;
                            }, $r2Config)
                        ]);
                        
                        return redirect()->back()->withErrors(['error' => 'Cloud storage configuration is incomplete']);
                    }

                    try {
                        Log::info('Attempting R2 upload for update', [
                            'filename' => $filename,
                            'storage_path' => $storagePath,
                            'file_size' => $audioFile->getSize(),
                            'file_mime' => $audioFile->getMimeType(),
                            'bucket' => $r2Config['bucket'],
                            'endpoint' => $r2Config['endpoint'],
                        ]);

                        // Upload to R2
                        $uploadSuccess = Storage::disk('r2')->putFileAs('episodes', $audioFile, $filename, 'public');
                        
                        if (!$uploadSuccess) {
                            Log::error('R2 upload returned false for update', [
                                'filename' => $filename,
                                'storage_path' => $storagePath,
                                'disk_config' => array_map(function($value) {
                                    return is_string($value) && strlen($value) > 10 ? substr($value, 0, 10) . '...' : $value;
                                }, $r2Config)
                            ]);

                            return redirect()->back()->withErrors(['error' => 'Failed to upload audio file to cloud storage']);
                        }

                        Log::info('R2 upload successful for update', [
                            'filename' => $filename,
                            'storage_path' => $storagePath,
                        ]);

                        // Generate full R2 URL
                        $fileUrl = Storage::disk('r2')->url($storagePath);
                        
                        Log::info('R2 upload successful for update', [
                            'filename' => $filename,
                            'file_url' => $fileUrl,
                        ]);

                    } catch (\Exception $e) {
                        Log::error('Exception during R2 upload for update', [
                            'filename' => $filename,
                            'storage_path' => $storagePath,
                            'exception_class' => get_class($e),
                            'exception_message' => $e->getMessage(),
                            'exception_code' => $e->getCode(),
                            'exception_file' => $e->getFile(),
                            'exception_line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        return redirect()->back()->withErrors(['error' => 'Failed to upload audio file to cloud storage: ' . $e->getMessage()]);
                    }
                } else {
                    // Fallback to local storage
                    $audioDir = public_path('audios');
                    if (! is_dir($audioDir)) {
                        mkdir($audioDir, 0755, true);
                    }

                    $uploadSuccess = $audioFile->move($audioDir, $filename);
                    
                    if (!$uploadSuccess) {
                        return redirect()->back()->withErrors(['error' => 'Failed to upload audio file']);
                    }

                    $fileUrl = '/audios/'.$filename;
                    $storagePath = 'audios/'.$filename;
                }

                // Get file info
                $fileSize = $audioFile->getSize();
                $format = $audioFile->getClientOriginalExtension();

                // Extract duration using getID3 from the temporary uploaded file
                $duration = null;
                try {
                    // Use the temporary file path to extract duration before it's moved/uploaded
                    $tempFilePath = $audioFile->getRealPath();
                    $getID3 = new \getID3;
                    $fileInfo = $getID3->analyze($tempFilePath);
                    if (isset($fileInfo['playtime_seconds'])) {
                        // Convert seconds to minutes with 2 decimal places
                        $duration = round($fileInfo['playtime_seconds'] / 60, 2);
                    }
                } catch (\Exception $e) {
                    // Log the error but don't fail the episode update
                    Log::warning('Failed to extract audio duration: '.$e->getMessage());
                }

                $updateData['filename'] = $filename;
                $updateData['url'] = $fileUrl;
                $updateData['file_size'] = $this->formatFileSize($fileSize);
                $updateData['format'] = $format;
                $updateData['duration'] = $duration;
            }

            $episode->update($updateData);

            // Return redirect back to episodes dashboard with success message
            return redirect()->route('episodes.dashboard')->with('success', 'Episode updated successfully');

        } catch (\Exception $e) {
            // Return redirect back with error message
            return redirect()->back()->withErrors(['error' => 'Error updating episode: '.$e->getMessage()]);
        }
    }

    /**
     * Delete an episode
     */
    public function destroy(Episode $episode)
    {
        try {
            // Delete audio file from storage
            if ($episode->url) {
                try {
                    if ($episode->isStoredOnR2()) {
                        // Extract filename from R2 URL for deletion
                        $urlParts = parse_url($episode->url);
                        $path = ltrim($urlParts['path'], '/');
                        Storage::disk('r2')->delete($path);
                    } else {
                        // Local file deletion
                        $filePath = public_path(ltrim($episode->url, '/'));
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete file from storage: '.$e->getMessage());
                }
            }

            // Delete episode record
            $episode->delete();

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
