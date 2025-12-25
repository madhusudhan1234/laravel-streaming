<?php

use App\Http\Controllers\AudioStreamController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\EpisodeController;
use Illuminate\Support\Facades\Route;

Route::middleware('block.admin')->group(function () {
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
});

// Admin routes (login + dashboard only on admin domain)
require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
