<?php

namespace App\Console\Commands;

use App\Models\LlmProviderConfig;
use App\Models\User;
use App\Services\Security\AuthTokenManager;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetupCommand extends Command
{
    protected $signature = 'stan:setup';

    protected $description = 'First-run setup wizard for Stan';

    public function handle(AuthTokenManager $tokenManager): int
    {
        $this->components->info('Welcome to Stan Setup');
        $this->newLine();

        $this->ensureDatabase();

        $user = $this->setupUser();
        $this->setupProvider($user);

        $token = $tokenManager->generateOnFirstRun();

        $this->newLine();
        $this->components->info('Setup complete!');
        $this->components->twoColumnDetail('Auth Token', $token);
        $this->newLine();
        $this->components->info('Run `php artisan stan:start` to launch Stan.');

        return self::SUCCESS;
    }

    private function ensureDatabase(): void
    {
        $dbPath = database_path('database.sqlite');

        if (! file_exists($dbPath)) {
            touch($dbPath);
            $this->components->info('Created SQLite database.');
        }

        $this->call('migrate', ['--force' => true]);
    }

    private function setupUser(): User
    {
        $existing = User::where('email', 'stan@localhost')->first();

        if ($existing) {
            $this->components->info('Admin user already exists.');

            return $existing;
        }

        return User::create([
            'name' => 'Stan Admin',
            'email' => 'stan@localhost',
            'password' => bcrypt(Str::random(32)),
        ]);
    }

    private function setupProvider(User $user): void
    {
        $provider = $this->choice(
            'Which LLM provider would you like to configure?',
            ['anthropic', 'openai', 'ollama', 'skip'],
            0,
        );

        if ($provider === 'skip') {
            $this->components->warn('Skipping provider setup. You can configure it later in Settings.');

            return;
        }

        if ($provider === 'ollama') {
            $baseUrl = $this->ask('Ollama base URL', 'http://localhost:11434');

            LlmProviderConfig::updateOrCreate(
                ['user_id' => $user->id, 'provider' => $provider],
                [
                    'api_key' => 'ollama',
                    'base_url' => $baseUrl,
                    'default_model' => 'llama3.2',
                    'is_default' => true,
                    'is_active' => true,
                ],
            );

            $this->components->info('Ollama configured.');

            return;
        }

        $apiKey = $this->secret("Enter your {$provider} API key");

        if (! $apiKey) {
            $this->components->warn('No API key provided. Skipping.');

            return;
        }

        $defaultModel = $provider === 'anthropic' ? 'claude-sonnet-4-20250514' : 'gpt-4o';

        LlmProviderConfig::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $provider],
            [
                'api_key' => $apiKey,
                'default_model' => $defaultModel,
                'is_default' => true,
                'is_active' => true,
            ],
        );

        $this->components->info("{$provider} configured with model {$defaultModel}.");
    }
}
