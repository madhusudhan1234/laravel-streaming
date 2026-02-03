<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class WhitelistUserProvider implements UserProvider
{
    protected array $emails;

    public function __construct(array $config = [])
    {
        $this->emails = (array) ($config['emails'] ?? config('whitelist.emails', []));

        // Also add the allowed admin email from Google OAuth config
        $allowedAdminEmail = config('services.google.allowed_email');
        if ($allowedAdminEmail && ! in_array($allowedAdminEmail, $this->emails)) {
            $this->emails[] = $allowedAdminEmail;
        }
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        // First check session for Google OAuth user
        $sessionUser = session('google_user');
        if ($sessionUser && $sessionUser['id'] == $identifier) {
            return new GenericUser([
                'id' => $sessionUser['id'],
                'email' => $sessionUser['email'],
                'name' => $sessionUser['name'],
            ]);
        }

        // Fallback to email-based lookup
        $email = (string) $identifier;
        if ($this->allowed($email)) {
            return new GenericUser([
                'id' => $email,
                'email' => $email,
                'name' => $email,
            ]);
        }

        return null;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return $this->retrieveById($identifier);
    }

    public function updateRememberToken(Authenticatable $user, $token): void {}

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        // Check session first
        $sessionUser = session('google_user');
        if ($sessionUser) {
            return new GenericUser([
                'id' => $sessionUser['id'],
                'email' => $sessionUser['email'],
                'name' => $sessionUser['name'],
            ]);
        }

        $email = (string) ($credentials['email'] ?? '');
        if ($this->allowed($email)) {
            return new GenericUser([
                'id' => $email,
                'email' => $email,
                'name' => $email,
            ]);
        }

        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return $this->allowed((string) ($credentials['email'] ?? ''));
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void {}

    protected function allowed(string $email): bool
    {
        return $email !== '' && in_array(strtolower($email), array_map('strtolower', $this->emails), true);
    }
}
