<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Inertia\Inertia;
use App\Http\Controllers\EpisodeController;

if (app()->runningUnitTests()) {
    Route::withoutMiddleware([VerifyCsrfToken::class])->group(function () {
        Route::get('dashboard', function () {
            return Inertia::render('Dashboard');
        })->middleware(['auth', 'verified'])->name('dashboard');

        Route::middleware(['auth', 'verified'])->group(function () {
            Route::get('/dashboard/episodes', [EpisodeController::class, 'dashboard'])->name('episodes.dashboard');
            Route::post('/dashboard/episodes', [EpisodeController::class, 'store'])->name('episodes.store');
            Route::post('/dashboard/episodes/sync', [EpisodeController::class, 'sync'])->name('episodes.sync');
            Route::get('/dashboard/episodes/{episode}/edit', [EpisodeController::class, 'edit'])->name('episodes.edit');
            Route::put('/dashboard/episodes/{episode}', [EpisodeController::class, 'update'])->name('episodes.update');
            Route::delete('/dashboard/episodes/{episode}', [EpisodeController::class, 'destroy'])->name('episodes.destroy');
        });
    });
} else {
    Route::domain(config('domains.admin'))->group(function () {
        Route::get('dashboard', function () {
            return Inertia::render('Dashboard');
        })->middleware(['auth', 'verified'])->name('dashboard');

        Route::middleware(['auth', 'verified'])->group(function () {
            Route::get('/dashboard/episodes', [EpisodeController::class, 'dashboard'])->name('episodes.dashboard');
            Route::post('/dashboard/episodes', [EpisodeController::class, 'store'])->name('episodes.store');
            Route::post('/dashboard/episodes/sync', [EpisodeController::class, 'sync'])->name('episodes.sync');
            Route::get('/dashboard/episodes/{episode}/edit', [EpisodeController::class, 'edit'])->name('episodes.edit');
            Route::put('/dashboard/episodes/{episode}', [EpisodeController::class, 'update'])->name('episodes.update');
            Route::delete('/dashboard/episodes/{episode}', [EpisodeController::class, 'destroy'])->name('episodes.destroy');
        });
    });
}
