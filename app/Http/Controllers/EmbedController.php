<?php

namespace App\Http\Controllers;

use App\Services\EpisodeService;

class EmbedController extends Controller
{
    public function __construct(private EpisodeService $episodes) {}

    public function show(int $id): mixed
    {
        $episode = $this->episodes->find($id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        return view('embed', ['episode' => $episode]);
    }

    public function generateEmbedCode(int $id): mixed
    {
        $episode = $this->episodes->find($id);

        if (! $episode) {
            return response()->json(['error' => 'Episode not found'], 404);
        }

        $embedUrl = url("/embed/{$id}");

        return response()->json([
            'embedCode' => $this->buildEmbedCode($embedUrl, $episode),
            'embedUrl' => $embedUrl,
            'episode' => $episode,
        ]);
    }

    private function buildEmbedCode(string $embedUrl, mixed $episode): string
    {
        $titleVal = is_array($episode) ? ($episode['title'] ?? '') : ($episode->title ?? '');

        return sprintf(
            '<iframe src="%s" width="100%%" height="120" frameborder="0" title="%s" allow="autoplay"></iframe>',
            $embedUrl,
            htmlspecialchars($titleVal)
        );
    }
}
