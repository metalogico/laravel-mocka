<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Metalogico\Mocka\Providers\MockaServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            MockaServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'MockaHttp' => \Metalogico\Mocka\Facades\MockaHttp::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure Mocka is active in tests by default
        config()->set('mocka.enabled', true);
        config()->set('mocka.environments', ['testing']);
        // Explicitly allow the hosts used in tests
        config()->set('mocka.allowed_hosts', ['svc.test', 'api.example.com', 'dms.example.com']);
        // Point to this package's tests fixtures directory
        config()->set('mocka.mocks_path', realpath(__DIR__ . '/Fixtures/mocka'));
        config()->set('mocka.default_delay', 0);
        config()->set('mocka.mappings', []);
    }

    protected function fixturesPath(string $file = ''): string
    {
        $base = realpath(__DIR__ . '/Fixtures/mocka');
        return $file ? $base . DIRECTORY_SEPARATOR . $file : $base;
    }
}
