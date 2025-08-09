<?php

namespace Metalogico\Mocka\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Mocka HTTP client.
 *
 * This is intended to be an API-compatible alternative to Laravel's Http facade.
 */
class MockaHttp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mocka.http';
    }
}
