<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateEpisodesToFiles extends Command
{
    protected $signature = 'episodes:migrate-to-files';
    protected $description = 'Split storage/app/episodes.json into storage/app/episodes/{id}.json files';

    public function handle(): int
    {
        $jsonPath = storage_path('app/episodes.json');
        $dir = storage_path('app/episodes');

        if (! File::exists($jsonPath)) {
            $this->info('episodes.json not found, nothing to migrate.');
            return self::SUCCESS;
        }

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $raw = File::get($jsonPath);
        $data = json_decode($raw, true);
        if ($data === null) {
            $this->error('Invalid JSON in episodes.json');
            return self::FAILURE;
        }

        $episodes = is_array($data) && isset($data['episodes']) && is_array($data['episodes'])
            ? $data['episodes']
            : (is_array($data) ? $data : []);

        $count = 0;
        foreach ($episodes as $ep) {
            if (! is_array($ep)) continue;
            $id = intval($ep['id'] ?? 0);
            if ($id <= 0) continue;
            File::put($dir.'/'.$id.'.json', json_encode($ep, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $count++;
        }

        $this->info("Migrated {$count} episodes to files in storage/app/episodes.");
        return self::SUCCESS;
    }
}

