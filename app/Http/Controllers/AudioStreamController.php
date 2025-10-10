<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
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
        $audioPath = public_path('audios/' . $filename);
        
        // Check if file exists
        if (!file_exists($audioPath)) {
            abort(404, 'Audio file not found');
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
        if (!preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
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
        
        return new StreamedResponse(function() use ($filePath, $start, $contentLength) {
            $file = fopen($filePath, 'rb');
            fseek($file, $start);
            
            $chunkSize = 8192; // 8KB chunks
            $bytesRemaining = $contentLength;
            
            while ($bytesRemaining > 0 && !feof($file)) {
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
        return new StreamedResponse(function() use ($filePath) {
            $file = fopen($filePath, 'rb');
            
            while (!feof($file)) {
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
     * Get episode by ID and return streaming URL
     */
    public function getEpisodeStreamUrl($id)
    {
        try {
            $episodesPath = storage_path('app/episodes.json');
            
            if (!file_exists($episodesPath)) {
                return response()->json(['error' => 'Episodes data not found'], 404);
            }
            
            $episodesJson = file_get_contents($episodesPath);
            $episodesData = json_decode($episodesJson, true);
            $episodes = $episodesData['episodes'] ?? [];
            
            $episode = collect($episodes)->firstWhere('id', (int)$id);
            
            if (!$episode) {
                return response()->json(['error' => 'Episode not found'], 404);
            }
            
            // Generate streaming URL
            $streamUrl = url("/api/stream/{$episode['filename']}");
            
            return response()->json([
                'episode' => $episode,
                'stream_url' => $streamUrl,
                'supports_range' => true
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get episode'], 500);
        }
    }
}