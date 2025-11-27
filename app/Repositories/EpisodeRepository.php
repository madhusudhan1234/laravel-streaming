<?php

namespace App\Repositories;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;

class EpisodeRepository
{
    private static function path(): string
    {
        return public_path('episodes.json');
    }

    private static function dir(): string
    {
        return public_path('episodes');
    }

    private static function load(): array
    {
        $dir = self::dir();
        if (File::isDirectory($dir)) {
            $episodes = [];
            foreach (File::glob($dir.'/*.json') as $file) {
                $json = File::get($file);
                $ep = json_decode($json, true);
                if (is_array($ep)) {
                    $episodes[] = $ep;
                }
            }
            if (! empty($episodes)) {
                return $episodes;
            }
        }

        $path = self::path();
        if (File::exists($path)) {
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
        return [];
    }

    public static function all(): array
    {
        $redisAll = Redis::get('episodes:all');
        if (is_string($redisAll)) {
            $decoded = json_decode($redisAll, true);
            if (is_array($decoded) && ! empty($decoded)) {
                $episodes = $decoded;
                usort($episodes, function ($a, $b) {
                    return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
                });
                return $episodes;
            }
        }
        $episodes = self::load();
        usort($episodes, function ($a, $b) {
            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        });
        return $episodes;
    }

    public static function find(int $id): ?array
    {
        $redisKey = 'episode:'.intval($id);
        $redisVal = Redis::get($redisKey);
        if (is_string($redisVal)) {
            $decoded = json_decode($redisVal, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        $dir = self::dir();
        if (File::isDirectory($dir)) {
            $file = $dir.'/'.intval($id).'.json';
            if (File::exists($file)) {
                $json = File::get($file);
                $ep = json_decode($json, true);
                if (is_array($ep)) {
                    Redis::set($redisKey, json_encode($ep));
                    return $ep;
                }
            }
        }
        foreach (self::load() as $episode) {
            if (($episode['id'] ?? null) === $id) {
                Redis::set($redisKey, json_encode($episode));
                return $episode;
            }
        }
        return null;
    }

    public static function findByFilename(string $filename): ?array
    {
        $base = basename($filename);
        $redisAll = Redis::get('episodes:all');
        if (is_string($redisAll)) {
            $decoded = json_decode($redisAll, true);
            if (is_array($decoded)) {
                foreach ($decoded as $ep) {
                    if (basename($ep['filename'] ?? '') === $base) {
                        return $ep;
                    }
                }
            }
        }
        $dir = self::dir();
        if (File::isDirectory($dir)) {
            foreach (File::glob($dir.'/*.json') as $file) {
                $json = File::get($file);
                $ep = json_decode($json, true);
                if (is_array($ep) && basename($ep['filename'] ?? '') === $base) {
                    Redis::set('episode:'.intval($ep['id'] ?? 0), json_encode($ep));
                    return $ep;
                }
            }
            return null;
        }
        foreach (self::load() as $episode) {
            if (basename($episode['filename'] ?? '') === $base) {
                Redis::set('episode:'.intval($episode['id'] ?? 0), json_encode($episode));
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
            if (! is_array($ep)) continue;
            $id = intval($ep['id'] ?? 0);
            if ($id <= 0) continue;
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
        $episodes = self::load();
        $maxId = 0;
        foreach ($episodes as $e) {
            $maxId = max($maxId, (int) ($e['id'] ?? 0));
        }
        $episode['id'] = $episode['id'] ?? ($maxId + 1);
        $ok = File::put($dir.'/'.intval($episode['id']).'.json', json_encode($episode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($ok !== false) {
            Redis::set('episode:'.intval($episode['id']), json_encode($episode));
            $all = self::all();
            Redis::set('episodes:all', json_encode($all));
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
        $file = self::dir().'/'.intval($id).'.json';
        if (! File::isDirectory(self::dir())) {
            File::makeDirectory(self::dir(), 0755, true);
        }
        $ok = File::put($file, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($ok !== false) {
            Redis::set('episode:'.intval($id), json_encode($merged));
            $all = self::all();
            Redis::set('episodes:all', json_encode($all));
            return $merged;
        }
        return null;
    }

    public static function delete(int $id): bool
    {
        $file = self::dir().'/'.intval($id).'.json';
        $deleted = File::exists($file) ? unlink($file) : false;
        if ($deleted) {
            Redis::del('episode:'.intval($id));
            $all = self::all();
            Redis::set('episodes:all', json_encode($all));
        }
        return $deleted;
    }
}
