<?php

namespace App\Http\Controllers;

use App\Models\Episode;

class EmbedController extends Controller
{
    /**
     * Show embed player for a specific episode
     */
    public function show($id)
    {
        $episode = $this->getEpisode($id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        return view('embed', [
            'episode' => $episode,
        ]);
    }

    /**
     * Generate embed code for an episode
     */
    public function generateEmbedCode($id)
    {
        $episode = $this->getEpisode($id);

        if (! $episode) {
            return response()->json(['error' => 'Episode not found'], 404);
        }

        $embedUrl = url("/embed/{$id}");
        $embedCode = $this->buildEmbedCode($embedUrl, $episode);

        return response()->json([
            'embedCode' => $embedCode,
            'embedUrl' => $embedUrl,
            'episode' => $episode,
        ]);
    }

    /**
     * Get episode data from database
     */
    private function getEpisode($id)
    {
        try {
            return Episode::find($id);
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
            '<iframe src="%s" width="100%%" height="120" frameborder="0" title="%s" allow="autoplay"></iframe>',
            $embedUrl,
            $title
        );
    }
}
