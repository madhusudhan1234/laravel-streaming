<?php

namespace App\Http\Controllers;

use App\Services\EpisodeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AudioStreamController extends Controller
{
    public function __construct(private EpisodeService $episodes) {}

    public function stream(Request $request, string $filename): mixed
    {
        $filename = basename($filename);
        $episode = $this->episodes->findByFilename($filename);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        $episodeUrl = is_array($episode) ? ($episode['url'] ?? null) : $episode->url;
        $externalUrl = $this->resolveExternalUrl($episodeUrl);

        if ($externalUrl) {
            return redirect($externalUrl, 302);
        }

        $audioPath = $this->getLocalFilePath($episodeUrl);

        if (! $audioPath || ! file_exists($audioPath)) {
            abort(404, 'Audio file not found on local storage');
        }

        $fileSize = filesize($audioPath);
        $mimeType = $this->getMimeType($filename);
        $range = $request->header('Range');

        if ($range) {
            return $this->handleRangeRequest($audioPath, $fileSize, $mimeType, $range);
        }

        return $this->serveFullFile($audioPath, $fileSize, $mimeType);
    }

    public function getEpisodeStreamUrl(int $id): mixed
    {
        $episode = $this->episodes->find($id);

        if (! $episode) {
            return response()->json(['error' => 'Episode not found'], 404);
        }

        $filename = is_array($episode) ? ($episode['filename'] ?? null) : $episode->filename;

        return response()->json([
            'episode' => $episode,
            'stream_url' => url("/api/stream/{$filename}"),
            'supports_range' => true,
        ]);
    }

    private function handleRangeRequest(string $filePath, int $fileSize, string $mimeType, string $range): mixed
    {
        if (! preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            return response('Invalid range', 416);
        }

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
            return response('Range not satisfiable', 416)
                ->header('Content-Range', "bytes */{$fileSize}");
        }

        $contentLength = $end - $start + 1;

        return new StreamedResponse(function () use ($filePath, $start, $contentLength) {
            $file = fopen($filePath, 'rb');
            fseek($file, $start);

            $bytesRemaining = $contentLength;
            while ($bytesRemaining > 0 && ! feof($file)) {
                $chunk = fread($file, min(8192, $bytesRemaining));
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

    private function serveFullFile(string $filePath, int $fileSize, string $mimeType): StreamedResponse
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

    private function resolveExternalUrl(?string $episodeUrl): ?string
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

    private function getLocalFilePath(?string $episodeUrl): ?string
    {
        if (! is_string($episodeUrl) || str_starts_with($episodeUrl, 'http') || str_starts_with($episodeUrl, '/episodes/')) {
            return null;
        }

        if (str_starts_with($episodeUrl, '/audios/')) {
            return public_path(ltrim($episodeUrl, '/'));
        }

        return public_path('audios/'.basename($episodeUrl));
    }

    private function getMimeType(string $filename): string
    {
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'flac' => 'audio/flac',
        ];

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return $mimeTypes[$ext] ?? 'audio/mpeg';
    }
}
