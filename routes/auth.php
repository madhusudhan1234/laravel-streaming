<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

// Google OAuth routes
Route::get('auth/google', [SocialiteController::class, 'redirectToGoogle'])
    ->name('auth.google');
Route::get('auth/google/callback', [SocialiteController::class, 'handleGoogleCallback'])
    ->name('auth.google.callback');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
});
