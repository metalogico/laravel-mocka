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
                // Config flags (LEAN activator: per-user)
                $enabled      = (bool) config('mocka.enabled', false);
                $users        = (array) config('mocka.users', []);
                $logging      = (bool) config('mocka.logs', false);
                $environments = (array) config('mocka.environments', ['local']);

                // Determine current app user email when available (via Auth facade)
                $user  = Auth::user();
                $email = $user ? ($user->email ?? null) : null;

                // Determine outgoing host and build display URL
                $uri  = $request->getUri();
                $host = $uri->getHost();
                $url  = (string) $uri;

                // Allowed hosts (security guard)
                $allowedHosts = (array) config('mocka.allowed_hosts', []);
                $hostAllowed  = empty($allowedHosts) || in_array($host, $allowedHosts, true);

                // Allowed environments (default only 'local')
                $envAllowed = empty($environments) || app()->environment($environments);

                // Activator: user email in config list (case-insensitive)
                $emailInList = false;
                if ($email && $users) {
                    $lower = array_map('strtolower', $users);
                    $emailInList = in_array(strtolower($email), $lower, true);
                }

                $withMocka = $enabled && $envAllowed && $hostAllowed && $emailInList;

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
                $mode = $withMocka ? 'With Mocka' : 'Without Mocka';

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
}
