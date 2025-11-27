<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MoveEpisodesToPublic extends Command
{
    protected $signature = 'episodes:move-to-public';
    protected $description = 'Move per-episode JSON files from storage/app/episodes to public/episodes';

    public function handle(): int
    {
        $src = storage_path('app/episodes');
        $dst = public_path('episodes');

        if (! File::isDirectory($src)) {
            $this->info('Source folder not found: '.$src);
            return self::SUCCESS;
        }

        if (! File::isDirectory($dst)) {
            File::makeDirectory($dst, 0755, true);
        }

        $moved = 0;
        foreach (File::glob($src.'/*.json') as $file) {
            $basename = basename($file);
            File::copy($file, $dst.'/'.$basename);
            $moved++;
        }

        $this->info("Moved {$moved} files to public/episodes");

        try {
            File::deleteDirectory($src);
            $this->info('Removed storage episodes directory');
        } catch (\Exception $e) {
            $this->warn('Could not remove storage episodes directory: '.$e->getMessage());
        }

        return self::SUCCESS;
    }
}

