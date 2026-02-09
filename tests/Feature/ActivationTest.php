<?php

use Metalogico\Mocka\Facades\MockaHttp;
use Illuminate\Support\Facades\Auth;

it('activates via options.mocka', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://api.example.com/users', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withOptions(['mocka' => true])->get('http://api.example.com/users');

    expect($res->successful())->toBeTrue();
});

it('activates via X-Mocka header', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://api.example.com/flag', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://api.example.com/flag');

    expect($res->successful())->toBeTrue();
});

it('activates via user allowlist', function () {
    config()->set('mocka.users', ['allowed@example.com']);
    Auth::shouldReceive('user')->andReturn((object) ['email' => 'allowed@example.com']);
    config()->set('mocka.mappings', [
        ['url' => 'http://api.example.com/allow', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::get('http://api.example.com/allow');

    expect($res->successful())->toBeTrue();

    // Reset
    \Mockery::close();
    config()->set('mocka.users', []);
});

it('does NOT activate via X-Mocka: 0', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://api.example.com/flag', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $middleware = \Metalogico\Mocka\Http\MockaMiddleware::handle();
    $nextCalled = false;
    $handler = function ($request, $options) use (&$nextCalled) {
        $nextCalled = true;
        return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, [], 'real'));
    };
    $callable = $middleware($handler);
    $req = (new \GuzzleHttp\Psr7\Request('GET', 'http://api.example.com/flag'))
        ->withHeader('X-Mocka', '0');
    $callable($req, [])->wait();

    expect($nextCalled)->toBeTrue();
});

it('does NOT activate via X-Mocka: false', function () {
    $middleware = \Metalogico\Mocka\Http\MockaMiddleware::handle();
    $nextCalled = false;
    $handler = function ($request, $options) use (&$nextCalled) {
        $nextCalled = true;
        return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, [], 'real'));
    };
    $callable = $middleware($handler);
    $req = (new \GuzzleHttp\Psr7\Request('GET', 'http://api.example.com/flag'))
        ->withHeader('X-Mocka', 'false');
    $callable($req, [])->wait();

    expect($nextCalled)->toBeTrue();
});

it('activates via X-Mocka present but empty', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://api.example.com/flag', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $middleware = \Metalogico\Mocka\Http\MockaMiddleware::handle();
    $nextCalled = false;
    $handler = function ($request, $options) use (&$nextCalled) {
        $nextCalled = true;
        return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(599, [], 'real'));
    };
    $callable = $middleware($handler);
    $req = (new \GuzzleHttp\Psr7\Request('GET', 'http://api.example.com/flag'))
        ->withHeader('X-Mocka', '');
    $callable($req, [])->wait();

    expect($nextCalled)->toBeFalse();
});

it('does NOT activate via options mocka false', function () {
    $middleware = \Metalogico\Mocka\Http\MockaMiddleware::handle();
    $nextCalled = false;
    $handler = function ($request, $options) use (&$nextCalled) {
        $nextCalled = true;
        return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, [], 'real'));
    };
    $callable = $middleware($handler);
    $req = new \GuzzleHttp\Psr7\Request('GET', 'http://api.example.com/x');
    $callable($req, ['mocka' => false])->wait();

    expect($nextCalled)->toBeTrue();
});

it('does NOT activate via options mocka empty string', function () {
    $middleware = \Metalogico\Mocka\Http\MockaMiddleware::handle();
    $nextCalled = false;
    $handler = function ($request, $options) use (&$nextCalled) {
        $nextCalled = true;
        return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, [], 'real'));
    };
    $callable = $middleware($handler);
    $req = new \GuzzleHttp\Psr7\Request('GET', 'http://api.example.com/x');
    $callable($req, ['mocka' => ''])->wait();

    expect($nextCalled)->toBeTrue();
});

it('activates via options mocka string yes', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://api.example.com/users', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withOptions(['mocka' => 'yes'])->get('http://api.example.com/users');

    expect($res->successful())->toBeTrue();
});

it('does NOT activate for user not in allowlist', function () {
    config()->set('mocka.users', ['allowed@example.com']);
    Auth::shouldReceive('user')->andReturn((object) ['email' => 'other@example.com']);

    $middleware = \Metalogico\Mocka\Http\MockaMiddleware::handle();
    $nextCalled = false;
    $handler = function ($request, $options) use (&$nextCalled) {
        $nextCalled = true;
        return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, [], 'real'));
    };
    $callable = $middleware($handler);
    $req = new \GuzzleHttp\Psr7\Request('GET', 'http://api.example.com/x');
    $callable($req, [])->wait();

    expect($nextCalled)->toBeTrue();

    \Mockery::close();
    config()->set('mocka.users', []);
});

it('is inactive when environment not allowed (passes through)', function () {
    // Use middleware directly to avoid real HTTP
    config()->set('mocka.enabled', true);
    config()->set('mocka.environments', ['local']); // 'testing' not in list, so inactive
    $middleware = \Metalogico\Mocka\Http\MockaMiddleware::handle();
    $nextCalled = false;
    $captured = null;
    $handler = function ($request, $options) use (&$nextCalled, &$captured) {
        $nextCalled = true;
        $captured = $request;
        return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(599, [], 'real'));
    };
    $callable = $middleware($handler);
    $req = new \GuzzleHttp\Psr7\Request('GET', 'http://api.example.com/x');
    $promise = $callable($req, []);
    $res = $promise->wait();

    expect($nextCalled)->toBeTrue()
        ->and($res->getStatusCode())->toBe(599)
        ->and((string) $res->getBody())->toBe('real');

    // restore envs
    config()->set('mocka.environments', ['testing']);
});
