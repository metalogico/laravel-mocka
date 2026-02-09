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

            // Error simulation (optional): mapping may define an 'errors' profile
            $errorConfig = null;
            if (array_key_exists('errors', $mapping)) {
                $errorsDef = $mapping['errors'];
                if (is_string($errorsDef)) {
                    // Load from the same file using provided key (method prefix supported by loader)
                    $errorConfig = MockaLoader::load((string) $mapping['file'], $method, $errorsDef);
                } elseif (is_array($errorsDef)) {
                    // Inline profile
                    $errorConfig = $errorsDef;
                }
            }

            if ($picked = self::maybePickError($errorConfig)) {
                // Build and return an error response immediately
                $errPayload = MockaEvaluator::resolve($picked['payload']);
                return MockaResponder::toPsrResponse($errPayload, $mapping, $picked['status']);
            }

            $value = MockaLoader::load((string) $mapping['file'], $method, $mapping['key'] ?? null);
            if ($value === null) {
                return null;
            }

            // v1 simplification: do not pass request/context to mapping closures
            $payload = MockaEvaluator::resolve($value);
            $response = MockaResponder::toPsrResponse($payload, $mapping, 200);

            return $response;
        } catch (\Throwable $e) {
            if ((bool) config('mocka.logs', false)) {
                Log::warning(' Mocka mapping error: ' . $e->getMessage());
            }
            return null;
        }
    }

    /**
     * Given an error profile, randomly decide whether to emit an error and which one.
     * Expected shape:
     * [
     *   'error_rate' => 25, // 0-100
     *   'errors' => [
     *     422 => array|Closure,
     *     404 => array|Closure,
     *     503 => array|Closure,
     *   ],
     * ]
     * Returns ['status' => int, 'payload' => mixed] or null if no error selected.
     */
    private static function maybePickError(mixed $errorConfig): ?array
    {
        if (!is_array($errorConfig)) {
            return null;
        }

        $rate = (int) ($errorConfig['error_rate'] ?? 0);
        if ($rate <= 0) {
            return null;
        }

        $roll = random_int(1, 100);

        if ($roll > $rate) {
            return null;
        }

        $errors = $errorConfig['errors'] ?? null;
        if (!is_array($errors) || empty($errors)) {
            return null;
        }

        $statuses = array_keys($errors);
        $pickIdx = array_rand($statuses);
        $status = (int) $statuses[$pickIdx];
        $payload = $errors[$statuses[$pickIdx]] ?? null;

        return [
            'status' => $status,
            'payload' => $payload,
        ];
    }
}
