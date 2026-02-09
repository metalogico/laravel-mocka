<?php

namespace Metalogico\Mocka\Http;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;

class MockaFactory extends Factory
{
    /**
     * Ensure every PendingRequest carries the Mocka middleware.
     */
    public function newPendingRequest(): PendingRequest
    {
        $pending = parent::newPendingRequest();
        // Attach our middleware at the highest priority
        $pending->withMiddleware(MockaMiddleware::handle());
        return $pending;
    }

}
