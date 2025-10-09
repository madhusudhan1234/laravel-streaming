<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EmbedController extends Controller
{
    /**
     * Show embed player for a specific episode
     */
    public function show($id)
    {
        $episode = $this->getEpisode($id);
        
        if (!$episode) {
            abort(404, 'Episode not found');
        }
        
        return view('embed', [
            'episode' => $episode
        ]);
    }

    /**
     * Generate embed code for an episode
     */
    public function generateEmbedCode($id)
    {
        $episode = $this->getEpisode($id);
        
        if (!$episode) {
            return response()->json(['error' => 'Episode not found'], 404);
        }
        
        $embedUrl = url("/embed/{$id}");
        $embedCode = $this->buildEmbedCode($embedUrl, $episode);
        
        return response()->json([
            'embedCode' => $embedCode,
            'embedUrl' => $embedUrl,
            'episode' => $episode
        ]);
    }

    /**
     * Get episode data from JSON file
     */
    private function getEpisode($id)
    {
        try {
            $episodesPath = storage_path('app/episodes.json');
            
            if (!file_exists($episodesPath)) {
                return null;
            }
            
            $episodesJson = file_get_contents($episodesPath);
            $episodesData = json_decode($episodesJson, true);
            $episodes = $episodesData['episodes'] ?? [];
            
            return collect($episodes)->firstWhere('id', (int)$id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Build HTML embed code
     */
    private function buildEmbedCode($embedUrl, $episode)
    {
        $title = htmlspecialchars($episode['title']);
        
        return sprintf(
            '<iframe src="%s" width="100%" height="120" frameborder="0" title="%s" allow="autoplay"></iframe>',
            $embedUrl,
            $title
        );
    }
}