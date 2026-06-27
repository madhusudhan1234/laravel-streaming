<?php

namespace App\Repositories;

use App\Services\EpisodeCacheService;
use Illuminate\Support\Facades\File;

class EpisodeRepository
{
    private static function cache(): EpisodeCacheService
    {
        return app(EpisodeCacheService::class);
    }

    private static function dir(): string
    {
        return public_path('episodes');
    }

    private static function loadFromFiles(): array
    {
        $dir = self::dir();

        if (! File::isDirectory($dir)) {
            return [];
        }

        $episodes = [];
        foreach (File::glob($dir.'/*.json') as $file) {
            $ep = json_decode(File::get($file), true);
            if (is_array($ep)) {
                $episodes[] = $ep;
            }
        }

        return $episodes;
    }

    public static function all(): array
    {
        $cached = self::cache()->getAll();
        if (! empty($cached)) {
            usort($cached, fn ($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));

            return $cached;
        }

        $episodes = self::loadFromFiles();
        usort($episodes, fn ($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));

        return $episodes;
    }

    public static function find(int $id): ?array
    {
        $cached = self::cache()->get($id);
        if ($cached) {
            return $cached;
        }

        $file = self::dir().'/'.intval($id).'.json';
        if (File::exists($file)) {
            $ep = json_decode(File::get($file), true);
            if (is_array($ep)) {
                self::cache()->put($ep);

                return $ep;
            }
        }

        foreach (self::loadFromFiles() as $episode) {
            if (($episode['id'] ?? null) === $id) {
                self::cache()->put($episode);

                return $episode;
            }
        }

        return null;
    }

    public static function findByFilename(string $filename): ?array
    {
        $base = basename($filename);

        foreach (self::cache()->getAll() as $ep) {
            if (basename($ep['filename'] ?? '') === $base) {
                return $ep;
            }
        }

        $dir = self::dir();
        if (File::isDirectory($dir)) {
            foreach (File::glob($dir.'/*.json') as $file) {
                $ep = json_decode(File::get($file), true);
                if (is_array($ep) && basename($ep['filename'] ?? '') === $base) {
                    self::cache()->put($ep);

                    return $ep;
                }
            }

            return null;
        }

        foreach (self::loadFromFiles() as $episode) {
            if (basename($episode['filename'] ?? '') === $base) {
                self::cache()->put($episode);

                return $episode;
            }
        }

        return null;
    }

    public static function saveAll(array $episodes): bool
    {
        $dir = self::dir();
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        foreach ($episodes as $ep) {
            if (! is_array($ep)) {
                continue;
            }
            $id = intval($ep['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            File::put($dir.'/'.$id.'.json', json_encode($ep, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return true;
    }

    public static function add(array $episode): ?array
    {
        $dir = self::dir();
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $episodes = self::loadFromFiles();
        $maxId = 0;
        foreach ($episodes as $e) {
            $maxId = max($maxId, (int) ($e['id'] ?? 0));
        }

        $episode['id'] = $episode['id'] ?? ($maxId + 1);
        $ok = File::put(
            $dir.'/'.intval($episode['id']).'.json',
            json_encode($episode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($ok !== false) {
            self::cache()->put($episode);
            self::cache()->setAll(self::all());

            return $episode;
        }

        return null;
    }

    public static function update(int $id, array $updates): ?array
    {
        $current = self::find($id);
        if (! $current) {
            return null;
        }

        $merged = array_merge($current, $updates);
        $dir = self::dir();

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $ok = File::put(
            $dir.'/'.intval($id).'.json',
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($ok !== false) {
            self::cache()->put($merged);
            self::cache()->setAll(self::all());

            return $merged;
        }

        return null;
    }

    public static function delete(int $id): bool
    {
        $file = self::dir().'/'.intval($id).'.json';
        $deleted = File::exists($file) ? unlink($file) : false;

        if ($deleted) {
            self::cache()->forget($id);
            self::cache()->setAll(self::all());
        }

        return $deleted;
    }
}
