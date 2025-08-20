<?php

namespace Metalogico\Mocka\Http;

use Psr\Http\Message\RequestInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Promise\Create as PromiseCreate;
use Metalogico\Mocka\Support\MockaEngine;

class MockaMiddleware
{
    /**
     * Return a Guzzle middleware callable. For MVP, always pass-through.
     */
    public static function handle(): callable
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                // Config flags
                $logging = (bool) config('mocka.logs', false);

                // Determine current app user email when available (via Auth facade)
                $user  = Auth::user();
                $email = $user ? ($user->email ?? null) : null;

                // Determine outgoing URL (for logs)
                $uri  = $request->getUri();
                $url  = (string) $uri;

                // Decide activation (also strips X-Mocka from request if present)
                [$withMocka, $request, $reason] = self::decideActivation($request, $options);

                // Prepare log fields once
                $internal = null;
                try {
                    if (function_exists('request') && request()) {
                        $internal = request()->getMethod() . ' ' . request()->getPathInfo();
                    }
                } catch (\Throwable $e) {
                    // ignore
                }

                $who  = $email ?: 'guest';
                $what = $internal ?: ($request->getMethod() . ' ' . $url);
                $mode = $withMocka ? ('With Mocka' . ($reason ? (' ['.$reason.']') : '')) : 'Without Mocka';

                if ($logging) {
                    Log::debug(sprintf('☕ Mocka: %s requested %s - %s', $who, $what, $mode));
                }

                // If Mocka is active, delegate to the Engine for a possible mocked response
                if ($withMocka) {
                    $response = MockaEngine::maybeRespond($request, $options);
                    if ($response) {
                        return PromiseCreate::promiseFor($response);
                    }
                }

                // Pass-through to real handler
                return $handler($request, $options);
            };
        };
    }

    /**
     * Decide whether Mocka should be active for this outgoing request.
     * Early-exit approach. Also strips the control header 'X-Mocka' from the request.
     *
     * Honors activation triggers in priority:
     * - user allowlist
     * - options['mocka'] (for Jobs/Artisan)
     * - header 'X-Mocka'
     *
     * Returns array: [bool $active, RequestInterface $request, string $reason]
     *   $reason is one of '', 'user', 'option', 'header'.
     */
    private static function decideActivation(RequestInterface $request, array $options): array
    {
        // Always sanitize control header (do not leak upstream)
        $hasXMocka  = $request->hasHeader('X-Mocka');
        $headerLine = strtolower(trim($request->getHeaderLine('X-Mocka')));
        if ($hasXMocka) {
            $request = $request->withoutHeader('X-Mocka');
        }

        // Global switches and security checks
        if (!(bool) config('mocka.enabled', false)) {
            return [false, $request, ''];
        }

        // Environment checks
        $environments = (array) config('mocka.environments', ['local']);
        if (!empty($environments) && !app()->environment($environments)) {
            return [false, $request, ''];
        }

        // Hostname checks
        $allowedHosts = (array) config('mocka.allowed_hosts', []);
        $host = $request->getUri()->getHost();
        if (!empty($allowedHosts) && !in_array($host, $allowedHosts, true)) {
            return [false, $request, ''];
        }

        // User allowlist
        $user  = Auth::user();
        $email = $user ? ($user->email ?? null) : null;
        $users = (array) config('mocka.users', []);
        if ($email && $users) {
            $lower = array_map('strtolower', $users);
            if (in_array(strtolower($email), $lower, true)) {
                return [true, $request, 'user'];
            }
        }

        // Forced via options (usable in Jobs/Artisan): withOptions(['mocka' => true])
        $forceOption  = $options['mocka'] ?? null;
        $optionActive = false;
        if ($forceOption !== null) {
            if (is_bool($forceOption)) {
                $optionActive = $forceOption;
            } else {
                $val = strtolower(trim((string) $forceOption));
                $optionActive = ($val === '' || $val === '1' || $val === 'true' || $val === 'yes' || $val === 'on');
            }
        }
        if ($optionActive) {
            return [true, $request, 'option'];
        }

        // Header activator (truthy or just present)
        $headerActive = $hasXMocka && ($headerLine === '' || $headerLine === '1' || $headerLine === 'true' || $headerLine === 'yes' || $headerLine === 'on');
        if ($headerActive) {
            return [true, $request, 'header'];
        }

        return [false, $request, ''];
    }
}
