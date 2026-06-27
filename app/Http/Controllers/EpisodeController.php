<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEpisodeRequest;
use App\Http\Requests\UpdateEpisodeRequest;
use App\Services\EpisodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EpisodeController extends Controller
{
    public function __construct(private EpisodeService $episodes) {}

    public function index(): Response
    {
        return Inertia::render('Home', [
            'episodes' => $this->episodes->getAll(),
        ]);
    }

    public function apiIndex(): JsonResponse
    {
        $episodes = $this->episodes->getAll();

        return response()->json([
            'episodes' => $episodes,
            'total' => count($episodes),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $episode = $this->episodes->find($id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        return response()->json($episode);
    }

    public function dashboard(): Response
    {
        $episodes = $this->episodes->getAll();
        usort($episodes, fn ($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));

        return Inertia::render('EpisodeManagement', [
            'episodes' => $episodes,
        ]);
    }

    public function store(StoreEpisodeRequest $request): RedirectResponse
    {
        try {
            $this->episodes->store($request);

            return redirect()->route('episodes.dashboard')->with('success', 'Episode creation queued');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Error creating episode: '.$e->getMessage()]);
        }
    }

    public function edit(int $id): JsonResponse
    {
        $episode = $this->episodes->find($id);

        if (! $episode) {
            return response()->json(['message' => 'Episode not found'], 404);
        }

        return response()->json($episode);
    }

    public function update(UpdateEpisodeRequest $request, int $id): RedirectResponse
    {
        try {
            $updated = $this->episodes->update($request, $id);

            if (! $updated) {
                return redirect()->back()->withErrors(['error' => 'Error updating episode']);
            }

            return redirect()->route('episodes.dashboard')->with('success', 'Episode updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Error updating episode: '.$e->getMessage()]);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->episodes->delete($id);

            if (! $deleted) {
                return response()->json(['message' => 'Episode not found'], 404);
            }

            return response()->json(['message' => 'Episode deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting episode: '.$e->getMessage()], 500);
        }
    }

    public function sync(): RedirectResponse
    {
        $this->episodes->sync();

        return redirect()->route('episodes.dashboard')->with('success', 'Episodes sync queued');
    }
}
