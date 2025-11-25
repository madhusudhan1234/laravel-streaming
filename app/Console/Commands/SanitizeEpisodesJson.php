<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SanitizeEpisodesJson extends Command
{
    protected $signature = 'episodes:sanitize-json';
    protected $description = 'Remove unnecessary properties (e.g., idx) from storage/app/episodes.json';

    public function handle(): int
    {
        $path = storage_path('app/episodes.json');
        if (! File::exists($path)) {
            $this->error('episodes.json not found at '.$path);
            return self::FAILURE;
        }

        $raw = File::get($path);
        $data = json_decode($raw, true);
        if ($data === null) {
            $this->error('Invalid JSON in episodes.json');
            return self::FAILURE;
        }

        $episodes = is_array($data) && isset($data['episodes']) && is_array($data['episodes'])
            ? $data['episodes']
            : (is_array($data) ? $data : []);

        $clean = [];
        $removedCount = 0;
        foreach ($episodes as $item) {
            if (is_array($item) && array_key_exists('idx', $item)) {
                unset($item['idx']);
                $removedCount++;
            }
            // Normalize episode URL: if http with "/episodes/" path, strip domain to keep relative path
            if (is_array($item) && isset($item['url']) && is_string($item['url'])) {
                $url = $item['url'];
                if (str_starts_with($url, 'http') && str_contains($url, '/episodes/')) {
                    $parts = parse_url($url);
                    if (! empty($parts['path'])) {
                        $item['url'] = (str_starts_with($parts['path'], '/')) ? $parts['path'] : '/'.$parts['path'];
                    }
                }
            }
            $clean[] = $item;
        }

        $json = json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        File::put($path, $json);

        $this->info('Sanitized episodes.json; idx removed from '.$removedCount.' entries.');
        return self::SUCCESS;
    }
}
