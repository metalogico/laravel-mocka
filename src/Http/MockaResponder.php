<?php

namespace Metalogico\Mocka\Http;

use GuzzleHttp\Psr7\Response as Psr7Response;

class MockaResponder
{
    /**
     * Build a PSR-7 response from the evaluated payload.
     * Honors mapping 'delay' (ms) or config('mocka.default_delay').
     */
    public static function toPsrResponse(mixed $payload, array $mapping = [], int $status = 200): Psr7Response
    {
        // Delay handling (milliseconds)
        $delay = 0;
        if (isset($mapping['delay']) && is_numeric($mapping['delay'])) {
            $delay = (int) $mapping['delay'];
        } else {
            $delay = (int) config('mocka.default_delay', 0);
        }
        if ($delay > 0) {
            usleep($delay * 1000);
        }

        // If payload already is a PSR-7 Response, return as-is
        if ($payload instanceof \Psr\Http\Message\ResponseInterface) {
            return $payload;
        }

        $headers = [];
        $body = '';

        if (is_array($payload) || is_object($payload)) {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $body = $json === false ? '' : $json;
            $headers['Content-Type'] = 'application/json; charset=utf-8';
        } elseif (is_string($payload)) {
            $trim = ltrim($payload);
            $isJsonLike = ($trim !== '' && ($trim[0] === '{' || $trim[0] === '['));
            $headers['Content-Type'] = $isJsonLike ? 'application/json; charset=utf-8' : 'text/plain; charset=utf-8';
            $body = $payload;
        } elseif (is_null($payload)) {
            $headers['Content-Type'] = 'application/json; charset=utf-8';
            $body = 'null';
        } else {
            // Fallback string cast
            $body = (string) $payload;
            $headers['Content-Type'] = 'text/plain; charset=utf-8';
        }

        return new Psr7Response($status, $headers, $body);
    }
}
