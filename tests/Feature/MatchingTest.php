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

it('matches wildcard in query string', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://dms.example.com?APPNAME=AdiutoAPI&PRGNAME=RQ_STATO_USER&ARGUMENTS=*', 'match' => 'wildcard', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://dms.example.com?APPNAME=AdiutoAPI&PRGNAME=RQ_STATO_USER&ARGUMENTS=-Apippo%2C-Apassword');

    expect($res->status())->toBe(200)
        ->and($res->json('ok'))->toBe(true);
});
