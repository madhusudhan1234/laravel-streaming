<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('status', 'Unable to authenticate with Google. Please try again.');
        }

        // Check if the email is the allowed admin email
        $allowedEmail = config('services.google.allowed_email');

        if ($googleUser->getEmail() !== $allowedEmail) {
            return redirect()->route('login')->withErrors([
                'email' => 'You are not authorized to access this application.',
            ]);
        }

        // Create a user instance without persisting to database
        $user = new User([
            'id' => 1,
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
        ]);
        $user->id = 1; // Set ID for auth purposes

        // Store user data in session
        session([
            'google_user' => [
                'id' => 1,
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
            ],
        ]);

        // Log the user in using the session guard
        Auth::login($user, remember: true);

        session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
