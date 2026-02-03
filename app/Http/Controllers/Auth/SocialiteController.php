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

        // Find or create the user
        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'password' => bcrypt(str()->random(24)), // Random password since we use OAuth
            ]
        );

        // Update name if it has changed
        if ($user->name !== $googleUser->getName()) {
            $user->update(['name' => $googleUser->getName()]);
        }

        // Log the user in
        Auth::login($user, remember: true);

        session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
