<?php

use Metalogico\Mocka\Facades\MockaHttp;

it('emits inline error profile when rate 100', function () {
    config()->set('mocka.mappings', [
        [
            'url' => 'http://svc.test/error-inline',
            'match' => 'exact',
            'file' => 'basic.mock.php',
            'key' => 'GET.ok',
            'errors' => [
                'error_rate' => 100,
                'errors' => [
                    422 => ['message' => 'Unprocessable'],
                ],
            ],
        ],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/error-inline');

    expect($res->status())->toBe(422)
        ->and($res->json('message'))->toBe('Unprocessable');
});

it('emits file-based error profile when rate 100', function () {
    config()->set('mocka.mappings', [
        [
            'url' => 'http://svc.test/error-file',
            'match' => 'exact',
            'file' => 'basic.mock.php',
            'key' => 'GET.ok',
            'errors' => 'GET.errorProfile',
        ],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/error-file');

    expect($res->status())->toBe(422)
        ->and($res->json('message'))->toBe('Unprocessable');
});

it('resolves dynamic closures in mapping values', function () {
    config()->set('mocka.mappings', [
        ['url' => 'http://svc.test/dyn', 'match' => 'exact', 'file' => 'basic.mock.php', 'key' => 'GET.dynamic'],
    ]);

    $res = MockaHttp::withHeaders(['X-Mocka' => '1'])->get('http://svc.test/dyn');

    expect($res->json('dyn'))->toBe('ok');
});
