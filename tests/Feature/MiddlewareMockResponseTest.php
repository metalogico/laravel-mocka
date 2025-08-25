<?php

it('returns mocked response via middleware when activated by header', function () {
    // Arrange: mapping for a simple JSON payload
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/json', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    // Middleware under test
    $middleware = \Metalogico\Mocka\Http\MockaMiddleware::handle();

    // A handler that would be called only on pass-through; fail if called
    $called = false;
    $handler = function ($request, $options) use (&$called) {
        $called = true;
        return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(599, [], 'should-not-be-called'));
    };

    $callable = $middleware($handler);

    // Request with activation header
    $req = (new \GuzzleHttp\Psr7\Request('GET', 'http://svc.test/json'))
        ->withHeader('X-Mocka', '1');

    // Act
    $res = $callable($req, [])->wait();

    // Assert: middleware did not pass through and produced JSON
    expect($called)->toBeFalse();
    expect($res->getStatusCode())->toBe(200);
    expect($res->getHeaderLine('Content-Type'))->toContain('application/json');
});
