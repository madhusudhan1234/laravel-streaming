<?php

namespace App\Console\Commands;

use App\Models\Episode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MigrateEpisodesToR2 extends Command
{
    protected $signature = 'episodes:migrate-to-r2 {--dry-run : Show what would be migrated without actually doing it} {--force : Skip confirmation prompt}';

    protected $description = 'Migrate existing episode audio files from local storage to Cloudflare R2';

    public function handle()
    {
        $this->info('Starting episode migration to Cloudflare R2...');

        // Check if R2 is configured
        if (!config('filesystems.disks.r2.key') || !config('filesystems.disks.r2.bucket')) {
            $this->error('Cloudflare R2 is not properly configured. Please check your environment variables.');
            return 1;
        }

        // Get episodes that need migration (local URLs or relative paths)
        $episodes = Episode::where(function ($query) {
            $query->where('url', 'like', '/audios/%')
                  ->orWhere('url', 'not like', 'http%');
        })->get();

        if ($episodes->isEmpty()) {
            $this->info('No episodes need migration. All episodes are already on R2.');
            return 0;
        }

        $this->info("Found {$episodes->count()} episodes to migrate.");

        if ($this->option('dry-run')) {
            $this->info('DRY RUN - No files will be actually migrated:');
            foreach ($episodes as $episode) {
                $localPath = public_path('audios/' . $episode->filename);
                $r2Path = 'episodes/' . $episode->filename;
                $exists = file_exists($localPath) ? '✓' : '✗';
                $this->line("  {$exists} Episode #{$episode->id}: {$episode->title}");
                $this->line("    Local: {$localPath}");
                $this->line("    R2: {$r2Path}");
            }
            return 0;
        }

        // Confirm migration unless --force is used
        if (!$this->option('force')) {
            if (!$this->confirm("This will migrate {$episodes->count()} episodes to R2. Continue?")) {
                $this->info('Migration cancelled.');
                return 0;
            }
        }

        $migrated = 0;
        $failed = 0;

        foreach ($episodes as $episode) {
            $this->info("Migrating Episode #{$episode->id}: {$episode->title}");

            try {
                $localPath = public_path('audios/' . $episode->filename);
                
                // Check if local file exists
                if (!file_exists($localPath)) {
                    $this->warn("  Local file not found: {$localPath}");
                    $failed++;
                    continue;
                }

                // Generate R2 storage path
                $r2Path = 'episodes/' . $episode->filename;

                // Upload to R2
                $fileContents = file_get_contents($localPath);
                $uploadSuccess = Storage::disk('r2')->put($r2Path, $fileContents, 'public');

                if (!$uploadSuccess) {
                    $this->error("  Failed to upload to R2: {$r2Path}");
                    $failed++;
                    continue;
                }

                // Update episode record with full R2 URL
                $r2Url = Storage::disk('r2')->url($r2Path);
                $episode->update([
                    'url' => $r2Url,
                ]);

                $this->info("  ✓ Successfully migrated to R2");
                $migrated++;

                // Optionally delete local file after successful migration
                if ($this->confirm("Delete local file after successful migration?", false)) {
                    unlink($localPath);
                    $this->info("  ✓ Local file deleted");
                }

            } catch (\Exception $e) {
                $this->error("  Failed to migrate: " . $e->getMessage());
                Log::error('Episode migration failed', [
                    'episode_id' => $episode->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->info("\nMigration completed:");
        $this->info("  Migrated: {$migrated}");
        if ($failed > 0) {
            $this->warn("  Failed: {$failed}");
        }

        return $failed > 0 ? 1 : 0;
    }
}
