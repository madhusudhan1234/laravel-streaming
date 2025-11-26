<?php

use App\Http\Controllers\AudioStreamController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\EpisodeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Home page - Episode listing
Route::get('/', [EpisodeController::class, 'index'])->name('home');

// Embed routes
Route::get('/embed/{id}', [EmbedController::class, 'show'])->name('embed.show');
Route::get('/api/embed/{id}/code', [EmbedController::class, 'generateEmbedCode'])->name('embed.code');


// API routes for episodes
Route::get('/api/episodes', [EpisodeController::class, 'apiIndex'])->name('api.episodes');
Route::get('/api/episodes/{id}', [EpisodeController::class, 'show'])->name('api.episodes.show');

// Audio streaming routes
Route::get('/api/stream/{filename}', [AudioStreamController::class, 'stream'])->name('audio.stream');
Route::get('/api/episodes/{id}/stream', [AudioStreamController::class, 'getEpisodeStreamUrl'])->name('api.episodes.stream');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Episode management routes (protected by auth middleware)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/episodes', [EpisodeController::class, 'dashboard'])->name('episodes.dashboard');
    Route::post('/dashboard/episodes', [EpisodeController::class, 'store'])->name('episodes.store');
    Route::post('/dashboard/episodes/sync', [EpisodeController::class, 'sync'])->name('episodes.sync');
    Route::get('/dashboard/episodes/{episode}/edit', [EpisodeController::class, 'edit'])->name('episodes.edit');
    Route::put('/dashboard/episodes/{episode}', [EpisodeController::class, 'update'])->name('episodes.update');
    Route::delete('/dashboard/episodes/{episode}', [EpisodeController::class, 'destroy'])->name('episodes.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
