# Laravel Mocka ☕

[![Latest Version on Packagist](https://img.shields.io/packagist/v/metalogico/laravel-mocka.svg?style=flat-square)](https://packagist.org/packages/metalogico/laravel-mocka)

Laravel Mocka provides fake API responses to designated users while serving real data to everyone else. A drop-in replacement for Laravel's Http facade, perfect for app store submissions, demos, and testing without disrupting production traffic.

## Why Mocka?

When your Laravel app calls external APIs (like DMS, MES, or any third-party service), you often need to provide mock responses for:

- 🍎 **App Store Reviews** - Apple and Google reviewers need working apps without real API access
- 🧪 **Testing Environments** - Stable, predictable responses for your test suite
- 👥 **Demo Users** - Showcase your app without depending on external services
- 🚀 **Development** - Work offline or with unstable external APIs

## Features

- 🎯 **User-specific mocking** - Mock responses only for designated users
- 🛣️ **Route-specific control** - Enable/disable mocking per route via middleware
- 🔄 **Drop-in replacement** - Use `MockaHttp` instead of Laravel's `Http` facade
- 📁 **File-based mocks** - Organize mock responses in PHP files
- 🎨 **Response templating** - Dynamic mock responses with Faker integration
- 🐌 **Rate limiting simulation** - Simulate slow APIs for testing
- ❌ **Error simulation** - Test failure scenarios easily
- 🔍 **Advanced URL matching** - Regex, wildcards, and parameter matching
- 📊 **Request logging** - Track which requests are mocked vs real
- ⚡ **Zero performance impact** - Only active for designated users

## Installation

You can install the package via composer:

```bash
composer require metalogico/laravel-mocka
```

Publish the config file:

```bash
php artisan vendor:publish --tag="mocka-config"
```

## Quick Start

### 1. Configure Mock Users

In your `.env` file:

```env
MOCKA_ENABLED=true
MOCKA_USERS=reviewer@apple.com,tester@google.com,demo@yourapp.com
MOCKA_LOGS=true
```

### 2. Create Mock Files

Create mock files in `resources/mocka/`:

**`resources/mocka/api.mock.php`**
```php
<?php

return [
    'POST' => [
        'authenticate' => [
            'success' => true,
            'user_id' => 12345,
            'token' => 'mock_token_12345',
            'expires_at' => time() + 3600,
        ],
    ],
    'GET' => [
        'getFileList' => [
            'success' => true,
            'files' => [
                [
                    'name' => 'file1.pdf',
                    'size' => 1024,
                    'created_at' => '2023-01-01 00:00:00',
                ],
                [
                    'name' => 'file2.pdf',
                    'size' => 2048,
                    'created_at' => '2023-01-02 00:00:00',
                ],
                // ...
            ],
        ]
    ],
];
```

### 3. Configure URL Mappings

In `config/mocka.php`:

```php
'mappings' => [
    [
        'url' => env('EXTERNAL_API_URL').'/api/authenticate',
        'file' => 'api.mock.php',
        'key' => 'POST.authenticate',
    ],
    [
        'url' => env('EXTERNAL_API_URL').'/api/files/',
        'file' => 'api.mock.php',
        'key' => 'GET.getFileList',
    ],
];
```

### 4. Use MockaHttp in Your Services

Replace Laravel's `Http` facade with `MockaHttp`:

```php
<?php

namespace App\Services;

use LaravelMocka\MockaHttp;

class DmsService
{
    public static function authenticate($user, $password)
    {
        $response = MockaHttp::post(config('external_api_url').'/api/authenticate', [
            'user' => $user,
            'password' => $password,
        ]);

        session()->put('token', $response->json()['token']);

        return $response->json();
    }

    public static function getFileList()
    {
        $response = MockaHttp::withHeaders([
            'Authorization' => 'Bearer '.session()->get('token'),
        ])->get(config('external_api_url').'/api/files');

        return $response->json();
    }
}
```

## Advanced Features

### Route-Specific Mocking

Use the middleware to enable mocking only for specific routes:

```php
// In your routes file
Route::middleware(['mocka'])->group(function () {
    Route::get('/demo', [DemoController::class, 'index']);
});

// Or on specific routes
Route::get('/api/files', [FileController::class, 'index'])->middleware('mocka:force');
```

### Response Types: Static, Dynamic, or Hybrid

Mocka supports three approaches for mock responses:

#### Static Responses
Perfect for app store reviews and basic demos:

```php
return [
    'GET' => [
        'simpleAuth' => [
            'success' => true,
            'token' => 'static_token_123',
            'user_id' => 12345,
        ],
    ],
];
```

#### Dynamic Responses
For complex testing with varying data:

```php
return [
    'GET' => [
        'userList' => fn() => [
            'users' => collect(range(1, faker()->numberBetween(3, 8)))
                ->map(fn() => [
                    'name' => faker()->name,
                    'email' => faker()->email,
                ]),
            'total' => faker()->numberBetween(50, 200),
            ],
        ],
    ],
];
```

#### Hybrid Responses
You can even mix static and dynamic data as needed:

```php
return [
    'GET' => [
        'mixedResponse' => fn() => [
            'status' => 'success',        // Static
            'timestamp' => time(),        // Static but with function
            'dynamic_data' => fn() => [
                'user_count' => faker()->numberBetween(5, 20),
                'featured_products' => collect(range(1, 3))
                    ->map(fn() => [
                        'id' => faker()->numberBetween(1000, 9999),
                        'name' => faker()->words(3, true),
                        'price' => faker()->randomFloat(2, 10, 500),
                    ]),
                ],
            ],
        ],
    ],
];
```

### Advanced URL Matching

Configure sophisticated URL matching patterns:

```php
'mappings' => [
    // Exact match
    [
        'url' => 'https://api.example.com/users/123',
        'match' => 'exact', // which is the default
        'file' => 'users.mock.php',
        'key' => 'GET.specificUser',
    ],
    
    // Wildcard matching
    [
        'url' => 'https://api.example.com/users/*',
        'match' => 'wildcard',
        'file' => 'users.mock.php',
        'key' => 'GET.anyUser',
    ],
    
    // Regex matching
    [
        'url' => '/^https:\/\/api\.example\.com\/orders\/\d+$/',
        'match' => 'regex',
        'file' => 'orders.mock.php',
        'key' => 'GET.orderDetail',
    ],
];
```

### Error Simulation

Simulate API errors by defining error configurations in your mappings:

```php
'mappings' => [
    [
        'url' => 'https://api.example.com/users/123',
        'file' => 'users.mock.php',
        'key' => 'GET.specificUser',
        'errors' => 'GET.specificUserErrors' // Optional error configuration
    ],
];
```

Define error responses in your mock files:

```php
return [
    'GET' => [
        'specificUser' => fn() => [
            'id' => faker()->numberBetween(1000, 9999),
            'name' => faker()->name,
            'email' => faker()->email,
        ],
        
        'specificUserErrors' => [
            'error_rate' => 25, // 25% chance of error
            'errors' => [
                422 => [
                    'message' => 'Validation failed',
                    'errors' => ['name' => ['Name is required']]
                ],
                404 => [
                    'message' => 'User not found',
                    'code' => 'USER_NOT_FOUND',
                    'timestamp' => time()
                ],
                503 => fn() => [ // Dynamic error responses
                    'message' => 'Service temporarily unavailable',
                    'retry_after' => faker()->numberBetween(30, 300),
                    'incident_id' => faker()->uuid,
                ],
            ],
        ],
    ],
];
```

### Rate Limiting Simulation

Add delays to simulate slow APIs:

```php
'mappings' => [
    [
        'url' => 'https://slow-api.com/data',
        'file' => 'slow.mock.php',
        'key' => 'GET.slowResponse',
        'delay' => 5000, // 5 second delay
    ],
];
```

## Artisan Commands

### Validate Mock Files

Check if your mock files and mappings are correct:

```bash
php artisan mocka:validate
```

### List Mock Mappings

See all configured mock mappings:

```bash
php artisan mocka:list
```

## Configuration

The configuration file `config/mocka.php` supports these options:

```php
return [
    // Globally enable Mocka (default: false in production)
    'enabled' => env('MOCKA_ENABLED', false),

    // Enable request logging (default: true in development)
    'logs' => env('MOCKA_LOGS', true),

    // Users that get mocked responses
    'users' => array_filter(explode(',', env('MOCKA_USERS', ''))),

    // Path to mock files
    'mocks_path' => resource_path('mocka'),

    // Default delay for all mocked requests (milliseconds)
    'default_delay' => 0,

    // URL mappings
    'mappings' => [
        // Your API mappings here
    ],
];
```

## How It Works

1. **User Check**: When a request is made, Mocka checks if the current user is in the `users` list
2. **URL Matching**: If the user should be mocked, it matches the request URL against the configured mappings
3. **Mock Loading**: Loads the appropriate mock file and extracts the response using dot notation
4. **Template Processing**: Processes any template variables (faker, time functions, etc.)
5. **Response Simulation**: Returns the mock response with optional delays or errors
6. **Logging**: Logs whether the request was mocked or passed through

## Testing

```bash
composer test
```

## Security

If you discover any security related issues, please email metalogico@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

Made with ☕ in Italy
