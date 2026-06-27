<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class EpisodeCacheService
{
    private const ALL_KEY = 'episodes:all';

    private const PREFIX = 'episode:';

    public function getAll(): array
    {
        $raw = Redis::get(self::ALL_KEY);

        if (! is_string($raw)) {
            return [];
        }

        return json_decode($raw, true) ?? [];
    }

    public function setAll(array $episodes): void
    {
        Redis::set(self::ALL_KEY, json_encode($episodes));
    }

    public function get(int $id): ?array
    {
        $raw = Redis::get(self::PREFIX.$id);

        if (! is_string($raw)) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function put(array $episode): void
    {
        Redis::set(self::PREFIX.intval($episode['id']), json_encode($episode));
    }

    public function forget(int $id): void
    {
        Redis::del(self::PREFIX.$id);
    }

    /**
     * Clear all episode keys from Redis (episodes:all + every episode:{id}).
     */
    public function flush(): void
    {
        foreach (Redis::keys(self::PREFIX.'*') as $key) {
            Redis::del($key);
        }

        Redis::del(self::ALL_KEY);
    }

    /**
     * Replace the entire episode cache: flush, sort by id, then write all keys.
     */
    public function writeAll(array $episodes): void
    {
        $this->flush();

        if (empty($episodes)) {
            $this->setAll([]);

            return;
        }

        usort($episodes, fn ($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));

        $this->setAll($episodes);

        foreach ($episodes as $ep) {
            $this->put($ep);
        }
    }

    public function maxId(): int
    {
        $max = 0;
        foreach ($this->getAll() as $ep) {
            $max = max($max, (int) ($ep['id'] ?? 0));
        }

        return $max;
    }
}
