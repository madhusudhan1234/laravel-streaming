<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AudioUploadService
{
    /**
     * Upload an audio file to R2 or local storage.
     *
     * Returns ['url' => string, 'disk' => 'r2'|'local'].
     */
    public function upload(UploadedFile $file, string $filename): array
    {
        if ($this->shouldUseR2()) {
            return $this->uploadToR2($file, $filename);
        }

        return $this->uploadToLocal($file, $filename);
    }

    public function delete(string $url): void
    {
        if ($url === '') {
            return;
        }

        try {
            if (str_starts_with($url, 'http') || str_starts_with($url, '/episodes/') || str_starts_with($url, 'episodes/')) {
                $path = str_starts_with($url, 'http')
                    ? ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/')
                    : ltrim($url, '/');
                Storage::disk('r2')->delete($path);
            } else {
                $localPath = public_path(ltrim($url, '/'));
                if (file_exists($localPath)) {
                    unlink($localPath);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete audio file: '.$e->getMessage(), ['url' => $url]);
        }
    }

    private function shouldUseR2(): bool
    {
        return config('filesystems.default') === 'r2'
            || (config('filesystems.disks.r2.key') && config('filesystems.disks.r2.bucket'));
    }

    private function uploadToR2(UploadedFile $file, string $filename): array
    {
        $r2Config = config('filesystems.disks.r2');

        if (empty($r2Config['key']) || empty($r2Config['secret']) || empty($r2Config['bucket']) || empty($r2Config['endpoint'])) {
            throw new \RuntimeException('Cloud storage configuration is incomplete');
        }

        $success = Storage::disk('r2')->putFileAs('episodes', $file, $filename, 'public');

        if (! $success) {
            throw new \RuntimeException('Failed to upload audio file to cloud storage');
        }

        return ['url' => '/episodes/'.$filename, 'disk' => 'r2'];
    }

    private function uploadToLocal(UploadedFile $file, string $filename): array
    {
        $audioDir = public_path('audios');

        if (! is_dir($audioDir)) {
            mkdir($audioDir, 0755, true);
        }

        if (! $file->move($audioDir, $filename)) {
            throw new \RuntimeException('Failed to upload audio file');
        }

        return ['url' => '/audios/'.$filename, 'disk' => 'local'];
    }
}
