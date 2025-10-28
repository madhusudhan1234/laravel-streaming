<?php

namespace Database\Seeders;

use App\Models\Episode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class EpisodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Read the episodes.json file
        $jsonPath = database_path('data/episodes.json');
        
        if (!File::exists($jsonPath)) {
            $this->command->error('Episodes JSON file not found at: ' . $jsonPath);
            return;
        }

        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        if (!$data || !isset($data['episodes'])) {
            $this->command->error('Invalid JSON structure in episodes.json');
            return;
        }

        // Clear existing episodes
        Episode::truncate();

        // Insert episodes from JSON
        foreach ($data['episodes'] as $episodeData) {
            Episode::create([
                'id' => $episodeData['id'],
                'title' => $episodeData['title'],
                'filename' => $episodeData['filename'],
                'url' => $episodeData['url'],
                'duration' => $episodeData['duration'],
                'file_size' => $episodeData['file_size'],
                'format' => $episodeData['format'],
                'published_date' => $episodeData['published_date'],
                'description' => $episodeData['description'],
            ]);
        }

        $this->command->info('Successfully seeded ' . count($data['episodes']) . ' episodes.');
    }
}
