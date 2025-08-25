# Changelog

All notable changes to this project will be documented in this file.

## [1.0.2] - 2025-08-25

- Added tests!

## [1.0.1] - 2025-08-25

- Matching: wildcard now supports the query string when the mapping pattern contains `?`.
  - In the query part, `*` matches any character sequence (including `&`, `=`, and percent-encodings).
  - For patterns without `?`, matching remains limited to scheme+host+path and `*` matches a single path segment (no `/`).
- Exact matching with `?`: now compares the full URL (path + query) to avoid false positives.

## [1.0.0] - 2025-08-20

- Initial stable release.
- Drop-in HTTP client via `MockaHttp` facade with full compatibility with Laravel's Http client API.
- Activation controls: user allowlist, `withOptions(['mocka' => true])`, `X-Mocka` header; gated by `enabled`, `environments`, and `allowed_hosts`.
- URL matching: exact and wildcard patterns (regex not included by design in 1.0.0).
- File-based mocks loaded from `config('mocka.mocks_path')` using dot-notation keys.
- Response templating: static, dynamic (closures), and hybrid values; Faker-friendly.
- Error simulation profiles with `error_rate` and per-status payloads.
- Rate limiting simulation via per-mapping `delay` or global `default_delay`.
- Logging of mocked vs pass-through requests.
- Artisan: `php artisan mocka:list` to list and validate mappings (including error profiles).
- Config publish tag: `mocka-config` with options `enabled`, `logs`, `users`, `mocks_path`, `default_delay`, `allowed_hosts`, `environments`, `mappings`.
- Compatibility: PHP 8.0+, Laravel 9.x–12.x.
