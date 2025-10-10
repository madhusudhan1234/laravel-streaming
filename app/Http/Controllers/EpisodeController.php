<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EpisodeController extends Controller
{
    /**
     * Display the home page with all episodes
     */
    public function index()
    {
        $episodes = $this->getEpisodes();

        return Inertia::render('Home', [
            'episodes' => $episodes,
        ]);
    }

    /**
     * Get episodes data from JSON file
     */
    public function getEpisodes()
    {
        try {
            // Use database_path for version-controlled episodes data
            $episodesJson = file_get_contents(database_path('data/episodes.json'));

            if ($episodesJson === false) {
                return [];
            }

            $episodesData = json_decode($episodesJson, true);

            return $episodesData['episodes'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * API endpoint to get episodes as JSON
     */
    public function apiIndex()
    {
        $episodes = $this->getEpisodes();

        return response()->json([
            'episodes' => $episodes,
            'total' => count($episodes),
        ]);
    }

    /**
     * Get a specific episode by ID
     */
    public function show($id)
    {
        $episodes = $this->getEpisodes();
        $episode = collect($episodes)->firstWhere('id', (int) $id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        return response()->json($episode);
    }

    /**
     * Get episode for embed player
     */
    public function embed($id)
    {
        $episodes = $this->getEpisodes();
        $episode = collect($episodes)->firstWhere('id', (int) $id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        return Inertia::render('Embed', [
            'episode' => $episode,
        ]);
    }
}
