<?php

namespace Metalogico\Mocka\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MockaLoader
{
    /**
     * Load the mock data from file and extract the value by a dot key.
     * The mapping key may include the HTTP method (e.g. GET.users) or not.
     */
    public static function load(string $file, string $method, ?string $key): mixed
    {
        $path = rtrim((string) config('mocka.mocks_path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);

        if (!is_file($path)) {
            return null;
        }

        $data = include $path;
        if (!is_array($data)) {
            return null;
        }

        // Normalize method to uppercase
        $method = strtoupper($method);
        // If key is null, try METHOD only
        $dot = $key ?: $method;
        // If key doesn't start with METHOD., prefix it
        if (!Str::startsWith($dot, $method . '.')) {
            $dot = $method . ($key ? ('.' . $key) : '');
        }

        return Arr::get($data, $dot);
    }
}
