<?php

namespace Metalogico\Mocka\Support;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Facades\Log;
use Metalogico\Mocka\Http\MockaResponder;

class MockaEngine
{
    /**
     * Try to produce a mocked response for the given request.
     * Returns null if no mapping/value is found or on failure.
     */
    public static function maybeRespond(RequestInterface $request, array $options = []): ?ResponseInterface
    {
        try {
            $mapping = MockaMatcher::match($request);
            if (!is_array($mapping) || !isset($mapping['file'])) {
                return null;
            }

            $method = strtoupper($request->getMethod());
            $value = MockaLoader::load((string) $mapping['file'], $method, $mapping['key'] ?? null);
            if ($value === null) {
                return null;
            }

            // v1 simplification: do not pass request/context to mapping closures
            $payload = MockaEvaluator::resolve($value);
            $response = MockaResponder::toPsrResponse($payload, $mapping);

            return $response;
        } catch (\Throwable $e) {
            if ((bool) config('mocka.logs', false)) {
                Log::warning('☕ Mocka mapping error: ' . $e->getMessage());
            }
            return null;
        }
    }
}
