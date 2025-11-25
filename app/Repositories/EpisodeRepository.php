<?php

namespace App\Repositories;

use Illuminate\Support\Facades\File;

class EpisodeRepository
{
    private static function path(): string
    {
        return storage_path('app/episodes.json');
    }

    private static function load(): array
    {
        $path = self::path();
        if (! File::exists($path)) {
            return [];
        }
        $json = File::get($path);
        $data = json_decode($json, true);
        if (! is_array($data)) {
            return [];
        }
        if (isset($data['episodes']) && is_array($data['episodes'])) {
            return $data['episodes'];
        }
        return $data;
    }

    public static function all(): array
    {
        $episodes = self::load();
        usort($episodes, function ($a, $b) {
            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        });
        return $episodes;
    }

    public static function find(int $id): ?array
    {
        foreach (self::load() as $episode) {
            if (($episode['id'] ?? null) === $id) {
                return $episode;
            }
        }
        return null;
    }

    public static function findByFilename(string $filename): ?array
    {
        $base = basename($filename);
        foreach (self::load() as $episode) {
            if (basename($episode['filename'] ?? '') === $base) {
                return $episode;
            }
        }
        return null;
    }

    public static function saveAll(array $episodes): bool
    {
        $payload = $episodes; // store as flat array for simplicity
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return File::put(self::path(), $json) !== false;
    }

    public static function add(array $episode): ?array
    {
        $episodes = self::load();
        $maxId = 0;
        foreach ($episodes as $e) {
            $maxId = max($maxId, (int) ($e['id'] ?? 0));
        }
        $episode['id'] = $episode['id'] ?? ($maxId + 1);
        $episodes[] = $episode;
        return self::saveAll($episodes) ? $episode : null;
    }

    public static function update(int $id, array $updates): ?array
    {
        $episodes = self::load();
        $updated = null;
        foreach ($episodes as $i => $e) {
            if ((int) ($e['id'] ?? 0) === $id) {
                $episodes[$i] = array_merge($e, $updates);
                $updated = $episodes[$i];
                break;
            }
        }
        return $updated && self::saveAll($episodes) ? $updated : null;
    }

    public static function delete(int $id): bool
    {
        $episodes = self::load();
        $new = [];
        $deleted = false;
        foreach ($episodes as $e) {
            if ((int) ($e['id'] ?? 0) === $id) {
                $deleted = true;
                continue;
            }
            $new[] = $e;
        }
        return $deleted ? self::saveAll($new) : false;
    }
}
