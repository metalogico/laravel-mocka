<?php

use Metalogico\Mocka\Facades\MockaHttp;

it('matches exact path ignoring query', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/api/users', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/api/users?foo=1');

    expect($res->status())->toBe(200)
        ->and($res->json('ok'))->toBe(true);
});

it('matches wildcard path segment', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/api/users/*', 'match' => 'wildcard', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/api/users/123');

    expect($res->status())->toBe(200)
        ->and($res->json('ok'))->toBe(true);
});

it('wildcard does NOT match multiple path segments', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/api/users/*', 'match' => 'wildcard', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    // * should match only one segment, not 123/posts
    $middleware = \Metalogico\Mocka\Http\MockaMiddleware::handle();
    $nextCalled = false;
    $handler = function ($request, $options) use (&$nextCalled) {
        $nextCalled = true;
        return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, [], 'real'));
    };
    $callable = $middleware($handler);
    $req = (new \GuzzleHttp\Psr7\Request('GET', 'http://svc.test/api/users/123/posts'))
        ->withHeader('X-Mocka', '1');
    $callable($req, [])->wait();

    expect($nextCalled)->toBeTrue();
});

it('matches multiple wildcards in path', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/api/*/items/*', 'match' => 'wildcard', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/api/orders/items/42');

    expect($res->status())->toBe(200)
        ->and($res->json('ok'))->toBe(true);
});

it('exact match takes priority over wildcard', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/api/users/*', 'match' => 'wildcard', 'file' => 'basic.mock.php', 'key' => 'GET.dynamic'],
        ['url' => 'http://svc.test/api/users/special', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/api/users/special');

    // Should match exact (GET.ok) not wildcard (GET.dynamic)
    expect($res->json('ok'))->toBe(true)
        ->and($res->json('dyn'))->toBeNull();
});

it('matches with trailing slash normalization', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/api/users/', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/api/users');

    expect($res->status())->toBe(200)
        ->and($res->json('ok'))->toBe(true);
});

it('matches wildcard in query string', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://dms.example.com?APPNAME=AdiutoAPI&PRGNAME=RQ_STATO_USER&ARGUMENTS=*', 'match' => 'wildcard', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://dms.example.com?APPNAME=AdiutoAPI&PRGNAME=RQ_STATO_USER&ARGUMENTS=-Apippo%2C-Apassword');

    expect($res->status())->toBe(200)
        ->and($res->json('ok'))->toBe(true);
});
