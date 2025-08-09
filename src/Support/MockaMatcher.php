<?php

namespace Metalogico\Mocka\Support;

use Psr\Http\Message\RequestInterface;
use Illuminate\Support\Str;

class MockaMatcher
{
    /**
     * Match the outgoing request URL against configured mappings.
     * Priority: exact > wildcard. Regex is intentionally unsupported in MVP.
     *
     * v1: no capture exposure. Returns the matched mapping as configured.
     */
    public static function match(RequestInterface $request): ?array
    {
        $mappings = (array) config('mocka.mappings', []);
        if (empty($mappings)) {
            return null;
        }

        $uri = $request->getUri();
        // Compare only scheme://authority + path (ignore query/fragment)
        $baseUrl = $uri->getScheme() . '://' . $uri->getAuthority() . $uri->getPath();
        // Normalize trailing slashes for stable comparisons
        $normUrl = Str::of($baseUrl)->rtrim('/')->toString();

        $exactCandidates = [];
        $wildcardCandidates = [];

        foreach ($mappings as $mapping) {
            if (!is_array($mapping) || empty($mapping['url'])) {
                continue;
            }

            $pattern = (string) $mapping['url'];
            $normPattern = Str::of($pattern)->rtrim('/')->toString();
            // Only rely on explicit 'match' key; default to 'exact'
            $matchMode = strtolower((string) ($mapping['match'] ?? 'exact'));
            if (!in_array($matchMode, ['exact', 'wildcard'], true)) {
                $matchMode = 'exact';
            }
            $isWildcard = ($matchMode === 'wildcard');

            if ($isWildcard) {
                // Collect tuple without mutating original mapping
                $wildcardCandidates[] = [
                    'mapping' => $mapping,
                    'norm' => $normPattern,
                ];
            } else {
                $exactCandidates[] = [
                    'mapping' => $mapping,
                    'norm' => $normPattern,
                ];
            }
        }

        // 1) Exact match first
        foreach ($exactCandidates as $item) {
            if ($normUrl === (string) $item['norm']) {
                return $item['mapping'];
            }
        }

        // 2) Wildcard match (* => non-greedy capture)
        foreach ($wildcardCandidates as $item) {
            $pattern = (string) $item['norm'];

            // Escape to safe regex (use '~' as delimiter to avoid slash conflicts),
            // then replace escaped '*' with a single path-segment capture group
            $quoted = preg_quote($pattern, '~');
            $regex = '~^' . str_replace('\\*', '([^/]+)', $quoted) . '$~';

            if (preg_match($regex, $normUrl)) {
                return $item['mapping'];
            }
        }

        return null;
    }
}
