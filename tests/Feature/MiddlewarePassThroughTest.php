<?php

use GuzzleHttp\Promise\Create as PromiseCreate;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Metalogico\Mocka\Http\MockaMiddleware;

it('strips X-Mocka header on pass-through', function () {
    config()->set('mocka.enabled', true);
    config()->set('mocka.environments', ['testing']);
    config()->set('mocka.allowed_hosts', ['other.example.com']); // Not matching host

    $middleware = MockaMiddleware::handle();

    $received = null;
    $handler = function ($request, $options) use (&$received) {
        $received = $request;
        return PromiseCreate::promiseFor(new Response(200, [], 'ok'));
    };

    $callable = $middleware($handler);

    $req = (new Request('GET', 'http://api.example.com/x'))
        ->withHeader('X-Mocka', '1');

    $callable($req, [])->wait();

    expect($received)->not->toBeNull()
        ->and($received->hasHeader('X-Mocka'))->toBeFalse();

    // reset
    config()->set('mocka.allowed_hosts', []);
});

