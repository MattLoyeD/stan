<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Str;

class AuthTokenManager
{
    private const TOKEN_FILE = '.stan_token';

    public function generateOnFirstRun(): string
    {
        $tokenPath = $this->getTokenPath();

        if (file_exists($tokenPath)) {
            return file_get_contents($tokenPath);
        }

        $user = User::firstOrCreate(
            ['email' => 'stan@localhost'],
            [
                'name' => 'Stan Admin',
                'password' => bcrypt(Str::random(32)),
            ],
        );

        $token = $user->createToken('stan-auth')->plainTextToken;

        $this->saveToken($token, $tokenPath);

        return $token;
    }

    public function getToken(): ?string
    {
        $tokenPath = $this->getTokenPath();

        if (! file_exists($tokenPath)) {
            return null;
        }

        return trim(file_get_contents($tokenPath));
    }

    public function regenerate(): string
    {
        $tokenPath = $this->getTokenPath();

        if (file_exists($tokenPath)) {
            unlink($tokenPath);
        }

        $user = User::where('email', 'stan@localhost')->first();

        if ($user) {
            $user->tokens()->delete();
        }

        return $this->generateOnFirstRun();
    }

    private function getTokenPath(): string
    {
        $dir = storage_path('app');

        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        return $dir . '/' . self::TOKEN_FILE;
    }

    private function saveToken(string $token, string $path): void
    {
        file_put_contents($path, $token);
        chmod($path, 0600);
    }
}
