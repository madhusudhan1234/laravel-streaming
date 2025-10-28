<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EpisodeController extends Controller
{
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
    public function store(\App\Http\Requests\StoreEpisodeRequest $request)
    {
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

            // Check directory permissions
            $audioDir = public_path('audios');
            if (! is_dir($audioDir)) {
                Log::error('Audio directory does not exist: '.$audioDir);

                return redirect()->back()->withErrors(['error' => 'Audio directory does not exist']);
            }

            if (! is_writable($audioDir)) {
                Log::error('Audio directory is not writable: '.$audioDir);

                return redirect()->back()->withErrors(['error' => 'Audio directory is not writable']);
            }

            // Generate filename and attempt upload
            $filename = time().'_'.$audioFile->getClientOriginalName();
            $destinationPath = $audioDir;

            Log::info('Attempting to upload file', [
                'original_name' => $audioFile->getClientOriginalName(),
                'filename' => $filename,
                'destination' => $destinationPath,
                'file_size' => $audioFile->getSize(),
                'mime_type' => $audioFile->getMimeType(),
            ]);

            // Try to move the file
            $uploadSuccess = $audioFile->move($destinationPath, $filename);

            if (! $uploadSuccess) {
                Log::error('Failed to move uploaded file to destination', [
                    'filename' => $filename,
                    'destination' => $destinationPath,
                ]);

                return redirect()->back()->withErrors(['error' => 'The audio file failed to upload. Please store inside the public/audios folder']);
            }

            // Verify file was actually uploaded
            $filePath = public_path('audios/'.$filename);
            if (! file_exists($filePath)) {
                Log::error('File does not exist after upload', ['path' => $filePath]);

                return redirect()->back()->withErrors(['error' => 'File upload verification failed']);
            }

            // Get file info
            $fileSize = filesize($filePath);
            $format = $audioFile->getClientOriginalExtension();

            Log::info('File uploaded successfully', [
                'filename' => $filename,
                'path' => $filePath,
                'size' => $fileSize,
                'format' => $format,
            ]);

            // Extract duration using getID3
            $duration = null;
            try {
                $getID3 = new \getID3;
                $fileInfo = $getID3->analyze($filePath);
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
                'url' => '/audios/'.$filename,
                'file_size' => $fileSize,
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
            if (isset($filename) && file_exists(public_path('audios/'.$filename))) {
                unlink(public_path('audios/'.$filename));
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
                // Delete old file
                $oldFilePath = public_path('audios/'.$episode->filename);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }

                // Upload new file
                $audioFile = $request->file('audio_file');
                $filename = time().'_'.$audioFile->getClientOriginalName();
                $audioFile->move(public_path('audios'), $filename);

                // Get file info
                $filePath = public_path('audios/'.$filename);
                $fileSize = filesize($filePath);
                $format = $audioFile->getClientOriginalExtension();

                // Extract duration using getID3
                $duration = null;
                try {
                    $getID3 = new \getID3;
                    $fileInfo = $getID3->analyze($filePath);
                    if (isset($fileInfo['playtime_seconds'])) {
                        // Convert seconds to minutes with 2 decimal places
                        $duration = round($fileInfo['playtime_seconds'] / 60, 2);
                    }
                } catch (\Exception $e) {
                    // Log the error but don't fail the episode update
                    Log::warning('Failed to extract audio duration: '.$e->getMessage());
                }

                $updateData['filename'] = $filename;
                $updateData['url'] = '/audios/'.$filename;
                $updateData['file_size'] = $fileSize;
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
            // Delete audio file
            $filePath = public_path('audios/'.$episode->filename);
            if (file_exists($filePath)) {
                unlink($filePath);
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
