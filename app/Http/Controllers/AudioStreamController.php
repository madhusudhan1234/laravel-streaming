<?php

namespace App\Http\Controllers;

// #######################################
// Imports
// #######################################

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Episode;
use App\Repositories\EpisodeRepository;

// #######################################
// AudioStreamController
// #######################################

class AudioStreamController extends Controller
{
    // ##############################
    // Episode Lookup
    // ##############################

    /**
     * Find an episode using repository first (if not testing), then Eloquent fallback
     */
    private function findEpisode(callable $repositoryLookup, callable $eloquentLookup)
    {
        $episode = null;
        if (! app()->environment('testing')) {
            $episode = $repositoryLookup();
        }
        return $episode ?: $eloquentLookup();
    }

    /**
     * Get a property from an episode (handles both array and object)
     */
    private function getEpisodeProperty($episode, string $property)
    {
        return is_array($episode) ? ($episode[$property] ?? null) : $episode->$property;
    }

    // ##############################
    // URL and Path Resolution
    // ##############################

    /**
     * Resolve episode URL to external URL if applicable
     */
    private function resolveExternalUrl($episodeUrl): ?string
    {
        if (! is_string($episodeUrl) || $episodeUrl === '') {
            return null;
        }

        if (str_starts_with($episodeUrl, 'http')) {
            return $episodeUrl;
        }

        if (str_starts_with($episodeUrl, '/episodes/') || str_starts_with($episodeUrl, 'episodes/')) {
            $base = config('filesystems.disks.r2.url') ?? env('R2_PUBLIC_URL');
            if ($base) {
                return rtrim($base, '/').'/'.ltrim($episodeUrl, '/');
            }
        }

        return null;
    }

    /**
     * Convert episode URL to local file path
     */
    private function getLocalFilePath($episodeUrl)
    {
        // If URL starts with http/https, it's external
        if (str_starts_with($episodeUrl, 'http') || str_starts_with($episodeUrl, '/episodes/')) {
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

    // ##############################
    // HTTP Headers
    // ##############################

    /**
     * Build base headers for streaming responses
     */
    private function baseStreamHeaders(string $mimeType, int $contentLength): array
    {
        return [
            'Content-Type' => $mimeType,
            'Content-Length' => $contentLength,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => '*',
        ];
    }

    /**
     * Build headers for range (partial content) responses
     */
    private function rangeHeaders(string $mimeType, int $contentLength, int $start, int $end, int $fileSize): array
    {
        return array_merge(
            $this->baseStreamHeaders($mimeType, $contentLength),
            [
                'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
                'Access-Control-Allow-Headers' => 'Range',
            ]
        );
    }

    // ##############################
    // Streaming Infrastructure
    // ##############################

    /**
     * Create a streaming callback for file output
     */
    private function createStreamCallback(string $filePath, int $start = 0, ?int $length = null): callable
    {
        return function () use ($filePath, $start, $length) {
            $file = fopen($filePath, 'rb');
            if ($start > 0) {
                fseek($file, $start);
            }

            $chunkSize = 8192;
            $bytesRemaining = $length;

            while (! feof($file)) {
                $bytesToRead = $bytesRemaining !== null
                    ? min($chunkSize, $bytesRemaining)
                    : $chunkSize;

                if ($bytesToRead <= 0) {
                    break;
                }

                $chunk = fread($file, $bytesToRead);
                if ($chunk === false || $chunk === '') {
                    break;
                }

                echo $chunk;
                flush();

                if ($bytesRemaining !== null) {
                    $bytesRemaining -= strlen($chunk);
                }
            }

            fclose($file);
        };
    }

    // ##############################
    // HTTP Response Handlers
    // ##############################

    /**
     * Handle HTTP range requests for partial content
     */
    private function handleRangeRequest($filePath, $fileSize, $mimeType, $range)
    {
        // ####################
        // Parse Range Header
        // ####################

        if (! preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            return response('Invalid range', 416);
        }

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        // ####################
        // Validate Range
        // ####################

        if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
            return response('Range not satisfiable', 416)
                ->header('Content-Range', "bytes */{$fileSize}");
        }

        // ####################
        // Build Response
        // ####################

        $contentLength = $end - $start + 1;

        return new StreamedResponse(
            $this->createStreamCallback($filePath, $start, $contentLength),
            206,
            $this->rangeHeaders($mimeType, $contentLength, $start, $end, $fileSize)
        );
    }

    /**
     * Serve full file without range
     */
    private function serveFullFile($filePath, $fileSize, $mimeType)
    {
        return new StreamedResponse(
            $this->createStreamCallback($filePath),
            200,
            $this->baseStreamHeaders($mimeType, $fileSize)
        );
    }

    // ##############################
    // Public API Endpoints
    // ##############################

    /**
     * Stream audio file with HTTP range request support
     */
    public function stream(Request $request, $filename)
    {
        // ####################
        // Validate Input
        // ####################

        $filename = basename($filename);

        // ####################
        // Find Episode
        // ####################

        $episode = $this->findEpisode(
            fn () => EpisodeRepository::findByFilename($filename),
            fn () => Episode::where('filename', $filename)->first()
        );

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        // ####################
        // Resolve URL
        // ####################

        $episodeUrl = $this->getEpisodeProperty($episode, 'url');

        $resolvedExternalUrl = $this->resolveExternalUrl($episodeUrl);
        if ($resolvedExternalUrl) {
            return redirect($resolvedExternalUrl, 302);
        }

        // ####################
        // Handle Local File
        // ####################

        $audioPath = $this->getLocalFilePath($episodeUrl);

        if (! file_exists($audioPath)) {
            abort(404, 'Audio file not found on local storage');
        }

        // ####################
        // Stream Response
        // ####################

        $fileSize = filesize($audioPath);
        $mimeType = $this->getMimeType($filename);

        $range = $request->header('Range');

        if ($range) {
            return $this->handleRangeRequest($audioPath, $fileSize, $mimeType, $range);
        }

        return $this->serveFullFile($audioPath, $fileSize, $mimeType);
    }

    /**
     * Get episode by ID and return streaming URL
     */
    public function getEpisodeStreamUrl($id)
    {
        try {
            // ####################
            // Find Episode
            // ####################

            $episode = $this->findEpisode(
                fn () => EpisodeRepository::find((int) $id),
                fn () => Episode::find($id)
            );

            if (! $episode) {
                return response()->json(['error' => 'Episode not found'], 404);
            }

            // ####################
            // Build Response
            // ####################

            $filename = $this->getEpisodeProperty($episode, 'filename');
            $streamUrl = url("/api/stream/{$filename}");

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
