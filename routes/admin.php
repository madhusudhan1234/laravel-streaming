<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;

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

    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
