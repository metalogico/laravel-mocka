<?php

namespace Metalogico\Mocka\Support;

use Closure;

class MockaEvaluator
{
    /**
     * Resolve static, dynamic (Closure), or hybrid values recursively.
     * v1: closures are invoked with zero arguments (no request/context exposure).
     */
    public static function resolve(mixed $value, array $context = [], int $depth = 0): mixed
    {
        if ($depth > 10) {
            return $value; // prevent runaway recursion
        }

        // If it's a Closure, call with zero arguments (no context)
        if ($value instanceof Closure) {
            $result = $value();
            return self::resolve($result, $context, $depth + 1);
        }

        // Arrays: resolve each element recursively, supporting hybrid closures
        if (is_array($value)) {
            $resolved = [];
            foreach ($value as $k => $v) {
                $resolved[$k] = self::resolve($v, $context, $depth + 1);
            }
            return $resolved;
        }

        // Objects that are not PSR-7: try to cast to array recursively
        if (is_object($value) && !($value instanceof \Psr\Http\Message\ResponseInterface)) {
            $vars = get_object_vars($value);
            if (!empty($vars)) {
                $resolved = [];
                foreach ($vars as $k => $v) {
                    $resolved[$k] = self::resolve($v, $context, $depth + 1);
                }
                return $resolved;
            }
        }

        return $value;
    }
}
