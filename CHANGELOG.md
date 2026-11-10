# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Security

- Updated locked Laravel Framework from 12.50.0 to 12.71.0 to include the latest security fixes.
- Updated locked Guzzle to 7.16.1, PSR-7 to 2.13.2, CommonMark to 2.10.7, and Flysystem to 3.36.0, together with their required dependencies.
- Updated locked Symfony HttpFoundation, HttpKernel, Routing, and Yaml to 7.4.23, Mailer and Mime to 7.4.22, and IntlIdn polyfill to 1.43.0 to address dependency security advisories.

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
