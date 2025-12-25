<?php

namespace App\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class WhitelistUserProvider implements UserProvider
{
    protected array $emails;

    public function __construct(array $config = [])
    {
        $this->emails = (array) ($config['emails'] ?? config('whitelist.emails'));
    }

    public function retrieveById($identifier): ?Authenticatable
    {
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

    public function updateRememberToken(Authenticatable $user, $token): void
    {
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
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

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
    }

    protected function allowed(string $email): bool
    {
        return $email !== '' && in_array(strtolower($email), array_map('strtolower', $this->emails), true);
    }
}
