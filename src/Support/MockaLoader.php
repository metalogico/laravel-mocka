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

        // If key is null, do not assume any convention
        if ($key === null) {
            return null;
        }

        // Resolve exactly the provided key (supports arbitrary groupings like users.*, etc.)
        $dot = $key;

        return Arr::get($data, $dot);
    }
}
