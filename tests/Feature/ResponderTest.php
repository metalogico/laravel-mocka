<?php

use Metalogico\Mocka\Facades\MockaHttp;

it('returns JSON content-type for array', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/json', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/json');

    expect($res->header('Content-Type'))->toContain('application/json');
});

it('returns JSON content-type for JSON-looking string', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/jsonstr', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.jsonString'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/jsonstr');

    expect($res->header('Content-Type'))->toContain('application/json');
});

it('returns text/plain for plain string', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/text', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.textString'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/text');

    expect($res->header('Content-Type'))->toContain('text/plain');
});

it('honors per-mapping delay (approx)', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/slow', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.ok', 'delay' => 20],
    ]);

    $t0 = microtime(true);
    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/slow');
    $elapsed = (microtime(true) - $t0) * 1000;

    expect($res->successful())->toBeTrue()
        ->and($elapsed)->toBeGreaterThanOrEqual(15.0);
});
