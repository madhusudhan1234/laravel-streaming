<?php

namespace App\Http\Controllers;

// #######################################
// Imports
// #######################################

use App\Models\Episode;
use App\Repositories\EpisodeRepository;

// #######################################
// EmbedController
// #######################################

class EmbedController extends Controller
{
    // ##############################
    // Episode Retrieval
    // ##############################

    /**
     * Get episode data from database
     */
    private function getEpisode($id)
    {
        // ####################
        // Try Repository First (Non-Testing)
        // ####################

        if (! app()->environment('testing')) {
            $e = EpisodeRepository::find((int) $id);
            if ($e) {
                return $e;
            }
        }

        // ####################
        // Fallback to Eloquent Model
        // ####################

        try {
            return Episode::find($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Execute callback with episode, or return 404 response
     */
    private function withEpisodeOr404($id, callable $onFound)
    {
        $episode = $this->getEpisode($id);

        if (! $episode) {
            return request()->expectsJson()
                ? response()->json(['error' => 'Episode not found'], 404)
                : abort(404, 'Episode not found');
        }

        return $onFound($episode);
    }

    // ##############################
    // Embed Code Generation
    // ##############################

    /**
     * Build HTML embed code
     */
    private function buildEmbedCode($embedUrl, $episode)
    {
        $titleVal = is_array($episode) ? ($episode['title'] ?? '') : ($episode->title ?? '');
        $title = htmlspecialchars($titleVal);

        return sprintf(
            '<iframe src="%s" width="100%%" height="120" frameborder="0" title="%s" allow="autoplay"></iframe>',
            $embedUrl,
            $title
        );
    }

    // ##############################
    // Public Endpoints
    // ##############################

    /**
     * Show embed player for a specific episode
     */
    public function show($id)
    {
        return $this->withEpisodeOr404($id, fn($episode) =>
            view('embed', ['episode' => $episode])
        );
    }

    /**
     * Generate embed code for an episode
     */
    public function generateEmbedCode($id)
    {
        return $this->withEpisodeOr404($id, function($episode) use ($id) {
            $embedUrl = url("/embed/{$id}");
            $embedCode = $this->buildEmbedCode($embedUrl, $episode);

            return response()->json([
                'embedCode' => $embedCode,
                'embedUrl' => $embedUrl,
                'episode' => $episode,
            ]);
        });
    }
}
