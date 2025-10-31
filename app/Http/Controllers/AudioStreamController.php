<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AudioStreamController extends Controller
{
    /**
     * Stream audio file with HTTP range request support
     */
    public function stream(Request $request, $filename)
    {
        // Validate filename to prevent directory traversal
        $filename = basename($filename);

        // Find episode by filename in database
        $episode = \App\Models\Episode::where('filename', $filename)->first();

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        // Get the episode URL (could be local or external)
        $episodeUrl = $episode->url;

        // Handle external URLs (like Cloudflare R2)
        if ($episode->isStoredOnR2()) {
            // For external URLs, redirect to the actual URL
            // This allows the CDN/external service to handle range requests
            return redirect($episodeUrl, 302);
        }

        // Handle local files
        $audioPath = $this->getLocalFilePath($episodeUrl);

        // Check if local file exists
        if (! file_exists($audioPath)) {
            abort(404, 'Audio file not found on local storage');
        }

        // Get file info
        $fileSize = filesize($audioPath);
        $mimeType = $this->getMimeType($filename);

        // Handle range requests for streaming
        $range = $request->header('Range');

        if ($range) {
            return $this->handleRangeRequest($audioPath, $fileSize, $mimeType, $range);
        }

        // Return full file if no range requested
        return $this->serveFullFile($audioPath, $fileSize, $mimeType);
    }

    /**
     * Handle HTTP range requests for partial content
     */
    private function handleRangeRequest($filePath, $fileSize, $mimeType, $range)
    {
        // Parse range header (e.g., "bytes=0-1023" or "bytes=1024-")
        if (! preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            return response('Invalid range', 416);
        }

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        // Validate range
        if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
            return response('Range not satisfiable', 416)
                ->header('Content-Range', "bytes */{$fileSize}");
        }

        $contentLength = $end - $start + 1;

        return new StreamedResponse(function () use ($filePath, $start, $contentLength) {
            $file = fopen($filePath, 'rb');
            fseek($file, $start);

            $chunkSize = 8192; // 8KB chunks
            $bytesRemaining = $contentLength;

            while ($bytesRemaining > 0 && ! feof($file)) {
                $bytesToRead = min($chunkSize, $bytesRemaining);
                $chunk = fread($file, $bytesToRead);

                if ($chunk === false) {
                    break;
                }

                echo $chunk;
                flush();

                $bytesRemaining -= strlen($chunk);
            }

            fclose($file);
        }, 206, [
            'Content-Type' => $mimeType,
            'Content-Length' => $contentLength,
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Range',
        ]);
    }

    /**
     * Serve full file without range
     */
    private function serveFullFile($filePath, $fileSize, $mimeType)
    {
        return new StreamedResponse(function () use ($filePath) {
            $file = fopen($filePath, 'rb');

            while (! feof($file)) {
                echo fread($file, 8192);
                flush();
            }

            fclose($file);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Get MIME type based on file extension
     */
    private function getMimeType($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'flac' => 'audio/flac',
        ];

        return $mimeTypes[$extension] ?? 'audio/mpeg';
    }

    /**
     * Convert episode URL to local file path
     */
    private function getLocalFilePath($episodeUrl)
    {
        // If URL starts with http/https, it's external
        if (str_starts_with($episodeUrl, 'http')) {
            return null;
        }

        // Handle relative URLs like "/audios/filename.mp3"
        if (str_starts_with($episodeUrl, '/audios/')) {
            return public_path(ltrim($episodeUrl, '/'));
        }

        // Handle direct filenames
        return public_path('audios/'.basename($episodeUrl));
    }

    /**
     * Get episode by ID and return streaming URL
     */
    public function getEpisodeStreamUrl($id)
    {
        try {
            $episode = \App\Models\Episode::find($id);

            if (! $episode) {
                return response()->json(['error' => 'Episode not found'], 404);
            }

            // Generate streaming URL
            $streamUrl = url("/api/stream/{$episode->filename}");

            return response()->json([
                'episode' => $episode,
                'stream_url' => $streamUrl,
                'supports_range' => true,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get episode'], 500);
        }
    }
}
