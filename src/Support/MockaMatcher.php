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
     * Matching rules:
     * - If a mapping pattern contains a query string ('?'), matching is performed
     *   against the full URL (scheme://authority/path?query). In the query part,
     *   '*' matches any character sequence.
     * - If a mapping pattern does NOT contain a query string, matching is performed
     *   against only scheme://authority/path (query ignored). In the path part,
     *   '*' matches any non-slash sequence (single path segment).
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
        // Base: scheme://authority + path
        $baseUrl = $uri->getScheme() . '://' . $uri->getAuthority() . $uri->getPath();
        // Normalize trailing slashes for stable comparisons (path-only)
        $normPathUrl = Str::of($baseUrl)->rtrim('/')->toString();
        // Full URL including query (no rtrim on query)
        $normFullUrl = $normPathUrl . ($uri->getQuery() !== '' ? ('?' . $uri->getQuery()) : '');

        $exactCandidates = [];
        $wildcardCandidates = [];

        foreach ($mappings as $mapping) {
            if (!is_array($mapping) || empty($mapping['url'])) {
                continue;
            }

            $pattern = (string) $mapping['url'];
            // Determine if pattern includes an explicit query string
            $hasQueryPattern = (strpos($pattern, '?') !== false);
            if ($hasQueryPattern) {
                // Normalize only the path portion by trimming trailing '/'
                $qPos = strpos($pattern, '?');
                $pPath = substr($pattern, 0, $qPos);
                $pQuery = substr($pattern, $qPos + 1);
                $normPatternPath = Str::of($pPath)->rtrim('/')->toString();
                $normPattern = $normPatternPath . '?' . $pQuery;
            } else {
                $normPattern = Str::of($pattern)->rtrim('/')->toString();
            }
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
                    'hasQuery' => $hasQueryPattern,
                ];
            } else {
                $exactCandidates[] = [
                    'mapping' => $mapping,
                    'norm' => $normPattern,
                    'hasQuery' => $hasQueryPattern,
                ];
            }
        }

        // 1) Exact match first
        foreach ($exactCandidates as $item) {
            if (!empty($item['hasQuery'])) {
                if ($normFullUrl === (string) $item['norm']) {
                    return $item['mapping'];
                }
            } else {
                if ($normPathUrl === (string) $item['norm']) {
                    return $item['mapping'];
                }
            }
        }

        // Helper to build a regex from a pattern depending on whether it includes a query
        $buildRegex = function (string $pattern, bool $hasQuery): string {
            if ($hasQuery) {
                $qPos = strpos($pattern, '?');
                $pPath = substr($pattern, 0, $qPos);
                $pQuery = substr($pattern, $qPos + 1);

                // Path part: '*' matches a single path segment (no '/')
                $quotedPath = preg_quote($pPath, '~');
                $pathRegex = str_replace('\\*', '([^/]+)', $quotedPath);

                // Query part: '*' matches any character sequence (including '&', '=', percent-encodings)
                $quotedQuery = preg_quote($pQuery, '~');
                $queryRegex = str_replace('\\*', '(.+)', $quotedQuery);

                return '~^' . $pathRegex . '\?' . $queryRegex . '$~';
            }

            // Path-only pattern
            $quoted = preg_quote($pattern, '~');
            $pathOnly = str_replace('\\*', '([^/]+)', $quoted);
            return '~^' . $pathOnly . '$~';
        };

        // 2) Wildcard match (* => non-greedy capture)
        foreach ($wildcardCandidates as $item) {
            $pattern = (string) $item['norm'];

            $regex = $buildRegex($pattern, !empty($item['hasQuery']));

            if (!empty($item['hasQuery'])) {
                if (preg_match($regex, $normFullUrl)) {
                    return $item['mapping'];
                }
            } else {
                if (preg_match($regex, $normPathUrl)) {
                    return $item['mapping'];
                }
            }
        }

        return null;
    }
}
