<?php

return [
    // Master switch. When false, middleware is pass-through only.
    'enabled' => env('MOCKA_ENABLED', false),

    // Basic logging (can be extended later)
    'logs' => env('MOCKA_LOGS', false),

    // Users (emails) for which Mocka is active when enabled
    // Supports comma-separated env var: MOCKA_USERS="apple@example.com, foo@bar.com"
    'users' => array_values(array_filter(array_map('trim', explode(',', env('MOCKA_USERS', ''))))),

    // Security: only allow mocking for these hostnames
    'allowed_hosts' => [],

    // Where to load mapping files from (for future steps)
    'mocks_path' => resource_path('mocka'),

    // Default artificial delay for mocked responses (ms)
    'default_delay_ms' => 0,

    // Allowed application environments for Mocka activation (default: local only)
    // Extend this array to enable in other envs (e.g. ['local','staging']).
    'environments' => ['local'],

    // URL mappings: exact and wildcard patterns only (no regex in MVP)
    // Example item:
    // [
    //   'url' => env('EXTERNAL_API_URL').'/api/users/*',
    //   'match' => 'wildcard', // or omit for exact
    //   'file' => 'users.mock.php',
    //   'key'  => 'GET.show',
    //   'delay' => 0,
    // ]
    'mappings' => [],
];

