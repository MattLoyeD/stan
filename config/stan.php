<?php

return [
    'home' => env('STAN_HOME', ''),

    'bind_address' => env('STAN_BIND_ADDRESS', '127.0.0.1'),
    'port' => env('STAN_PORT', 0),

    'security' => [
        'localhost_only' => env('STAN_LOCALHOST_ONLY', true),
        'max_iterations_per_objective' => 50,
        'max_iterations_per_session' => 200,
        'default_token_budget' => 100000,
        'default_session_token_budget' => 200000,
    ],

    'agent' => [
        'soul_path' => base_path('SOUL.md'),
        'default_provider' => env('STAN_DEFAULT_PROVIDER', 'anthropic'),
        'default_model' => env('STAN_DEFAULT_MODEL', 'claude-sonnet-4-20250514'),
        'max_steps' => 50,
        'temperature' => 0.3,
    ],

    'sandbox' => [
        'use_bwrap' => env('STAN_USE_BWRAP', true),
        'default_timeout_ms' => 30000,
        'max_output_bytes' => 1_048_576,
        'max_memory_mb' => 512,
    ],

    'tool_permissions' => [
        'file_read' => 'auto_approve',
        'file_search' => 'auto_approve',
        'web_search' => 'auto_approve',
        'file_write' => 'session_approve',
        'web_fetch' => 'session_approve',
        'shell' => 'explicit_approve',
        'api_call' => 'always_ask',
    ],

    'mercure' => [
        'url' => env('MERCURE_URL', '/.well-known/mercure'),
        'jwt_secret' => env('MERCURE_JWT_SECRET'),
    ],

    'plugins' => [
        'directory' => base_path('plugins'),
        'registry_url' => env('STAN_PLUGIN_REGISTRY_URL', ''),
    ],

    'channels' => [
        'telegram' => [
            'token' => env('TELEGRAM_BOT_TOKEN'),
        ],
        'slack' => [
            'token' => env('SLACK_BOT_TOKEN'),
            'signing_secret' => env('SLACK_SIGNING_SECRET'),
        ],
        'whatsapp' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'phone_number' => env('TWILIO_PHONE_NUMBER'),
        ],
        'signal' => [
            'cli_path' => env('SIGNAL_CLI_PATH', '/usr/local/bin/signal-cli'),
            'phone_number' => env('SIGNAL_PHONE_NUMBER'),
        ],
        'teams' => [
            'app_id' => env('TEAMS_APP_ID'),
            'app_password' => env('TEAMS_APP_PASSWORD'),
        ],
    ],
];
